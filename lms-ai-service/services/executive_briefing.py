"""
services/executive_briefing.py

Multi-query executive dashboard for broad school-performance questions.
Runs verified SQL across KPIs, risk, gaps, and trends — then scores health
against TaleemX LMS benchmarks (attendance, fees, admissions, operations).
"""

from __future__ import annotations

import logging
import re
from dataclasses import dataclass, field
from typing import Any

from services.insights import _jsonify

logger = logging.getLogger(__name__)

_PRESENT = (
    "CASE WHEN LOWER(COALESCE(at.type, '')) IN ('p','present','1') "
    "OR LOWER(COALESCE(at.long_lang_name, '')) LIKE '%present%' THEN 1 ELSE 0 END"
)

_SQL_KPI = (
    "SELECT 'Active students' AS metric, "
    "CAST((SELECT COUNT(*) FROM students WHERE is_active = 'yes') AS CHAR) AS value "
    "UNION ALL SELECT 'Active staff', "
    "CAST((SELECT COUNT(*) FROM staff WHERE is_active = 1) AS CHAR) "
    "UNION ALL SELECT 'Teachers', "
    "CAST((SELECT COUNT(DISTINCT sr.staff_id) FROM staff_roles sr "
    "JOIN roles r ON r.id = sr.role_id "
    "WHERE LOWER(r.slug) = 'teacher' OR LOWER(r.name) LIKE '%teacher%') AS CHAR) "
    f"UNION ALL SELECT 'Student attendance % this month', "
    f"CAST(COALESCE(ROUND(100.0 * SUM({_PRESENT}) / NULLIF(COUNT(*), 0), 1), 0) AS CHAR) "
    "FROM student_attendences sa "
    "LEFT JOIN attendence_type at ON at.id = sa.attendence_type_id "
    "WHERE MONTH(sa.date) = MONTH(CURDATE()) AND YEAR(sa.date) = YEAR(CURDATE()) "
    "UNION ALL SELECT 'Students absent today', "
    "CAST((SELECT COUNT(*) FROM student_attendences sa2 "
    "JOIN attendence_type at2 ON at2.id = sa2.attendence_type_id "
    "WHERE sa2.date = CURDATE() AND LOWER(at2.type) IN ('a','absent')) AS CHAR) "
    "UNION ALL SELECT 'Fee collected this month', "
    "CAST(COALESCE((SELECT SUM(CAST(jt.amt AS DECIMAL(10,2))) "
    "FROM student_fees_deposite sfd, "
    "JSON_TABLE(sfd.amount_detail, '$.*' COLUMNS (amt VARCHAR(50) PATH '$.amount', "
    "payment_date DATE PATH '$.date')) AS jt "
    "WHERE MONTH(jt.payment_date) = MONTH(CURDATE()) "
    "AND YEAR(jt.payment_date) = YEAR(CURDATE())), 0) AS CHAR) "
    "UNION ALL SELECT 'Outstanding fee balance', "
    "CAST(GREATEST(COALESCE((SELECT SUM(sfm.amount) FROM student_fees_master sfm), 0) - "
    "COALESCE((SELECT SUM(CAST(jt.amt AS DECIMAL(10,2))) FROM student_fees_deposite sfd, "
    "JSON_TABLE(sfd.amount_detail, '$.*' COLUMNS (amt VARCHAR(50) PATH '$.amount')) AS jt), 0), 0) AS CHAR) "
    "UNION ALL SELECT 'New admissions this month', "
    "CAST((SELECT COUNT(*) FROM students "
    "WHERE MONTH(admission_date) = MONTH(CURDATE()) AND YEAR(admission_date) = YEAR(CURDATE())) AS CHAR) "
    "UNION ALL SELECT 'New admissions last month', "
    "CAST((SELECT COUNT(*) FROM students "
    "WHERE admission_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') "
    "AND admission_date < DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS CHAR) "
    "UNION ALL SELECT 'Admission enquiries this month', "
    "CAST((SELECT COUNT(*) FROM enquiry "
    "WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())) AS CHAR) "
    "UNION ALL SELECT 'Open complaints', "
    "CAST((SELECT COUNT(*) FROM complaint "
    "WHERE action_taken IS NULL OR TRIM(action_taken) = '') AS CHAR) "
    "UNION ALL SELECT 'Behaviour incidents this month', "
    "CAST((SELECT COUNT(*) FROM student_incidents "
    "WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())) AS CHAR) "
    "UNION ALL SELECT 'Estimated net surplus this month', "
    "CAST(COALESCE((SELECT SUM(amount) FROM income "
    "WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())),0) - "
    "COALESCE((SELECT SUM(amount) FROM expenses "
    "WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())),0) - "
    "COALESCE((SELECT SUM(net_salary) FROM staff_payroll "
    "WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())),0) AS CHAR) "
    f"UNION ALL SELECT 'Avg exam score %', "
    f"CAST(COALESCE(ROUND(AVG(ms.exam_marks / NULLIF(ms.exam_total, 0) * 100), 1), 0) AS CHAR) "
    "FROM mark_sheet ms WHERE ms.exam_total > 0"
)

_SQL_RISK = (
    "SELECT risk_area, risk_indicator, issue_count, severity, recommended_action FROM ( "
    "SELECT 'Academic & attendance' AS risk_area, "
    "'Students below 75% attendance (month)' AS risk_indicator, COUNT(*) AS issue_count, "
    "CASE WHEN COUNT(*) >= 10 THEN 'High' WHEN COUNT(*) >= 3 THEN 'Medium' ELSE 'Low' END, "
    "'Review class attendance; contact guardians' AS recommended_action "
    f"FROM (SELECT s.id FROM student_attendences sa "
    "JOIN student_session ss ON ss.id = sa.student_session_id "
    "JOIN students s ON s.id = ss.student_id "
    "LEFT JOIN attendence_type at ON at.id = sa.attendence_type_id "
    "WHERE MONTH(sa.date)=MONTH(CURDATE()) AND YEAR(sa.date)=YEAR(CURDATE()) "
    f"GROUP BY s.id HAVING ROUND(100.0*SUM({_PRESENT})/NULLIF(COUNT(*),0),2)<75) t "
    "UNION ALL SELECT 'Finance','Students with outstanding fees',COUNT(*), "
    "CASE WHEN COUNT(*)>=10 THEN 'High' WHEN COUNT(*)>=3 THEN 'Medium' ELSE 'Low' END, "
    "'Fee reminders and class-wise collection drive' "
    "FROM (SELECT s.id FROM students s JOIN student_session ss ON ss.student_id=s.id "
    "JOIN student_fees_master sfm ON sfm.student_session_id=ss.id "
    "LEFT JOIN (SELECT sfd.student_fees_master_id,SUM(CAST(jt.amt AS DECIMAL(10,2))) paid "
    "FROM student_fees_deposite sfd,JSON_TABLE(sfd.amount_detail,'$.*' "
    "COLUMNS(amt VARCHAR(50) PATH '$.amount')) jt GROUP BY sfd.student_fees_master_id) p "
    "ON p.student_fees_master_id=sfm.id GROUP BY s.id "
    "HAVING COALESCE(SUM(sfm.amount),0)-COALESCE(SUM(p.paid),0)>0) f "
    "UNION ALL SELECT 'Finance','Monthly deficit (income-expense-payroll)', "
    "CASE WHEN (COALESCE((SELECT SUM(amount) FROM income WHERE MONTH(date)=MONTH(CURDATE()) "
    "AND YEAR(date)=YEAR(CURDATE())),0)-COALESCE((SELECT SUM(amount) FROM expenses "
    "WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())),0)-"
    "COALESCE((SELECT SUM(net_salary) FROM staff_payroll WHERE MONTH(payment_date)=MONTH(CURDATE()) "
    "AND YEAR(payment_date)=YEAR(CURDATE())),0))<0 THEN 1 ELSE 0 END, "
    "CASE WHEN (COALESCE((SELECT SUM(amount) FROM income WHERE MONTH(date)=MONTH(CURDATE()) "
    "AND YEAR(date)=YEAR(CURDATE())),0)-COALESCE((SELECT SUM(amount) FROM expenses "
    "WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())),0)-"
    "COALESCE((SELECT SUM(net_salary) FROM staff_payroll WHERE MONTH(payment_date)=MONTH(CURDATE()) "
    "AND YEAR(payment_date)=YEAR(CURDATE())),0))<0 THEN 'High' ELSE 'Low' END, "
    "'Review expenses and payroll vs income' "
    "UNION ALL SELECT 'Discipline','Negative behaviour (month)',COUNT(DISTINCT si.student_id), "
    "CASE WHEN COUNT(DISTINCT si.student_id)>=5 THEN 'High' "
    "WHEN COUNT(DISTINCT si.student_id)>=1 THEN 'Medium' ELSE 'Low' END, "
    "'Behaviour support for repeat cases' "
    "FROM student_incidents si JOIN student_behaviour sb ON sb.id=si.incident_id "
    "WHERE sb.point<0 AND MONTH(si.created_at)=MONTH(CURDATE()) "
    "AND YEAR(si.created_at)=YEAR(CURDATE()) "
    "UNION ALL SELECT 'Operations','Open complaints',COUNT(*), "
    "CASE WHEN COUNT(*)>=3 THEN 'High' WHEN COUNT(*)>=1 THEN 'Medium' ELSE 'Low' END, "
    "'Close complaint loop with owners' "
    "FROM complaint WHERE action_taken IS NULL OR TRIM(action_taken)='' "
    "UNION ALL SELECT 'Admissions','Pending enquiries',COUNT(*), "
    "CASE WHEN COUNT(*)>=10 THEN 'High' WHEN COUNT(*)>=3 THEN 'Medium' ELSE 'Low' END, "
    "'Front-office follow-up on pipeline' "
    "FROM enquiry WHERE LOWER(status) IN ('active','pending','open') "
    ") r WHERE issue_count > 0 ORDER BY FIELD(severity,'High','Medium','Low'), issue_count DESC LIMIT 12"
)

_SQL_GAPS = (
    "SELECT concern_area, issue_count, severity, priority_note FROM ( "
    "SELECT 'Low attendance (<75%)' AS concern_area, COUNT(*) AS issue_count, "
    "CASE WHEN COUNT(*)>=10 THEN 'High' WHEN COUNT(*)>=3 THEN 'Medium' ELSE 'Low' END, "
    "'Attendance intervention program' AS priority_note "
    f"FROM (SELECT s.id FROM student_attendences sa "
    "JOIN student_session ss ON ss.id=sa.student_session_id "
    "JOIN students s ON s.id=ss.student_id "
    "LEFT JOIN attendence_type at ON at.id=sa.attendence_type_id "
    "WHERE MONTH(sa.date)=MONTH(CURDATE()) AND YEAR(sa.date)=YEAR(CURDATE()) "
    f"GROUP BY s.id HAVING ROUND(100.0*SUM({_PRESENT})/NULLIF(COUNT(*),0),2)<75) a "
    "UNION ALL SELECT 'Outstanding fees',COUNT(*), "
    "CASE WHEN COUNT(*)>=10 THEN 'High' WHEN COUNT(*)>=3 THEN 'Medium' ELSE 'Low' END, "
    "'Accelerate fee collection' "
    "FROM (SELECT s.id FROM students s JOIN student_session ss ON ss.student_id=s.id "
    "JOIN student_fees_master sfm ON sfm.student_session_id=ss.id "
    "LEFT JOIN (SELECT sfd.student_fees_master_id,SUM(CAST(jt.amt AS DECIMAL(10,2))) paid "
    "FROM student_fees_deposite sfd,JSON_TABLE(sfd.amount_detail,'$.*' "
    "COLUMNS(amt VARCHAR(50) PATH '$.amount')) jt GROUP BY sfd.student_fees_master_id) p "
    "ON p.student_fees_master_id=sfm.id GROUP BY s.id "
    "HAVING COALESCE(SUM(sfm.amount),0)-COALESCE(SUM(p.paid),0)>0) b "
    "UNION ALL SELECT 'Homework with no submissions (month)',COUNT(*), "
    "CASE WHEN COUNT(*)>=20 THEN 'High' WHEN COUNT(*)>=5 THEN 'Medium' ELSE 'Low' END, "
    "'Homework completion drive' "
    "FROM homework h WHERE MONTH(COALESCE(h.homework_date, h.submit_date))=MONTH(CURDATE()) "
    "AND YEAR(COALESCE(h.homework_date, h.submit_date))=YEAR(CURDATE()) "
    "AND NOT EXISTS (SELECT 1 FROM submit_assignment sa WHERE sa.homework_id=h.id) "
    "UNION ALL SELECT 'Open complaints',COUNT(*), "
    "CASE WHEN COUNT(*)>=3 THEN 'High' WHEN COUNT(*)>=1 THEN 'Medium' ELSE 'Low' END, "
    "'Resolve grievances' "
    "FROM complaint WHERE action_taken IS NULL OR TRIM(action_taken)='' "
    ") z WHERE issue_count>0 ORDER BY issue_count DESC LIMIT 8"
)

_SQL_ADMISSIONS_TREND = (
    "SELECT DATE_FORMAT(admission_date, '%Y-%m') AS month_label, COUNT(*) AS new_admissions "
    "FROM students WHERE admission_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) "
    "GROUP BY DATE_FORMAT(admission_date, '%Y-%m') ORDER BY month_label ASC LIMIT 12"
)

_SQL_WEAKEST_CLASSES = (
    f"SELECT c.class AS class_name, "
    f"ROUND(100.0 * SUM({_PRESENT}) / NULLIF(COUNT(*), 0), 1) AS attendance_percent "
    "FROM student_attendences sa "
    "JOIN student_session ss ON ss.id = sa.student_session_id "
    "JOIN classes c ON c.id = ss.class_id "
    "LEFT JOIN attendence_type at ON at.id = sa.attendence_type_id "
    "WHERE MONTH(sa.date) = MONTH(CURDATE()) AND YEAR(sa.date) = YEAR(CURDATE()) "
    "GROUP BY c.class ORDER BY attendance_percent ASC LIMIT 8"
)

_SCOPE_PATTERNS = (
    r"how\s+is\s+(our\s+|the\s+)?school\s+perform",
    r"school\s+perform",
    r"school\s+performance",
    r"overall\s+performance\s+of\s+(the\s+)?school",
    r"overall\s+school\s+performance",
    r"give\s+me\s+school\s+perform",
    r"school\s+health",
    r"executive\s+(summary|dashboard|briefing|overview)",
    r"big\s+picture\s+.*school",
    r"overview\s+of\s+(our\s+)?school",
    r"how\s+are\s+we\s+doing\s+as\s+a\s+school",
)

_NARROW_EXCLUDES = (
    r"\bgrade\s+\d",
    r"\bclass\s+\d",
    r"student\s+named",
    r"for\s+student\s+",
    r"marksheet\s+of",
    r"one\s+student",
)


def _complaint_table(tables: set[str]) -> str | None:
    if "complaint" in tables:
        return "complaint"
    if "complain" in tables:
        return "complain"
    return None


def _build_kpi_queries(tables: set[str]) -> list[tuple[str, str]]:
    """One small query per KPI so a missing table cannot break the whole briefing."""
    queries: list[tuple[str, str]] = []

    def add(label: str, sql: str, required_tables: tuple[str, ...] = ()):
        if required_tables and not all(t in tables for t in required_tables):
            return
        queries.append((label, sql))

    add(
        "Active students",
        "SELECT CAST(COUNT(*) AS CHAR) FROM students WHERE is_active = 'yes'",
        ("students",),
    )
    add(
        "Active staff",
        "SELECT CAST(COUNT(*) AS CHAR) FROM staff WHERE is_active = 1",
        ("staff",),
    )
    add(
        "Teachers",
        "SELECT CAST(COUNT(DISTINCT sr.staff_id) AS CHAR) FROM staff_roles sr "
        "JOIN roles r ON r.id = sr.role_id "
        "WHERE LOWER(r.slug) = 'teacher' OR LOWER(r.name) LIKE '%teacher%'",
        ("staff_roles", "roles"),
    )
    add(
        "Student attendance % this month",
        f"SELECT CAST(COALESCE(ROUND(100.0 * SUM({_PRESENT}) / NULLIF(COUNT(*), 0), 1), 0) AS CHAR) "
        "FROM student_attendences sa "
        "LEFT JOIN attendence_type at ON at.id = sa.attendence_type_id "
        "WHERE MONTH(sa.date) = MONTH(CURDATE()) AND YEAR(sa.date) = YEAR(CURDATE())",
        ("student_attendences",),
    )
    add(
        "Students absent today",
        "SELECT CAST(COUNT(*) AS CHAR) FROM student_attendences sa2 "
        "JOIN attendence_type at2 ON at2.id = sa2.attendence_type_id "
        "WHERE sa2.date = CURDATE() AND LOWER(at2.type) IN ('a','absent')",
        ("student_attendences", "attendence_type"),
    )
    if "student_fees_deposite" in tables:
        add(
            "Fee collected this month",
            "SELECT CAST(COALESCE((SELECT SUM(CAST(jt.amt AS DECIMAL(10,2))) "
            "FROM student_fees_deposite sfd, "
            "JSON_TABLE(sfd.amount_detail, '$.*' COLUMNS (amt VARCHAR(50) PATH '$.amount', "
            "payment_date DATE PATH '$.date')) AS jt "
            "WHERE MONTH(jt.payment_date) = MONTH(CURDATE()) "
            "AND YEAR(jt.payment_date) = YEAR(CURDATE())), 0) AS CHAR)",
        )
        if "student_fees_master" in tables:
            add(
                "Outstanding fee balance",
                "SELECT CAST(GREATEST(COALESCE((SELECT SUM(sfm.amount) FROM student_fees_master sfm), 0) - "
                "COALESCE((SELECT SUM(CAST(jt.amt AS DECIMAL(10,2))) FROM student_fees_deposite sfd, "
                "JSON_TABLE(sfd.amount_detail, '$.*' COLUMNS (amt VARCHAR(50) PATH '$.amount')) AS jt), 0), 0) AS CHAR)",
            )
    add(
        "New admissions this month",
        "SELECT CAST(COUNT(*) AS CHAR) FROM students "
        "WHERE MONTH(admission_date) = MONTH(CURDATE()) AND YEAR(admission_date) = YEAR(CURDATE())",
        ("students",),
    )
    add(
        "New admissions last month",
        "SELECT CAST(COUNT(*) AS CHAR) FROM students "
        "WHERE admission_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') "
        "AND admission_date < DATE_FORMAT(CURDATE(), '%Y-%m-01')",
        ("students",),
    )
    if "enquiry" in tables:
        add(
            "Admission enquiries this month",
            "SELECT CAST(COUNT(*) AS CHAR) FROM enquiry "
            "WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())",
        )
    ct = _complaint_table(tables)
    if ct:
        add(
            "Open complaints",
            f"SELECT CAST(COUNT(*) AS CHAR) FROM {ct} "
            "WHERE action_taken IS NULL OR TRIM(COALESCE(action_taken, '')) = ''",
        )
    if "student_incidents" in tables:
        add(
            "Behaviour incidents this month",
            "SELECT CAST(COUNT(*) AS CHAR) FROM student_incidents "
            "WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())",
        )
    if "income" in tables and "expenses" in tables:
        payroll = (
            " - COALESCE((SELECT SUM(net_salary) FROM staff_payroll "
            "WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())),0)"
            if "staff_payroll" in tables
            else ""
        )
        add(
            "Estimated net surplus this month",
            "SELECT CAST(COALESCE((SELECT SUM(amount) FROM income "
            "WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())),0) - "
            "COALESCE((SELECT SUM(amount) FROM expenses "
            "WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())),0)"
            + payroll
            + " AS CHAR)",
        )
    if "mark_sheet" in tables:
        add(
            "Avg exam score %",
            "SELECT CAST(COALESCE(ROUND(AVG(ms.exam_marks / NULLIF(ms.exam_total, 0) * 100), 1), 0) AS CHAR) "
            "FROM mark_sheet ms WHERE ms.exam_total > 0",
        )
    return queries


def _build_risk_sql(tables: set[str]) -> str:
    """Build risk SQL; omit unions for tables that do not exist."""
    parts: list[str] = []
    if "student_attendences" in tables:
        parts.append(
            f"SELECT 'Academic & attendance' AS risk_area, "
            "'Students below 75% attendance (month)' AS risk_indicator, COUNT(*) AS issue_count, "
            "CASE WHEN COUNT(*) >= 10 THEN 'High' WHEN COUNT(*) >= 3 THEN 'Medium' ELSE 'Low' END AS severity, "
            "'Review class attendance; contact guardians' AS recommended_action "
            f"FROM (SELECT s.id FROM student_attendences sa "
            "JOIN student_session ss ON ss.id = sa.student_session_id "
            "JOIN students s ON s.id = ss.student_id "
            "LEFT JOIN attendence_type at ON at.id = sa.attendence_type_id "
            "WHERE MONTH(sa.date)=MONTH(CURDATE()) AND YEAR(sa.date)=YEAR(CURDATE()) "
            f"GROUP BY s.id HAVING ROUND(100.0*SUM({_PRESENT})/NULLIF(COUNT(*),0),2)<75) t"
        )
    if "student_fees_master" in tables and "student_fees_deposite" in tables:
        parts.append(
            "SELECT 'Finance','Students with outstanding fees',COUNT(*), "
            "CASE WHEN COUNT(*)>=10 THEN 'High' WHEN COUNT(*)>=3 THEN 'Medium' ELSE 'Low' END, "
            "'Fee reminders and class-wise collection drive' "
            "FROM (SELECT s.id FROM students s JOIN student_session ss ON ss.student_id=s.id "
            "JOIN student_fees_master sfm ON sfm.student_session_id=ss.id "
            "LEFT JOIN (SELECT sfd.student_fees_master_id,SUM(CAST(jt.amt AS DECIMAL(10,2))) paid "
            "FROM student_fees_deposite sfd,JSON_TABLE(sfd.amount_detail,'$.*' "
            "COLUMNS(amt VARCHAR(50) PATH '$.amount')) jt GROUP BY sfd.student_fees_master_id) p "
            "ON p.student_fees_master_id=sfm.id GROUP BY s.id "
            "HAVING COALESCE(SUM(sfm.amount),0)-COALESCE(SUM(p.paid),0)>0) f"
        )
    if "income" in tables and "expenses" in tables:
        payroll = (
            "COALESCE((SELECT SUM(net_salary) FROM staff_payroll "
            "WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())),0)"
            if "staff_payroll" in tables
            else "0"
        )
        parts.append(
            "SELECT 'Finance','Monthly deficit (income-expense-payroll)', "
            "CASE WHEN (COALESCE((SELECT SUM(amount) FROM income WHERE MONTH(date)=MONTH(CURDATE()) "
            "AND YEAR(date)=YEAR(CURDATE())),0)-COALESCE((SELECT SUM(amount) FROM expenses "
            "WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())),0)-"
            f"{payroll})<0 THEN 1 ELSE 0 END, "
            "CASE WHEN (COALESCE((SELECT SUM(amount) FROM income WHERE MONTH(date)=MONTH(CURDATE()) "
            "AND YEAR(date)=YEAR(CURDATE())),0)-COALESCE((SELECT SUM(amount) FROM expenses "
            "WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())),0)-"
            f"{payroll})<0 THEN 'High' ELSE 'Low' END, "
            "'Review expenses and payroll vs income'"
        )
    if "student_incidents" in tables and "student_behaviour" in tables:
        parts.append(
            "SELECT 'Discipline','Negative behaviour (month)',COUNT(DISTINCT si.student_id), "
            "CASE WHEN COUNT(DISTINCT si.student_id)>=5 THEN 'High' "
            "WHEN COUNT(DISTINCT si.student_id)>=1 THEN 'Medium' ELSE 'Low' END, "
            "'Behaviour support for repeat cases' "
            "FROM student_incidents si JOIN student_behaviour sb ON sb.id=si.incident_id "
            "WHERE sb.point<0 AND MONTH(si.created_at)=MONTH(CURDATE()) "
            "AND YEAR(si.created_at)=YEAR(CURDATE())"
        )
    ct = _complaint_table(tables)
    if ct:
        parts.append(
            "SELECT 'Operations','Open complaints',COUNT(*), "
            "CASE WHEN COUNT(*)>=3 THEN 'High' WHEN COUNT(*)>=1 THEN 'Medium' ELSE 'Low' END, "
            "'Close complaint loop with owners' "
            f"FROM {ct} WHERE action_taken IS NULL OR TRIM(COALESCE(action_taken,''))=''"
        )
    if "enquiry" in tables:
        parts.append(
            "SELECT 'Admissions','Pending enquiries',COUNT(*), "
            "CASE WHEN COUNT(*)>=10 THEN 'High' WHEN COUNT(*)>=3 THEN 'Medium' ELSE 'Low' END, "
            "'Front-office follow-up on pipeline' "
            "FROM enquiry WHERE LOWER(status) IN ('active','pending','open')"
        )
    if not parts:
        return ""
    return (
        "SELECT risk_area, risk_indicator, issue_count, severity, recommended_action FROM ( "
        + " UNION ALL ".join(parts)
        + " ) r WHERE issue_count > 0 ORDER BY FIELD(severity,'High','Medium','Low'), "
        "issue_count DESC LIMIT 12"
    )


def _build_gaps_sql(tables: set[str]) -> str:
    parts: list[str] = []
    if "student_attendences" in tables:
        parts.append(
            f"SELECT 'Low attendance (<75%)' AS concern_area, COUNT(*) AS issue_count, "
            "CASE WHEN COUNT(*)>=10 THEN 'High' WHEN COUNT(*)>=3 THEN 'Medium' ELSE 'Low' END AS severity, "
            "'Attendance intervention program' AS priority_note "
            f"FROM (SELECT s.id FROM student_attendences sa "
            "JOIN student_session ss ON ss.id=sa.student_session_id "
            "JOIN students s ON s.id=ss.student_id "
            "LEFT JOIN attendence_type at ON at.id=sa.attendence_type_id "
            "WHERE MONTH(sa.date)=MONTH(CURDATE()) AND YEAR(sa.date)=YEAR(CURDATE()) "
            f"GROUP BY s.id HAVING ROUND(100.0*SUM({_PRESENT})/NULLIF(COUNT(*),0),2)<75) a"
        )
    if "student_fees_master" in tables and "student_fees_deposite" in tables:
        parts.append(
            "SELECT 'Outstanding fees',COUNT(*), "
            "CASE WHEN COUNT(*)>=10 THEN 'High' WHEN COUNT(*)>=3 THEN 'Medium' ELSE 'Low' END, "
            "'Accelerate fee collection' "
            "FROM (SELECT s.id FROM students s JOIN student_session ss ON ss.student_id=s.id "
            "JOIN student_fees_master sfm ON sfm.student_session_id=ss.id "
            "LEFT JOIN (SELECT sfd.student_fees_master_id,SUM(CAST(jt.amt AS DECIMAL(10,2))) paid "
            "FROM student_fees_deposite sfd,JSON_TABLE(sfd.amount_detail,'$.*' "
            "COLUMNS(amt VARCHAR(50) PATH '$.amount')) jt GROUP BY sfd.student_fees_master_id) p "
            "ON p.student_fees_master_id=sfm.id GROUP BY s.id "
            "HAVING COALESCE(SUM(sfm.amount),0)-COALESCE(SUM(p.paid),0)>0) b"
        )
    if "homework" in tables and "submit_assignment" in tables:
        parts.append(
            "SELECT 'Homework with no submissions (month)',COUNT(*), "
            "CASE WHEN COUNT(*)>=20 THEN 'High' WHEN COUNT(*)>=5 THEN 'Medium' ELSE 'Low' END, "
            "'Homework completion drive' "
            "FROM homework h WHERE MONTH(COALESCE(h.homework_date,h.submit_date))=MONTH(CURDATE()) "
            "AND YEAR(COALESCE(h.homework_date,h.submit_date))=YEAR(CURDATE()) "
            "AND NOT EXISTS (SELECT 1 FROM submit_assignment sa WHERE sa.homework_id=h.id)"
        )
    ct = _complaint_table(tables)
    if ct:
        parts.append(
            "SELECT 'Open complaints',COUNT(*), "
            "CASE WHEN COUNT(*)>=3 THEN 'High' WHEN COUNT(*)>=1 THEN 'Medium' ELSE 'Low' END, "
            "'Resolve grievances' "
            f"FROM {ct} WHERE action_taken IS NULL OR TRIM(COALESCE(action_taken,''))=''"
        )
    if not parts:
        return ""
    return (
        "SELECT concern_area, issue_count, severity, priority_note FROM ( "
        + " UNION ALL ".join(parts)
        + " ) z WHERE issue_count>0 ORDER BY issue_count DESC LIMIT 8"
    )


def _run_kpi_metrics(
    db_service, sql_validator, tables: set[str]
) -> tuple[dict[str, Any], list[str]]:
    metrics: dict[str, Any] = {}
    sql_used: list[str] = []
    for label, sql in _build_kpi_queries(tables):
        sanitized = sql_validator.sanitize_limit(sql, 1)
        rows, _, err = db_service.execute(sanitized)
        if err or not rows:
            logger.debug("KPI metric skipped (%s): %s", label, err)
            continue
        metrics[label] = rows[0][0]
        sql_used.append(sanitized)
    return metrics, sql_used


def is_executive_scope_question(question: str) -> bool:
    """Broad executive / whole-school performance questions."""
    q = (question or "").lower().strip()
    if not q:
        return False
    for pat in _NARROW_EXCLUDES:
        if re.search(pat, q):
            return False
    return any(re.search(pat, q) for pat in _SCOPE_PATTERNS)


@dataclass
class BriefingResult:
    status: str
    narrative: str = ""
    structured_data: dict | None = None
    sql_used: list[str] = field(default_factory=list)
    metrics: dict[str, Any] = field(default_factory=dict)


def _num(val) -> float | None:
    if val is None or val == "":
        return None
    try:
        return float(str(val).replace(",", ""))
    except (TypeError, ValueError):
        return None


def _rows_to_dicts(columns: list[str], rows: list[tuple]) -> list[dict]:
    out = []
    for row in rows:
        out.append({
            columns[i]: _jsonify(row[i], columns[i] if i < len(columns) else "")
            for i in range(len(row))
        })
    return out


def _table_payload(columns: list[str], rows: list[tuple], max_rows: int = 50) -> dict:
    safe = [
        [_jsonify(row[i], columns[i] if i < len(columns) else "") for i in range(len(row))]
        for row in rows[:max_rows]
    ]
    return {
        "kind": "table",
        "columns": list(columns),
        "rows": safe,
        "row_count": len(rows),
        "shown_count": len(safe),
    }


def _chart_from_columns(
    columns: list[str], rows: list[tuple], chart_kind: str, label_col: int, value_col: int
) -> dict:
    labels = []
    values = []
    for row in rows:
        if label_col < len(row):
            labels.append(str(_jsonify(row[label_col], columns[label_col])))
        if value_col < len(row):
            v = _num(row[value_col])
            values.append(v if v is not None else 0)
    return {
        "kind": chart_kind,
        "label_column": columns[label_col].replace("_", " ").title(),
        "value_column": columns[value_col].replace("_", " ").title(),
        "labels": labels,
        "datasets": [{"label": columns[value_col].replace("_", " ").title(), "data": values}],
    }


def _score_school_health(metrics: dict[str, Any], risks: list[dict], gaps: list[dict]) -> dict:
    """
    Score 0–100 from TaleemX operational benchmarks.
    Strong school: attendance ≥90%, low risk highs, fees under control, growth stable.
    """
    score = 50.0
    notes: list[str] = []
    strengths: list[str] = []
    priorities: list[str] = []

    att = _num(metrics.get("Student attendance % this month"))
    if att is not None:
        if att >= 90:
            score += 18
            strengths.append(f"Student attendance is **{att}%** this month (strong).")
        elif att >= 75:
            score += 8
            notes.append(f"Attendance **{att}%** is acceptable but below the 90% excellence target.")
        else:
            score -= 12
            priorities.append(f"Raise attendance from **{att}%** (target ≥90%).")
    else:
        notes.append("No attendance marks recorded this month yet.")

    surplus = _num(metrics.get("Estimated net surplus this month"))
    if surplus is not None:
        if surplus >= 0:
            score += 8
            strengths.append("Monthly finances show a **non-negative** estimated surplus.")
        else:
            score -= 10
            priorities.append(f"Address monthly deficit (estimated **{abs(surplus):,.0f}**).")

    outstanding = _num(metrics.get("Outstanding fee balance"))
    collected = _num(metrics.get("Fee collected this month"))
    if outstanding is not None and collected is not None and outstanding > 0:
        ratio = collected / outstanding if outstanding else 1
        if ratio >= 0.5:
            score += 6
        elif ratio >= 0.2:
            score += 2
        else:
            score -= 8
            priorities.append(
                f"Fee collection pressure: **{outstanding:,.0f}** outstanding vs **{collected:,.0f}** collected."
            )

    new_m = _num(metrics.get("New admissions this month"))
    new_lm = _num(metrics.get("New admissions last month"))
    if new_m is not None and new_lm is not None:
        if new_m >= new_lm:
            score += 6
            if new_m > new_lm:
                strengths.append(
                    f"Admissions grew: **{int(new_m)}** this month vs **{int(new_lm)}** last month."
                )
        elif new_lm > 0:
            score -= 4
            notes.append(f"Admissions slowed (**{int(new_m)}** vs **{int(new_lm)}** last month).")

    high_risks = sum(1 for r in risks if str(r.get("severity", "")).lower() == "high")
    med_risks = sum(1 for r in risks if str(r.get("severity", "")).lower() == "medium")
    score -= high_risks * 7
    score -= med_risks * 3
    if high_risks:
        priorities.append(f"Resolve **{high_risks}** high-severity risk signal(s) immediately.")
    if not risks:
        strengths.append("No active high-priority risk indicators in the current scan.")

    complaints = _num(metrics.get("Open complaints"))
    if complaints is not None and complaints > 0:
        score -= min(8, complaints * 2)
        if complaints >= 3:
            priorities.append(f"Close **{int(complaints)}** open complaint(s).")

    exam = _num(metrics.get("Avg exam score %"))
    if exam is not None and exam > 0:
        if exam >= 70:
            score += 5
            strengths.append(f"Recent average exam performance: **{exam}%**.")
        elif exam < 50:
            score -= 5
            priorities.append(f"Improve academic outcomes (avg exam **{exam}%**).")

    if gaps:
        top = gaps[0]
        priorities.append(
            f"Top operational gap: **{top.get('concern_area', '')}** ({top.get('issue_count', '')} cases)."
        )

    score = max(0, min(100, round(score)))

    if score >= 80:
        rating = "Strong performance"
        level = "strong"
    elif score >= 65:
        rating = "Good — some areas to watch"
        level = "good"
    elif score >= 50:
        rating = "Needs improvement"
        level = "watch"
    else:
        rating = "Critical attention required"
        level = "critical"

    summary_parts = [
        f"Overall health score: **{score}/100** ({rating}).",
    ]
    if strengths:
        summary_parts.append("**Strengths:** " + "; ".join(strengths[:3]))
    if priorities:
        summary_parts.append("**Priorities:** " + "; ".join(priorities[:4]))
    elif notes:
        summary_parts.append("**Watch:** " + "; ".join(notes[:2]))

    return {
        "score": score,
        "rating": rating,
        "rating_level": level,
        "summary": " ".join(summary_parts),
        "strengths": strengths[:6],
        "priorities": priorities[:6],
        "watch_notes": notes[:4],
    }


def _compose_narrative(question: str, health: dict, metrics: dict, risks: list[dict]) -> str:
    lines = [
        "## School performance — executive briefing",
        "",
        health.get("summary", ""),
        "",
        "### How we judge performance (your LMS data)",
        "A **well-performing school** in TaleemX typically shows:",
        "- **≥90%** student attendance in the current month",
        "- **Stable or growing** new admissions vs last month",
        "- **Healthy fee collection** relative to outstanding balances",
        "- **Non-negative** monthly surplus (income − expenses − payroll)",
        "- **Few high-severity risks** (fees, behaviour, complaints, enquiries)",
        "",
        "### Key numbers",
    ]
    for key in (
        "Active students", "Teachers", "Student attendance % this month",
        "Fee collected this month", "Outstanding fee balance",
        "New admissions this month", "Open complaints",
        "Estimated net surplus this month", "Avg exam score %",
    ):
        if key in metrics:
            lines.append(f"- **{key}:** {metrics[key]}")

    if risks:
        lines.extend(["", "### Risk highlights", ""])
        for r in risks[:6]:
            sev = r.get("severity", "")
            ind = r.get("risk_indicator", r.get("risk_area", ""))
            cnt = r.get("issue_count", "")
            lines.append(f"- **[{sev}]** {ind}: **{cnt}**")

    lines.extend([
        "",
        "Charts and detailed risk/gap tables are below.",
        "",
        "_Based on live data from students, attendance, fees, finance, admissions, exams, and operations._",
    ])
    return "\n".join(lines)


def run_executive_briefing(db_service, sql_validator, question: str) -> BriefingResult:
    """Execute briefing queries and assemble dashboard payload."""
    tables = db_service.list_tables()
    logger.info("Executive briefing: %d tables visible in schema.", len(tables))

    metrics, sql_used = _run_kpi_metrics(db_service, sql_validator, tables)
    results: dict[str, tuple] = {}

    if len(metrics) < 3:
        return BriefingResult(
            status="error",
            narrative=(
                "Could not load enough school KPI data. "
                f"Only {len(metrics)} metric(s) returned — check DB tables (students, staff, attendance)."
            ),
        )

    risk_sql = _build_risk_sql(tables)
    if risk_sql:
        sanitized = sql_validator.sanitize_limit(risk_sql, 50)
        rows, columns, err = db_service.execute(sanitized)
        if not err:
            results["risk"] = (columns, rows)
            sql_used.append(sanitized)
        else:
            logger.warning("Executive briefing query risk failed: %s", err)

    gaps_sql = _build_gaps_sql(tables)
    if gaps_sql:
        sanitized = sql_validator.sanitize_limit(gaps_sql, 50)
        rows, columns, err = db_service.execute(sanitized)
        if not err:
            results["gaps"] = (columns, rows)
            sql_used.append(sanitized)
        else:
            logger.warning("Executive briefing query gaps failed: %s", err)

    for name, sql in (
        ("admissions", _SQL_ADMISSIONS_TREND),
        ("classes", _SQL_WEAKEST_CLASSES),
    ):
        if name == "admissions" and "students" not in tables:
            continue
        if name == "classes" and "student_attendences" not in tables:
            continue
        sanitized = sql_validator.sanitize_limit(sql, 50)
        rows, columns, err = db_service.execute(sanitized)
        if err:
            logger.warning("Executive briefing query %s failed: %s", name, err)
            continue
        results[name] = (columns, rows)
        sql_used.append(sanitized)

    risks: list[dict] = []
    if "risk" in results:
        rc, rr = results["risk"]
        risks = _rows_to_dicts(rc, rr)

    gaps: list[dict] = []
    if "gaps" in results:
        gc, gr = results["gaps"]
        gaps = _rows_to_dicts(gc, gr)

    health = _score_school_health(metrics, risks, gaps)
    narrative = _compose_narrative(question, health, metrics, risks)

    # KPI tiles for UI
    kpi_tiles = []
    status_map = {
        "Student attendance % this month": "attendance",
        "Estimated net surplus this month": "finance",
        "Outstanding fee balance": "finance",
        "Open complaints": "operations",
    }
    for label, value in metrics.items():
        tile_status = "neutral"
        if status_map.get(label) == "attendance":
            v = _num(value)
            if v is not None:
                tile_status = "strong" if v >= 90 else ("watch" if v >= 75 else "critical")
        elif label == "Estimated net surplus this month":
            v = _num(value)
            if v is not None:
                tile_status = "strong" if v >= 0 else "critical"
        kpi_tiles.append({"label": label, "value": value, "status": tile_status})

    structured: dict[str, Any] = {
        "kind": "executive_briefing",
        "health": health,
        "kpis": kpi_tiles,
        "benchmarks": [
            {"label": "Attendance target", "value": "≥ 90%"},
            {"label": "Fee health", "value": "Collections vs outstanding"},
            {"label": "Growth", "value": "Admissions vs last month"},
            {"label": "Finance", "value": "Income − expenses − payroll ≥ 0"},
        ],
    }

    if risks:
        rc, rr = results["risk"]
        structured["risk_chart"] = _chart_from_columns(
            rc, rr, "bar_chart", 1, 2
        )  # indicator, count
        structured["risk_table"] = _table_payload(rc, rr)

    if gaps:
        gc, gr = results["gaps"]
        structured["gaps_chart"] = _chart_from_columns(gc, gr, "bar_chart", 0, 1)
        structured["gaps_table"] = _table_payload(gc, gr)

    if "admissions" in results:
        ac, ar = results["admissions"]
        if ar:
            structured["admissions_chart"] = _chart_from_columns(
                ac, ar, "line_chart", 0, 1
            )

    if "classes" in results:
        cc, cr = results["classes"]
        if cr:
            structured["class_attendance_chart"] = _chart_from_columns(
                cc, cr, "bar_chart", 0, 1
            )

    # KPI bar chart (numeric metrics only)
    chart_labels = []
    chart_values = []
    for label, value in metrics.items():
        v = _num(value)
        if v is not None and "%" not in str(label):
            chart_labels.append(label[:28])
            chart_values.append(v)
    if chart_labels:
        structured["kpi_chart"] = {
            "kind": "bar_chart",
            "labels": chart_labels,
            "datasets": [{"label": "Value", "data": chart_values}],
        }

    return BriefingResult(
        status="ok",
        narrative=narrative,
        structured_data=structured,
        sql_used=sql_used,
        metrics=metrics,
    )
