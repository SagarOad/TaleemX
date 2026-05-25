"""
services/ai_service.py
All calls to the LLM (Gemini primary, Groq fallback):
SQL planning, SQL generation, result critique, result formatting,
caption summarisation / explanation, Arabic translation.
"""

import json
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
from services.text_sanitize import sanitize_cell

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
        Load the SMS app manual + Ask AI knowledge base (if present) once at
        startup. The knowledge base is richer and more navigation-focused, so
        it's preferred — sms.txt is appended as additional context.
        """
        manual_parts: list[str] = []
        base_dir = Path(__file__).resolve().parents[1]
        # 1) Newer, richer knowledge base file (askai_knowledge_base.md from the
        #    PHP project). Copied / mounted into the container at build time.
        for candidate in (
            base_dir / "data" / "askai_knowledge_base.md",
            base_dir / "askai_knowledge_base.md",
        ):
            try:
                if candidate.exists():
                    text = candidate.read_text(encoding="utf-8", errors="ignore").strip()
                    if text:
                        manual_parts.append(text)
                        logger.info(
                            "Loaded knowledge base from %s (chars=%d)",
                            candidate, len(text),
                        )
                        break
            except Exception as exc:
                logger.warning("Knowledge base load failed at %s: %s", candidate, exc)
        # 2) Legacy short manual.
        try:
            manual_path = base_dir / "sms.txt"
            if manual_path.exists():
                text = manual_path.read_text(encoding="utf-8", errors="ignore").strip()
                if text:
                    manual_parts.append(text)
                    logger.info("Loaded app manual from %s (chars=%d)", manual_path, len(text))
        except Exception as exc:
            logger.error("Failed to load app manual: %s", exc)

        combined = "\n\n---\n\n".join(manual_parts)
        if not combined:
            logger.warning("No app manual content available.")
        return combined

    def _extract_relevant_manual_section(self, question: str, max_chars: int = 6000) -> str:
        """
        Pull the section(s) of the manual most relevant to the user's question
        instead of dumping the whole 30K+ document into every prompt. Cuts
        prompt size 5–10x, which is what was timing out `gemini-2.5-pro` on
        manual answers.
        """
        manual = self.app_manual or ""
        if not manual:
            return ""
        # Split on Markdown headings (## or #) — sections are typically a few
        # paragraphs each in askai_knowledge_base.md.
        chunks = re.split(r"\n(?=##\s+)", manual)
        q_tokens = {t for t in re.findall(r"[a-zA-Z]+", (question or "").lower()) if len(t) >= 3}
        if not q_tokens:
            return manual[:max_chars]

        scored: list[tuple[int, str]] = []
        for chunk in chunks:
            text_l = chunk.lower()
            score = 0
            # Heading match weighs much more than body match.
            head_match = re.search(r"^##\s+(.+)", chunk, re.MULTILINE)
            heading = (head_match.group(1).lower() if head_match else "").strip()
            for tok in q_tokens:
                if tok in heading:
                    score += 5
                # count occurrences (cap to avoid one chunk dominating)
                count = text_l.count(tok)
                score += min(count, 6)
            scored.append((score, chunk))

        scored.sort(key=lambda x: -x[0])
        # Always keep the very first chunk (the "product overview" preface)
        # plus the top relevant ones, up to the char budget.
        selected: list[str] = []
        used = 0
        # Preface first (chunks[0] is the file header / overview).
        if chunks:
            preface = chunks[0]
            selected.append(preface)
            used += len(preface)
        for score, chunk in scored:
            if score <= 0 or chunk in selected:
                continue
            if used + len(chunk) > max_chars and selected:
                break
            selected.append(chunk)
            used += len(chunk)
            if used >= max_chars:
                break

        return "\n\n".join(selected)[:max_chars]

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

        # Groq models have much smaller context windows than Gemini. Conservatively
        # cap the prompt at ~24k characters (~6k tokens) so we stay under the
        # tightest tier (llama-3.1-8b-instant at 8k). The 70b versatile model
        # supports 128k and isn't affected.
        safe_prompt = prompt
        max_chars = 24000
        if len(prompt) > max_chars:
            head = prompt[: int(max_chars * 0.6)]
            tail = prompt[-int(max_chars * 0.35):]
            safe_prompt = (
                head
                + "\n\n... (prompt truncated to fit Groq context window) ...\n\n"
                + tail
            )
            logger.warning(
                "Groq prompt truncated from %d to %d chars to fit context.",
                len(prompt), len(safe_prompt),
            )

        payload = {
            "model": self.groq_model,
            "messages": [{"role": "user", "content": safe_prompt}],
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

    def _call_llm(
        self,
        prompt: str,
        temperature: float = 0.2,
        max_tokens: int = 1024,
        prefer_fast: bool = False,
        disable_thinking: bool = False,
    ) -> str:
        """
        Primary Gemini call with Groq fallback.

        prefer_fast=True routes the call to the fast Gemini variant
        (default ``gemini-2.5-flash``) for short, latency-sensitive prompts
        like plan / critic.

        disable_thinking=True asks Gemini 2.5 models to skip internal reasoning
        tokens (``thinkingConfig.thinkingBudget = 0``). Use it whenever you do
        not need chain-of-thought and want the full ``max_tokens`` budget to go
        to the visible response — otherwise short prompts like the manual
        answer get truncated mid-sentence by the reasoning step.
        """
        primary_model = self.model
        if prefer_fast and Config.GEMINI_FAST_MODEL:
            primary_model = Config.GEMINI_FAST_MODEL

        try:
            return self._call_gemini(
                prompt,
                temperature=temperature,
                max_tokens=max_tokens,
                model_override=primary_model,
                disable_thinking=disable_thinking,
            )
        except RuntimeError as gemini_error:
            if not self.groq_api_key:
                raise
            logger.warning("Gemini failed, trying Groq fallback: %s", gemini_error)
            return self._call_groq(prompt, temperature=temperature, max_tokens=max_tokens)

    def set_runtime_schema(self, schema_text: str):
        """Update SQL prompt schema dynamically (e.g., from live DB introspection)."""
        if schema_text and schema_text.strip():
            self.schema = schema_text.strip()

    def _call_gemini(
        self,
        prompt: str,
        temperature: float = 0.2,
        max_tokens: int = 1024,
        model_override: str | None = None,
        disable_thinking: bool = False,
    ) -> str:
        """
        Send a single-turn prompt to Gemini and return the text response.
        Raises RuntimeError on API failure.
        """
        generation_config = {
            "temperature": temperature,
            "maxOutputTokens": max_tokens,
        }
        # Gemini 2.5 models reserve a chunk of `maxOutputTokens` for internal
        # reasoning. For short, deterministic outputs (manual answers, etc.)
        # disable thinking so the full budget goes to the visible response.
        if disable_thinking:
            generation_config["thinkingConfig"] = {"thinkingBudget": 0}

        payload = {
            "contents": [{"parts": [{"text": prompt}]}],
            "generationConfig": generation_config,
        }

        primary = model_override or self.model
        model_candidates = [primary] + [m for m in self.fallback_models if m != primary]
        if primary != self.model and self.model not in model_candidates:
            # Always keep the main model in the fallback list so we degrade
            # gracefully if the fast variant is unavailable.
            model_candidates.append(self.model)
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
                    response = requests.post(
                        url,
                        json=payload,
                        timeout=Config.GEMINI_REQUEST_TIMEOUT_SECONDS,
                    )
                    response.raise_for_status()
                    success = True
                    if model_name != primary:
                        logger.warning(
                            "Gemini fallback model succeeded: %s", model_name
                        )
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
        usage = data.get("usageMetadata") or {}
        logger.debug(
            "Gemini response | model=%s | finish=%s | chars=%d | usage=%s",
            model_name, finish_reason, len(text), usage,
        )
        # If Gemini stopped early because it ran out of its (possibly invisible
        # reasoning) budget, retry once with a much larger budget AND thinking
        # disabled so the full budget goes to the visible answer.
        if finish_reason == "MAX_TOKENS" and text and len(text) < 400:
            logger.warning(
                "Gemini truncated short answer (%d chars, max_tokens=%d). "
                "Retrying once with disable_thinking=True and 4x budget.",
                len(text), max_tokens,
            )
            retry_config = dict(generation_config)
            retry_config["maxOutputTokens"] = max_tokens * 4
            retry_config["thinkingConfig"] = {"thinkingBudget": 0}
            try:
                retry_url = f"{GEMINI_BASE_URL}/{model_name}:generateContent?key={self.api_key}"
                self._acquire_gemini_slot()
                retry_response = requests.post(
                    retry_url,
                    json={"contents": [{"parts": [{"text": prompt}]}], "generationConfig": retry_config},
                    timeout=Config.GEMINI_REQUEST_TIMEOUT_SECONDS,
                )
                retry_response.raise_for_status()
                rdata = retry_response.json()
                rcands = rdata.get("candidates") or []
                if rcands:
                    rparts = (rcands[0].get("content") or {}).get("parts") or []
                    rtext = "".join(p.get("text") or "" for p in rparts).strip()
                    if rtext and len(rtext) > len(text):
                        return rtext
            except Exception as exc:
                logger.warning("Gemini retry-after-truncation failed: %s", exc)
        if text:
            return text

        if finish_reason == "MAX_TOKENS":
            logger.warning("Gemini output empty due to MAX_TOKENS | raw: %s", data)
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
        from services.executive_briefing import is_executive_scope_question

        if is_executive_scope_question(question):
            return ""  # Handled by executive_briefing multi-query path in SQLAgent.

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

        # HR / departments — must run before generic "how many teachers do we have"
        # (both phrasings contain "we have" and confuse vector fast-path).
        if "department" in q or "departments" in q:
            if ("how many" in q or "count" in q) and "department" in q and "staff" not in q and "each" not in q:
                return (
                    "SELECT COUNT(*) AS department_count FROM department"
                )
            if ("how many" in q or "count" in q or "each" in q) and (
                "staff" in q or "employee" in q or "teacher" in q
            ):
                return (
                    "SELECT d.department_name, COUNT(s.id) AS active_staff_count "
                    "FROM department d "
                    "LEFT JOIN staff s ON s.department = d.id AND s.is_active = 1 "
                    "GROUP BY d.id, d.department_name "
                    "ORDER BY active_staff_count DESC, d.department_name "
                    "LIMIT 50"
                )
            if any(w in q for w in ("what", "which", "list", "show", "name", "all", "give")):
                return (
                    "SELECT d.department_name FROM department d "
                    "ORDER BY d.department_name LIMIT 50"
                )

        if ("human resource" in q or " hr " in f" {q} ") and any(
            w in q for w in ("department", "departments", "designation", "headcount")
        ):
            if "designation" in q:
                return (
                    "SELECT sd.designation, COUNT(s.id) AS active_staff_count "
                    "FROM staff_designation sd "
                    "LEFT JOIN staff s ON s.designation = sd.id AND s.is_active = 1 "
                    "GROUP BY sd.id, sd.designation "
                    "ORDER BY active_staff_count DESC, sd.designation "
                    "LIMIT 50"
                )
            if "headcount" in q or "summary" in q:
                return (
                    "SELECT 'Active staff' AS metric, CAST(COUNT(*) AS CHAR) AS value "
                    "FROM staff WHERE is_active = 1 "
                    "UNION ALL SELECT 'Teachers', CAST(COUNT(DISTINCT sr.staff_id) AS CHAR) "
                    "FROM staff_roles sr JOIN roles r ON r.id = sr.role_id "
                    "WHERE LOWER(r.slug) = 'teacher' OR LOWER(r.name) LIKE '%teacher%' "
                    "UNION ALL SELECT 'Departments', CAST(COUNT(*) AS CHAR) FROM department"
                )
            return (
                "SELECT d.department_name FROM department d "
                "ORDER BY d.department_name LIMIT 50"
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

        # One student's profile — must run before the generic "give … student …" list rule,
        # otherwise "give me detail of student X" matches list_terms + "student" and dumps all students.
        student_name_match = (
            re.search(
                r"(?:give|show|get|fetch|tell)\s+(?:me\s+)?(?:the\s+)?(?:a\s+)?"
                r"(?:detail|details|info|information|profile)\s+(?:of\s+)?(?:the\s+)?(?:student\s+)(.+)$",
                q,
            )
            or re.search(
                r"(?:details?|info|information|profile)\s+of\s+(?:the\s+)?(?:student\s+)?(.+)$",
                q,
            )
            or re.search(r"student\s+details?\s+(?:of\s+)?(?:the\s+)?(.+)$", q)
            or re.search(r"(?:about|for)\s+(?:the\s+)?student\s+(.+)$", q)
        )
        if student_name_match:
            raw_name = student_name_match.group(1).strip().strip("?.!,;:")
            if raw_name and "all student" not in raw_name and "all students" not in raw_name:
                student_name = " ".join(raw_name.split())
                safe_student_name = student_name.replace("'", "''")
                return (
                    f"SELECT s.admission_no, s.roll_no, s.admission_date, s.firstname, s.middlename, s.lastname, "
                    f"s.mobileno, s.email, s.dob, s.gender, s.father_name, s.father_phone, s.mother_name, "
                    f"s.guardian_name, s.guardian_phone, s.guardian_email, s.current_address, "
                    f"(SELECT c.class FROM student_session ss JOIN classes c ON c.id = ss.class_id "
                    f"WHERE ss.student_id = s.id ORDER BY ss.id DESC LIMIT 1) AS current_class, "
                    f"(SELECT sec.section FROM student_session ss "
                    f"JOIN sections sec ON sec.id = ss.section_id "
                    f"WHERE ss.student_id = s.id ORDER BY ss.id DESC LIMIT 1) AS current_section "
                    f"FROM students s "
                    f"WHERE (LOWER(CONCAT_WS(' ', s.firstname, s.middlename, s.lastname)) LIKE LOWER('%{safe_student_name}%') "
                    f"OR LOWER(CONCAT_WS(' ', s.firstname, s.lastname)) LIKE LOWER('%{safe_student_name}%')) "
                    f"ORDER BY "
                    f"(LOWER(CONCAT_WS(' ', s.firstname, s.middlename, s.lastname)) = LOWER('{safe_student_name}')) DESC, "
                    f"CHAR_LENGTH(CONCAT_WS(' ', s.firstname, s.middlename, s.lastname)) ASC"
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
            and not re.search(r"\b(?:detail|details|info|information|profile)\b", q)
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

        # Student attendance summary (all students) — BEFORE per-name heuristic which
        # wrongly treats "per student this month" as a student name.
        if ("attendance" in q or "attendence" in q) and "staff" not in q and (
            "summary per student" in q
            or ("summary" in q and "per student" in q)
            or ("summary" in q and "each student" in q)
            or re.search(r"attendance\s+summary.*\bstudent", q)
            or re.search(r"\bstudent.*attendance\s+summary", q)
        ):
            month_sql = ""
            if "this month" in q or re.search(r"\bthis\s+month\b", q):
                month_sql = (
                    " AND MONTH(sa.date) = MONTH(CURDATE()) "
                    "AND YEAR(sa.date) = YEAR(CURDATE())"
                )
            present = (
                "CASE WHEN LOWER(COALESCE(at.type, '')) IN ('p','present','1') "
                "OR LOWER(COALESCE(at.long_lang_name, '')) LIKE '%present%' "
                "THEN 1 ELSE 0 END"
            )
            absent = (
                "CASE WHEN LOWER(COALESCE(at.type, '')) IN ('a','absent') "
                "OR LOWER(COALESCE(at.long_lang_name, '')) LIKE '%absent%' "
                "THEN 1 ELSE 0 END"
            )
            late = (
                "CASE WHEN LOWER(COALESCE(at.type, '')) IN ('l','late') "
                "OR LOWER(COALESCE(at.long_lang_name, '')) LIKE '%late%' "
                "THEN 1 ELSE 0 END"
            )
            return (
                "SELECT CONCAT_WS(' ', s.firstname, s.lastname) AS student_name, "
                f"SUM({present}) AS present_days, SUM({absent}) AS absent_days, "
                f"SUM({late}) AS late_days, COUNT(*) AS days_marked, "
                f"ROUND(100.0 * SUM({present}) / NULLIF(COUNT(*), 0), 2) AS attendance_percent "
                "FROM student_attendences sa "
                "JOIN student_session ss ON ss.id = sa.student_session_id "
                "JOIN students s ON s.id = ss.student_id "
                "LEFT JOIN attendence_type at ON at.id = sa.attendence_type_id "
                f"WHERE 1=1 {month_sql} "
                "GROUP BY s.id, student_name "
                "ORDER BY attendance_percent DESC, present_days DESC "
                "LIMIT 50"
            )

        # Best / top student attendance history (overall, not staff).
        if ("attendance" in q or "attendence" in q) and "staff" not in q and (
            ("best" in q or "highest" in q or "top" in q or "good" in q)
            and ("history" in q or "overall" in q or "record" in q or "student" in q)
        ):
            present = (
                "CASE WHEN LOWER(COALESCE(at.type, '')) IN ('p','present','1') "
                "OR LOWER(COALESCE(at.long_lang_name, '')) LIKE '%present%' "
                "THEN 1 ELSE 0 END"
            )
            return (
                "SELECT CONCAT_WS(' ', s.firstname, s.lastname) AS student_name, "
                f"COUNT(*) AS days_marked, SUM({present}) AS present_days, "
                f"ROUND(100.0 * SUM({present}) / NULLIF(COUNT(*), 0), 2) AS attendance_percent "
                "FROM student_attendences sa "
                "JOIN student_session ss ON ss.id = sa.student_session_id "
                "JOIN students s ON s.id = ss.student_id "
                "LEFT JOIN attendence_type at ON at.id = sa.attendence_type_id "
                "GROUP BY s.id, student_name "
                "HAVING COUNT(*) >= 1 "
                "ORDER BY attendance_percent DESC, present_days DESC "
                "LIMIT 50"
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

        # Tolerate the typo "attendence" and the (very common) phrasing
        # "attendance report of <name>" / "attendence of <name>" / "attendance for <name>".
        attendance_name_match = re.search(
            r"\b(?:attendance|attendence)\s*(?:report|summary|record|records|sheet|history|log)?\s+(?:for\s+|of\s+|about\s+)?(?:the\s+)?(?:student\s+)?(.+)$",
            q,
        )
        if attendance_name_match:
            raw_name = attendance_name_match.group(1).strip().strip("?.!,;:")
            # Defensive: strip a leading "report"/"summary"/etc. that the
            # earlier optional group may have skipped past on weird wordings.
            raw_name = re.sub(
                r"^(?:report|summary|record|records|sheet|history|log)\s+(?:of\s+|for\s+|about\s+)?",
                "",
                raw_name,
            ).strip()
            _not_a_name = (
                "per student", "this month", "all students", "each student",
                "every student", "summary", "report", "history", "overall",
                "best", "worst", "grade", "class", "staff", "the month",
                "lowest", "highest", "top", "bottom", "month",
            )
            if raw_name and "all student" not in raw_name and "all students" not in raw_name:
                if not any(b in raw_name for b in _not_a_name):
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

        if "how many" in q and "teacher" in q and "department" not in q:
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
        Decide whether to route the question to the manual (UI navigation / how-to)
        or to the SQL pipeline (data fetch). Conservative on purpose: anything
        that even hints at "give me the data" stays on the SQL path. Modules like
        "admission" only count as manual when paired with a clear navigation verb
        like "how" or "where", because "admission enquiries" alone is a data
        request.
        """
        q = (question or "").lower().strip()
        if not q:
            return False

        # Hard-block: data verbs always mean SQL, even if a module noun is present.
        data_verbs = (
            "list", "show", "give", "fetch", "find", "get me", "display",
            "count", "how many", "how much", "total", "sum", "average", "avg",
            "top ", "bottom ", "best ", "worst ", "compare", " vs ", " versus ",
            "report", "summary", "overview", "trend", "breakdown", "details of",
            "give me details", "names of", "any ", "available ",
        )
        if any(t in q for t in data_verbs):
            return False

        if self._looks_like_data_request(q):
            return False

        # Clear "how to navigate" markers — these go to the manual.
        manual_markers = (
            "how do i", "how can i", "how to ", "where do i", "where can i",
            "how does", "what is this", "what is the platform", "what does this",
            "how it works", "how this platform", "what can you do",
            "what can this", "tell me about the system", "tell me about this",
            "explain ", "guide me", "walk me through",
        )
        if any(t in q for t in manual_markers):
            return True

        # Bare "what is X" — only manual if X is a feature/module concept
        # (not a person/data column).
        if q.startswith("what is "):
            return True

        # Standalone navigation nouns without any data verb.
        nav_only = (
            "navigation", "menu", "sidebar", "settings", "preference",
            "configure", "configuration", "permission", "role", "module",
        )
        return any(t in q for t in nav_only)

    def answer_from_manual(self, question: str) -> str:
        """
        Answer app-flow / how-to / "what is" questions grounded in the
        knowledge base. Uses the fast model and a chunked, keyword-scored
        section of the manual so we don't time out on the 30k-char dump.
        """
        if not self.app_manual:
            return (
                "I don't have the product manual loaded right now. "
                "You can still ask me data questions like 'how many students do we have?'."
            )

        # Pull only the few sections relevant to this question — saves ~80%
        # of the tokens vs. shipping the whole manual.
        section = self._extract_relevant_manual_section(question, max_chars=6000)

        prompt = f"""You are a friendly product expert for the Smart School / TaleemX LMS.
Use ONLY the reference below to answer. If the answer isn't in there, say so
briefly and suggest the closest related feature you DO see in the reference.

Reference (relevant excerpt from the product knowledge base):
---
{section}
---

User question: {question}

How to respond:
1. Open with a 1–2 sentence direct answer.
2. If navigation is involved, give the path as "Module > Submodule > Action".
3. Mention 2–4 things the user can do in that screen (bullet list).
4. Keep total response under ~180 words. No code blocks. No SQL.
5. End with one short suggestion of what the user could ask next (an
   actionable, data-style question they can run from Ask AI).
"""
        # Use the fast model with thinking DISABLED — handles manual prompts
        # fine, ~5x faster than Pro, and the full max_tokens budget goes to
        # the visible answer instead of being burned on internal reasoning.
        try:
            return self._call_llm(
                prompt,
                temperature=0.3,
                max_tokens=900,
                prefer_fast=True,
                disable_thinking=True,
            )
        except RuntimeError as exc:
            logger.warning("Fast model failed for manual (%s); retrying default model.", exc)

        try:
            return self._call_llm(
                prompt, temperature=0.3, max_tokens=900, disable_thinking=True,
            )
        except RuntimeError as exc:
            logger.error("answer_from_manual failed (LLM): %s", exc)

        # Final fallback: extract the heading + first paragraph of the best
        # section so the user gets SOMETHING useful even if all LLM calls fail.
        return self._manual_fallback_excerpt(question, section)

    def _manual_fallback_excerpt(self, question: str, section: str) -> str:
        """
        Pure-Python fallback for manual questions when every LLM is unavailable.
        Returns the most relevant heading + a short summary so the user isn't
        left with the blank-error message we used to ship.
        """
        if not section:
            return (
                "I couldn't reach the AI service to look that up right now. "
                "Please try again in a moment."
            )
        # First heading + a few following sentences.
        match = re.search(r"##\s+(.+?)\n+(.+?)(?:\n##|\Z)", section, re.DOTALL)
        if not match:
            return section[:600]
        heading = match.group(1).strip()
        body = match.group(2).strip()
        sentences = re.split(r"(?<=[.!?])\s+", body)
        summary = " ".join(sentences[:3]).strip()
        return (
            f"**{heading}**\n\n{summary}\n\n"
            "_(I had trouble reaching the AI model just now — this is the closest "
            "section from the product manual. Please try again for a full answer.)_"
        )

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
            "give me details", "show all", "available exams", "available homework",
            "available homeworks", "homework", "homeworks", "assignment", "assignments",
            "staff", "list of students", "students of grade", "behaviour report",
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
                display = sanitize_cell(val, column_name=str(col))
                out.append(f"   - {_label(col)}: {display}")
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

    # ── Public alias used by the agent loop ──────────────────────────────────
    def deterministic_sql(self, question: str) -> str:
        """Public wrapper around the hand-tuned deterministic SQL patterns."""
        return self._deterministic_sql(question)

    # ── Agent loop: planning ────────────────────────────────────────────────
    def plan_query(self, question: str, schema_text: str, examples_text: str) -> str:
        """
        Produce a 1–3 line plan describing how we will answer the question.
        Cheap call (small max_tokens) — improves accuracy on the SQL step
        because it forces the model to pick tables before writing JOINs.
        """
        prompt = f"""You are a SQL query planner for an LMS database.
A user asked: {question!r}

Relevant schema:
---
{schema_text}
---

Similar past examples (question → SQL):
---
{examples_text}
---

Write a brief plan (1-3 short lines) describing:
1) which table(s) to use as primary source,
2) which columns to select, and
3) the filter strategy (date ranges, name match, joins).

Do NOT write SQL. Plan only.
"""
        try:
            return self._call_llm(
                prompt,
                temperature=Config.AGENT_PLAN_TEMPERATURE,
                max_tokens=300,
                prefer_fast=True,
            ).strip()
        except RuntimeError as exc:
            logger.warning("plan_query failed: %s", exc)
            return ""

    # ── Agent loop: SQL generation grounded in retrieved context ────────────
    def generate_sql_with_context(
        self,
        question: str,
        plan: str,
        schema_text: str,
        examples_text: str,
        prior_feedback: str = "",
    ) -> str:
        """
        Context-aware SQL generation used by the agent loop.
        Includes RAG-retrieved schema + few-shot examples + (optional)
        feedback from the previous failed attempt.
        """
        # Cheap deterministic shortcut first — same as the legacy path.
        deterministic = self._deterministic_sql(question)
        if deterministic and not prior_feedback:
            logger.info("Agent using deterministic SQL for: %s", question)
            return deterministic

        feedback_block = ""
        if prior_feedback:
            feedback_block = (
                f"\nFEEDBACK FROM PREVIOUS ATTEMPT (must be fixed):\n{prior_feedback}\n"
            )

        plan_block = f"\nPLAN:\n{plan}\n" if plan else ""

        prompt = f"""You are a senior MySQL engineer answering questions on a Learning Management System.

DATABASE SCHEMA (only these tables and columns exist — never invent others):
---
{schema_text}
---

GOLD EXAMPLES (verified question → SQL from this same database):
---
{examples_text}
---
{plan_block}{feedback_block}
RULES:
1. Return ONLY the raw SQL query — no markdown, no backticks, no commentary.
2. Only SELECT statements. Never INSERT/UPDATE/DELETE/DROP/ALTER/TRUNCATE/GRANT/CALL.
3. Use ONLY tables and columns shown in the schema above. If a needed table is missing, respond exactly: NOT_DATA_QUESTION
4. Add LIMIT 50 unless the query is an aggregate (COUNT/SUM/AVG/MIN/MAX).
5. Use proper JOINs when data spans multiple tables; respect the foreign keys hinted in the schema.
6. For name searches, prefer LOWER(CONCAT_WS(' ', firstname, lastname)) LIKE LOWER('%name%').
7. For grade/class filters, accept all three styles: c.class = '1' OR 'Grade 1' OR 'Class 1'.
8. Date filters: use MONTH(col)=MONTH(CURDATE()) AND YEAR(col)=YEAR(CURDATE()) for "this month", DATE(col)=CURDATE() for "today".
9. Treat list/show/give/fetch as data-fetch intents.
10. Be deterministic — same inputs should produce the same SQL.
11. GROUP BY rules: every non-aggregated column in the SELECT or HAVING clause MUST appear in the GROUP BY clause. If you need a per-row condition like "amount > sum(paid)", either include `amount` in GROUP BY, or refactor with a subquery / derived table / a SUM(amount) aggregate. Never put a bare non-aggregated column in HAVING when GROUP BY is present.
12. For "how many X have remaining/pending fees" type questions: count students whose total invoice amount exceeds total paid. Prefer a subquery shape:
    SELECT COUNT(*) FROM (
      SELECT sfm.student_id FROM student_fees_master sfm
      LEFT JOIN student_fees_deposite sfd ON sfd.student_fees_master_id = sfm.id
      GROUP BY sfm.id, sfm.student_id, sfm.amount
      HAVING sfm.amount > COALESCE(SUM(sfd.amount), 0)
    ) t
   …or just `WHERE sfd.id IS NULL` for the simple "never paid" case.

USER QUESTION: {question}

SQL:"""

        try:
            result = self._call_llm(
                prompt,
                temperature=Config.AGENT_SQL_TEMPERATURE,
                max_tokens=8192,
            )
        except RuntimeError as exc:
            logger.error("generate_sql_with_context failed: %s", exc)
            return self._fallback_sql(question)

        result = re.sub(r"```sql|```", "", result, flags=re.IGNORECASE).strip()

        if result.upper().startswith("NOT_DATA_QUESTION"):
            if self._looks_like_data_request(question):
                logger.warning(
                    "Model said NOT_DATA_QUESTION but question looks data-shaped; "
                    "retrying with strict recovery prompt."
                )
                recovery = f"""Convert this LMS request to ONE MySQL SELECT using only the schema below.

SCHEMA:
{schema_text}

REQUEST: {question}

Rules:
1. Return ONLY SQL.
2. Must start with SELECT.
3. Add LIMIT 50 for non-aggregate queries.
4. Never return NOT_DATA_QUESTION.
"""
                try:
                    recovered = self._call_llm(recovery, temperature=0.0, max_tokens=8192)
                    recovered = re.sub(r"```sql|```", "", recovered, flags=re.IGNORECASE).strip()
                    if recovered.upper().startswith("SELECT"):
                        return recovered
                except RuntimeError as exc:
                    logger.error("Recovery SQL generation failed: %s", exc)
            return ""

        return result

    # ── Agent loop: result critique ──────────────────────────────────────────
    def critique_result(
        self,
        question: str,
        sql: str,
        columns: list,
        rows: list,
    ) -> dict:
        """
        Ask the model: "do these rows answer the user's question?"
        Returns {"decision": "satisfied" | "regenerate", "reason": "..."}.
        Failures default to 'satisfied' so we don't loop forever on LLM hiccups.
        """
        if not rows:
            return {"decision": "regenerate", "reason": "Query returned 0 rows."}

        preview_rows = rows[:5]
        preview = " | ".join(columns) + "\n"
        preview += "\n".join(" | ".join(str(c) for c in r) for r in preview_rows)

        prompt = f"""You are a strict SQL reviewer for an LMS assistant.
The user asked: {question!r}

The assistant ran this SQL:
{sql}

Result columns: {columns}
First {len(preview_rows)} rows of the result:
{preview}

Decide if this result satisfies the user's intent.

Reply in strict JSON only:
{{ "decision": "satisfied" | "regenerate", "reason": "<one short sentence>" }}

Use "regenerate" only if:
- the rows clearly do NOT answer what was asked (wrong entity, wrong filter, wrong granularity), OR
- critical columns are missing for the user to act on the answer, OR
- the result is duplicated/incoherent.
Otherwise reply "satisfied".
"""
        try:
            raw = self._call_llm(
                prompt,
                temperature=Config.AGENT_CRITIC_TEMPERATURE,
                max_tokens=200,
                prefer_fast=True,
            ).strip()
        except RuntimeError as exc:
            logger.warning("critique_result failed (defaulting to satisfied): %s", exc)
            return {"decision": "satisfied", "reason": ""}

        # Strip any markdown fencing.
        raw = re.sub(r"```json|```", "", raw, flags=re.IGNORECASE).strip()
        try:
            data = json.loads(raw)
        except json.JSONDecodeError:
            match = re.search(r"\{.*\}", raw, re.DOTALL)
            if not match:
                return {"decision": "satisfied", "reason": ""}
            try:
                data = json.loads(match.group(0))
            except json.JSONDecodeError:
                return {"decision": "satisfied", "reason": ""}

        decision = (data.get("decision") or "satisfied").strip().lower()
        if decision not in ("satisfied", "regenerate"):
            decision = "satisfied"
        return {"decision": decision, "reason": (data.get("reason") or "").strip()}

    # ── Legacy single-shot SQL generation (kept for tests/back-compat) ───────
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
        def _cell_text(cell, col_name: str) -> str:
            if cell is None:
                return ""
            if isinstance(cell, (int, float)) and not isinstance(cell, bool):
                return str(cell)
            return sanitize_cell(cell, column_name=col_name)

        row_lines = [
            " | ".join(_cell_text(cell, columns[i] if i < len(columns) else "") for i, cell in enumerate(row))
            for row in rows
        ]
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
7. Never repeat raw HTML tags — describe content in plain words only.

Answer:"""

        try:
            # gemini-2.5-* can spend most of maxOutputTokens on "thinking"; keep headroom.
            # Use the fast model — formatting doesn't need the heavyweight reasoner.
            return self._call_llm(
                prompt, temperature=0.3, max_tokens=4096, prefer_fast=True
            )
        except RuntimeError as exc:
            logger.error("format_results failed: %s", exc)
            return self._fallback_natural_answer(question, columns, rows)

    def translate_structured_data_to_arabic(
        self, user_question: str, structured_data: dict
    ) -> dict:
        """
        Translate table headers, KPI labels, and text cell values to Arabic.
        Numbers, dates, and proper names are preserved.
        """
        if not structured_data:
            return structured_data
        import json

        try:
            payload = json.dumps(structured_data, ensure_ascii=False, default=str)
        except (TypeError, ValueError):
            return structured_data
        # Never truncate JSON — invalid JSON breaks translation and wastes a call.
        if len(payload) > 8000:
            from services.arabic_localize import localize_structured_data

            return localize_structured_data(structured_data)

        prompt = f"""The user asked: {user_question!r}

Translate this JSON API payload into Modern Standard Arabic for a school admin UI.
Rules:
- Translate English column headers (e.g. student_name → اسم الطالب).
- Translate English enum/status words in cells (Present, Absent, High, Medium, Staff, Student).
- Keep numbers, percentages, dates, emails, phone numbers, and person names unchanged.
- Return ONLY valid JSON with the exact same structure and keys as the input.
- For chart datasets, translate label strings only.

JSON to translate:
{payload}
"""
        try:
            raw = self._call_llm(
                prompt, temperature=0.1, max_tokens=8192, prefer_fast=True
            ).strip()
            if raw.startswith("```"):
                raw = re.sub(r"^```(?:json)?\s*", "", raw)
                raw = re.sub(r"\s*```$", "", raw)
            return json.loads(raw)
        except Exception as exc:
            logger.warning("translate_structured_data_to_arabic failed: %s", exc)
            return self._arabic_structured_fallback(structured_data)

    def _arabic_structured_fallback(self, sd: dict) -> dict:
        """Lightweight header map when LLM translation fails."""
        header_map = {
            "student_name": "اسم الطالب",
            "person_name": "الاسم",
            "request_type": "النوع",
            "present_days": "أيام الحضور",
            "absent_days": "أيام الغياب",
            "late_days": "أيام التأخر",
            "days_marked": "أيام مسجلة",
            "attendance_percent": "نسبة الحضور %",
            "metric": "المؤشر",
            "value": "القيمة",
            "risk_area": "مجال الخطر",
            "risk_indicator": "مؤشر الخطر",
            "issue_count": "العدد",
            "severity": "الخطورة",
            "recommended_action": "الإجراء المقترح",
            "concern_area": "مجال الاهتمام",
            "priority_note": "ملاحظة",
            "apply_date": "تاريخ الطلب",
            "from_date": "من",
            "to_date": "إلى",
            "reason": "السبب",
            "status": "الحالة",
        }
        out = dict(sd)
        cols = out.get("columns")
        if isinstance(cols, list):
            out["columns"] = [
                header_map.get(str(c).lower(), str(c).replace("_", " "))
                for c in cols
            ]
        label = out.get("label")
        if isinstance(label, str):
            out["label"] = header_map.get(label.lower(), label)
        return out

    def translate_list_to_arabic(self, items: list[str], user_question: str = "") -> list[str]:
        if not items:
            return items
        joined = "\n".join(f"- {s}" for s in items[:12])
        prompt = f"""Translate each bullet line to Modern Standard Arabic (school LMS context).
Keep names and numbers. Return the same number of lines, one translation per line, no bullets.

User question: {user_question!r}
Lines:
{joined}
"""
        try:
            text = self._call_llm(
                prompt, temperature=0.1, max_tokens=1024, prefer_fast=True
            ).strip()
            lines = [ln.strip().lstrip("-• ").strip() for ln in text.splitlines() if ln.strip()]
            if len(lines) >= len(items):
                return lines[: len(items)]
        except Exception as exc:
            logger.warning("translate_list_to_arabic failed: %s", exc)
        return items

    def translate_answer_to_arabic(
        self,
        user_question: str,
        answer_en: str,
        presentation: str | None = None,
    ) -> str:
        """
        Post-process: same factual content, Modern Standard Arabic wording.
        One LLM call; shorter input for executive briefing to avoid timeouts.
        """
        text = (answer_en or "").strip()
        if not text:
            return text
        max_chars = 5000 if presentation == "executive_briefing" else 9000
        snippet = text[:max_chars]
        if len(text) > max_chars:
            snippet += "\n\n[Dashboard tables and charts below carry full detail.]"

        prompt = f"""The user asked (they may have used any language): {user_question!r}

Translate the assistant answer below into Modern Standard Arabic (العربية الفصحى).
- Preserve proper names, numbers, dates, emails, URLs, and IDs exactly.
- Keep lists readable (bullets or numbered lines are fine in Arabic).
- Output ONLY the Arabic translation — no English preamble, no labels like "Translation:".
- Do NOT output HTML or JSON.

Answer to translate:
---
{snippet}
---
"""
        try:
            return self._call_llm(
                prompt, temperature=0.15, max_tokens=4096, prefer_fast=True
            ).strip()
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
