import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.inventory_sql import (
    _extract_category_filter,
    _is_issued_list,
    _is_issued_summary,
    _is_newly_added,
    _period_sql_and_label,
    is_inventory_question,
)


def test_laboratory_inventory():
    q = "Show current inventory for laboratory items"
    assert is_inventory_question(q)
    assert _extract_category_filter(q) == "laboratory"


def test_items_issued_today():
    assert is_inventory_question("Show items issued today")


def test_newly_added_month():
    assert is_inventory_question("Show all newly added items this month")


def test_issued_summary_category():
    assert is_inventory_question("Summarize items issued this month by category")


def test_not_library_books():
    assert not is_inventory_question("Show books returned this week")


def test_intent_routing():
    q_issued = "Show items issued today"
    q_new = "Show all newly added items this month"
    q_summary = "Summarize items issued this month by category"
    assert _is_issued_list(q_issued)
    assert not _is_newly_added(q_issued)
    assert _is_newly_added(q_new)
    assert not _is_issued_list(q_new)
    assert _is_issued_summary(q_summary)


def test_april_period_filter():
    sql, label, fallback = _period_sql_and_label(
        "ii.issue_date", "give me inventory items issued in april",
    )
    assert "MONTH(ii.issue_date) = 4" in sql
    assert "YEAR(ii.issue_date) = YEAR(CURDATE())" in sql
    assert label == "April"
    assert fallback == "MONTH(ii.issue_date) = 4"


def test_this_month_period_filter():
    sql, label, fallback = _period_sql_and_label(
        "ii.issue_date", "Show all newly added items this month",
    )
    assert "MONTH(ii.issue_date) = MONTH(CURDATE())" in sql
    assert label == "this month"
    assert fallback == ""
