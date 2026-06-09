import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.fee_due_engine import (
    matches_offline_bank_pending_question,
    offline_bank_pending_sql,
    try_fee_sql,
)
from services.sql_validator import SQLValidator
from services.student_sql import _latest_admitted_count, try_student_sql


class _FakeDB:
    pass


def test_latest_admitted_count():
    assert _latest_admitted_count("Show latest 2 admitted students") == 2
    assert _latest_admitted_count("Show latest 2 admitted students.") == 2
    assert _latest_admitted_count("last 3 admission students") == 3


def test_latest_admitted_sql():
    sql = try_student_sql("Show latest 2 admitted students")
    assert sql and "LIMIT 2" in sql
    assert "COALESCE(s.admission_date" in sql
    assert "REPLACE" not in sql.upper()


def test_offline_pending_match():
    assert matches_offline_bank_pending_question(
        "which student offline bank payment is in pending"
    )


def test_offline_pending_sql_valid():
    sql = offline_bank_pending_sql()
    ok, err = SQLValidator().validate(sql)
    assert ok, err
    assert "ofp.student_session_id" in sql or "ss.id = ofp.student_session_id" in sql
    assert "ofp.student_id" not in sql


def test_latest_admitted_routes_via_query_router():
    from unittest.mock import MagicMock
    from services.query_router import route_question

    routed = route_question(MagicMock(), "Show latest 2 admitted students")
    assert routed is not None and routed.sql
    assert "LIMIT 2" in routed.sql
    assert "admission_date" in routed.sql


def test_try_fee_sql_offline():
    sql = try_fee_sql(_FakeDB(), "which student offline bank payment is in pending")
    assert sql and "offline_fees_payments" in sql
