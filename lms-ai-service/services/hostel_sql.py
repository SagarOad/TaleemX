"""
Hostel module SQL — aligned with TaleemX hostel schema:

  hostel        (id, hostel_name, type, address, intake)
  room_types    (id, room_type, description)
  hostel_rooms  (id, hostel_id, room_type_id, room_no, no_of_bed, cost_per_bed)
  students.hostel_room_id → hostel_rooms.id  (room assignment)
"""

from __future__ import annotations

import re
from typing import Optional


_HOSTEL_RE = re.compile(r"\bhostel\b|\bdormitor", re.IGNORECASE)


def is_hostel_question(question: str) -> bool:
    return bool(_HOSTEL_RE.search(question or ""))


def hostel_rooms_sql() -> str:
    return (
        "SELECT h.hostel_name, rt.room_type, hr.room_no, hr.no_of_bed AS beds, "
        "hr.cost_per_bed "
        "FROM hostel_rooms hr "
        "JOIN hostel h ON h.id = hr.hostel_id "
        "JOIN room_types rt ON rt.id = hr.room_type_id "
        "ORDER BY h.hostel_name, hr.room_no "
        "LIMIT 100"
    )


def hostel_occupancy_sql() -> str:
    return (
        "SELECT h.hostel_name, rt.room_type, hr.room_no, hr.no_of_bed AS capacity, "
        "(SELECT COUNT(*) FROM students s "
        "WHERE s.hostel_room_id = hr.id AND s.is_active = 'yes') AS occupied, "
        "(hr.no_of_bed - (SELECT COUNT(*) FROM students s "
        "WHERE s.hostel_room_id = hr.id AND s.is_active = 'yes')) AS available, "
        "hr.cost_per_bed "
        "FROM hostel_rooms hr "
        "JOIN hostel h ON h.id = hr.hostel_id "
        "JOIN room_types rt ON rt.id = hr.room_type_id "
        "ORDER BY h.hostel_name, hr.room_no "
        "LIMIT 100"
    )


def hostels_list_sql() -> str:
    return (
        "SELECT h.hostel_name, h.type AS hostel_type, h.address, h.intake "
        "FROM hostel h "
        "ORDER BY h.hostel_name "
        "LIMIT 100"
    )


def try_hostel_sql(question: str) -> Optional[str]:
    q = (question or "").lower().strip()
    if not q or not is_hostel_question(question):
        return None

    if re.search(r"\b(?:occupanc|occupied|vacanc|vacant|capacity)|available\s+bed", q):
        return hostel_occupancy_sql()

    if "room" in q:
        return hostel_rooms_sql()

    # Bare "list hostels" → buildings; default to rooms otherwise.
    if re.search(r"\b(?:hostels|buildings)\b", q) and "room" not in q and (
        "list" in q or "all hostels" in q or "show hostels" in q
    ):
        return hostels_list_sql()

    return hostel_rooms_sql()
