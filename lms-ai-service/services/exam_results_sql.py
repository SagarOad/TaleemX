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
from typing import TYPE_CHECKING, Optional

if TYPE_CHECKING:
    from services.db_service import DBService

_GRADE_RE = re.compile(r"\b(?:grade|class)\s*(\d+)\b", re.IGNORECASE)
_RESULTS_RE = re.compile(
    r"\b(?:result|results|marksheet|mark\s*sheet|score|scores|marks)\b",
    re.IGNORECASE,
)
_SCHEDULE_RE = re.compile(
    r"\b(?:schedule|scheduled|timetable|upcoming|when\s+is|when\s+are)\b",
    re.IGNORECASE,
)


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


def resolve_exam_results_sql(
    db: "DBService",
    question: str,
    grade_no: Optional[str] = None,
) -> Optional[str]:
    """
    Pick the best exam-results SELECT for this school schema.
    Returns None if the question is not a grade-scoped exam-results request.
    """
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
