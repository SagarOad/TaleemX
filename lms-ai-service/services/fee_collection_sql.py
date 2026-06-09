"""
Fee collection queries — payments by student, month, class, and recent history.
"""

from __future__ import annotations

import re
from datetime import date
from typing import TYPE_CHECKING, Optional

from services.concept_answers import is_explain_concept_question
from services.date_filters import (
    _MONTHS,
    month_english_name,
    parse_month_from_question,
    parse_year_from_question,
)

_MONTH_WORDS = frozenset(
    list(_MONTHS.keys())
    + [month_english_name(i).lower() for i in range(1, 13)]
    + ["this", "last", "recent", "latest", "current"]
)
from services.name_filters import (
    extract_person_name,
    extract_student_name,
    is_likely_fake_name,
    normalize_person_name,
    student_name_sql_filter,
)

if TYPE_CHECKING:
    from services.db_service import DBService

_FEE_COLLECTION_RE = re.compile(
    r"\bfee\s+collection\b|\bfees?\s+(?:collected|paid)\b|\bpaid\s+fees?\b|"
    r"\bfee\s+payments?\b|\brecent\s+fee\b|\blatest\s+fee\s+payment",
    re.IGNORECASE,
)

_EFFECTIVE_PAYMENT_DATE = "COALESCE(jt.payment_date, DATE(sfd.created_at))"


def _json_payment_table() -> str:
    return (
        "JSON_TABLE(sfd.amount_detail, '$.*' COLUMNS ("
        "amt VARCHAR(50) PATH '$.amount', "
        "payment_date DATE PATH '$.date', "
        "payment_mode VARCHAR(50) PATH '$.payment_mode', "
        "amount_discount VARCHAR(50) PATH '$.amount_discount', "
        "inv_no VARCHAR(20) PATH '$.inv_no', "
        "collected_by VARCHAR(100) PATH '$.collected_by'"
        ")) AS jt"
    )


def _deposit_from_clause() -> str:
    return (
        "FROM student_fees_deposite sfd "
        "JOIN student_fees_master sfm ON sfm.id = sfd.student_fees_master_id "
        "JOIN fee_session_groups fsg ON fsg.id = sfm.fee_session_group_id "
        "JOIN fee_groups fg ON fg.id = fsg.fee_groups_id "
        "JOIN fee_groups_feetype fgf ON fgf.id = sfd.fee_groups_feetype_id "
        "JOIN feetype ft ON ft.id = fgf.feetype_id "
        "JOIN student_session ss ON ss.id = sfm.student_session_id "
        "JOIN students s ON s.id = ss.student_id "
        "JOIN classes c ON c.id = ss.class_id "
        "LEFT JOIN sections sec ON sec.id = ss.section_id, "
        f"{_json_payment_table()} "
    )


def matches_fee_collection_question(question: str) -> bool:
    q = (question or "").lower()
    if is_explain_concept_question(question) or re.search(r"\bexplain\b", q):
        return False
    if _FEE_COLLECTION_RE.search(q):
        return True
    if re.search(r"\bhow much\b", q) and "fee" in q and re.search(
        r"\b(?:collect|collected|collection|paid)\b", q
    ):
        return True
    if re.search(r"\bclass[\s-]?wise\b", q) and "fee" in q:
        return True
    return False


def _collection_month_clause(question: str) -> str:
    month = parse_month_from_question(question)
    year_expr = parse_year_from_question(question)
    year_sql = str(year_expr) if year_expr else "YEAR(CURDATE())"
    if month == 0:
        return (
            f" AND MONTH({_EFFECTIVE_PAYMENT_DATE}) = MONTH(CURDATE()) "
            f"AND YEAR({_EFFECTIVE_PAYMENT_DATE}) = YEAR(CURDATE())"
        )
    if month:
        return (
            f" AND MONTH({_EFFECTIVE_PAYMENT_DATE}) = {month} "
            f"AND YEAR({_EFFECTIVE_PAYMENT_DATE}) = {year_sql}"
        )
    return ""


def _grade_clause(question: str) -> str:
    m = re.search(r"\b(?:grade|class)\s*(\d+)\b", (question or "").lower())
    if not m:
        return ""
    grade = m.group(1)
    return (
        f" AND (c.class = '{grade}' OR c.class = 'Grade {grade}' "
        f"OR c.class = 'Class {grade}')"
    )


def _payment_limit(question: str, default: int = 50) -> int:
    m = re.search(r"\b(?:last|latest|recent)\s+(\d+)\b", (question or "").lower())
    if m:
        return min(max(int(m.group(1)), 1), 100)
    if re.search(r"\b(?:recent|latest)\b", (question or "").lower()):
        return 20
    return default


def _extract_student_for_fees(question: str) -> Optional[str]:
    q = question or ""
    for pat in (
        r"\bfees?\s+(?:paid|collected|payment)\s+(?:by|for|of)\s+([A-Za-z][A-Za-z\s\-']+)",
        r"\b(?:paid|collected)\s+fees?\s+(?:by|for|of)\s+([A-Za-z][A-Za-z\s\-']+)",
        r"\bshow\s+([A-Za-z][A-Za-z\s\-']+?)\s+(?:fee|paid|payment)",
        r"\b(?:full\s+)?fee\s+ledger\s+of\s+([A-Za-z][A-Za-z\s\-']+)",
        r"\bfee\s+records?\s+(?:of|for)\s+([A-Za-z][A-Za-z\s\-']+)",
    ):
        m = re.search(pat, q, re.IGNORECASE)
        if m:
            name = normalize_person_name(m.group(1))
            if len(name) >= 3 and name.lower() not in _MONTH_WORDS:
                if len(name.split()) >= 2 or not parse_month_from_question(name):
                    return name
    name = extract_student_name(question) or extract_person_name(question)
    if name and name.lower() not in _MONTH_WORDS:
        return name
    return None


def fee_collection_classwise_sql(question: str) -> str:
    month_sql = _collection_month_clause(question)
    return (
        "SELECT c.class AS class_name, COUNT(DISTINCT s.id) AS paying_students, "
        "COALESCE(SUM(CAST(jt.amt AS DECIMAL(10,2))), 0) AS total_collected "
        f"{_deposit_from_clause()}"
        f"WHERE s.is_active = 'yes' "
        f"AND CAST(jt.amt AS DECIMAL(10,2)) > 0{month_sql} "
        "GROUP BY c.class "
        "ORDER BY total_collected DESC "
        "LIMIT 50"
    )


def fee_collection_total_sql(question: str) -> str:
    month_sql = _collection_month_clause(question)
    grade_sql = _grade_clause(question)
    return (
        "SELECT COALESCE(SUM(CAST(jt.amt AS DECIMAL(10,2))), 0) AS total_collected, "
        f"COUNT(DISTINCT s.id) AS paying_students "
        f"{_deposit_from_clause()}"
        f"WHERE s.is_active = 'yes' "
        f"AND CAST(jt.amt AS DECIMAL(10,2)) > 0{month_sql}{grade_sql}"
    )


def fee_collection_detail_sql(question: str) -> str:
    month_sql = _collection_month_clause(question)
    grade_sql = _grade_clause(question)
    limit = _payment_limit(question)
    return (
        "SELECT s.admission_no, s.firstname, s.middlename, s.lastname, "
        "c.class AS class_name, sec.section AS section_name, "
        "fg.name AS fee_group, ft.type AS fee_type, "
        "CAST(jt.amt AS DECIMAL(10,2)) AS amount_paid, "
        "CAST(COALESCE(jt.amount_discount, '0') AS DECIMAL(10,2)) AS discount, "
        "jt.payment_mode, "
        f"{_EFFECTIVE_PAYMENT_DATE} AS payment_date, "
        "jt.inv_no, jt.collected_by "
        f"{_deposit_from_clause()}"
        f"WHERE s.is_active = 'yes' "
        f"AND CAST(jt.amt AS DECIMAL(10,2)) > 0{month_sql}{grade_sql} "
        f"ORDER BY {_EFFECTIVE_PAYMENT_DATE} DESC, s.firstname "
        f"LIMIT {limit}"
    )


def student_paid_fees_sql(student_name: str, question: str) -> str:
    name_filter = student_name_sql_filter(student_name)
    month_sql = _collection_month_clause(question)
    limit = _payment_limit(question, default=100)
    return (
        "SELECT s.admission_no, s.firstname, s.middlename, s.lastname, "
        "c.class AS class_name, sec.section AS section_name, "
        "fg.name AS fee_group, ft.type AS fee_type, "
        "CAST(jt.amt AS DECIMAL(10,2)) AS amount_paid, "
        "CAST(COALESCE(jt.amount_discount, '0') AS DECIMAL(10,2)) AS discount, "
        "jt.payment_mode, "
        f"{_EFFECTIVE_PAYMENT_DATE} AS payment_date, "
        "jt.inv_no, jt.collected_by "
        f"{_deposit_from_clause()}"
        f"WHERE {name_filter} "
        f"AND CAST(jt.amt AS DECIMAL(10,2)) > 0{month_sql} "
        f"ORDER BY {_EFFECTIVE_PAYMENT_DATE} DESC "
        f"LIMIT {limit}"
    )


def recent_fee_payments_sql(question: str) -> str:
    return fee_collection_detail_sql(question)


def _list_collection_months(db: "DBService") -> list[tuple[int, int, int]]:
    sql = (
        "SELECT YEAR(pd) AS y, MONTH(pd) AS m, COUNT(*) AS cnt FROM ("
        f"SELECT {_EFFECTIVE_PAYMENT_DATE} AS pd "
        "FROM student_fees_deposite sfd, "
        f"{_json_payment_table()} "
        "WHERE sfd.amount_detail IS NOT NULL AND sfd.amount_detail != '' "
        f"AND CAST(jt.amt AS DECIMAL(10,2)) > 0"
        ") AS payments "
        "GROUP BY y, m ORDER BY y DESC, m DESC LIMIT 8"
    )
    rows, _, err = db.execute(sql, max_rows=8)
    if err or not rows:
        return []
    return [(int(r[0]), int(r[1]), int(r[2])) for r in rows]


def fee_collection_empty_message(db: "DBService", question: str) -> Optional[str]:
    """Helpful empty-state when a month/class collection query has no rows."""
    if not matches_fee_collection_question(question):
        return None
    q = (question or "").lower()
    month = parse_month_from_question(question)
    year = parse_year_from_question(question)
    student = _extract_student_for_fees(question)
    if student and not is_likely_fake_name(student):
        month_label = ""
        if month and month != 0:
            month_label = f" in **{month_english_name(month)} {year or 'this year'}**"
        elif month == 0:
            month_label = " **this month**"
        return (
            f"No **fee payments** were found for **{student}**{month_label}. "
            "Try **show full fee ledger of {student}** for assigned fees and balances, "
            "or ask without a month filter to see all past payments."
        ).replace("{student}", student)

    if month is None:
        if re.search(r"\bclass[\s-]?wise\b", q):
            return (
                "No **class-wise fee collection** totals were found. "
                "If you meant a specific month, try e.g. **show May fee collection class-wise**."
            )
        return None

    month_num = month if month != 0 else date.today().month
    year_num = year or date.today().year
    label = f"**{month_english_name(month_num)} {year_num}**"
    available = _list_collection_months(db)
    if not available:
        return f"No fee collections are recorded in the system yet for {label}."

    parts = [
        f"{month_english_name(m)} {y} ({c} payment{'s' if c != 1 else ''})"
        for y, m, c in available[:4]
    ]
    suggest = available[0]
    suggest_q = (
        f"show {month_english_name(suggest[1]).lower()} fee collection class-wise"
        if re.search(r"\bclass[\s-]?wise\b", q)
        else f"show fee collection for {month_english_name(suggest[1]).lower()}"
    )
    return (
        f"No fee collections were recorded in {label} yet. "
        f"Collections on file: {', '.join(parts)}. "
        f"Try **{suggest_q}** or **show recent fee payments**."
    )


def try_fee_collection_sql(db: "DBService", question: str) -> Optional[str]:
    q = (question or "").lower()
    if is_explain_concept_question(question) or re.search(r"\bexplain\b", q):
        return None
    if not matches_fee_collection_question(question):
        return None

    if re.search(r"\bclass[\s-]?wise\b", q):
        return fee_collection_classwise_sql(question)

    student = _extract_student_for_fees(question)
    if student and not is_likely_fake_name(student):
        if re.search(r"\b(?:paid|payment|collected|records?)\b", q) or "fee" in q:
            return student_paid_fees_sql(student, question)

    if re.search(r"\b(?:recent|latest|last)\b", q) and re.search(
        r"\b(?:fee|payment|collect)", q
    ):
        return recent_fee_payments_sql(question)

    if re.search(r"\bhow much\b|\btotal\b", q) and re.search(
        r"\b(?:collect|collected|collection|paid)\b", q
    ):
        return fee_collection_total_sql(question)

    if re.search(r"\bfee\s+collection\b", q) or parse_month_from_question(question):
        return fee_collection_detail_sql(question)

    if re.search(r"\bfees?\s+(?:paid|collected)\b|\bpaid\s+fees?\b", q):
        return fee_collection_detail_sql(question)

    return None
