"""
Transport module SQL — aligned with TaleemX transport schema:

  transport_route        (id, route_title)
  pickup_point           (id, name, latitude, longitude)
  route_pickup_point     (id, transport_route_id, pickup_point_id, fees,
                          destination_distance, pickup_time, order_number, session_id)
  vehicles               (id, vehicle_no, vehicle_model, driver_name,
                          driver_licence, driver_contact)
  vehicle_routes         (id, route_id, vehicle_id)
  transport_feemaster    (id, session_id, month, monthly_fees, due_date,
                          fine_type, fine_amount, fine_percentage)
  student_transport_fees (id, student_session_id, transport_feemaster_id,
                          route_pickup_point_id)

Transport fee balance mirrors Studentfee::feesearch: the per-month due comes from
route_pickup_point.fees and paid/discount come from student_fees_deposite.amount_detail.
"""

from __future__ import annotations

import re
from typing import TYPE_CHECKING, Optional

from services.name_filters import extract_student_name, extract_person_name

if TYPE_CHECKING:
    from services.db_service import DBService


_TRANSPORT_RE = re.compile(r"\btransport(?:ation)?\b|\bschool\s+vehicle", re.IGNORECASE)
_STATUS_RE = re.compile(
    r"\b(?:paid|unpaid|pending|outstanding|remaining|due|balance|collected|status)\b",
    re.IGNORECASE,
)
_MONTH_NAMES = {
    "january": 1, "february": 2, "march": 3, "april": 4, "may": 5, "june": 6,
    "july": 7, "august": 8, "september": 9, "october": 10, "november": 11,
    "december": 12, "jan": 1, "feb": 2, "mar": 3, "apr": 4, "jun": 6, "jul": 7,
    "aug": 8, "sep": 9, "sept": 9, "oct": 10, "nov": 11, "dec": 12,
}


def _esc(s: str) -> str:
    return (s or "").replace("'", "''")


def is_transport_question(question: str) -> bool:
    return bool(_TRANSPORT_RE.search(question or ""))


def _route_name_filter(question: str) -> Optional[str]:
    """Extract the route title from 'pickup points for Route 1 - Olaya'."""
    q = question or ""
    m = re.search(
        r"(?:for|of|in|on)\s+(route\s+[A-Za-z0-9][A-Za-z0-9\s\-/]*?)\s*[.?!]*\s*$",
        q,
        re.IGNORECASE,
    )
    if not m:
        m = re.search(
            r"\b(route\s+[A-Za-z0-9][A-Za-z0-9\s\-/]*?)\s*[.?!]*\s*$",
            q,
            re.IGNORECASE,
        )
    if not m:
        return None
    name = " ".join(m.group(1).split()).strip(" .?!,")
    return name or None


def _route_match_clause(name: str, col: str = "tr.route_title") -> str:
    n = _esc(name)
    return (
        f"(LOWER({col}) = LOWER('{n}') "
        f"OR LOWER({col}) LIKE LOWER('{n} -%') "
        f"OR LOWER({col}) LIKE LOWER('{n}-%') "
        f"OR LOWER({col}) LIKE LOWER('{n} %'))"
    )


def transport_fee_structures_sql() -> str:
    """Route → pickup point monthly fee structure (the amounts students pay)."""
    return (
        "SELECT tr.route_title, pp.name AS pickup_point, rpp.fees AS monthly_fee, "
        "rpp.destination_distance, rpp.pickup_time "
        "FROM route_pickup_point rpp "
        "JOIN transport_route tr ON tr.id = rpp.transport_route_id "
        "JOIN pickup_point pp ON pp.id = rpp.pickup_point_id "
        "ORDER BY tr.route_title, rpp.order_number "
        "LIMIT 100"
    )


def transport_fee_master_sql() -> str:
    """Monthly transport fee master (Transport → Transport Fees Master)."""
    return (
        "SELECT tfm.month, tfm.monthly_fees, tfm.due_date, tfm.fine_type, "
        "tfm.fine_amount, tfm.fine_percentage "
        "FROM transport_feemaster tfm "
        "ORDER BY tfm.id "
        "LIMIT 100"
    )


def pickup_points_sql(route_name: Optional[str]) -> str:
    if route_name:
        where = _route_match_clause(route_name)
        return (
            "SELECT tr.route_title, pp.name AS pickup_point, rpp.fees AS monthly_fee, "
            "rpp.destination_distance, rpp.pickup_time, rpp.order_number "
            "FROM route_pickup_point rpp "
            "JOIN transport_route tr ON tr.id = rpp.transport_route_id "
            "JOIN pickup_point pp ON pp.id = rpp.pickup_point_id "
            f"WHERE {where} "
            "ORDER BY rpp.order_number "
            "LIMIT 100"
        )
    return (
        "SELECT pp.name AS pickup_point, pp.latitude, pp.longitude "
        "FROM pickup_point pp "
        "ORDER BY pp.name "
        "LIMIT 100"
    )


def transport_routes_sql(*, active_only: bool = False) -> str:
    """Transport routes with pickup-point and vehicle counts."""
    where = ""
    if active_only:
        where = (
            "WHERE (EXISTS (SELECT 1 FROM route_pickup_point rpp "
            "WHERE rpp.transport_route_id = tr.id) "
            "OR EXISTS (SELECT 1 FROM vehicle_routes vr WHERE vr.route_id = tr.id)) "
        )
    return (
        "SELECT tr.id AS route_id, tr.route_title, "
        "(SELECT COUNT(*) FROM route_pickup_point rpp "
        "WHERE rpp.transport_route_id = tr.id) AS pickup_points, "
        "(SELECT COUNT(*) FROM vehicle_routes vr WHERE vr.route_id = tr.id) AS vehicles "
        "FROM transport_route tr "
        f"{where}"
        "ORDER BY tr.route_title "
        "LIMIT 100"
    )


def vehicles_sql() -> str:
    return (
        "SELECT v.vehicle_no, v.vehicle_model, v.driver_name, v.driver_contact, "
        "v.driver_licence "
        "FROM vehicles v "
        "ORDER BY v.vehicle_no "
        "LIMIT 100"
    )


def try_transport_sql(question: str) -> Optional[str]:
    """High-confidence transport SELECTs that need no JSON post-processing."""
    q = (question or "").lower().strip()
    if not q:
        return None

    # School vehicles / fleet.
    if re.search(r"\b(?:vehicle|vehicles|bus|buses|fleet)\b", q) and not (
        "route" in q and "vehicle" not in q
    ):
        if re.search(r"\b(?:vehicle|vehicles|bus|buses|fleet|driver|drivers)\b", q):
            return vehicles_sql()

    if not is_transport_question(question) and "pickup" not in q and "route" not in q:
        return None

    # Pickup points (optionally scoped to a named route).
    if "pickup" in q or "pick up" in q or "pick-up" in q or "stop" in q:
        return pickup_points_sql(_route_name_filter(question))

    # Transport fee structure / fee master.
    if "transport" in q and ("fee structure" in q or "fee structures" in q or (
        "fee" in q and "structure" in q
    )):
        return transport_fee_structures_sql()
    if "transport" in q and ("fee master" in q or "fees master" in q):
        return transport_fee_master_sql()

    # Transport routes.
    if "route" in q:
        active = "active" in q or "available" in q
        return transport_routes_sql(active_only=active)

    return None


def _parse_amount_detail(raw) -> tuple[float, float]:
    import json

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


def _is_transport_fee_status_question(question: str) -> bool:
    q = (question or "").lower()
    if not is_transport_question(question):
        return False
    if "fee" not in q and "fees" not in q:
        return False
    return bool(_STATUS_RE.search(q))


def resolve_transport_fee_status_all(
    db: "DBService", question: str
) -> Optional[tuple[list, list, str]]:
    """
    Transport fee paid/unpaid for all students (or pending-only).

    Returns (columns, rows, sql_note). Defers (returns None) when the question
    targets a single named student — fee_due_engine.resolve_transport_fee_status
    handles that precise per-student/month case.
    """
    q = (question or "").lower()
    if not _is_transport_fee_status_question(question):
        return None

    # Defer named-student lookups to the precise resolver.
    name = extract_student_name(question) or extract_person_name(question)
    if name and name.lower() not in ("transport", "transport fees", "transport fee"):
        return None

    pending_only = bool(
        re.search(r"\b(?:pending|unpaid|outstanding|remaining|due)\b", q)
        and "paid and unpaid" not in q
        and "paid & unpaid" not in q
        and "both" not in q
    )

    month_sql = ""
    for label, _num in _MONTH_NAMES.items():
        if re.search(rf"\b{label}\b", q):
            month_sql = f" AND LOWER(tf.month) LIKE LOWER('%{_esc(label[:3])}%')"
            break

    grade_sql = ""
    gm = re.search(r"\b(?:grade|class)\s*(\d+)\b", q)
    if gm:
        g = _esc(gm.group(1))
        grade_sql = (
            f" AND (c.class = '{g}' OR c.class = 'Grade {g}' OR c.class = 'Class {g}')"
        )

    sql = (
        "SELECT s.firstname, s.lastname, s.admission_no, c.class AS class_name, "
        "sec.section AS section_name, tf.month AS fee_month, "
        "rp.fees AS due_amount, sfd.amount_detail "
        "FROM student_transport_fees stf "
        "JOIN transport_feemaster tf ON tf.id = stf.transport_feemaster_id "
        "JOIN route_pickup_point rp ON rp.id = stf.route_pickup_point_id "
        "LEFT JOIN student_fees_deposite sfd ON sfd.student_transport_fee_id = stf.id "
        "JOIN student_session ss ON ss.id = stf.student_session_id "
        "JOIN students s ON s.id = ss.student_id "
        "JOIN classes c ON c.id = ss.class_id "
        "LEFT JOIN sections sec ON sec.id = ss.section_id "
        f"WHERE s.is_active = 'yes'{grade_sql}{month_sql} "
        "ORDER BY s.firstname, tf.month "
        "LIMIT 500"
    )
    raw_rows, _, err = db.execute(sql, max_rows=500)
    if err:
        return [], [], sql
    if not raw_rows:
        scope = "pending " if pending_only else ""
        return [], [], (
            f"No {scope}transport fee records were found. Make sure the Transport "
            "module is enabled and students are assigned to routes."
        )

    out = []
    for row in raw_rows:
        due = float(row[6] or 0)
        paid, discount = _parse_amount_detail(row[7])
        balance = round(due - (paid + discount), 2)
        status = "paid" if balance <= 0.01 else "unpaid"
        if pending_only and status == "paid":
            continue
        out.append((
            row[0], row[1], row[2], row[3], row[4], row[5],
            round(due, 2), round(paid, 2), round(discount, 2), balance, status,
        ))

    columns = [
        "student_firstname", "student_lastname", "admission_no", "class_name",
        "section_name", "fee_month", "due_amount", "paid_amount",
        "discount_amount", "remaining_balance", "status",
    ]
    if not out:
        return [], [], (
            "All assigned transport fees are fully **paid** — no pending balances found."
        )
    return columns, out[:50], sql
