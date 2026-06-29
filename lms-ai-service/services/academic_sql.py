"""
Academic module SQL — timetable, class teacher, sections, behaviour incidents,
class performance and guardian-data completeness.

Schema reference (TaleemX):
  subject_timetable     (id, class_id, section_id, day, start_time, time_from,
                         time_to, room_no, staff_id, subject_group_subject_id, session_id)
  subject_group_subjects(id, subject_id, ...) → subjects (id, name, code, type)
  class_teacher         (id, class_id, section_id, staff_id, session_id)
  class_sections        (id, class_id, section_id)
  classes (id, class) · sections (id, section) · staff (id, name, surname, ...)
  student_incidents     (id, student_id, incident_id, assign_by, session_id)
  student_behaviour     (id, title, point, description)  ← incident_id references this
  exam_group_* tables for class performance.
"""

from __future__ import annotations

import re
from typing import TYPE_CHECKING, Optional

if TYPE_CHECKING:
    from services.db_service import DBService


_DAY_ORDER = (
    "FIELD(LOWER(st.day),'monday','tuesday','wednesday','thursday',"
    "'friday','saturday','sunday')"
)


def _esc(s: str) -> str:
    return (s or "").replace("'", "''")


def _grade_where(grade: str, col: str = "c.class") -> str:
    g = _esc(grade)
    return f"({col} = '{g}' OR {col} = 'Grade {g}' OR {col} = 'Class {g}')"


def _parse_grade(question: str) -> Optional[str]:
    m = re.search(r"\b(?:grade|class)\s*(\d+)\b", (question or "").lower())
    return m.group(1) if m else None


def _parse_grade_section(question: str) -> tuple[Optional[str], Optional[str]]:
    """Grade 6-B / Grade 1-A / class 5 B → (grade, section)."""
    q = question or ""
    m = re.search(
        r"\b(?:grade|class)\s*(\d+)\s*[-/\s]\s*([A-Za-z])\b", q, re.IGNORECASE
    )
    if m:
        return m.group(1), m.group(2).upper()
    grade = _parse_grade(question)
    sec = re.search(r"\bsection\s+([A-Za-z0-9]+)\b", q, re.IGNORECASE)
    return grade, (sec.group(1).upper() if sec else None)


def _extract_teacher_name(question: str) -> Optional[str]:
    q = question or ""
    m = re.search(
        r"(?:for|of)\s+(?:mr\.?|mrs\.?|ms\.?|miss|dr\.?|sir|teacher\s+)?"
        r"([A-Za-z][A-Za-z\s\-'\.]{2,60})\s*[.?!]*\s*$",
        q,
        re.IGNORECASE,
    )
    if not m:
        return None
    name = " ".join(m.group(1).split()).strip(" .?!,")
    name = re.sub(r"^(?:mr|mrs|ms|miss|dr|sir)\.?\s+", "", name, flags=re.IGNORECASE)
    low = name.lower()
    # Reject grade/section/room phrases — those are not teacher names.
    if re.search(r"\d", name):
        return None
    if re.search(r"\b(?:grade|class|section|room|today|week|this)\b", low):
        return None
    if len(name) < 3 or low in ("the school", "class", "grade"):
        return None
    return name


def timetable_by_teacher_sql(name: str) -> str:
    n = _esc(name)
    return (
        "SELECT staff.name AS teacher_name, staff.surname AS teacher_surname, "
        "st.day, st.start_time, st.time_from, st.time_to, st.room_no, "
        "c.class AS class_name, sec.section AS section_name, sub.name AS subject_name "
        "FROM subject_timetable st "
        "JOIN staff ON staff.id = st.staff_id "
        "JOIN subject_group_subjects sgs ON sgs.id = st.subject_group_subject_id "
        "JOIN subjects sub ON sub.id = sgs.subject_id "
        "LEFT JOIN classes c ON c.id = st.class_id "
        "LEFT JOIN sections sec ON sec.id = st.section_id "
        f"WHERE LOWER(CONCAT_WS(' ', staff.name, staff.surname)) LIKE LOWER('%{n}%') "
        f"ORDER BY {_DAY_ORDER}, st.start_time "
        "LIMIT 100"
    )


def class_teacher_sql(grade: str, section: Optional[str]) -> str:
    where = _grade_where(grade)
    if section:
        where += f" AND LOWER(sec.section) = LOWER('{_esc(section)}')"
    return (
        "SELECT staff.name AS teacher_name, staff.surname AS teacher_surname, "
        "staff.employee_id, staff.contact_no, staff.email, "
        "c.class AS class_name, sec.section AS section_name "
        "FROM class_teacher ct "
        "JOIN staff ON staff.id = ct.staff_id "
        "JOIN classes c ON c.id = ct.class_id "
        "JOIN sections sec ON sec.id = ct.section_id "
        f"WHERE {where} AND staff.is_active = 1 "
        "ORDER BY staff.name "
        "LIMIT 50"
    )


def sections_in_grade_sql(grade: str) -> str:
    where = _grade_where(grade)
    return (
        "SELECT c.class AS class_name, sec.section AS section_name "
        "FROM class_sections cs "
        "JOIN classes c ON c.id = cs.class_id "
        "JOIN sections sec ON sec.id = cs.section_id "
        f"WHERE {where} "
        "ORDER BY sec.section "
        "LIMIT 50"
    )


def incident_students_sql(incident_id: str) -> str:
    iid = _esc(incident_id)
    return (
        "SELECT DISTINCT s.firstname, s.middlename, s.lastname, s.admission_no, "
        "c.class AS class_name, sec.section AS section_name, "
        "sb.title AS incident_title, sb.point AS incident_point "
        "FROM student_incidents si "
        "JOIN students s ON s.id = si.student_id "
        "JOIN student_behaviour sb ON sb.id = si.incident_id "
        "LEFT JOIN student_session ss ON ss.id = "
        "(SELECT MAX(ss2.id) FROM student_session ss2 WHERE ss2.student_id = s.id) "
        "LEFT JOIN classes c ON c.id = ss.class_id "
        "LEFT JOIN sections sec ON sec.id = ss.section_id "
        f"WHERE s.is_active = 'yes' AND si.incident_id = {iid} "
        "ORDER BY s.firstname "
        "LIMIT 100"
    )


def incomplete_guardian_sql() -> str:
    return (
        "SELECT s.admission_no, s.firstname, s.middlename, s.lastname, "
        "c.class AS class_name, sec.section AS section_name, "
        "s.guardian_name, s.guardian_relation, s.guardian_phone, s.guardian_email "
        "FROM students s "
        "LEFT JOIN student_session ss ON ss.id = "
        "(SELECT MAX(ss2.id) FROM student_session ss2 WHERE ss2.student_id = s.id) "
        "LEFT JOIN classes c ON c.id = ss.class_id "
        "LEFT JOIN sections sec ON sec.id = ss.section_id "
        "WHERE s.is_active = 'yes' AND ("
        "s.guardian_name IS NULL OR TRIM(s.guardian_name) = '' "
        "OR s.guardian_phone IS NULL OR TRIM(s.guardian_phone) = '' "
        "OR s.guardian_relation IS NULL OR TRIM(s.guardian_relation) = '') "
        "ORDER BY c.class, s.firstname "
        "LIMIT 100"
    )


def class_performance_sql(grade: str, section: Optional[str]) -> str:
    where = _grade_where(grade)
    if section:
        where += f" AND LOWER(sec.section) = LOWER('{_esc(section)}')"
    return (
        "SELECT sub.name AS subject_name, "
        "COUNT(DISTINCT egbs.id) AS students_assessed, "
        "ROUND(AVG(CAST(eger.get_marks AS DECIMAL(10,2))), 2) AS avg_marks, "
        "MAX(egcbes.max_marks) AS max_marks, "
        "ROUND(AVG(CASE WHEN egcbes.max_marks > 0 "
        "THEN 100 * CAST(eger.get_marks AS DECIMAL(10,2)) / egcbes.max_marks END), 1) "
        "AS avg_percentage "
        "FROM exam_group_class_batch_exam_students egbs "
        "JOIN student_session ss ON ss.id = egbs.student_session_id "
        "JOIN classes c ON c.id = ss.class_id "
        "JOIN sections sec ON sec.id = ss.section_id "
        "JOIN exam_group_class_batch_exams egcbe "
        "ON egcbe.id = egbs.exam_group_class_batch_exam_id "
        "JOIN exam_group_class_batch_exam_subjects egcbes "
        "ON egcbes.exam_group_class_batch_exams_id = egcbe.id "
        "JOIN subjects sub ON sub.id = egcbes.subject_id "
        "LEFT JOIN exam_group_exam_results eger "
        "ON eger.exam_group_class_batch_exam_student_id = egbs.id "
        "AND eger.exam_group_class_batch_exam_subject_id = egcbes.id "
        f"WHERE {where} "
        "GROUP BY sub.id, sub.name "
        "ORDER BY avg_percentage DESC "
        "LIMIT 50"
    )


def try_academic_sql(db: "DBService", question: str) -> Optional[str]:
    q = (question or "").lower().strip()
    if not q:
        return None

    # Behaviour / incident assignment — "students assigned to incident ID 6".
    if "incident" in q:
        m = re.search(r"incident\s*(?:id|number|no\.?|#)?\s*(\d+)", q)
        if m:
            return incident_students_sql(m.group(1))

    # Weekly / daily timetable for a named teacher.
    if "timetable" in q or "time table" in q:
        name = _extract_teacher_name(question)
        if name:
            return timetable_by_teacher_sql(name)

    # Class teacher assignment for a grade-section.
    if "class teacher" in q or ("class" in q and "teacher" in q and "assigned" in q):
        grade, section = _parse_grade_section(question)
        if grade:
            return class_teacher_sql(grade, section)

    # Students with incomplete guardian contact info.
    if "guardian" in q and re.search(
        r"\b(?:incomplete|missing|without|no\s+guardian|blank|empty)\b", q
    ):
        return incomplete_guardian_sql()

    # Sections within a grade — only when the question is genuinely about listing
    # sections (not "students ... section A" or a class-teacher lookup).
    if (
        "student" not in q
        and "teacher" not in q
        and "timetable" not in q
        and (
            re.search(r"\bsections?\s+(?:in|of|for)\b", q)
            or re.search(r"\b(?:all|list|how\s+many)\s+sections?\b", q)
        )
    ):
        grade = _parse_grade(question)
        if grade:
            return sections_in_grade_sql(grade)

    # Class performance report for a grade-section (modern exam group schema).
    if "performance" in q:
        grade, section = _parse_grade_section(question)
        if grade and db.table_exists("exam_group_exam_results"):
            return class_performance_sql(grade, section)

    return None
