import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.fee_collection_sql import (
    fee_collection_classwise_sql,
    fee_collection_empty_message,
    matches_fee_collection_question,
    student_paid_fees_sql,
    try_fee_collection_sql,
)
from services.fee_due_engine import try_fee_sql
from services.sql_validator import SQLValidator
from unittest.mock import MagicMock


def test_matches_fee_collection_questions():
    assert matches_fee_collection_question("Show June fee collection class-wise")
    assert matches_fee_collection_question("show recent fee payments")
    assert matches_fee_collection_question("fees paid by mohammed al qahtani")
    assert not matches_fee_collection_question("Explain june fee collection report")


def test_june_classwise_sql_uses_effective_payment_date():
    sql = fee_collection_classwise_sql("Show June fee collection class-wise")
    ok, err = SQLValidator().validate(sql)
    assert ok, err
    assert "COALESCE(jt.payment_date" in sql
    assert "GROUP BY c.class" in sql


def test_may_classwise_via_try_fee_sql():
    sql = try_fee_sql(MagicMock(), "Show May fee collection class-wise")
    assert sql and "GROUP BY c.class" in sql


def test_student_paid_fees_sql():
    sql = student_paid_fees_sql("muhammad al qahtani", "show fees paid by muhammad al qahtani")
    ok, err = SQLValidator().validate(sql)
    assert ok, err
    assert "mohammed" in sql.lower()
    assert "amount_paid" in sql


def test_recent_fee_payments_route():
    sql = try_fee_collection_sql(MagicMock(), "show recent fee payments")
    assert sql and "payment_date" in sql


def test_june_empty_message_suggests_available_months():
    db = MagicMock()
    db.execute.return_value = ([(2026, 5, 20), (2026, 4, 18)], [], None)
    msg = fee_collection_empty_message(db, "Show June fee collection class-wise")
    assert msg
    assert "June" in msg
    assert "May" in msg
    assert "show may fee collection" in msg.lower()
