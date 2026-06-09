"""
Fee due / remaining logic — mirrors Studentfee::feesearch post-processing in PHP.
"""

from __future__ import annotations

import json
import re
from typing import TYPE_CHECKING, Optional

from services.date_filters import parse_month_from_question, sql_date_filter
from services.concept_answers import is_explain_concept_question
from services.fee_collection_sql import (
    fee_collection_classwise_sql,
    try_fee_collection_sql,
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

_FEE_DUE_RE = re.compile(
    r"\b(?:remaining|pending|due|unpaid|outstanding|balance)\b.*\bfee",
    re.IGNORECASE,
)
_FEE_DUE_RE2 = re.compile(
    r"\bfee\b.*\b(?:remaining|pending|due|unpaid|outstanding|balance)\b",
    re.IGNORECASE,
)
_COLLECTED_RE = re.compile(
    r"\b(?:collected|collection|paid|payment)\b.*\bfee|\bfee\b.*\b(?:collected|collection)\b",
    re.IGNORECASE,
)
_PAYMENT_ID_RE = re.compile(
    r"(?:payment\s*id|search\s+payment\s*id)\s*(\d+)\s*/\s*(\d+)\b",
    re.IGNORECASE,
)
_PAYMENT_ID_LOOSE_RE = re.compile(r"\b(\d+)\s*/\s*(\d+)\b")
_QUICK_FEE_RE = re.compile(r"\bquick\s+fee", re.IGNORECASE)
_DISCOUNT_RE = re.compile(
    r"\b(?:active\s+)?fee\s+discount|\bdiscount.*\bfee|\bfinancial\s+discount",
    re.IGNORECASE,
)
_OFFLINE_PENDING_RE = re.compile(
    r"\b(?:offline\s+bank\s+payment|offline\s+payment|bank\s+payment)\b.*\bpending\b|"
    r"\bpending\b.*\b(?:offline\s+bank\s+payment|offline\s+payment|offline\s+bank)\b|"
    r"\b(?:which|what)\s+student\b.*\boffline\b.*\bpending\b|"
    r"\boffline\s+bank\b.*\bpending\b",
    re.IGNORECASE,
)


def matches_offline_bank_pending_question(question: str) -> bool:
    return bool(_OFFLINE_PENDING_RE.search(question or ""))


def students_receiving_financial_discount_sql() -> str:
    """Assigned fee discounts from student_fees_discounts (not deposit column)."""
    return (
        "SELECT s.admission_no, s.firstname, s.middlename, s.lastname, "
        "c.class AS class_name, sec.section AS section_name, "
        "fd.name AS discount_name, fd.code AS discount_code, "
        "fd.type AS discount_type, fd.percentage, fd.amount, "
        "COALESCE(NULLIF(sfdisc.description, ''), fd.description, fd.name) AS discount_reason, "
        "sfdisc.status AS assignment_status "
        "FROM student_fees_discounts sfdisc "
        "JOIN fees_discounts fd ON fd.id = sfdisc.fees_discount_id "
        "JOIN student_session ss ON ss.id = sfdisc.student_session_id "
        "JOIN students s ON s.id = ss.student_id "
        "JOIN classes c ON c.id = ss.class_id "
        "JOIN sections sec ON sec.id = ss.section_id "
        "WHERE s.is_active = 'yes' "
        "ORDER BY s.firstname, fd.name "
        "LIMIT 50"
    )


def student_fee_ledger_sql(student_name: str) -> str:
    """Full fee ledger for one student — assigned lines + deposit JSON."""
    name_filter = student_name_sql_filter(student_name)
    return (
        "SELECT s.admission_no, s.firstname, s.middlename, s.lastname, "
        "c.class AS class_name, sec.section AS section_name, "
        "fg.name AS fee_group, ft.type AS fee_type, "
        "CASE WHEN fg.is_system = 1 THEN sfm.amount ELSE fgf.amount END AS due_amount, "
        "sfd.amount_detail, sfd.created_at AS last_payment_at "
        "FROM student_fees_master sfm "
        "INNER JOIN fee_session_groups fsg ON fsg.id = sfm.fee_session_group_id "
        "INNER JOIN fee_groups fg ON fg.id = fsg.fee_groups_id AND fg.nature != 'custom' "
        "INNER JOIN fee_groups_feetype fgf ON fgf.fee_session_group_id = sfm.fee_session_group_id "
        "INNER JOIN feetype ft ON ft.id = fgf.feetype_id "
        "LEFT JOIN student_fees_deposite sfd ON sfd.student_fees_master_id = sfm.id "
        "AND sfd.fee_groups_feetype_id = fgf.id "
        "INNER JOIN student_session ss ON ss.id = sfm.student_session_id "
        "INNER JOIN students s ON s.id = ss.student_id "
        "INNER JOIN classes c ON c.id = ss.class_id "
        "LEFT JOIN sections sec ON sec.id = ss.section_id "
        f"WHERE {name_filter} "
        "ORDER BY fg.name, ft.type, sfd.created_at DESC "
        "LIMIT 200"
    )


def active_fee_discounts_sql(*, active_only: bool = True) -> str:
    """
    TaleemX fees_discounts uses varchar is_active ('yes'/'no') but the admin UI
    never maintains it — "active" means not past expire_date (same as assign screen).
    """
    expiry_filter = (
        " AND (fd.expire_date IS NULL OR fd.expire_date >= CURDATE())"
        if active_only
        else ""
    )
    return (
        "SELECT fd.name AS discount_name, fd.code, fd.type AS discount_type, "
        "fd.percentage, fd.amount, fd.discount_limit, fd.expire_date "
        "FROM fees_discounts fd "
        f"WHERE 1=1{expiry_filter} "
        "ORDER BY fd.name LIMIT 50"
    )


def offline_bank_pending_sql() -> str:
    """Pending offline fee bank payments — is_active '0' (varchar), join via student_session."""
    return (
        "SELECT CONCAT_WS(' ', s.firstname, s.middlename, s.lastname) AS student_name, "
        "s.admission_no, c.class AS class_name, sec.section AS section_name, "
        "ofp.amount, ofp.payment_date, ofp.bank_from, ofp.bank_account_transferred, "
        "ofp.reference, ofp.submit_date, "
        "CASE "
        "WHEN ofp.is_active IN ('0', 0) THEN 'Pending' "
        "WHEN ofp.is_active IN ('1', 1) THEN 'Approved' "
        "WHEN ofp.is_active IN ('2', 2) THEN 'Rejected' "
        "ELSE COALESCE(ofp.is_active, 'Unknown') END AS payment_status "
        "FROM offline_fees_payments ofp "
        "JOIN student_session ss ON ss.id = ofp.student_session_id "
        "JOIN students s ON s.id = ss.student_id "
        "LEFT JOIN classes c ON c.id = ss.class_id "
        "LEFT JOIN sections sec ON sec.id = ss.section_id "
        "WHERE ofp.is_active IN ('0', 0) "
        "ORDER BY ofp.submit_date DESC, ofp.id DESC "
        "LIMIT 50"
    )
_FEE_GROUP_RE = re.compile(
    r"fee\s+group\s+[\"']?([^\"']+?)[\"']?\s*(?:if|$|\?)",
    re.IGNORECASE,
)
_TRANSPORT_STATUS_RE = re.compile(
    r"\btransport\b.*\bfee|\bfee\b.*\btransport\b",
    re.IGNORECASE,
)
_MONTH_NAMES = {
    "january": 1, "february": 2, "march": 3, "april": 4, "may": 5, "june": 6,
    "july": 7, "august": 8, "september": 9, "october": 10, "november": 11, "december": 12,
    "apr": 4, "jun": 6,
}


def matches_fee_due_question(question: str) -> bool:
    q = question or ""
    if _FEE_DUE_RE.search(q) or _FEE_DUE_RE2.search(q):
        return True
    if "search due fee" in q.lower() or "feesearch" in q.lower():
        return True
    if "list students" in q.lower() and "pending fee" in q.lower():
        return True
    if "students with pending fee" in q.lower() or "pending fees" in q.lower():
        return True
    if "how many students" in q.lower() and "fee" in q.lower() and "remaining" in q.lower():
        return True
    if "total remaining fee" in q.lower() or "total outstanding" in q.lower():
        return True
    if "remaining fees" in q.lower() and "student" in q.lower():
        return True
    return False


def _parse_amount_detail(raw) -> tuple[float, float]:
    """Return (paid, discount) summed from amount_detail JSON."""
    if not raw:
        return 0.0, 0.0
    try:
        data = json.loads(raw) if isinstance(raw, str) else raw
    except (json.JSONDecodeError, TypeError):
        return 0.0, 0.0
    if not isinstance(data, dict):
        return 0.0, 0.0
    paid = 0.0
    discount = 0.0
    for entry in data.values():
        if not isinstance(entry, dict):
            continue
        try:
            paid += float(entry.get("amount") or 0)
            discount += float(entry.get("amount_discount") or 0)
        except (TypeError, ValueError):
            continue
    return paid, discount


def _parse_payment_entry(raw, inv_no: str) -> Optional[dict]:
    if not raw:
        return None
    try:
        data = json.loads(raw) if isinstance(raw, str) else raw
    except (json.JSONDecodeError, TypeError):
        return None
    if not isinstance(data, dict):
        return None
    target = str(inv_no).strip()
    for entry in data.values():
        if isinstance(entry, dict) and str(entry.get("inv_no", "")).strip() == target:
            return entry
    return None


def _esc(s: str) -> str:
    return (s or "").replace("'", "''")


def _student_name_filter(question: str) -> Optional[str]:
    q = question or ""
    for pat in (
        r"\b(?:full\s+)?fee\s+ledger\s+of\s+([A-Za-z][A-Za-z\s\-']+)",
    ):
        m = re.search(pat, q, re.IGNORECASE)
        if m:
            name = normalize_person_name(m.group(1))
            if name and len(name) >= 3:
                return name
    name = extract_student_name(question) or extract_person_name(question)
    if name:
        low = name.lower()
        if low not in ("grade", "class", "april", "june", "this month", "transport fees"):
            return name
    return None


def _grade_filter(question: str) -> Optional[str]:
    m = re.search(r"\b(?:grade|class)\s*(\d+)\b", (question or "").lower())
    return m.group(1) if m else None


def _month_filter_sql(question: str, date_col: str = "sfd.created_at") -> str:
    df = sql_date_filter(date_col, question)
    if df.kind in ("month", "this_month") and df.sql_on_column:
        return f" AND {df.sql_on_column}"
    q = (question or "").lower()
    for name, num in _MONTH_NAMES.items():
        if re.search(rf"\b{name}\b", q):
            return f" AND MONTH({date_col}) = {num} AND YEAR({date_col}) = YEAR(CURDATE())"
    return ""


def _fee_group_exists(db: "DBService", group_name: str) -> bool:
    sn = _esc(group_name.strip())
    rows, _, err = db.execute(
        f"SELECT 1 FROM fee_groups WHERE LOWER(name) LIKE LOWER('%{sn}%') LIMIT 1",
        max_rows=1,
    )
    return bool(not err and rows)


def resolve_payment_id_rows(
    db: "DBService", question: str
) -> Optional[tuple[list, list, str]]:
    m = _PAYMENT_ID_RE.search(question or "")
    if not m:
        q = (question or "").lower()
        if "payment" in q and _PAYMENT_ID_LOOSE_RE.search(question or ""):
            m = _PAYMENT_ID_LOOSE_RE.search(question or "")
        elif not re.search(r"\b(?:payment\s*id|show\s+payment|search\s+payment)\b", q):
            return None
        else:
            m = _PAYMENT_ID_LOOSE_RE.search(question or "")
    if not m:
        return None

    dep_id, inv = int(m.group(1)), m.group(2)
    name = _student_name_filter(question)
    name_sql = ""
    if name:
        sn = _esc(name)
        name_sql = (
            f" AND (LOWER(CONCAT_WS(' ', s.firstname, s.lastname)) LIKE LOWER('%{sn}%'))"
        )

    sql = (
        "SELECT sfd.id AS deposit_id, s.firstname, s.lastname, s.admission_no, "
        "sfd.amount_detail, fg.name AS fee_group, ft.type AS fee_type, sfd.created_at "
        "FROM student_fees_deposite sfd "
        "LEFT JOIN student_fees_master sfm ON sfm.id = sfd.student_fees_master_id "
        "LEFT JOIN fee_session_groups fsg ON fsg.id = sfm.fee_session_group_id "
        "LEFT JOIN fee_groups fg ON fg.id = fsg.fee_groups_id "
        "LEFT JOIN fee_groups_feetype fgf ON fgf.id = sfd.fee_groups_feetype_id "
        "LEFT JOIN feetype ft ON ft.id = fgf.feetype_id "
        "LEFT JOIN student_session ss ON ss.id = sfm.student_session_id "
        "LEFT JOIN students s ON s.id = ss.student_id "
        f"WHERE sfd.id = {dep_id}{name_sql} "
        "LIMIT 5"
    )
    rows, _, err = db.execute(sql, max_rows=5)
    if err or not rows:
        return [], [], sql

    out = []
    for row in rows:
        entry = _parse_payment_entry(row[4], inv)
        if not entry:
            continue
        out.append((
            f"{dep_id}/{inv}",
            row[1], row[2], row[3],
            entry.get("amount"), entry.get("amount_discount"),
            entry.get("amount_fine"), entry.get("date"),
            row[5], row[6], row[7],
        ))
    cols = [
        "payment_id", "student_firstname", "student_lastname", "admission_no",
        "amount_paid", "discount", "fine", "payment_date",
        "fee_group", "fee_type", "recorded_at",
    ]
    return cols, out, sql


def resolve_transport_fee_status(
    db: "DBService", question: str
) -> Optional[tuple[list, list, str]]:
    q = (question or "").lower()
    if not _TRANSPORT_STATUS_RE.search(q):
        return None
    if not re.search(r"\b(?:paid|unpaid|remaining|due|pending)\b", q):
        return None

    name = _student_name_filter(question)
    if not name:
        return None

    month_label = None
    for label in _MONTH_NAMES:
        if re.search(rf"\b{label}\b", q):
            month_label = label.capitalize() if len(label) > 3 else {"apr": "April", "jun": "June"}.get(label, label)
            break

    sn = _esc(name)
    month_sql = ""
    if month_label:
        month_sql = f" AND LOWER(tf.month) LIKE LOWER('%{_esc(month_label[:3])}%')"

    sql = (
        "SELECT s.firstname, s.lastname, s.admission_no, tf.month AS fee_month, "
        "rp.fees AS due_amount, sfd.amount_detail "
        "FROM student_transport_fees stf "
        "JOIN transport_feemaster tf ON tf.id = stf.transport_feemaster_id "
        "JOIN route_pickup_point rp ON rp.id = stf.route_pickup_point_id "
        "LEFT JOIN student_fees_deposite sfd ON sfd.student_transport_fee_id = stf.id "
        "JOIN student_session ss ON ss.id = stf.student_session_id "
        "JOIN students s ON s.id = ss.student_id "
        f"WHERE s.is_active = 'yes' "
        f"AND LOWER(CONCAT_WS(' ', s.firstname, s.lastname)) LIKE LOWER('%{sn}%')"
        f"{month_sql} "
        "ORDER BY tf.month LIMIT 10"
    )
    rows, _, err = db.execute(sql, max_rows=10)
    if err:
        return [], [], sql
    if not rows:
        return [], [], sql

    out = []
    for row in rows:
        due = float(row[4] or 0)
        paid, discount = _parse_amount_detail(row[5])
        balance = round(due - (paid + discount), 2)
        status = "paid" if balance <= 0.01 else "unpaid"
        out.append((
            row[0], row[1], row[2], row[3], due, paid, discount, balance, status,
        ))
    cols = [
        "student_firstname", "student_lastname", "admission_no", "fee_month",
        "due_amount", "paid_amount", "discount_amount", "remaining_balance", "status",
    ]
    return cols, out, sql


def fee_group_missing_message(db: "DBService", question: str) -> Optional[str]:
    gm = _FEE_GROUP_RE.search(question or "")
    if not gm:
        return None
    group = gm.group(1).strip()
    if not group or _fee_group_exists(db, group):
        return None
    return f"No fee group named **{group}** exists in your school. Check **Fee Collection → Fee Group** for configured groups."


def try_fee_sql(db: "DBService", question: str) -> Optional[str]:
    """Pure SQL paths that don't need JSON post-processing."""
    q = (question or "").lower()

    gm = _FEE_GROUP_RE.search(question or "")
    if gm and re.search(r"\b(?:assigned|students?)\b", q):
        group = gm.group(1).strip()
        if group and not _fee_group_exists(db, group):
            return None

    if matches_offline_bank_pending_question(question):
        return offline_bank_pending_sql()

    if re.search(r"\b(?:full\s+)?fee\s+ledger\b", q):
        name = _student_name_filter(question)
        if name and not is_likely_fake_name(name):
            return student_fee_ledger_sql(name)

    collection_sql = try_fee_collection_sql(db, question)
    if collection_sql:
        return collection_sql

    if _DISCOUNT_RE.search(q):
        if (
            "financial discount" in q
            or "sibling" in q
            or "receiving" in q
            or ("discount" in q and "student" in q and "reason" in q)
        ):
            return students_receiving_financial_discount_sql()
        active_only = "active" in q or "valid" in q or "current" in q
        return active_fee_discounts_sql(active_only=active_only)

    if _QUICK_FEE_RE.search(q):
        name = _student_name_filter(question)
        name_f = ""
        if name:
            sn = _esc(name)
            name_f = (
                f" AND (LOWER(CONCAT_WS(' ', s.firstname, s.lastname)) LIKE LOWER('%{sn}%'))"
            )
        return (
            "SELECT s.firstname, s.lastname, s.admission_no, fg.name AS fee_group, "
            "ft.type AS fee_type, sfm.amount AS fee_amount, sfd.amount_detail "
            "FROM student_fees_master sfm "
            "JOIN fee_session_groups fsg ON fsg.id = sfm.fee_session_group_id "
            "JOIN fee_groups fg ON fg.id = fsg.fee_groups_id AND fg.nature = 'custom' "
            "JOIN fee_groups_feetype fgf ON fgf.fee_session_group_id = sfm.fee_session_group_id "
            "JOIN feetype ft ON ft.id = fgf.feetype_id "
            "LEFT JOIN student_fees_deposite sfd ON sfd.student_fees_master_id = sfm.id "
            "AND sfd.fee_groups_feetype_id = fgf.id "
            "JOIN student_session ss ON ss.id = sfm.student_session_id "
            "JOIN students s ON s.id = ss.student_id "
            f"WHERE s.is_active = 'yes'{name_f} "
            "ORDER BY s.firstname LIMIT 50"
        )

    if re.search(r"\bfee\s+payment\s+history\b|\bpayment\s+history\b", q):
        name = _student_name_filter(question)
        if name and not is_likely_fake_name(name):
            from services.fee_collection_sql import student_paid_fees_sql

            return student_paid_fees_sql(name, question)

    return None


def _accumulate_due_row(out_rows, total_outstanding, students_with_due, row, due_idx=7, detail_idx=8):
    due = float(row[due_idx] or 0)
    paid, discount = _parse_amount_detail(row[detail_idx])
    balance = due - (paid + discount)
    if balance <= 0.01:
        return out_rows, total_outstanding, students_with_due
    out_rows.append((
        row[0], row[1], row[2], row[3], row[4], row[5], row[6],
        round(due, 2), round(paid, 2), round(discount, 2), round(balance, 2),
    ))
    total_outstanding += balance
    students_with_due.add(row[2])
    return out_rows, total_outstanding, students_with_due


def compute_fee_due_rows(db: "DBService", question: str) -> tuple[list, list, str]:
    """
    PHP feesearch-style balance calculation in Python (regular + transport fees).
    Returns (columns, rows, sql_note).
    """
    grade = _grade_filter(question)
    name = _student_name_filter(question)
    if name and is_likely_fake_name(name):
        return [], [], ""

    grade_sql = ""
    if grade:
        grade_sql = (
            f" AND (c.class = '{grade}' OR c.class = 'Grade {grade}' "
            f"OR c.class = 'Class {grade}')"
        )
    name_sql = ""
    if name:
        sn = _esc(name)
        name_sql = (
            f" AND (LOWER(CONCAT_WS(' ', s.firstname, s.lastname)) LIKE LOWER('%{sn}%'))"
        )

    sql = (
        "SELECT s.firstname, s.lastname, s.admission_no, c.class AS class_name, "
        "sec.section AS section_name, fg.name AS fee_group, ft.type AS fee_type, "
        "CASE WHEN fg.is_system = 1 THEN sfm.amount ELSE fgf.amount END AS due_amount, "
        "sfd.amount_detail "
        "FROM student_fees_master sfm "
        "INNER JOIN fee_session_groups fsg ON fsg.id = sfm.fee_session_group_id "
        "INNER JOIN fee_groups fg ON fg.id = fsg.fee_groups_id AND fg.nature != 'custom' "
        "INNER JOIN fee_groups_feetype fgf ON fgf.fee_session_group_id = sfm.fee_session_group_id "
        "INNER JOIN feetype ft ON ft.id = fgf.feetype_id "
        "LEFT JOIN student_fees_deposite sfd ON sfd.student_fees_master_id = sfm.id "
        "AND sfd.fee_groups_feetype_id = fgf.id "
        "INNER JOIN student_session ss ON ss.id = sfm.student_session_id "
        "INNER JOIN students s ON s.id = ss.student_id "
        "INNER JOIN classes c ON c.id = ss.class_id "
        "LEFT JOIN sections sec ON sec.id = ss.section_id "
        f"WHERE s.is_active = 'yes'{grade_sql}{name_sql} "
        "ORDER BY s.firstname, fg.name LIMIT 500"
    )
    raw_rows, _, err = db.execute(sql, max_rows=500)

    transport_sql = (
        "SELECT s.firstname, s.lastname, s.admission_no, c.class AS class_name, "
        "sec.section AS section_name, 'Transport Fees' AS fee_group, tf.month AS fee_type, "
        "rp.fees AS due_amount, sfd.amount_detail "
        "FROM student_transport_fees stf "
        "JOIN transport_feemaster tf ON tf.id = stf.transport_feemaster_id "
        "JOIN route_pickup_point rp ON rp.id = stf.route_pickup_point_id "
        "LEFT JOIN student_fees_deposite sfd ON sfd.student_transport_fee_id = stf.id "
        "JOIN student_session ss ON ss.id = stf.student_session_id "
        "JOIN students s ON s.id = ss.student_id "
        "JOIN classes c ON c.id = ss.class_id "
        "LEFT JOIN sections sec ON sec.id = ss.section_id "
        f"WHERE s.is_active = 'yes'{grade_sql}{name_sql} "
        "ORDER BY s.firstname LIMIT 500"
    )
    transport_rows, _, terr = db.execute(transport_sql, max_rows=500)

    if err and terr:
        return [], [], sql

    out_rows = []
    total_outstanding = 0.0
    students_with_due = set()

    for row in raw_rows or []:
        out_rows, total_outstanding, students_with_due = _accumulate_due_row(
            out_rows, total_outstanding, students_with_due, row
        )
    for row in transport_rows or []:
        out_rows, total_outstanding, students_with_due = _accumulate_due_row(
            out_rows, total_outstanding, students_with_due, row
        )

    columns = [
        "student_firstname", "student_lastname", "admission_no", "class_name",
        "section_name", "fee_group", "fee_type", "due_amount", "paid_amount",
        "discount_amount", "remaining_balance",
    ]

    q = (question or "").lower()
    if "how many" in q and "student" in q:
        return (
            ["students_with_remaining_fees"],
            [(len(students_with_due),)],
            sql + " | " + transport_sql,
        )
    if "total remaining" in q or "total outstanding" in q:
        return (
            ["total_outstanding_fee", "students_with_balance"],
            [(round(total_outstanding, 2), len(students_with_due))],
            sql + " | " + transport_sql,
        )

    return columns, out_rows[:50], sql + " | " + transport_sql
