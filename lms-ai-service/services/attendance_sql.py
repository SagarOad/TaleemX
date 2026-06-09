"""
Student attendance SQL — month / date scoped reports (Student Attendance module).
"""

from __future__ import annotations

import re
from dataclasses import dataclass, field
from typing import TYPE_CHECKING, Optional

if TYPE_CHECKING:
    from services.db_service import DBService

from services.date_filters import (
    fetch_school_date_format,
    month_english_name,
    parse_month_from_question,
    parse_year_from_question,
    sql_date_filter,
)
from services.exam_results_sql import parse_grade_from_question
from services.question_normalize import routing_question


@dataclass
class AttendanceReport:
    message: str = ""
    columns: list[str] = field(default_factory=list)
    rows: list = field(default_factory=list)
    sql_note: str = ""


def is_student_attendance_report_question(question: str) -> bool:
    rq = routing_question(question).lower()
    if not re.search(r"\battend", rq):
        return False
    if "staff" in rq or "employee" in rq or "faculty" in rq:
        return False
    if "student" in rq:
        return True
    if re.search(r"\battend", rq) and "teacher" not in rq and parse_month_from_question(question) is not None:
        return True
    return False


def _grade_where(grade: str) -> str:
    g = grade.replace("'", "''")
    return f"(c.class = '{g}' OR c.class = 'Grade {g}' OR c.class = 'Class {g}')"


def resolve_student_attendance_report(
    db: "DBService",
    question: str,
) -> Optional[AttendanceReport]:
    if not is_student_attendance_report_question(question):
        return None
    if not db.table_exists("student_attendences"):
        return AttendanceReport(
            message="Student attendance module is not available.",
            sql_note="-- student_attendences",
        )

    rq = routing_question(question).lower()
    school_fmt = fetch_school_date_format(db)
    grade = parse_grade_from_question(question)
    month_num = parse_month_from_question(question)
    year = parse_year_from_question(question)
    df = sql_date_filter("sa.date", question, school_fmt)

    if df.kind == "invalid":
        return AttendanceReport(message=df.message, sql_note="-- invalid date")

    where_parts = ["1=1"]
    label = "student attendance"

    if df.kind != "none" and df.sql_on_column:
        where_parts.append(df.sql_on_column.replace("sa.date", "sa.date"))
        if df.parsed:
            label = f"student attendance on **{df.parsed.strftime('%B %d, %Y')}**"
    elif month_num is not None:
        if month_num == 0:
            where_parts.append(
                "MONTH(sa.date) = MONTH(CURDATE()) AND YEAR(sa.date) = YEAR(CURDATE())"
            )
            label = "student attendance **this month**"
        else:
            yr = year if year else "YEAR(CURDATE())"
            if isinstance(yr, int):
                where_parts.append(f"MONTH(sa.date) = {month_num} AND YEAR(sa.date) = {yr}")
            else:
                where_parts.append(
                    f"MONTH(sa.date) = {month_num} AND YEAR(sa.date) = YEAR(CURDATE())"
                )
            label = f"student attendance for **{month_english_name(month_num)}**"
            if year:
                label = f"student attendance for **{month_english_name(month_num)} {year}**"
    elif re.search(r"\bthis month\b|\bcurrent month\b", rq):
        where_parts.append(
            "MONTH(sa.date) = MONTH(CURDATE()) AND YEAR(sa.date) = YEAR(CURDATE())"
        )
        label = "student attendance **this month**"

    if grade:
        where_parts.append(_grade_where(grade))
        label += f" — **Grade {grade}**"

    summary = bool(
        re.search(r"\bsummary\b", rq)
        or re.search(r"\bper student\b|\beach student\b", rq)
    )

    if summary:
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
        sql = (
            "SELECT CONCAT_WS(' ', s.firstname, s.middlename, s.lastname) AS student_name, "
            "c.class AS class_name, sec.section AS section_name, "
            f"SUM({present}) AS present_days, SUM({absent}) AS absent_days, "
            "COUNT(*) AS days_marked, "
            f"ROUND(100.0 * SUM({present}) / NULLIF(COUNT(*), 0), 2) AS attendance_percent "
            "FROM student_attendences sa "
            "JOIN student_session ss ON ss.id = sa.student_session_id "
            "JOIN students s ON s.id = ss.student_id "
            "JOIN classes c ON c.id = ss.class_id "
            "LEFT JOIN sections sec ON sec.id = ss.section_id "
            "LEFT JOIN attendence_type at ON at.id = sa.attendence_type_id "
            f"WHERE {' AND '.join(where_parts)} "
            "GROUP BY s.id, student_name, c.class, sec.section "
            "ORDER BY attendance_percent DESC, student_name "
            "LIMIT 200"
        )
    else:
        sql = (
            "SELECT sa.date, CONCAT_WS(' ', s.firstname, s.middlename, s.lastname) AS student_name, "
            "c.class AS class_name, sec.section AS section_name, "
            "COALESCE(at.long_lang_name, at.type) AS attendance_status, "
            "sa.in_time, sa.out_time, sa.remark "
            "FROM student_attendences sa "
            "JOIN student_session ss ON ss.id = sa.student_session_id "
            "JOIN students s ON s.id = ss.student_id "
            "JOIN classes c ON c.id = ss.class_id "
            "LEFT JOIN sections sec ON sec.id = ss.section_id "
            "LEFT JOIN attendence_type at ON at.id = sa.attendence_type_id "
            f"WHERE {' AND '.join(where_parts)} "
            "ORDER BY sa.date DESC, student_name "
            "LIMIT 200"
        )

    rows, cols, err = db.execute(sql, max_rows=200)
    if err:
        return AttendanceReport(
            message=f"Could not load student attendance: {err}",
            sql_note=sql,
        )
    if not rows:
        return AttendanceReport(
            message=f"No **{label}** records found.",
            sql_note=sql,
        )
    return AttendanceReport(
        message=f"**{len(rows)}** {label} record(s):",
        columns=cols,
        rows=rows,
        sql_note=sql,
    )
