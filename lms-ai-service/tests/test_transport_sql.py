import json
import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.sql_validator import SQLValidator
from services.transport_sql import (
    resolve_transport_fee_status_all,
    try_transport_sql,
)

_VALIDATOR = SQLValidator()


def _assert_valid(sql):
    ok, err = _VALIDATOR.validate(sql)
    assert ok, f"SQL failed validation: {err}\n{sql}"


def test_fee_structures():
    sql = try_transport_sql("Show all transport-related fee structures.")
    assert sql
    assert "route_pickup_point" in sql
    assert "rpp.fees" in sql
    _assert_valid(sql)


def test_active_routes():
    sql = try_transport_sql("Show all active transport routes")
    assert sql
    assert "transport_route" in sql
    assert "EXISTS" in sql
    _assert_valid(sql)


def test_all_routes_without_active():
    sql = try_transport_sql("Show all transport routes")
    assert sql
    assert "transport_route" in sql
    assert "EXISTS" not in sql
    _assert_valid(sql)


def test_vehicles():
    sql = try_transport_sql("Show all school vehicles.")
    assert sql
    assert "FROM vehicles" in sql
    assert "driver_name" in sql
    _assert_valid(sql)


def test_pickup_points_for_named_route():
    sql = try_transport_sql("Show all pickup points for Route 1 - Olaya")
    assert sql
    assert "route_pickup_point" in sql
    assert "Route 1 - Olaya".lower() in sql.lower()
    _assert_valid(sql)


def test_pickup_points_route_one_precise_match():
    sql = try_transport_sql("Show all pickup points for Route 1.")
    assert sql
    # Precise matching avoids "Route 10" / "Route 11" leakage.
    assert "LIKE LOWER('Route 1 %')" in sql
    _assert_valid(sql)


def test_pickup_points_all():
    sql = try_transport_sql("Show all pickup points")
    assert sql
    assert "FROM pickup_point" in sql
    _assert_valid(sql)


class _FakeDB:
    def __init__(self, rows):
        self._rows = rows

    def execute(self, sql, max_rows=None):
        return self._rows, [], None


def test_transport_fee_paid_and_unpaid_both():
    rows = [
        ("Ali", "Khan", "A-1", "5", "A", "January",
         500, json.dumps({"1": {"amount": "500", "amount_discount": "0"}})),
        ("Sara", "Noor", "A-2", "5", "B", "January", 500, None),
    ]
    db = _FakeDB(rows)
    out = resolve_transport_fee_status_all(
        db, "Show all transport-related fee paid and unpaid both"
    )
    assert out is not None
    cols, result_rows, note = out
    assert "status" in cols
    statuses = {r[-1] for r in result_rows}
    assert statuses == {"paid", "unpaid"}


def test_pending_transport_fees_only_unpaid():
    rows = [
        ("Ali", "Khan", "A-1", "5", "A", "January",
         500, json.dumps({"1": {"amount": "500", "amount_discount": "0"}})),
        ("Sara", "Noor", "A-2", "5", "B", "January", 500, None),
    ]
    db = _FakeDB(rows)
    out = resolve_transport_fee_status_all(
        db, "Show students with pending transport fees"
    )
    assert out is not None
    cols, result_rows, note = out
    assert all(r[-1] == "unpaid" for r in result_rows)
    assert len(result_rows) == 1


def test_named_student_defers_to_precise_resolver():
    db = _FakeDB([])
    out = resolve_transport_fee_status_all(
        db, "Is transport fee paid for student Ali Khan"
    )
    assert out is None
