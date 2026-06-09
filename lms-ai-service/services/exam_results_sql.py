"""
Exam results SQL aligned with TaleemX /admin/examresult (Examresult controller).

Primary data path (modern):
  exam_group_class_batch_exam_students
  exam_group_class_batch_exam_subjects
  exam_group_exam_results  (get_marks, attendence, note)
  exam_group_class_batch_exams (exam name)

Legacy fallback:
  exam_results + exam_schedules + exams + teacher_subjects
"""

from __future__ import annotations

import re
from dataclasses import dataclass, field
from typing import TYPE_CHECKING, Optional

if TYPE_CHECKING:
    from services.db_service import DBService

from services.online_course_sql import is_online_course_lms_question


@dataclass
class ExamAnalyticsReport:
    """Precomputed exam analytics (grade distribution, etc.)."""

    message: str = ""
    columns: list[str] = field(default_factory=list)
    rows: list = field(default_factory=list)
    sql_note: str = ""

_GRADE_RE = re.compile(r"\b(?:grade|class)\s*(\d+)\b", re.IGNORECASE)
_RESULTS_RE = re.compile(
    r"\b(?:result|results|marksheet|mark\s*sheet|score|scores|marks)\b",
    re.IGNORECASE,
)
_SCHEDULE_RE = re.compile(
    r"\b(?:schedule|scheduled|timetable|upcoming|when\s+is|when\s+are)\b",
    re.IGNORECASE,
)


def _esc(s: str) -> str:
    return (s or "").replace("'", "''")


def parse_grade_from_question(question: str) -> Optional[str]:
    m = _GRADE_RE.search(question or "")
    return m.group(1) if m else None


def is_exam_results_question(question: str) -> bool:
    """Marks / results for examinations — not schedules or online exam catalog."""
    q = (question or "").lower()
    if "exam" not in q and "examination" not in q:
        return False
    if _SCHEDULE_RE.search(q) and not _RESULTS_RE.search(q):
        return False
    if _RESULTS_RE.search(q):
        return True
    if "exam result" in q or "examination result" in q:
        return True
    if re.search(r"\bresult\s+of\s+(?:grade|class)\b", q):
        return True
    return False


def is_exam_schedule_question(question: str) -> bool:
    q = (question or "").lower()
    if is_online_course_lms_question(question):
        return False
    if "exam" not in q:
        return False
    if is_exam_results_question(question):
        return False
    return bool(
        _SCHEDULE_RE.search(q)
        or "available exams" in q
        or re.search(r"\bwhen\s+(?:is|are)\b.*\bexam", q)
    )


def _grade_where(grade_no: str) -> str:
    g = str(grade_no).replace("'", "''")
    return (
        f"(c.class = '{g}' OR c.class = 'Grade {g}' OR c.class = 'Class {g}')"
    )


def build_exam_group_results_by_grade_sql(grade_no: str) -> str:
    """Same shape as Examresult_model::getStudentResultByExam + class filter."""
    where = _grade_where(grade_no)
    return (
        "SELECT CONCAT_WS(' ', st.firstname, st.middlename, st.lastname) AS student_name, "
        "c.class AS class_name, "
        "sec.section AS section_name, "
        "egcbe.exam AS exam_name, "
        "sub.name AS subject_name, "
        "sub.code AS subject_code, "
        "egcbes.max_marks, "
        "egcbes.min_marks, "
        "eger.get_marks AS obtain_marks, "
        "eger.attendence AS attendance_status, "
        "eger.note, "
        "egbs.roll_no AS exam_roll_no, "
        "egbs.rank AS exam_rank "
        "FROM exam_group_class_batch_exam_students egbs "
        "INNER JOIN student_session ss ON ss.id = egbs.student_session_id "
        "INNER JOIN students st ON st.id = ss.student_id "
        "INNER JOIN classes c ON c.id = ss.class_id "
        "LEFT JOIN sections sec ON sec.id = ss.section_id "
        "INNER JOIN exam_group_class_batch_exams egcbe "
        "ON egcbe.id = egbs.exam_group_class_batch_exam_id "
        "INNER JOIN exam_group_class_batch_exam_subjects egcbes "
        "ON egcbes.exam_group_class_batch_exams_id = egcbe.id "
        "INNER JOIN subjects sub ON sub.id = egcbes.subject_id "
        "LEFT JOIN exam_group_exam_results eger "
        "ON eger.exam_group_class_batch_exam_student_id = egbs.id "
        "AND eger.exam_group_class_batch_exam_subject_id = egcbes.id "
        f"WHERE st.is_active = 'yes' AND {where} "
        "ORDER BY egcbe.exam, st.firstname, sub.name"
    )


def build_legacy_exam_results_by_grade_sql(grade_no: str) -> str:
    """Older exam_results + exam_schedules path (Examresult_model legacy)."""
    where = _grade_where(grade_no)
    return (
        "SELECT CONCAT_WS(' ', s.firstname, s.lastname) AS student_name, "
        "c.class AS class_name, "
        "e.name AS exam_name, "
        "sub.name AS subject_name, "
        "sub.code AS subject_code, "
        "es.full_marks AS max_marks, "
        "es.passing_marks AS min_marks, "
        "er.get_marks AS obtain_marks, "
        "er.attendence AS attendance_status, "
        "er.note, "
        "es.date_of_exam "
        "FROM exam_results er "
        "INNER JOIN exam_schedules es ON es.id = er.exam_schedule_id "
        "INNER JOIN exams e ON e.id = es.exam_id "
        "INNER JOIN teacher_subjects ts ON ts.id = es.teacher_subject_id "
        "INNER JOIN class_sections cs ON cs.id = ts.class_section_id "
        "INNER JOIN classes c ON c.id = cs.class_id "
        "INNER JOIN subjects sub ON sub.id = ts.subject_id "
        "INNER JOIN students s ON s.id = er.student_id "
        f"WHERE {where} "
        "ORDER BY e.name, s.firstname, sub.name"
    )


def build_online_exam_results_by_grade_sql(grade_no: str) -> str:
    where = _grade_where(grade_no)
    return (
        "SELECT CONCAT_WS(' ', s.firstname, s.lastname) AS student_name, "
        "c.class AS class_name, "
        "oe.exam AS exam_name, "
        "oer.marks AS obtain_marks, "
        "oer.total_marks AS max_marks, "
        "oer.percentage "
        "FROM onlineexam_results oer "
        "INNER JOIN onlineexam oe ON oe.id = oer.exam_id "
        "INNER JOIN students s ON s.id = oer.student_id "
        "INNER JOIN student_session ss ON ss.student_id = s.id "
        "INNER JOIN classes c ON c.id = ss.class_id "
        f"WHERE {where} "
        "ORDER BY oer.percentage DESC"
    )


def is_exam_group_list_question(question: str) -> bool:
    """List exam groups (exam_groups table) — not onlineexam quizzes."""
    q = (question or "").lower()
    if "exam group" not in q and "exam groups" not in q:
        return False
    return bool(
        re.search(r"\b(?:show|list|display|all|active|available|give)\b", q)
        or q.strip().endswith("exam groups.")
        or q.strip().endswith("exam groups")
    )


def build_exam_groups_list_sql(*, active_only: bool = True) -> str:
    where = "WHERE eg.is_active = 1" if active_only else ""
    return (
        "SELECT eg.id AS exam_group_id, eg.name AS exam_group_name, "
        "eg.exam_type, eg.description, "
        "CASE WHEN eg.is_active = 1 THEN 'Yes' ELSE 'No' END AS is_active, "
        "(SELECT COUNT(*) FROM exam_group_class_batch_exams e "
        " WHERE e.exam_group_id = eg.id) AS exam_count "
        "FROM exam_groups eg "
        f"{where} "
        "ORDER BY eg.name ASC"
    )


def resolve_exam_groups_sql(
    db: "DBService",
    question: str,
) -> Optional[str]:
    if not is_exam_group_list_question(question):
        return None
    if not db.table_exists("exam_groups"):
        return None
    ql = (question or "").lower()
    active_only = "active" in ql or "available" in ql
    if re.search(r"\b(?:all|every)\b", ql) and "inactive" not in ql:
        active_only = "active" in ql or "available" in ql
    return build_exam_groups_list_sql(active_only=active_only)


def is_grade_distribution_question(question: str) -> bool:
    """Overall letter-grade breakdown for a class exam — not raw mark sheets."""
    q = (question or "").lower()
    if is_exam_group_list_question(question):
        return False
    if not re.search(r"\b(?:distribution|breakdown|spread)\b", q):
        if "overall grade" not in q and "grade distribution" not in q:
            return False
    if "exam" not in q and "final" not in q and not parse_grade_from_question(question):
        return False
    return True


def _parse_exam_label_filter(question: str) -> Optional[str]:
    q = (question or "").lower()
    for label in (
        "final exam",
        "final",
        "mid term",
        "midterm",
        "mid-term",
        "half yearly",
        "half-yearly",
        "annual",
        "unit test",
        "preliminary",
    ):
        if label in q:
            return label.replace("-", " ").split()[0] if label != "final exam" else "final"
    m = re.search(
        r"\b(?:for|of)\s+(?:grade\s+\d+\s+)?([A-Za-z][A-Za-z0-9\s\-]{2,40}?)\s+exam\b",
        question or "",
        re.IGNORECASE,
    )
    if m:
        return " ".join(m.group(1).split()).strip()
    return None


def _grade_letter_for_percentage(
    percentage: float,
    grade_rows: list[tuple],
) -> str:
    for _gid, name, mark_from, mark_upto, _exam_type in grade_rows:
        try:
            lo = float(mark_upto)
            hi = float(mark_from)
        except (TypeError, ValueError):
            continue
        if lo <= percentage <= hi:
            return str(name)
    return "Ungraded"


def resolve_grade_distribution_report(
    db: "DBService",
    question: str,
) -> Optional[ExamAnalyticsReport]:
    if not is_grade_distribution_question(question):
        return None
    if not db.table_exists("exam_group_exam_results"):
        return None

    grade = parse_grade_from_question(question)
    if not grade:
        return None

    exam_filter = _parse_exam_label_filter(question) or "final"
    esc_exam = exam_filter.replace("'", "''")
    where_grade = _grade_where(grade)
    student_sql = (
        "SELECT eg.exam_type, "
        "SUM(COALESCE(CAST(eger.get_marks AS DECIMAL(10,2)), 0)) AS total_obtained, "
        "SUM(COALESCE(CAST(egcbes.max_marks AS DECIMAL(10,2)), 0)) AS total_max "
        "FROM exam_group_class_batch_exam_students egbs "
        "INNER JOIN student_session ss ON ss.id = egbs.student_session_id "
        "INNER JOIN students st ON st.id = ss.student_id "
        "INNER JOIN classes c ON c.id = ss.class_id "
        "INNER JOIN exam_group_class_batch_exams egcbe "
        "ON egcbe.id = egbs.exam_group_class_batch_exam_id "
        "INNER JOIN exam_groups eg ON eg.id = egcbe.exam_group_id "
        "INNER JOIN exam_group_class_batch_exam_subjects egcbes "
        "ON egcbes.exam_group_class_batch_exams_id = egcbe.id "
        "LEFT JOIN exam_group_exam_results eger "
        "ON eger.exam_group_class_batch_exam_student_id = egbs.id "
        "AND eger.exam_group_class_batch_exam_subject_id = egcbes.id "
        f"WHERE st.is_active = 'yes' AND {where_grade} "
        f"AND LOWER(egcbe.exam) LIKE LOWER('%{esc_exam}%') "
        "GROUP BY egbs.id, eg.exam_type "
        "HAVING total_max > 0"
    )
    student_rows, _, err = db.execute(student_sql, max_rows=5000)
    if err:
        return None
    if not student_rows:
        return ExamAnalyticsReport(
            message=(
                f"No exam results found for **Grade {grade}** "
                f"matching **{exam_filter}** exam — cannot compute grade distribution."
            ),
            sql_note=f"-- grade distribution: grade={grade}, exam~{exam_filter!r} (0 students)",
        )

    exam_type = str(student_rows[0][0] or "school_grade_system")
    grade_scale_sql = (
        "SELECT id, name, mark_from, mark_upto, exam_type FROM grades "
        f"WHERE exam_type = '{_esc(exam_type)}' "
        "ORDER BY mark_upto ASC"
    )
    grade_scale, _, _ = db.execute(grade_scale_sql, max_rows=50)
    if not grade_scale:
        grade_scale, _, _ = db.execute(
            "SELECT id, name, mark_from, mark_upto, exam_type FROM grades ORDER BY mark_upto ASC",
            max_rows=100,
        )

    buckets: dict[str, int] = {}
    for row in student_rows:
        _etype, obtained, maximum = row
        try:
            pct = round(100.0 * float(obtained) / float(maximum), 2)
        except (TypeError, ValueError, ZeroDivisionError):
            continue
        letter = _grade_letter_for_percentage(pct, grade_scale or [])
        buckets[letter] = buckets.get(letter, 0) + 1

    if not buckets:
        return ExamAnalyticsReport(
            message=(
                f"Exam marks exist for **Grade {grade}** ({exam_filter} exam), "
                "but percentages could not be mapped to the configured grade scale."
            ),
            sql_note=f"-- grade distribution: grade={grade}, exam~{exam_filter!r} (unmapped)",
        )

    total_students = sum(buckets.values())
    columns = ["grade_letter", "student_count", "percentage_of_class"]
    rows = []
    for letter in sorted(buckets.keys(), key=lambda x: (x == "Ungraded", x)):
        count = buckets[letter]
        pct_class = round(100.0 * count / total_students, 1)
        rows.append([letter, count, pct_class])

    lead = (
        f"**Grade distribution** for **Grade {grade}** — **{exam_filter}** exam "
        f"({total_students} students with marks):"
    )
    return ExamAnalyticsReport(
        message=lead,
        columns=columns,
        rows=rows,
        sql_note=f"-- grade distribution: grade={grade}, exam~{exam_filter!r}, type={exam_type}",
    )


def resolve_exam_results_sql(
    db: "DBService",
    question: str,
    grade_no: Optional[str] = None,
) -> Optional[str]:
    """
    Pick the best exam-results SELECT for this school schema.
    Returns None if the question is not a grade-scoped exam-results request.
    """
    if is_grade_distribution_question(question) or is_exam_group_list_question(question):
        return None

    grade = grade_no or parse_grade_from_question(question)
    if not grade or not is_exam_results_question(question):
        return None

    q = (question or "").lower()
    if "online" in q and db.table_exists("onlineexam_results"):
        return build_online_exam_results_by_grade_sql(grade)

    if db.table_exists("exam_group_exam_results"):
        return build_exam_group_results_by_grade_sql(grade)

    if db.table_exists("exam_results"):
        return build_legacy_exam_results_by_grade_sql(grade)

    return None
