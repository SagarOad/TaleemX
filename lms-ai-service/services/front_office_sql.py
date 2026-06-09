"""
Front office SQL — aligned with TaleemX enquiry / visitors / complaint / phone / income.
"""

from __future__ import annotations

import re
from typing import Optional

from services.date_filters import parse_month_from_question, sql_date_filter
from services.name_filters import extract_person_name, is_likely_fake_name


_ENQUIRY_CLASS_JOIN = " LEFT JOIN classes c ON c.id = e.class_id "


def _esc(s: str) -> str:
    return (s or "").replace("'", "''")


def _extract_person_name(question: str) -> Optional[str]:
    q = question or ""
    for pat in (
        r"(?:student|applicant|name)\s+([A-Za-z][A-Za-z\s\-']{2,60})",
        r"(?:named|for)\s+([A-Za-z][A-Za-z\s\-']{2,60})",
        r"([A-Za-z][A-Za-z\s\-']{2,60})\s+notreal",
    ):
        m = re.search(pat, q, re.IGNORECASE)
        if m:
            name = " ".join(m.group(1).split()).strip("?.!,;:")
            if len(name) >= 3 and name.lower() not in ("grade", "class", "admission"):
                return name
    return None


def _extract_grade(question: str) -> Optional[str]:
    m = re.search(r"\b(?:grade|class)\s*(\d+)\b", (question or "").lower())
    return m.group(1) if m else None


def _extract_section(question: str) -> Optional[str]:
    m = re.search(
        r"\bsection\s+([A-Za-z0-9]+)\b", (question or ""), re.IGNORECASE
    )
    return m.group(1).upper() if m else None


def try_front_office_sql(question: str, date_format: str = "m/d/Y") -> Optional[str]:
    q = (question or "").lower().strip()
    if not q:
        return None

    # --- Admission enquiry ---
    is_enquiry = (
        ("enquir" in q or "inquiry" in q or "inquiries" in q)
        and ("admission" in q or "admissions" in q or "front office" in q or "prospect" in q)
    ) or "admission enquir" in q

    if is_enquiry:
        if re.search(r"\b(?:phone|contact|mobile)\s*(?:number|numbers|no)?\b", q):
            df = sql_date_filter("e.date", question, date_format)
            if df.kind == "invalid":
                return None
            where = df.sql_on_column if df.sql_on_column else "1=1"
            return (
                "SELECT e.contact AS phone_number, e.name "
                "FROM enquiry e "
                f"WHERE {where} "
                "ORDER BY e.date DESC LIMIT 50"
            )

        if re.search(r"\b(?:most|top|highest)\b.*\bsource\b", q) or (
            "which source" in q and "enquir" in q
        ):
            df = sql_date_filter("e.date", question, date_format)
            extra = f" AND {df.sql_on_column}" if df.sql_on_column else ""
            return (
                "SELECT e.source, COUNT(*) AS enquiry_count "
                "FROM enquiry e "
                f"WHERE e.source IS NOT NULL AND TRIM(e.source) <> ''{extra} "
                "GROUP BY e.source "
                "ORDER BY enquiry_count DESC LIMIT 10"
            )

        if "pending" in q:
            df = sql_date_filter("e.date", question, date_format)
            extra = f" AND {df.sql_on_column}" if df.sql_on_column else ""
            return (
                "SELECT e.name, e.contact, e.email, e.date AS enquiry_date, e.status, e.source "
                "FROM enquiry e "
                f"WHERE LOWER(e.status) IN ('active', 'pending', 'open', 'follow up'){extra} "
                "ORDER BY e.date DESC LIMIT 50"
            )

        df = sql_date_filter("e.date", question, date_format)
        if df.kind == "invalid":
            return None

        grade = _extract_grade(question)
        name = _extract_person_name(question) or extract_person_name(question)
        if name and is_likely_fake_name(name):
            return (
                "SELECT e.name, e.contact, e.email, e.date AS enquiry_date, e.description, "
                "e.status, e.source, e.reference, c.`class` AS class_name "
                f"FROM enquiry e{_ENQUIRY_CLASS_JOIN} "
                f"WHERE 1=0 LIMIT 0"
            )
        parts = ["1=1"]
        if df.sql_on_column:
            parts.append(df.sql_on_column)
        if grade:
            parts.append(
                f"(c.`class` = '{grade}' OR c.`class` = 'Grade {grade}' OR c.`class` = 'Class {grade}')"
            )
        if name:
            sn = _esc(name)
            parts.append(
                f"(LOWER(e.name) LIKE LOWER('%{sn}%') "
                f"OR LOWER(e.description) LIKE LOWER('%{sn}%'))"
            )
        join = ""
        if grade:
            join = _ENQUIRY_CLASS_JOIN
        return (
            "SELECT e.name, e.contact, e.email, e.date AS enquiry_date, e.description, "
            "e.status, e.source, e.reference, c.`class` AS class_name "
            f"FROM enquiry e{join or _ENQUIRY_CLASS_JOIN} "
            f"WHERE {' AND '.join(parts)} "
            "ORDER BY e.date DESC LIMIT 50"
        )

    # --- Visitor book ---
    is_visitor = "visitor" in q or ("front office" in q and "meet" in q)
    if is_visitor and not ("teacher" in q and "complaint" in q):
        if re.search(r"\bid\s*card|id_proof|id proof", q):
            df = sql_date_filter("v.date", question, date_format)
            extra = f" AND {df.sql_on_column}" if df.sql_on_column else ""
            return (
                "SELECT v.name, v.id_proof AS id_card_number, v.date, v.purpose, v.contact "
                "FROM visitors_book v "
                f"WHERE v.id_proof IS NOT NULL AND TRIM(v.id_proof) <> ''{extra} "
                "ORDER BY v.date DESC LIMIT 50"
            )

        if re.search(r"\bhighest\b|\bmost\b|\bpeak\b", q) and "visitor" in q:
            month = parse_month_from_question(question)
            if month == 0:
                month = None
            if month:
                yr = re.search(r"\b(20\d{2})\b", question)
                year_clause = yr.group(1) if yr else "YEAR(CURDATE())"
                return (
                    "SELECT DATE(v.date) AS visit_date, COUNT(*) AS visitor_count "
                    "FROM visitors_book v "
                    f"WHERE MONTH(v.date) = {month} AND YEAR(v.date) = {year_clause} "
                    "GROUP BY DATE(v.date) "
                    "ORDER BY visitor_count DESC LIMIT 5"
                )

        df = sql_date_filter("v.date", question, date_format)
        if df.kind == "invalid":
            return None

        staff_match = (
            re.search(
                r"staff\s*\(?\s*([A-Za-z][A-Za-z\s\-']+?)\s*-\s*(\d+)\)?",
                question,
                re.IGNORECASE,
            )
            or re.search(
                r"meet(?:ing)?\s+(?:with\s+)?(?:staff\s*)?\(?\s*"
                r"([A-Za-z][A-Za-z\s\-']+?)\s*-\s*(\d+)\)?",
                question,
                re.IGNORECASE,
            )
            or re.search(
                r"(?:staff|meet|meeting)\s*\(?\s*([A-Za-z][A-Za-z\s\-']+?)"
                r"(?:\s*-\s*(\d+))?\)?",
                question,
                re.IGNORECASE,
            )
        )
        parts = []
        if df.sql_on_column:
            parts.append(df.sql_on_column)
        join = " LEFT JOIN staff sf ON sf.id = v.staff_id "
        if staff_match or re.search(r"\bstaff\b", q):
            parts.append("LOWER(v.meeting_with) = 'staff'")
        if staff_match:
            staff_name = _esc(staff_match.group(1).strip())
            if staff_name.lower() != "staff":
                parts.append(
                    f"(LOWER(CONCAT_WS(' ', sf.name, sf.surname)) LIKE LOWER('%{staff_name}%') "
                    f"OR LOWER(v.meeting_with) LIKE LOWER('%{staff_name}%'))"
                )
            if staff_match.group(2):
                parts.append(f"sf.employee_id = '{_esc(staff_match.group(2))}'")
        where = f"WHERE {' AND '.join(parts)}" if parts else ""
        return (
            "SELECT v.name, v.date, v.purpose, v.contact, v.id_proof, "
            "v.meeting_with, CONCAT_WS(' ', sf.name, sf.surname) AS staff_met, "
            "sf.employee_id AS staff_employee_id, v.in_time, v.out_time, v.note "
            f"FROM visitors_book v{join} "
            f"{where} "
            "ORDER BY v.date DESC, v.in_time DESC LIMIT 50"
        )

    # --- Complaints ---
    if "complaint" in q or "complain" in q:
        if re.search(r"\b(?:against|about)\s+teacher", q):
            return (
                "SELECT c.name AS complainant, c.contact, c.complaint_type, c.description, "
                "c.action_taken, c.assigned, c.date "
                "FROM complaint c "
                "WHERE (LOWER(c.complaint_type) LIKE '%teacher%' "
                "OR LOWER(c.description) LIKE '%teacher%' "
                "OR LOWER(c.note) LIKE '%teacher%') "
                "ORDER BY c.date DESC LIMIT 50"
            )
        if "open" in q or "مفتوحة" in question:
            return (
                "SELECT c.name, c.contact, c.complaint_type, c.description, c.status, "
                "c.action_taken, c.assigned, c.date "
                "FROM complaint c "
                "WHERE LOWER(c.status) IN ('open', 'active', 'pending') "
                "OR c.action_taken IS NULL OR TRIM(c.action_taken) = '' "
                "ORDER BY c.date DESC LIMIT 50"
            )
        if "no action" in q or "without action" in q:
            return (
                "SELECT c.name, c.complaint_type, c.description, c.action_taken, c.date "
                "FROM complaint c "
                "WHERE c.action_taken IS NULL OR TRIM(c.action_taken) = '' "
                "ORDER BY c.date DESC LIMIT 50"
            )

    # --- Phone call log (TaleemX table: general_calls, not phone_call_log) ---
    if "phone call" in q or "call log" in q or ("phone" in q and "call" in q):
        if re.search(r"\bcount\b", q) and "call type" in q:
            return (
                "SELECT gc.call_type, COUNT(*) AS total_calls "
                "FROM general_calls gc "
                "WHERE MONTH(gc.date) = MONTH(CURDATE()) "
                "AND YEAR(gc.date) = YEAR(CURDATE()) "
                "GROUP BY gc.call_type "
                "ORDER BY total_calls DESC LIMIT 50"
            )
        df = sql_date_filter("gc.date", question, date_format)
        extra = f" AND {df.sql_on_column}" if df.sql_on_column else ""
        if "this week" in q:
            extra = " AND gc.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
        elif "this month" in q:
            extra = (
                " AND MONTH(gc.date) = MONTH(CURDATE()) "
                "AND YEAR(gc.date) = YEAR(CURDATE())"
            )
        return (
            "SELECT gc.name, gc.contact, gc.date, gc.description, "
            "gc.follow_up_date, gc.call_duration, gc.note, gc.call_type "
            "FROM general_calls gc "
            f"WHERE 1=1{extra} "
            "ORDER BY gc.date DESC LIMIT 50"
        )

    # --- Income / expense ---
    if "income" in q and "expense" in q and re.search(r"\b(?:versus|vs|compare|comparison)\b", q):
        df = sql_date_filter("i.date", question, date_format)
        extra_i = f" AND {df.sql_on_column}" if df.sql_on_column else ""
        df2 = sql_date_filter("e.date", question, date_format)
        extra_e = f" AND {df2.sql_on_column}" if df2.sql_on_column else ""
        return (
            "SELECT "
            f"(SELECT COALESCE(SUM(i.amount), 0) FROM income i WHERE 1=1{extra_i}) AS total_income, "
            f"(SELECT COALESCE(SUM(e.amount), 0) FROM expenses e WHERE 1=1{extra_e}) AS total_expense"
        )

    if re.search(r"\bexplain\b", q) and "income" in q and "report" in q:
        df = sql_date_filter("i.date", question, date_format)
        extra = f" AND {df.sql_on_column}" if df.sql_on_column else ""
        return (
            "SELECT i.date, i.name, i.invoice_no, i.amount, ih.income_category, i.note "
            "FROM income i "
            "JOIN income_head ih ON ih.id = i.income_head_id "
            f"WHERE 1=1{extra} "
            "ORDER BY i.date DESC LIMIT 50"
        )

    if re.search(r"\bexplain\b", q) and "expense" in q and "report" in q:
        df = sql_date_filter("e.date", question, date_format)
        extra = f" AND {df.sql_on_column}" if df.sql_on_column else ""
        return (
            "SELECT e.date, e.name, e.invoice_no, e.amount, eh.exp_category, e.note "
            "FROM expenses e "
            "JOIN expense_head eh ON eh.id = e.exp_head_id "
            f"WHERE 1=1{extra} "
            "ORDER BY e.date DESC LIMIT 50"
        )

    if "income head" in q or "income heads" in q:
        return (
            "SELECT ih.id, ih.income_category, ih.description "
            "FROM income_head ih ORDER BY ih.income_category LIMIT 50"
        )
    if "expense head" in q or "expense heads" in q:
        return (
            "SELECT eh.id, eh.exp_category, eh.description "
            "FROM expense_head eh ORDER BY eh.exp_category LIMIT 50"
        )
    if "income" in q and re.search(r"\b(received|entry|entries|show|list|all)\b", q):
        df = sql_date_filter("i.date", question, date_format)
        if df.kind == "invalid":
            return None
        extra = f" AND {df.sql_on_column}" if df.sql_on_column else ""
        if "last" in q and "entry" in q:
            return (
                "SELECT i.date, i.name, i.invoice_no, i.amount, ih.income_category "
                "FROM income i "
                "JOIN income_head ih ON ih.id = i.income_head_id "
                "ORDER BY i.date DESC, i.id DESC LIMIT 1"
            )
        return (
            "SELECT i.date, i.name, i.invoice_no, i.amount, ih.income_category, i.note "
            "FROM income i "
            "JOIN income_head ih ON ih.id = i.income_head_id "
            f"WHERE 1=1{extra} "
            "ORDER BY i.date DESC LIMIT 50"
        )
    if "expense" in q and "income" not in q:
        df = sql_date_filter("e.date", question, date_format)
        extra = f" AND {df.sql_on_column}" if df.sql_on_column else ""
        if re.search(r"\b(?:total|how much)\b", q) and "head" not in q:
            return (
                "SELECT COALESCE(SUM(e.amount), 0) AS total_expenses "
                "FROM expenses e "
                f"WHERE 1=1{extra}"
            )
        if "head" not in q:
            return (
                "SELECT e.date, e.name, e.invoice_no, e.amount, eh.exp_category, e.note "
                "FROM expenses e "
                "JOIN expense_head eh ON eh.id = e.exp_head_id "
                f"WHERE 1=1{extra} "
                "ORDER BY e.date DESC LIMIT 50"
            )

    return None


def run_front_office_summary(db, question: str):
    """Multi-count front office KPIs (today / overall). Returns (cols, rows, sql) or None."""
    q = (question or "").lower()
    if not (("front office" in q or "front-office" in q) and "summary" in q):
        return None

    today_only = bool(re.search(r"\btoday\b|\bاليوم\b", q))
    parts = []
    sql_parts = []

    def _count(label, table, date_col):
        where = f" WHERE DATE({date_col}) = CURDATE()" if today_only else ""
        sql = f"SELECT COUNT(*) FROM {table}{where}"
        rows, _, err = db.execute(sql, max_rows=1)
        val = rows[0][0] if rows and not err else 0
        parts.append((label, val))
        sql_parts.append(sql)

    _count("admission_enquiries", "enquiry e", "e.date")
    _count("visitors", "visitors_book v", "v.date")
    _count("phone_calls", "general_calls gc", "gc.date")

    sql = " | ".join(sql_parts)
    rows, _, err = db.execute(
        "SELECT COUNT(*) FROM complaint c "
        "WHERE c.action_taken IS NULL OR TRIM(c.action_taken) = ''",
        max_rows=1,
    )
    parts.append(("open_complaints", rows[0][0] if rows and not err else 0))

    cols = ["metric", "count"]
    out = [(label, cnt) for label, cnt in parts]
    return cols, out, sql
