import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.concept_answers import try_concept_answer
from services.front_office_sql import try_front_office_sql


def test_quick_fee_concept():
    ans = try_concept_answer("Explain what is quick fee")
    assert ans and "Quick fees" in ans
    assert "TaleemX" in ans


def test_disable_reason_concept_paragraph():
    ans = try_concept_answer("Explain disable reasons report")
    assert ans
    assert "Disable Reason" in ans
    assert "Student Information" in ans
    assert "\n\n" in ans
    assert "SELECT" not in ans.upper()


def test_disable_reason_explain_skips_student_sql():
    from services.student_sql import try_student_sql

    assert try_student_sql("Explain disable reasons report") is None


def test_income_heads_sql():
    sql = try_front_office_sql("show all income heads")
    assert sql and "income_head" in sql.lower()


def test_expense_heads_sql():
    sql = try_front_office_sql("show all the expense heads")
    assert sql and "expense_head" in sql.lower()
