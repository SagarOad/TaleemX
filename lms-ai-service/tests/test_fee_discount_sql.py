import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.concept_answers import try_concept_answer
from services.fee_collection_sql import fee_collection_classwise_sql
from services.fee_due_engine import (
    active_fee_discounts_sql,
    student_fee_ledger_sql,
    students_receiving_financial_discount_sql,
    try_fee_sql,
)
from services.name_filters import student_name_sql_filter
from services.sql_validator import SQLValidator
from unittest.mock import MagicMock


def test_active_fee_discount_sql_uses_expiry_not_is_active():
    sql = active_fee_discounts_sql(active_only=True)
    ok, err = SQLValidator().validate(sql)
    assert ok, err
    assert "expire_date" in sql
    assert "is_active = 1" not in sql
    assert "CURDATE()" in sql


def test_show_active_fee_discounts_routes():
    sql = try_fee_sql(MagicMock(), "Show active fee discounts")
    assert sql
    assert "fees_discounts" in sql
    assert "expire_date" in sql
    assert "is_active = 1" not in sql


def test_financial_discount_students_sql():
    sql = students_receiving_financial_discount_sql()
    ok, err = SQLValidator().validate(sql)
    assert ok, err
    assert "student_fees_discounts" in sql
    assert "discount_reason" in sql
    assert "student_fees_deposite" not in sql


def test_show_financial_discount_with_reason():
    sql = try_fee_sql(MagicMock(), "Show students receiving financial discount with reason.")
    assert sql
    assert "student_fees_discounts" in sql


def test_fee_ledger_muhammad_spelling():
    filt = student_name_sql_filter("muhammad al qahtani")
    assert "mohammed" in filt.lower()
    sql = student_fee_ledger_sql("muhammad al qahtani")
    ok, err = SQLValidator().validate(sql)
    assert ok, err
    assert try_fee_sql(MagicMock(), "Show full fee ledger of muhammad al qahtani")


def test_explain_april_fee_collection_paragraph():
    ans = try_concept_answer("Explain aprils fee collection report.")
    assert ans
    assert "Fee Collection Report" in ans
    assert "\n\n" in ans
    assert try_fee_sql(MagicMock(), "Explain aprils fee collection report.") is None


def test_explain_june_classwise_paragraph():
    ans = try_concept_answer("Explain this june's fee collection class-wise.")
    assert ans
    assert "class-wise" in ans.lower()
    assert try_fee_sql(MagicMock(), "Explain this june's fee collection class-wise.") is None


def test_june_classwise_data_sql():
    sql = fee_collection_classwise_sql("Show June fee collection class-wise")
    ok, err = SQLValidator().validate(sql)
    assert ok, err
    assert "GROUP BY c.class" in sql
    assert "MONTH(COALESCE(jt.payment_date" in sql
