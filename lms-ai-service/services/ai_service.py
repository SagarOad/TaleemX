"""
services/ai_service.py
All calls to the Gemini API: SQL generation, result formatting, and caption AI.
"""

import logging
import re
import time
import threading
from pathlib import Path
import requests

from config import Config
from services.training_question_bank import (
    _normalize_tokens,
    build_training_question_bank,
    pick_relevant_training_examples,
)

logger = logging.getLogger(__name__)

GEMINI_BASE_URL = "https://generativelanguage.googleapis.com/v1beta/models"
GROQ_BASE_URL = "https://api.groq.com/openai/v1/chat/completions"


class AIService:
    def __init__(self):
        self.api_key = Config.GEMINI_API_KEY
        self.model = Config.GEMINI_MODEL
        self.fallback_models = [m for m in Config.GEMINI_FALLBACK_MODELS if m != self.model]
        self.max_retries = max(1, Config.GEMINI_MAX_RETRIES)
        self.min_call_interval_seconds = max(0.0, Config.GEMINI_MIN_CALL_INTERVAL_SECONDS)
        self.cooldown_seconds_429 = max(1.0, Config.GEMINI_429_COOLDOWN_SECONDS)
        self.groq_api_key = Config.GROQ_API_KEY
        self.groq_model = Config.GROQ_MODEL
        self._gemini_lock = threading.Lock()
        self._last_gemini_call_at = 0.0
        self._gemini_blocked_until = 0.0
        self.schema = Config.DB_SCHEMA
        self.app_manual = self._load_app_manual()
        self.training_question_bank = build_training_question_bank()
        logger.info("Loaded %d NL training question examples.", len(self.training_question_bank))

        if not self.api_key:
            logger.warning("GEMINI_API_KEY is not set — AI features will not work.")

    # ── Internal ──────────────────────────────────────────────────────────────
    def _load_app_manual(self) -> str:
        """
        Load SMS app manual from repository root once at startup.
        """
        try:
            manual_path = Path(__file__).resolve().parents[1] / "sms.txt"
            if not manual_path.exists():
                logger.warning("App manual file not found at %s", manual_path)
                return ""
            text = manual_path.read_text(encoding="utf-8", errors="ignore").strip()
            logger.info("Loaded app manual from %s (chars=%d)", manual_path, len(text))
            return text
        except Exception as exc:
            logger.error("Failed to load app manual: %s", exc)
            return ""

    def _acquire_gemini_slot(self):
        """
        Pace outbound Gemini requests to avoid free-tier burst throttling.
        Shared per worker process.
        """
        while True:
            with self._gemini_lock:
                now = time.monotonic()
                ready_at = max(
                    self._gemini_blocked_until,
                    self._last_gemini_call_at + self.min_call_interval_seconds,
                )
                wait_seconds = ready_at - now
                if wait_seconds <= 0:
                    self._last_gemini_call_at = now
                    return
            logger.info("Gemini throttling active; waiting %.2fs", wait_seconds)
            time.sleep(wait_seconds)

    def _apply_429_cooldown(self, retry_after_header: str | None = None):
        now = time.monotonic()
        cooldown = self.cooldown_seconds_429
        if retry_after_header:
            try:
                cooldown = max(cooldown, float(retry_after_header))
            except ValueError:
                pass
        with self._gemini_lock:
            self._gemini_blocked_until = max(self._gemini_blocked_until, now + cooldown)

    def _call_groq(self, prompt: str, temperature: float = 0.2, max_tokens: int = 1024) -> str:
        """
        Secondary provider fallback when Gemini is unavailable/rate-limited.
        """
        if not self.groq_api_key:
            raise RuntimeError("GROQ_API_KEY is not configured.")

        payload = {
            "model": self.groq_model,
            "messages": [{"role": "user", "content": prompt}],
            "temperature": temperature,
            "max_tokens": max_tokens,
        }
        headers = {
            "Authorization": f"Bearer {self.groq_api_key}",
            "Content-Type": "application/json",
        }
        try:
            response = requests.post(GROQ_BASE_URL, json=payload, headers=headers, timeout=30)
            response.raise_for_status()
            data = response.json()
            text = data["choices"][0]["message"]["content"]
            return text.strip()
        except requests.Timeout as exc:
            raise RuntimeError("Groq API timed out.") from exc
        except requests.RequestException as exc:
            raise RuntimeError(f"Groq API error: {exc}") from exc
        except (KeyError, IndexError) as exc:
            raise RuntimeError("Unexpected Groq response format.") from exc

    def _call_llm(self, prompt: str, temperature: float = 0.2, max_tokens: int = 1024) -> str:
        """
        Primary Gemini call with Groq fallback.
        """
        try:
            return self._call_gemini(prompt, temperature=temperature, max_tokens=max_tokens)
        except RuntimeError as gemini_error:
            if not self.groq_api_key:
                raise
            logger.warning("Gemini failed, trying Groq fallback: %s", gemini_error)
            return self._call_groq(prompt, temperature=temperature, max_tokens=max_tokens)

    def set_runtime_schema(self, schema_text: str):
        """Update SQL prompt schema dynamically (e.g., from live DB introspection)."""
        if schema_text and schema_text.strip():
            self.schema = schema_text.strip()

    def _call_gemini(self, prompt: str, temperature: float = 0.2, max_tokens: int = 1024) -> str:
        """
        Send a single-turn prompt to Gemini and return the text response.
        Raises RuntimeError on API failure.
        """
        payload = {
            "contents": [{"parts": [{"text": prompt}]}],
            "generationConfig": {
                "temperature": temperature,
                "maxOutputTokens": max_tokens,
            },
        }

        model_candidates = [self.model] + self.fallback_models
        max_attempts = self.max_retries
        backoff_seconds = 1.2
        response = None
        success = False
        last_error = "Unknown Gemini error."
        for model_name in model_candidates:
            url = f"{GEMINI_BASE_URL}/{model_name}:generateContent?key={self.api_key}"
            logger.info("Calling Gemini model: %s", model_name)
            response = None
            success = False
            for attempt in range(1, max_attempts + 1):
                try:
                    self._acquire_gemini_slot()
                    response = requests.post(url, json=payload, timeout=30)
                    response.raise_for_status()
                    success = True
                    if model_name != self.model:
                        logger.warning("Gemini fallback model succeeded: %s", model_name)
                    break
                except requests.Timeout:
                    logger.error(
                        "Gemini request timed out for model=%s (attempt %d/%d).",
                        model_name,
                        attempt,
                        max_attempts,
                    )
                    last_error = f"Gemini API timed out for model {model_name}."
                    if attempt == max_attempts:
                        break
                    time.sleep(backoff_seconds * attempt)
                except requests.RequestException as exc:
                    status = getattr(exc.response, "status_code", None)
                    logger.error(
                        "Gemini request failed for model=%s (attempt %d/%d, status=%s): %s",
                        model_name,
                        attempt,
                        max_attempts,
                        status,
                        exc,
                    )
                    last_error = f"Gemini API error ({model_name}): {exc}"
                    retryable = status in (429, 500, 502, 503, 504)
                    if status == 429:
                        retry_after = None
                        if getattr(exc, "response", None) is not None:
                            retry_after = exc.response.headers.get("Retry-After")
                        self._apply_429_cooldown(retry_after)
                        # Move to next model instead of hammering same model.
                        break
                    if attempt == max_attempts or not retryable:
                        break
                    time.sleep(backoff_seconds * attempt)
            if success:
                break

        if response is None or not success:
            raise RuntimeError(last_error)
        data = response.json()

        # Navigate response structure safely.
        candidates = data.get("candidates") or []
        if not candidates:
            logger.error("Gemini returned no candidates | raw: %s", data)
            raise RuntimeError("Gemini returned no candidates.")

        candidate = candidates[0] or {}
        finish_reason = (candidate.get("finishReason") or "").upper()
        content = candidate.get("content") or {}
        parts = content.get("parts") or []
        text_chunks = []
        for part in parts:
            chunk = part.get("text")
            if chunk:
                text_chunks.append(chunk)
        text = "".join(text_chunks).strip()
        if text:
            return text

        if finish_reason == "MAX_TOKENS":
            logger.warning("Gemini output truncated due to MAX_TOKENS | raw: %s", data)
            raise RuntimeError("Gemini output truncated (MAX_TOKENS).")

        logger.error("Unexpected Gemini response structure: raw=%s", data)
        raise RuntimeError("Unexpected Gemini response format.")

    def _fallback_sql(self, question: str) -> str:
        """
        Lightweight offline fallback for common LMS analytics questions.
        Keeps local demo usable when Gemini is rate-limited/unavailable.
        """
        q = question.lower().strip()

        if "how many" in q and "student" in q and ("enrolled" in q or "active course" in q):
            return (
                "SELECT COUNT(DISTINCT e.student_id) AS active_students "
                "FROM enrollments e "
                "JOIN courses c ON c.id = e.course_id "
                "WHERE e.status = 'active' AND c.status = 'published'"
            )

        if "dropout" in q or "dropped" in q:
            return (
                "SELECT c.title, COUNT(*) AS dropped_students "
                "FROM enrollments e "
                "JOIN courses c ON c.id = e.course_id "
                "WHERE e.status = 'dropped' "
                "GROUP BY c.title "
                "ORDER BY dropped_students DESC "
                "LIMIT 50"
            )

        if ("top" in q and "student" in q and "quiz" in q) or ("average quiz" in q):
            return (
                "SELECT u.name, ROUND(AVG(q.score), 2) AS avg_score "
                "FROM quiz_results q "
                "JOIN users u ON u.id = q.student_id "
                "GROUP BY u.name "
                "ORDER BY avg_score DESC "
                "LIMIT 5"
            )

        if "published course" in q and ("how many" in q or "count" in q):
            return (
                "SELECT COUNT(*) AS published_courses "
                "FROM courses "
                "WHERE status = 'published'"
            )

        if "pending submission" in q or ("assignment" in q and "pending" in q):
            return (
                "SELECT COUNT(*) AS pending_submissions "
                "FROM submissions "
                "WHERE status = 'pending'"
            )

        return ""

    def _deterministic_sql(self, question: str) -> str:
        """
        High-confidence question patterns that should bypass model generation.
        Keep this list narrow to avoid unintended regressions.
        """
        q = question.lower().strip()
        list_terms = ("list", "show", "give", "fetch", "display")
        exam_schedule_match = re.search(
            r"(?:when.*exam\s+scheduled|exam.*when.*scheduled|when is this exam scheduled)\b",
            q,
        )
        if exam_schedule_match and "exam" in q:
            exam_phrase = re.sub(
                r"(when.*scheduled|when is this exam scheduled|schedule[sd]?|when is)",
                "",
                q,
            ).strip(" ,?.!:")
            if exam_phrase:
                safe_exam = exam_phrase.replace("'", "''")
                return (
                    "SELECT exam, exam_from AS schedule_start, exam_to AS schedule_end, time_from, time_to "
                    "FROM onlineexam "
                    f"WHERE LOWER(exam) LIKE '%{safe_exam}%' "
                    "AND exam_from IS NOT NULL "
                    "ORDER BY exam_from DESC"
                )

        if ("list" in q or "show" in q or "give" in q) and "exam" in q and "schedule" in q:
            return (
                "SELECT exam, exam_from AS schedule_start, exam_to AS schedule_end, time_from, time_to, duration "
                "FROM onlineexam "
                "WHERE exam_from IS NOT NULL "
                "ORDER BY exam_from DESC, exam"
            )

        if ("all available exams" in q) or (
            ("list" in q or "show" in q or "give" in q) and "exam" in q
        ):
            return (
                "SELECT exam, exam_from AS schedule_start, exam_to AS schedule_end, time_from, time_to, duration "
                "FROM onlineexam "
                "ORDER BY COALESCE(exam_from, created_at) DESC, exam"
            )

        # Front-office admission enquiries (table `enquiry`) — not the same as online application forms.
        if (
            ("enquir" in q or "inquiry" in q or "inquiries" in q)
            and ("admission" in q or "admissions" in q or "front office" in q or "prospect" in q)
        ) or ("admission enquir" in q) or ("admission inquiry" in q):
            date_f = " AND e.created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)"
            if "this month" in q or "current month" in q or re.search(r"\bthis\s+month\b", q):
                date_f = " AND MONTH(e.created_at) = MONTH(CURDATE()) AND YEAR(e.created_at) = YEAR(CURDATE())"
            if "today" in q:
                date_f = " AND DATE(e.created_at) = CURDATE()"
            return (
                "SELECT e.name, e.contact, e.email, e.date AS enquiry_date, e.description, e.status, "
                "e.source, e.reference, e.follow_up_date, e.created_at "
                "FROM enquiry e "
                f"WHERE 1=1 {date_f} "
                "ORDER BY e.created_at DESC"
            )

        # Online admission applications / submitted forms (table `online_admissions`).
        if ("online" in q and "admission" in q) and any(
            w in q for w in ("application", "applications", "form", "submit", "applicant", "portal")
        ):
            date_f = ""
            if "this month" in q or "current month" in q or re.search(r"\bthis\s+month\b", q):
                date_f = " AND MONTH(oa.created_at) = MONTH(CURDATE()) AND YEAR(oa.created_at) = YEAR(CURDATE())"
            return (
                "SELECT oa.reference_no, oa.admission_no, oa.firstname, oa.middlename, oa.lastname, "
                "oa.email, oa.mobileno, oa.admission_date, oa.form_status, oa.is_enroll, oa.created_at "
                "FROM online_admissions oa "
                f"WHERE 1=1 {date_f} "
                "ORDER BY oa.created_at DESC"
            )

        staff_name_match = (
            re.search(r"(?:teacher|staff).*(?:by\s+name)\s+(.+)$", q)
            or re.search(r"(?:teacher|staff).*(?:for)\s+(.+)$", q)
            or re.search(r"(?:details?|info|information)\s+of\s+(?:staff|teacher)\s+(.+)$", q)
            or re.search(r"(?:staff|teacher)\s+details?\s+(.+)$", q)
        )
        if staff_name_match:
            raw_name = staff_name_match.group(1).strip().strip("?.!,;:")
            staff_name = " ".join(raw_name.split())
            if staff_name:
                safe_staff_name = staff_name.replace("'", "''")
                return (
                    "SELECT name, surname, email, contact_no, department, designation, qualification, work_exp "
                    "FROM staff "
                    f"WHERE LOWER(CONCAT_WS(' ', name, surname)) LIKE LOWER('%{safe_staff_name}%') "
                    "ORDER BY name, surname"
                )

        if ("list" in q or "show" in q or "give" in q) and "staff" in q:
            return (
                "SELECT name, surname, email, contact_no, department, designation "
                "FROM staff "
                "WHERE is_active = 1 "
                "ORDER BY name, surname"
            )

        if (
            any(t in q for t in list_terms)
            and "student" in q
            and "grade" not in q
            and "class" not in q
            and "behavio" not in q
            and "behaviour" not in q
            and "attendance" not in q
            and "attendence" not in q
        ) or q in {"list of them", "give me list of them", "show them"}:
            return (
                "SELECT firstname, middlename, lastname "
                "FROM students "
                "ORDER BY firstname, lastname"
            )

        # Subjects assigned to a grade/class — before per-student subject lookup so
        # "what subjects for grade 2" is not treated as a student named "grade 2".
        if ("subject" in q or "subjects" in q or "coursework" in q) and re.search(
            r"\b(?:grade|class)\s*(\d+)\b", q
        ):
            gm = re.search(r"\b(?:grade|class)\s*(\d+)\b", q)
            grade_no = gm.group(1)
            return (
                "SELECT DISTINCT sub.name AS subject_name, c.class AS class_name, sec.section AS section_name "
                "FROM subjects sub "
                "JOIN subject_group_subjects sgs ON sgs.subject_id = sub.id "
                "JOIN subject_group_class_sections sgcs ON sgcs.subject_group_id = sgs.subject_group_id "
                "JOIN class_sections cs ON cs.id = sgcs.class_section_id "
                "JOIN classes c ON c.id = cs.class_id "
                "JOIN sections sec ON sec.id = cs.section_id "
                f"WHERE (c.class = '{grade_no}' OR c.class = 'Grade {grade_no}' OR c.class = 'Class {grade_no}') "
                "ORDER BY sub.name, sec.section"
            )

        subject_for_student = re.search(
            r"(?:which|what)\s+subjects?.*?\b(?:assigned\s+to|for)\s+(.+)$",
            q,
        )
        if subject_for_student:
            raw_name = subject_for_student.group(1).strip().strip("?.!,;:")
            student_name = " ".join(raw_name.split())
            if student_name and not re.match(
                r"^(?:grade|class)\s*\d+$", student_name, flags=re.IGNORECASE
            ):
                safe_name = student_name.replace("'", "''")
                return (
                    "SELECT DISTINCT sub.name "
                    "FROM students s "
                    "JOIN student_session ss ON ss.student_id = s.id "
                    "JOIN class_sections cs ON cs.class_id = ss.class_id AND cs.section_id = ss.section_id "
                    "JOIN subject_group_class_sections sgcs ON sgcs.class_section_id = cs.id "
                    "JOIN subject_group_subjects sgs ON sgs.subject_group_id = sgcs.subject_group_id "
                    "JOIN subjects sub ON sub.id = sgs.subject_id "
                    f"WHERE LOWER(CONCAT_WS(' ', s.firstname, s.middlename, s.lastname)) = LOWER('{safe_name}') "
                    f"OR LOWER(CONCAT_WS(' ', s.firstname, s.lastname)) = LOWER('{safe_name}') "
                    "ORDER BY sub.name"
                )

        grade_match = re.search(r"\bgrade\s*(\d+)\b", q)
        if grade_match and ("student" in q or "students" in q or "list of grade" in q):
            grade_no = grade_match.group(1)
            return (
                "SELECT DISTINCT s.firstname, s.lastname "
                "FROM students s "
                "JOIN student_session ss ON ss.student_id = s.id "
                "JOIN classes c ON c.id = ss.class_id "
                f"WHERE (c.class = '{grade_no}' OR c.class = 'Grade {grade_no}' OR c.class = 'Class {grade_no}') "
                "ORDER BY s.firstname, s.lastname"
            )

        student_name_match = (
            re.search(r"(?:details?|info|information|profile)\s+(?:of\s+)?(?:student\s+)?(.+)$", q)
            or re.search(r"student\s+details?\s+(.+)$", q)
        )
        if student_name_match:
            raw_name = student_name_match.group(1).strip().strip("?.!,;:")
            if raw_name and "all student" not in raw_name and "all students" not in raw_name:
                student_name = " ".join(raw_name.split())
                safe_student_name = student_name.replace("'", "''")
                return (
                    "SELECT admission_no, roll_no, firstname, middlename, lastname, mobileno, email, "
                    "father_name, mother_name, guardian_name, guardian_phone, class_sections.class_id, class_sections.section_id "
                    "FROM students s "
                    "LEFT JOIN student_session ss ON ss.student_id = s.id "
                    "LEFT JOIN class_sections ON class_sections.class_id = ss.class_id AND class_sections.section_id = ss.section_id "
                    f"WHERE LOWER(CONCAT_WS(' ', s.firstname, s.middlename, s.lastname)) LIKE LOWER('%{safe_student_name}%') "
                    f"OR LOWER(CONCAT_WS(' ', s.firstname, s.lastname)) LIKE LOWER('%{safe_student_name}%') "
                    "ORDER BY s.firstname, s.lastname"
                )

        if (
            ("behavio" in q or "behaviour" in q or "behavior" in q or "incident" in q)
            and re.search(r"\b(?:grade|class)\s*(\d+)\b", q)
            and "staff" not in q
        ):
            gm = re.search(r"\b(?:grade|class)\s*(\d+)\b", q)
            grade_no = gm.group(1)
            return (
                "SELECT CONCAT_WS(' ', s.firstname, s.middlename, s.lastname) AS student_name, "
                "c.class AS class_name, sb.title, sb.description, sb.point AS behaviour_points, "
                "si.created_at AS incident_date "
                "FROM student_incidents si "
                "JOIN students s ON s.id = si.student_id "
                "JOIN student_behaviour sb ON sb.id = si.incident_id "
                "JOIN student_session ss ON ss.student_id = s.id "
                "JOIN classes c ON c.id = ss.class_id "
                f"WHERE (c.class = '{grade_no}' OR c.class = 'Grade {grade_no}' OR c.class = 'Class {grade_no}') "
                "ORDER BY si.created_at DESC, student_name"
            )

        if ("behavio" in q or "behaviour" in q) and ("all student" in q or "all students" in q):
            return (
                "SELECT CONCAT_WS(' ', s.firstname, s.middlename, s.lastname) AS student_name, "
                "sb.title, sb.description, sb.point, sb.created_at "
                "FROM student_incidents si "
                "JOIN students s ON s.id = si.student_id "
                "JOIN student_behaviour sb ON sb.id = si.incident_id "
                "ORDER BY sb.created_at DESC"
            )

        # Behaviour / discipline incidents for one student (many phrasings: "behaviour record of X", etc.)
        behavior_name_match = (
            re.search(
                r"(?:behavio(?:u)?r|behavior)\s+records?\s+(?:of|for)\s+(?:student\s+)?(.+)$",
                q,
            )
            or re.search(
                r"(?:behavio(?:u)?r|behavior)\s+record\s+(?:of|for)\s+(?:student\s+)?(.+)$",
                q,
            )
            or re.search(
                r"(?:behavio(?:u)?r\s+report|behavior\s+report)\s+(?:of\s+)?(?:student\s+)?(.+)$",
                q,
            )
            or re.search(
                r"(?:give|show|get|list|fetch)\s+(?:me\s+)?(?:the\s+)?(?:a\s+)?(?:behavio(?:u)?r|behavior)\s+records?\s+(?:of|for)\s+(?:student\s+)?(.+)$",
                q,
            )
            or re.search(
                r"(?:give|show|get|list|fetch)\s+(?:me\s+)?(?:the\s+)?(?:behavio(?:u)?r|behavior)\s+record\s+(?:of|for)\s+(?:student\s+)?(.+)$",
                q,
            )
            or re.search(
                r"(?:incident|incidents)\s+(?:of|for)\s+(?:student\s+)?(.+)$",
                q,
            )
            or re.search(
                r"(?:discipline|misconduct)\s+(?:record|records)\s+(?:of|for)\s+(?:student\s+)?(.+)$",
                q,
            )
        )
        if behavior_name_match:
            raw_name = behavior_name_match.group(1).strip().strip("?.!,;:")
            if raw_name and "all student" not in raw_name and "all students" not in raw_name:
                student_name = " ".join(raw_name.split())
                safe_student_name = student_name.replace("'", "''")
                return (
                    "SELECT si.id AS incident_row_id, si.created_at AS incident_date, "
                    "sb.title AS behaviour_title, sb.description AS behaviour_description, sb.point AS behaviour_points, "
                    "CONCAT_WS(' ', s.firstname, s.middlename, s.lastname) AS student_name, "
                    "s.admission_no, s.roll_no "
                    "FROM student_incidents si "
                    "JOIN students s ON s.id = si.student_id "
                    "JOIN student_behaviour sb ON sb.id = si.incident_id "
                    f"WHERE LOWER(CONCAT_WS(' ', s.firstname, s.middlename, s.lastname)) LIKE LOWER('%{safe_student_name}%') "
                    f"OR LOWER(CONCAT_WS(' ', s.firstname, s.lastname)) LIKE LOWER('%{safe_student_name}%') "
                    "ORDER BY si.created_at DESC"
                )

        # Staff / teacher punch attendance (table `staff_attendance`).
        if (
            ("attendance" in q or "attendence" in q)
            and (
                "staff" in q
                or "employee" in q
                or "faculty" in q
                or "personnel" in q
                or (("teacher" in q or "teachers" in q) and "student" not in q)
            )
            and "student" not in q
        ):
            month_sql = " AND sa.date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)"
            if "this month" in q or "current month" in q or re.search(r"\bthis\s+month\b", q):
                month_sql = " AND MONTH(sa.date) = MONTH(CURDATE()) AND YEAR(sa.date) = YEAR(CURDATE())"
            if "today" in q:
                month_sql = " AND sa.date = CURDATE()"
            return (
                "SELECT sa.date, CONCAT_WS(' ', st.name, st.surname) AS staff_name, "
                "COALESCE(sat.long_lang_name, sat.type) AS attendance_status, "
                "sa.in_time, sa.out_time, sa.remark "
                "FROM staff_attendance sa "
                "JOIN staff st ON st.id = sa.staff_id "
                "JOIN staff_attendance_type sat ON sat.id = sa.staff_attendance_type_id "
                f"WHERE 1=1 {month_sql} "
                "ORDER BY sa.date DESC, staff_name"
            )

        # Grade/class attendance — must run before the per-student name heuristic below,
        # otherwise "attendance report of grade 2" is treated as a student name search.
        if ("attendance" in q or "attendence" in q) and re.search(r"\b(?:grade|class)\s*(\d+)\b", q):
            gm = re.search(r"\b(?:grade|class)\s*(\d+)\b", q)
            grade_no = gm.group(1)
            month_sql = ""
            if "this month" in q or "current month" in q or re.search(r"\bthis\s+month\b", q):
                month_sql = " AND MONTH(sa.date) = MONTH(CURDATE()) AND YEAR(sa.date) = YEAR(CURDATE())"
            elif re.search(r"\b(?:last|past)\s+30\s+days\b", q):
                month_sql = " AND sa.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
            return (
                "SELECT CONCAT_WS(' ', s.firstname, s.middlename, s.lastname) AS student_name, "
                "c.class AS class_name, sec.section AS section_name, sa.date, "
                "at.long_lang_name AS attendance_status, sa.in_time, sa.out_time, sa.remark "
                "FROM student_attendences sa "
                "JOIN student_session ss ON ss.id = sa.student_session_id "
                "JOIN students s ON s.id = ss.student_id "
                "JOIN classes c ON c.id = ss.class_id "
                "LEFT JOIN sections sec ON sec.id = ss.section_id "
                "LEFT JOIN attendence_type at ON at.id = sa.attendence_type_id "
                f"WHERE (c.class = '{grade_no}' OR c.class = 'Grade {grade_no}' OR c.class = 'Class {grade_no}') "
                f"{month_sql} "
                "ORDER BY sa.date DESC, student_name"
            )

        attendance_name_match = re.search(
            r"(?:attendance\s+report|attendance|attendence)\s+(?:of\s+)?(?:student\s+)?(.+)$",
            q,
        )
        if attendance_name_match:
            raw_name = attendance_name_match.group(1).strip().strip("?.!,;:")
            if raw_name and "all student" not in raw_name and "all students" not in raw_name:
                student_name = " ".join(raw_name.split())
                safe_student_name = student_name.replace("'", "''")
                return (
                    "SELECT CONCAT_WS(' ', s.firstname, s.middlename, s.lastname) AS student_name, "
                    "sa.date, at.long_lang_name AS attendance_status, sa.in_time, sa.out_time, sa.remark "
                    "FROM student_attendences sa "
                    "JOIN student_session ss ON ss.id = sa.student_session_id "
                    "JOIN students s ON s.id = ss.student_id "
                    "LEFT JOIN attendence_type at ON at.id = sa.attendence_type_id "
                    f"WHERE LOWER(CONCAT_WS(' ', s.firstname, s.middlename, s.lastname)) LIKE LOWER('%{safe_student_name}%') "
                    f"OR LOWER(CONCAT_WS(' ', s.firstname, s.lastname)) LIKE LOWER('%{safe_student_name}%') "
                    "ORDER BY sa.date DESC"
                )

        if ("attendance" in q or "attendence" in q) and ("all student" in q or "all students" in q):
            return (
                "SELECT CONCAT_WS(' ', s.firstname, s.middlename, s.lastname) AS student_name, "
                "sa.date, at.long_lang_name AS attendance_status, sa.in_time, sa.out_time, sa.remark "
                "FROM student_attendences sa "
                "JOIN student_session ss ON ss.id = sa.student_session_id "
                "JOIN students s ON s.id = ss.student_id "
                "LEFT JOIN attendence_type at ON at.id = sa.attendence_type_id "
                "ORDER BY sa.date DESC"
            )

        if "timetable" in q and ("grade" in q or "class" in q):
            grade_match = re.search(r"\b(?:grade|class)\s*(\d+)\b", q)
            if grade_match:
                grade_no = grade_match.group(1)
                return (
                    "SELECT c.class AS class_name, sec.section AS section_name, st.day, st.time_from, st.time_to, "
                    "sub.name AS subject_name, CONCAT_WS(' ', sf.name, sf.surname) AS teacher_name "
                    "FROM subject_timetable st "
                    "JOIN classes c ON c.id = st.class_id "
                    "LEFT JOIN sections sec ON sec.id = st.section_id "
                    "LEFT JOIN subject_group_subjects sgs ON sgs.id = st.subject_group_subject_id "
                    "LEFT JOIN subjects sub ON sub.id = sgs.subject_id "
                    "LEFT JOIN staff sf ON sf.id = st.staff_id "
                    f"WHERE (c.class = '{grade_no}' OR c.class = 'Grade {grade_no}' OR c.class = 'Class {grade_no}') "
                    "ORDER BY st.day, st.time_from"
                )

        if "timetable" in q and ("all class" in q or "all classes" in q):
            return (
                "SELECT c.class AS class_name, sec.section AS section_name, st.day, st.time_from, st.time_to, "
                "sub.name AS subject_name, CONCAT_WS(' ', sf.name, sf.surname) AS teacher_name "
                "FROM subject_timetable st "
                "JOIN classes c ON c.id = st.class_id "
                "LEFT JOIN sections sec ON sec.id = st.section_id "
                "LEFT JOIN subject_group_subjects sgs ON sgs.id = st.subject_group_subject_id "
                "LEFT JOIN subjects sub ON sub.id = sgs.subject_id "
                "LEFT JOIN staff sf ON sf.id = st.staff_id "
                "ORDER BY c.class, sec.section, st.day, st.time_from"
            )
        if ("list" in q or "show" in q or "give" in q) and "teacher" in q and "name" in q:
            return (
                "SELECT DISTINCT s.name, s.surname "
                "FROM staff s "
                "JOIN staff_roles sr ON sr.staff_id = s.id "
                "JOIN roles r ON r.id = sr.role_id "
                "WHERE LOWER(r.slug) = 'teacher' OR LOWER(r.name) LIKE '%teacher%' "
                "ORDER BY s.name, s.surname"
            )

        if "how many" in q and "teacher" in q and re.search(r"\b(?:grade|class)\s*(\d+)\b", q):
            gm = re.search(r"\b(?:grade|class)\s*(\d+)\b", q)
            grade_no = gm.group(1)
            return (
                "SELECT COUNT(DISTINCT ct.staff_id) AS teacher_count_for_grade "
                "FROM class_teacher ct "
                "JOIN classes c ON c.id = ct.class_id "
                f"WHERE (c.class = '{grade_no}' OR c.class = 'Grade {grade_no}' OR c.class = 'Class {grade_no}')"
            )

        if (
            ("teacher" in q or "teachers" in q or "instructor" in q)
            and re.search(r"\b(?:grade|class)\s*(\d+)\b", q)
            and any(
                w in q
                for w in (
                    "assign", "assigned", "who", "which", "list", "show", "give",
                    "teach", "class teacher", "subject teacher", "tutor",
                )
            )
            and "how many" not in q
        ):
            gm = re.search(r"\b(?:grade|class)\s*(\d+)\b", q)
            grade_no = gm.group(1)
            return (
                "SELECT DISTINCT sf.id AS staff_id, sf.name, sf.surname, sf.email, sf.contact_no, "
                "c.class AS class_name, sec.section AS section_name "
                "FROM class_teacher ct "
                "JOIN classes c ON c.id = ct.class_id "
                "LEFT JOIN sections sec ON sec.id = ct.section_id "
                "JOIN staff sf ON sf.id = ct.staff_id "
                f"WHERE (c.class = '{grade_no}' OR c.class = 'Grade {grade_no}' OR c.class = 'Class {grade_no}') "
                "ORDER BY c.class, sec.section, sf.name, sf.surname"
            )

        if "how many" in q and "teacher" in q:
            return (
                "SELECT COUNT(DISTINCT sr.staff_id) AS teacher_count "
                "FROM staff_roles sr "
                "JOIN roles r ON r.id = sr.role_id "
                "WHERE LOWER(r.slug) = 'teacher' OR LOWER(r.name) LIKE '%teacher%'"
            )
        if ("how many" in q or "count" in q) and "fee" in q and "unpaid" in q:
            return (
                "SELECT COUNT(*) AS unpaid_fee_records "
                "FROM student_fees_master sfm "
                "LEFT JOIN student_fees_deposite sfd ON sfd.student_fees_master_id = sfm.id "
                "WHERE sfm.amount > 0 AND sfd.id IS NULL"
            )
        return ""

    def repair_sql(self, question: str, broken_sql: str, validation_error: str = "") -> str:
        """
        Attempt one strict SQL repair pass if generated SQL is malformed.
        """
        prompt = f"""You are a MySQL SQL repair assistant.
Given a broken SQL query, return exactly ONE corrected SELECT query.

DATABASE SCHEMA:
{self.schema}

User question:
{question}

Broken SQL:
{broken_sql}

Validation error:
{validation_error}

Rules:
1. Return ONLY SQL (no markdown or explanation).
2. Must start with SELECT and include FROM.
3. Use only schema tables/columns.
4. Ensure parentheses and JOIN clauses are complete.
5. For aggregate COUNT/SUM/AVG queries, do not add LIMIT unless needed.
"""
        try:
            repaired = self._call_llm(prompt, temperature=0.0, max_tokens=8192)
            repaired = re.sub(r"```sql|```", "", repaired, flags=re.IGNORECASE).strip()
            if repaired.upper().startswith("SELECT"):
                return repaired
        except RuntimeError as exc:
            logger.error("repair_sql failed: %s", exc)
        return ""

    def retry_sql_for_empty_results(self, question: str, prior_sql: str) -> str:
        """
        Generate a second SQL attempt when first query executes but returns no rows.
        """
        prompt = f"""You are a MySQL SQL assistant.
The previous SQL returned 0 rows. Produce ONE alternative SELECT query that is
more tolerant with matching (e.g., use LIKE, broader joins) while staying faithful
to user intent.

DATABASE SCHEMA:
{self.schema}

User question:
{question}

Previous SQL (returned no rows):
{prior_sql}

Rules:
1. Return ONLY SQL.
2. Must start with SELECT and include FROM.
3. Use only schema tables/columns.
4. Preserve intent; broaden matching strategy if useful.
5. Add LIMIT 50 for non-aggregate queries.
"""
        try:
            retry_sql = self._call_llm(prompt, temperature=0.1, max_tokens=8192)
            retry_sql = re.sub(r"```sql|```", "", retry_sql, flags=re.IGNORECASE).strip()
            if retry_sql.upper().startswith("SELECT"):
                return retry_sql
        except RuntimeError as exc:
            logger.error("retry_sql_for_empty_results failed: %s", exc)
        return ""

    def _looks_like_data_request(self, question: str) -> bool:
        """
        Heuristic classifier to catch command-style data requests
        that models may mislabel as NOT_DATA_QUESTION.
        """
        q = (question or "").lower().strip()
        if not q:
            return False

        intent_terms = (
            "list", "show", "give", "fetch", "find", "get",
            "count", "how many", "total", "details", "name", "names",
            "what", "teaching", "taught", "curriculum", "syllabus",
            "lesson", "lessons", "topic", "topics", "content",
        )
        entity_terms = (
            "teacher", "teachers", "student", "students", "staff",
            "course", "courses", "class", "classes", "role", "roles",
            "language", "arabic",
            "behaviour", "behavior", "incident", "discipline",
            "fee", "fees", "leave", "enquiry", "visitor", "payroll",
            "homework", "exam", "timetable", "attendance", "library",
        )
        has_intent = any(t in q for t in intent_terms)
        has_entity = any(t in q for t in entity_terms)
        return has_intent and has_entity

    def is_app_flow_question(self, question: str) -> bool:
        """
        Route app navigation/how-to questions to manual-grounded answering.
        Keep count/list/details data requests on SQL path.
        """
        q = (question or "").lower().strip()
        if not q:
            return False

        data_terms = (
            "count", "how many", "total", "list", "show", "give me list",
            "names", "email", "phone", "id", "students", "teachers", "staff",
            "teaching", "taught", "curriculum", "syllabus", "lesson", "lessons",
            "topic", "topics", "content", "what we are teaching", "what is taught",
        )
        if any(t in q for t in data_terms):
            return False

        # Reuse data-request heuristic to avoid routing content questions to manual.
        if self._looks_like_data_request(q):
            return False

        app_flow_terms = (
            "where", "how", "how to", "where do i", "where can i",
            "navigation", "menu", "screen", "page", "settings", "module",
            "billing", "fees", "admission", "course", "password",
        )
        return any(t in q for t in app_flow_terms)

    def answer_from_manual(self, question: str) -> str:
        """
        Answer app flow / navigation questions grounded in sms.txt manual text.
        """
        if not self.app_manual:
            return "App manual is not available right now. Please ask a data question or try again."

        prompt = f"""You are an expert assistant for the SMS LMS.
You have two tools: SQL for data and the App Manual for UI navigation.
For this request, use ONLY the App Manual below to guide the user.
If the manual does not contain enough detail, say what is missing briefly.

App Manual:
---
{self.app_manual[:30000]}
---

User question: {question}

Rules:
1. Provide practical navigation steps using "Module > Submodule > Action" style when possible.
2. Keep response concise and actionable.
3. Do not invent features or menus not present in the manual.
4. This is an app-flow/manual question, not a SQL/data extraction task.
"""
        try:
            return self._call_llm(prompt, temperature=0.2, max_tokens=700)
        except RuntimeError as exc:
            logger.error("answer_from_manual failed: %s", exc)
            return "I could not load a manual-based answer right now. Please try again."

    def _fallback_natural_answer(self, question: str, columns: list, rows: list) -> str:
        """Generate a readable non-AI answer when Gemini formatting fails."""
        if not rows:
            return "No records were found for your query."

        if len(columns) == 1 and len(rows) == 1:
            label = str(columns[0]).replace("_", " ")
            value = rows[0][0]
            return f"For '{question}', the {label} is {value}."

        if len(rows) == 1:
            pairs = [f"{col}: {val}" for col, val in zip(columns, rows[0])]
            return f"Here is the result for '{question}': " + ", ".join(pairs) + "."

        first_col = columns[0]
        second_col = columns[1] if len(columns) > 1 else None
        highlights = []
        for row in rows[:5]:
            if second_col:
                highlights.append(f"{row[0]} ({row[1]})")
            else:
                highlights.append(str(row[0]))
        return (
            f"I found {len(rows)} records for '{question}'. "
            f"Top results by {first_col}: " + ", ".join(highlights) + "."
        )

    def should_use_structured_answer(self, question: str) -> bool:
        """
        For list/detail style requests, avoid LLM paraphrasing that can truncate
        or alter factual fields; return structured DB-grounded output directly.
        """
        q = (question or "").lower()
        triggers = (
            "details", "detail", "all available", "list all", "give me all",
            "give me details", "show all", "available exams", "staff",
            "list of students", "students of grade", "behaviour report",
            "behavior report", "behaviour record", "behavior record",
            "behaviour records", "behavior records", "incident", "incidents",
            "attendance report", "detailed attendance", "admission enquir",
            "admission inquiry", "subject", "subjects", "staff attendance",
            "online admission",
        )
        return any(t in q for t in triggers)

    def structured_answer_from_rows(self, question: str, columns: list, rows: list) -> str:
        """
        Deterministic renderer for list/detail responses (Ask AI card layout).
        """
        if not rows:
            return "No records were found for your query."

        def _label(col) -> str:
            return str(col).replace("_", " ").strip().title()

        def _row_lines(row: tuple) -> list[str]:
            out: list[str] = []
            for col, val in zip(columns, row):
                if val is None or val == "":
                    continue
                out.append(f"   - {_label(col)}: {val}")
            if not out:
                out.append("   - (no values in this row)")
            return out

        max_rows_to_show = min(len(rows), 50)
        lines = [f"I found {max_rows_to_show} record(s):", ""]
        for i in range(max_rows_to_show):
            lines.append(f"{i + 1}.")
            lines.extend(_row_lines(rows[i]))
            lines.append("")
        while lines and lines[-1] == "":
            lines.pop()
        return "\n".join(lines)

    def _simple_text_summary(self, text: str, max_sentences: int = 4) -> str:
        """
        Local fallback summarizer:
        score sentences by token frequency and return top-ranked ones in order.
        """
        clean = " ".join(text.split())
        if not clean:
            return "No caption text was provided."
        sentences = re.split(r"(?<=[.!?])\s+", clean)
        sentences = [s.strip() for s in sentences if s.strip()]
        if len(sentences) <= max_sentences:
            return " ".join(sentences)

        words = re.findall(r"[A-Za-z][A-Za-z0-9'-]*", clean.lower())
        stop = {
            "the", "a", "an", "and", "or", "to", "of", "in", "on", "for", "is", "are",
            "was", "were", "be", "this", "that", "it", "with", "as", "by", "at", "from",
            "we", "you", "they", "i", "he", "she", "them", "our", "your", "their"
        }
        freq = {}
        for w in words:
            if w in stop or len(w) < 3:
                continue
            freq[w] = freq.get(w, 0) + 1

        scored = []
        for idx, sentence in enumerate(sentences):
            score = 0
            for w in re.findall(r"[A-Za-z][A-Za-z0-9'-]*", sentence.lower()):
                score += freq.get(w, 0)
            scored.append((idx, score))

        top_idx = sorted(
            [idx for idx, _ in sorted(scored, key=lambda x: x[1], reverse=True)[:max_sentences]]
        )
        return " ".join(sentences[i] for i in top_idx)

    # ── SQL Generation ────────────────────────────────────────────────────────
    def generate_sql(self, question: str) -> str:
        """
        Given a natural language question and the DB schema, return a SELECT SQL statement.
        Returns empty string if question is not data-related.
        """
        selected_examples = pick_relevant_training_examples(
            question=question,
            training_bank=self.training_question_bank,
            max_examples=25,
        )
        examples_text = "\n".join(f"- {example}" for example in selected_examples)

        prompt = f"""You are a MySQL expert connected to a Learning Management System database.

DATABASE SCHEMA:
{self.schema}

TRAINING QUESTION EXAMPLES (intent patterns from this LMS; use as semantic guidance only):
{examples_text}

RULES:
1. Return ONLY the raw SQL query — no explanation, no markdown, no backticks, no comments.
2. Only generate SELECT statements.
3. Never use INSERT, UPDATE, DELETE, DROP, ALTER, TRUNCATE, GRANT, or any DDL/DML.
4. Use only tables and columns defined in the schema above.
5. Always add LIMIT 50 unless the question asks for an aggregate (COUNT, SUM, AVG, etc.).
6. Use proper JOINs when data spans multiple tables.
7. Treat both question-style and command-style requests as data-related when they ask for lists, counts, names, or details (e.g. "give me list of teachers").
8. If the request is truly not data-related or cannot be answered from this schema, respond with exactly: NOT_DATA_QUESTION
9. If a referenced table or column is missing from schema, do NOT guess alternatives; respond with NOT_DATA_QUESTION.
10. The training examples above are question variants only; generate SQL strictly from the schema shown.

USER QUESTION: {question}

SQL:"""

        # Deterministic shortcuts for high-confidence intent to avoid model drift.
        deterministic_sql = self._deterministic_sql(question)
        if deterministic_sql:
            logger.info("Using deterministic SQL strategy for question: %s", question)
            return deterministic_sql

        try:
            result = self._call_llm(prompt, temperature=0.1, max_tokens=8192)
        except RuntimeError as exc:
            logger.error("generate_sql failed: %s", exc)
            fallback = self._fallback_sql(question)
            if fallback:
                logger.info("Using fallback SQL strategy for question: %s", question)
            return fallback

        # Clean up any accidental markdown fences
        result = re.sub(r"```sql|```", "", result, flags=re.IGNORECASE).strip()

        if result.upper().startswith("NOT_DATA_QUESTION"):
            if self._looks_like_data_request(question):
                logger.warning(
                    "Model misclassified likely data request as NOT_DATA_QUESTION. "
                    "Retrying with strict SQL-only recovery prompt."
                )
                recovery_prompt = f"""Convert this request to ONE MySQL SELECT query using only schema-provided tables/columns.
Request: {question}

Rules:
1. Return ONLY SQL.
2. Must start with SELECT.
3. If listing people, include their name columns.
4. Add LIMIT 50 if non-aggregate.
5. Do not return NOT_DATA_QUESTION.
"""
                try:
                    recovered = self._call_llm(recovery_prompt, temperature=0.1, max_tokens=8192)
                    recovered = re.sub(r"```sql|```", "", recovered, flags=re.IGNORECASE).strip()
                    if recovered.upper().startswith("SELECT"):
                        return recovered
                except RuntimeError as exc:
                    logger.error("Recovery SQL generation failed: %s", exc)
            logger.info("Gemini identified question as non-data-related.")
            return ""

        return result

    # ── Result Formatting ─────────────────────────────────────────────────────
    def format_results(self, question: str, columns: list, rows: list) -> str:
        """
        Convert raw SQL result rows into a clean, human-readable paragraph.
        """
        # Build a compact text table to pass to Gemini
        header = " | ".join(columns)
        separator = "-" * len(header)
        row_lines = [" | ".join(str(cell) for cell in row) for row in rows]
        data_block = "\n".join([header, separator] + row_lines[:50])

        prompt = f"""You are a helpful assistant for a Learning Management System.

A user asked: "{question}"

Here is the database result:
{data_block}

RULES:
1. Write a clear, concise human-readable answer in plain English.
2. Do NOT include tables, JSON, raw data, or code.
3. Focus only on the most meaningful information.
4. Use natural sentences. Numbers and names are fine.
5. If there are many rows, summarise the pattern rather than listing every row.
6. Keep the response under 300 words.

Answer:"""

        try:
            # gemini-2.5-* can spend most of maxOutputTokens on "thinking"; keep headroom.
            return self._call_llm(prompt, temperature=0.3, max_tokens=8192)
        except RuntimeError as exc:
            logger.error("format_results failed: %s", exc)
            return self._fallback_natural_answer(question, columns, rows)

    def translate_answer_to_arabic(self, user_question: str, answer_en: str) -> str:
        """
        Post-process: same factual content, Modern Standard Arabic wording.
        """
        text = (answer_en or "").strip()
        if not text:
            return text
        snippet = text[:12000]
        prompt = f"""The user asked (they may have used any language): {user_question!r}

Translate the assistant answer below into Modern Standard Arabic (العربية الفصحى).
- Preserve proper names, numbers, dates, emails, URLs, and IDs exactly.
- Keep lists readable (bullets or numbered lines are fine in Arabic).
- Output ONLY the Arabic translation — no English preamble, no labels like "Translation:".

Answer to translate:
---
{snippet}
---
"""
        try:
            return self._call_llm(prompt, temperature=0.15, max_tokens=8192).strip()
        except RuntimeError as exc:
            logger.error("translate_answer_to_arabic failed: %s", exc)
            return text + "\n\n(تعذر تحويل الرد إلى العربية مؤقتاً — أعد المحاولة لاحقاً.)"

    _CAPABILITY_SNIPPETS: tuple[str, ...] = (
        "Give me list of available staff",
        "List students in Grade 1",
        "Give me behaviour record of Abdullah Bin Ahmed",
        "Show all behaviour incidents for all students",
        "Give me all available behavior records for Grade 1",
        "Give me list of all available courses",
        "Give me list of subjects for Grade 1",
        "Give me all subjects assigned to Grade 2",
        "List student leave requests",
        "List approved leave requests",
        "Give me timetable of classes for Grade 1",
        "Give me detailed attendance report of Grade 1 this month",
        "What is the attendance report of Grade 2?",
        "Give me attendance report of staff this month",
        "Show staff attendance for this month",
        "How many teachers are there?",
        "Show students with pending fees",
        "List visitors from the visitor book today",
        "Show admission enquiries this month",
        "List online admission applications this month",
        "Count active students by class",
    )

    def pick_capability_suggestions(self, question: str, max_items: int = 5) -> list[str]:
        """Rank canned NL questions by token overlap with the user's question."""
        q_tokens = _normalize_tokens(question)
        scored: list[tuple[int, str]] = []
        for item in self._CAPABILITY_SNIPPETS:
            t = _normalize_tokens(item)
            overlap = len(q_tokens.intersection(t)) if q_tokens else 0
            bonus = 2 if ("behav" in question.lower() and "behav" in item.lower()) else 0
            bonus += 2 if ("staff" in question.lower() and "staff" in item.lower()) else 0
            bonus += 2 if ("course" in question.lower() and "course" in item.lower()) else 0
            bonus += 2 if ("leave" in question.lower() and "leave" in item.lower()) else 0
            bonus += 2 if ("exam" in question.lower() and "exam" in item.lower()) else 0
            bonus += 2 if ("subject" in question.lower() and "subject" in item.lower()) else 0
            bonus += 2 if ("enquir" in question.lower() and "enquir" in item.lower()) else 0
            bonus += 2 if ("admission" in question.lower() and "admission" in item.lower()) else 0
            bonus += 2 if (
                ("attend" in question.lower() or "attendence" in question.lower())
                and ("attend" in item.lower() or "attendence" in item.lower())
            ) else 0
            scored.append((overlap + bonus, item))
        scored.sort(key=lambda x: (-x[0], x[1]))
        out = []
        for _, item in scored:
            if item not in out:
                out.append(item)
            if len(out) >= max_items:
                break
        if len(out) < max_items:
            for item in self._CAPABILITY_SNIPPETS:
                if item not in out:
                    out.append(item)
                if len(out) >= max_items:
                    break
        return out[:max_items]

    def append_capability_suggestions(self, message: str, question: str) -> str:
        picks = self.pick_capability_suggestions(question, max_items=5)
        lines = [message.rstrip(), "", "**You can try asking (examples this assistant can answer from your data):**"]
        for p in picks:
            lines.append(f"- {p}")
        lines.append("")
        lines.append(
            "_Tip: use a student’s full name as shown in the student list, "
            "and name the module (fees, leave, behaviour, courses, staff, timetable, exams, attendance, enquiries)._"
        )
        return "\n".join(lines)

    # ── Caption Summarization ─────────────────────────────────────────────────
    def summarize_captions(self, captions: str) -> str:
        """
        Summarize a block of video captions/subtitles into a structured overview.
        """
        prompt = f"""You are an educational content assistant.

Below are the captions/subtitles from a course video:

---
{captions[:8000]}
---

RULES:
1. Write a clear, structured summary of this content.
2. Use short sections or bullet points where appropriate.
3. Highlight the main topic, key points, and takeaways.
4. Keep it under 250 words.
5. Write in plain, readable English — no technical jargon unless it's from the content itself.
6. Do NOT repeat sentences verbatim from the captions.

Summary:"""

        try:
            return self._call_llm(prompt, temperature=0.4, max_tokens=600)
        except RuntimeError as exc:
            logger.error("summarize_captions failed: %s", exc)
            summary = self._simple_text_summary(captions, max_sentences=4)
            return (
                "Gemini is temporarily rate-limited, so this is a local fallback summary:\n\n"
                f"{summary}"
            )

    # ── Caption Explanation ───────────────────────────────────────────────────
    def explain_captions(self, captions: str, question: str = "") -> str:
        """
        Explain the content of video captions in simple language.
        Optionally focus on a specific user question.
        """
        focus = f'\nThe user specifically wants to understand: "{question}"' if question else ""

        prompt = f"""You are a patient, knowledgeable teacher helping a student understand a video lesson.

Here are the captions/subtitles from the lesson:

---
{captions[:8000]}
---
{focus}

RULES:
1. Explain the content simply, as if speaking to a curious student.
2. Break down complex ideas into easy-to-understand language.
3. If a specific question was asked, focus your explanation on answering it using the captions.
4. Use examples or analogies if helpful.
5. Keep the explanation under 350 words.
6. Avoid repeating verbatim phrases from the captions.
7. Do NOT include raw data, tables, or JSON.

Explanation:"""

        try:
            return self._call_llm(prompt, temperature=0.5, max_tokens=700)
        except RuntimeError as exc:
            logger.error("explain_captions failed: %s", exc)
            explanation = self._simple_text_summary(captions, max_sentences=5)
            if question:
                return (
                    "Gemini is temporarily rate-limited, so this is a local fallback explanation.\n\n"
                    f"Focus question: {question}\n\n"
                    f"From the provided captions: {explanation}"
                )
            return (
                "Gemini is temporarily rate-limited, so this is a local fallback explanation:\n\n"
                f"{explanation}"
            )
