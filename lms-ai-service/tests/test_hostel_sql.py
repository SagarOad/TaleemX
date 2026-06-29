import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.hostel_sql import try_hostel_sql
from services.sql_validator import SQLValidator

_VALIDATOR = SQLValidator()


def _assert_valid(sql):
    ok, err = _VALIDATOR.validate(sql)
    assert ok, f"SQL failed validation: {err}\n{sql}"


def test_hostel_rooms():
    sql = try_hostel_sql("Show all hostel rooms.")
    assert sql
    assert "hostel_rooms" in sql
    assert "no_of_bed" in sql
    _assert_valid(sql)


def test_hostel_occupancy_report():
    sql = try_hostel_sql("show hostel occupancy report")
    assert sql
    assert "occupied" in sql
    assert "available" in sql
    assert "hostel_room_id" in sql
    _assert_valid(sql)


def test_non_hostel_returns_none():
    assert try_hostel_sql("show all students") is None
