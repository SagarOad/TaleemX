"""
HR module SQL — staff attendance, payroll, leave, disabled staff, ratings, holiday types.
Mirrors TaleemX HR / Payroll / Leave / Holiday admin screens.
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
    parse_exact_date_from_question,
    parse_month_from_question,
    parse_year_from_question,
    sql_date_filter,
)
from services.exam_results_sql import parse_grade_from_question
from services.question_normalize import routing_question


def _esc(s: str) -> str:
    return (s or "").replace("'", "''")


def _grade_where(grade: str) -> str:
    g = _esc(grade)
    return f"(c.class = '{g}' OR c.class = 'Grade {g}' OR c.class = 'Class {g}')"


@dataclass
class HrReport:
    message: str = ""
    columns: list[str] = field(default_factory=list)
    rows: list = field(default_factory=list)
    sql_note: str = ""


def is_holiday_types_question(question: str) -> bool:
    rq = routing_question(question).lower()
    if "holiday type" in rq or "holiday types" in rq:
        return True
    if "holiday" in rq and re.search(r"\b(?:type|types|configured)\b", rq):
        if "language" not in rq:
            return True
    return False


def is_disabled_staff_question(question: str) -> bool:
    rq = routing_question(question).lower()
    if "student" in rq:
        return False
    return bool(
        re.search(r"\bdisabled\s+staff\b", rq)
        or re.search(r"\bstaff\b.*\bdisabled\b", rq)
        or re.search(r"\binactive\s+staff\b", rq)
        or re.search(r"\bdeactivated\s+staff\b", rq)
    )


def is_staff_attendance_question(question: str) -> bool:
    rq = routing_question(question).lower()
    if "student" in rq:
        return False
    return bool(
        re.search(r"\bstaff\b.*\battend", rq)
        or re.search(r"\battend.*\bstaff\b", rq)
        or re.search(r"\bemployee\b.*\battend", rq)
        or re.search(r"\bfaculty\b.*\battend", rq)
        or (re.search(r"\battend", rq) and re.search(r"\bapril\b|\bjune\b|\btoday\b|\b20\d{2}\b", rq)
            and "student" not in rq)
    )


def is_payroll_question(question: str) -> bool:
    rq = routing_question(question).lower()
    return bool(re.search(r"\bpayroll\b|\bpayslip\b|\bsalary\b|\bsalaries\b", rq))


def is_staff_leave_question(question: str) -> bool:
    rq = routing_question(question).lower()
    if "student" in rq and "staff" not in rq and "teacher" not in rq:
        return False
    return bool(
        re.search(r"\bleaves?\b", rq)
        and re.search(r"\b(?:staff|teacher|employee|applied|request|approval)\b", rq)
    )


def is_teacher_ratings_question(question: str) -> bool:
    rq = routing_question(question).lower()
    return bool(
        re.search(r"\b(?:rating|ratings|review|reviews)\b", rq)
        and re.search(r"\bteacher", rq)
    )


def _teacher_role_filter(alias: str = "r") -> str:
    return (
        f"(LOWER({alias}.slug) = 'teacher' OR LOWER({alias}.name) LIKE '%teacher%')"
    )


def resolve_hr_report(db: "DBService", question: str) -> Optional[HrReport]:
    rq = routing_question(question).lower()
    school_fmt = fetch_school_date_format(db)

    if is_holiday_types_question(question):
        if not db.table_exists("holiday_type"):
            return HrReport(message="Holiday type table is not available.", sql_note="-- holiday_type")
        sql = (
            "SELECT id, type AS holiday_type, is_default "
            "FROM holiday_type ORDER BY type ASC"
        )
        rows, cols, err = db.execute(sql, max_rows=50)
        if err:
            return HrReport(message=f"Could not load holiday types: {err}", sql_note=sql)
        if not rows:
            return HrReport(message="No **holiday types** are configured yet.", sql_note=sql)
        return HrReport(
            message=f"**{len(rows)}** configured **holiday type(s)**:",
            columns=cols,
            rows=rows,
            sql_note=sql,
        )

    if is_disabled_staff_question(question):
        sql = (
            "SELECT st.employee_id, CONCAT_WS(' ', st.name, st.surname) AS staff_name, "
            "r.name AS role, d.department_name, st.disable_at, st.email, st.contact_no "
            "FROM staff st "
            "LEFT JOIN staff_roles sr ON sr.staff_id = st.id "
            "LEFT JOIN roles r ON r.id = sr.role_id "
            "LEFT JOIN department d ON d.id = st.department "
            "WHERE st.is_active = 0 "
            "ORDER BY st.disable_at DESC, st.name LIMIT 100"
        )
        rows, cols, err = db.execute(sql, max_rows=100)
        if err:
            return HrReport(message=f"Could not load disabled staff: {err}", sql_note=sql)
        if not rows:
            return HrReport(message="No **disabled staff** records found.", sql_note=sql)
        return HrReport(
            message=f"**{len(rows)}** disabled staff member(s):",
            columns=cols,
            rows=rows,
            sql_note=sql,
        )

    if is_staff_attendance_question(question):
        if not db.table_exists("staff_attendance"):
            return HrReport(message="Staff attendance module is not available.", sql_note="-- staff_attendance")
        df = sql_date_filter("sa.date", question, school_fmt)
        if df.kind == "invalid":
            return HrReport(message=df.message, sql_note="-- staff attendance: invalid date")
        date_sql = df.sql_on_column if df.kind != "none" else ""
        if not date_sql:
            month = parse_month_from_question(question)
            if month == 0:
                date_sql = "MONTH(sa.date) = MONTH(CURDATE()) AND YEAR(sa.date) = YEAR(CURDATE())"
            elif month:
                year = parse_year_from_question(question) or "YEAR(CURDATE())"
                if isinstance(year, int):
                    date_sql = f"MONTH(sa.date) = {month} AND YEAR(sa.date) = {year}"
                else:
                    date_sql = f"MONTH(sa.date) = {month} AND YEAR(sa.date) = YEAR(CURDATE())"
        where = f"WHERE {date_sql}" if date_sql else "WHERE 1=1"
        sql = (
            "SELECT sa.date, CONCAT_WS(' ', st.name, st.surname) AS staff_name, "
            "COALESCE(sat.long_lang_name, sat.type) AS attendance_status, "
            "sa.in_time, sa.out_time, sa.remark "
            "FROM staff_attendance sa "
            "JOIN staff st ON st.id = sa.staff_id "
            "JOIN staff_attendance_type sat ON sat.id = sa.staff_attendance_type_id "
            f"{where} "
            "ORDER BY sa.date DESC, staff_name LIMIT 200"
        )
        rows, cols, err = db.execute(sql, max_rows=200)
        if err:
            return HrReport(message=f"Could not load staff attendance: {err}", sql_note=sql)
        label = "staff attendance"
        if df.parsed:
            label = f"staff attendance on **{df.parsed.strftime('%B %d, %Y')}**"
        elif parse_month_from_question(question):
            m = parse_month_from_question(question)
            yr = parse_year_from_question(question) or ""
            label = f"staff attendance for **{month_english_name(m)} {yr}**".strip()
        if not rows:
            return HrReport(message=f"No **{label}** records found.", sql_note=sql)
        return HrReport(
            message=f"**{len(rows)}** {label} record(s):",
            columns=cols,
            rows=rows,
            sql_note=sql,
        )

    if is_payroll_question(question):
        if not db.table_exists("staff_payslip"):
            return HrReport(message="Payroll module is not available.", sql_note="-- staff_payslip")
        month_num = parse_month_from_question(question)
        year = parse_year_from_question(question)
        if month_num and month_num > 0:
            month_name = month_english_name(month_num)
            year_sql = str(year) if year else "YEAR(CURDATE())"
            where = f"sp.month = '{_esc(month_name)}' AND sp.year = '{year_sql}'"
            label = f"payroll for **{month_name} {year or 'current year'}**"
        else:
            where = (
                "MONTH(sp.payment_date) = MONTH(CURDATE()) "
                "AND YEAR(sp.payment_date) = YEAR(CURDATE())"
            )
            label = "payroll **this month**"
        sql = (
            "SELECT st.employee_id, CONCAT_WS(' ', st.name, st.surname) AS staff_name, "
            "r.name AS role, sp.month, sp.year, sp.basic, sp.total_allowance, "
            "sp.total_deduction, sp.net_salary, sp.payment_date, sp.status "
            "FROM staff_payslip sp "
            "JOIN staff st ON st.id = sp.staff_id "
            "LEFT JOIN staff_roles sr ON sr.staff_id = st.id "
            "LEFT JOIN roles r ON r.id = sr.role_id "
            f"WHERE {where} "
            "ORDER BY sp.payment_date DESC, staff_name LIMIT 200"
        )
        rows, cols, err = db.execute(sql, max_rows=200)
        if err:
            return HrReport(message=f"Could not load payroll: {err}", sql_note=sql)
        if not rows:
            return HrReport(message=f"No **{label}** records found.", sql_note=sql)
        return HrReport(
            message=f"**{len(rows)}** {label} record(s):",
            columns=cols,
            rows=rows,
            sql_note=sql,
        )

    if is_staff_leave_question(question):
        if not db.table_exists("staff_leave_request"):
            return HrReport(message="Staff leave module is not available.", sql_note="-- staff_leave_request")
        month_num = parse_month_from_question(question)
        year = parse_year_from_question(question)
        teacher_only = "teacher" in rq
        parts = ["1=1"]
        if teacher_only:
            parts.append(
                "EXISTS (SELECT 1 FROM staff_roles sr2 JOIN roles r2 ON r2.id = sr2.role_id "
                f"WHERE sr2.staff_id = s.id AND {_teacher_role_filter('r2')})"
            )
        if month_num and month_num > 0:
            yr = year if year else "YEAR(CURDATE())"
            if isinstance(yr, int):
                parts.append(
                    f"MONTH(lr.leave_from) = {month_num} AND YEAR(lr.leave_from) = {yr}"
                )
            else:
                parts.append(
                    f"MONTH(lr.leave_from) = {month_num} AND YEAR(lr.leave_from) = YEAR(CURDATE())"
                )
        sql = (
            "SELECT CONCAT_WS(' ', s.name, s.surname) AS staff_name, "
            "lr.date AS applied_date, lr.leave_from, lr.leave_to, lr.leave_days, "
            "lt.type AS leave_type, lr.status, lr.employee_remark "
            "FROM staff_leave_request lr "
            "JOIN staff s ON s.id = lr.staff_id "
            "JOIN leave_types lt ON lt.id = lr.leave_type_id "
            f"WHERE {' AND '.join(parts)} "
            "ORDER BY lr.leave_from DESC LIMIT 200"
        )
        rows, cols, err = db.execute(sql, max_rows=200)
        if err:
            return HrReport(message=f"Could not load staff leave: {err}", sql_note=sql)
        label = "staff leave"
        if teacher_only:
            label = "teacher leave"
        if month_num and month_num > 0:
            label += f" in **{month_english_name(month_num)}**"
        if not rows:
            return HrReport(message=f"No **{label}** records found.", sql_note=sql)
        return HrReport(
            message=f"**{len(rows)}** {label} request(s):",
            columns=cols,
            rows=rows,
            sql_note=sql,
        )

    if is_teacher_ratings_question(question):
        if not db.table_exists("staff_rating"):
            return HrReport(message="Staff rating module is not available.", sql_note="-- staff_rating")
        grade = parse_grade_from_question(question)
        if not grade:
            return HrReport(
                message="Please specify a **grade** for teacher ratings (e.g. Grade 5).",
                sql_note="-- teacher ratings: missing grade",
            )
        gw = _grade_where(grade)
        sql = (
            "SELECT CONCAT_WS(' ', st.name, st.surname) AS teacher_name, "
            "ROUND(AVG(sr.rate), 2) AS avg_rating, COUNT(*) AS review_count, "
            "c.class AS grade, sec.section AS section_name "
            "FROM staff_rating sr "
            "JOIN staff st ON st.id = sr.staff_id AND st.is_active = 1 "
            "JOIN class_teacher ct ON ct.staff_id = st.id "
            "JOIN classes c ON c.id = ct.class_id "
            "LEFT JOIN sections sec ON sec.id = ct.section_id "
            f"WHERE sr.status = 1 AND {gw} "
            "GROUP BY st.id, teacher_name, c.class, sec.section "
            "ORDER BY avg_rating DESC, review_count DESC LIMIT 50"
        )
        rows, cols, err = db.execute(sql, max_rows=50)
        if err:
            return HrReport(message=f"Could not load teacher ratings: {err}", sql_note=sql)
        if not rows:
            return HrReport(
                message=(
                    f"No **approved teacher ratings** found for **Grade {grade}** teachers. "
                    "Ratings appear after students submit reviews and they are approved."
                ),
                sql_note=sql,
            )
        return HrReport(
            message=f"**Teacher ratings** for **Grade {grade}** teachers:",
            columns=cols,
            rows=rows,
            sql_note=sql,
        )

    return None


def is_hr_module_question(question: str) -> bool:
    """True when the question targets HR tables — not a generic staff directory."""
    return (
        is_holiday_types_question(question)
        or is_disabled_staff_question(question)
        or is_staff_attendance_question(question)
        or is_payroll_question(question)
        or is_staff_leave_question(question)
        or is_teacher_ratings_question(question)
    )
