"""Tests for date parsing and action guard."""

import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.action_guard import check_action_guard
from services.date_filters import parse_literal_date, sql_date_filter
from services.exam_results_sql import is_exam_results_question, is_exam_schedule_question
from services.fee_due_engine import matches_fee_due_question


def test_literal_date_us():
    d = parse_literal_date("Show me 05/01/2026 admission enquiries", "m/d/Y")
    assert d and d.isoformat() == "2026-05-01"


def test_literal_date_dmY():
    d = parse_literal_date("Show me 05/01/2026 admission enquiries", "d/m/Y")
    assert d and d.isoformat() == "2026-01-05"


def test_literal_date_us_may2():
    d = parse_literal_date("05/02/2026", "m/d/Y")
    assert d and d.isoformat() == "2026-05-02"


def test_invalid_day():
    df = sql_date_filter("e.date", "Who visited on 32 January?")
    assert df.kind == "invalid"


def test_this_month_sql():
    df = sql_date_filter("e.date", "admission enquiries this month")
    assert df.kind == "this_month"
    assert "MONTH" in df.sql_on_column


def test_delete_guard():
    msg = check_action_guard("Delete all phone call logs from this month.")
    assert msg and "read" in msg.lower()


def test_exam_results_not_schedule():
    assert is_exam_results_question("show exam results of grade 2")
    assert not is_exam_schedule_question("show exam results of grade 2")


def test_fee_due_match():
    assert matches_fee_due_question("how many students have remaining fees?")


def test_query_router_handler_imports():
    """Catch missing resolve_* imports before runtime /ask failures."""
    import services.query_router as qr

    for name in (
        "resolve_student_attendance_report",
        "resolve_library_report",
        "resolve_inventory_report",
        "resolve_download_center_report",
        "resolve_gmeet_report",
        "resolve_hr_report",
        "resolve_video_lessons_report",
        "resolve_teacher_lessons_report",
        "resolve_lesson_plan_report",
    ):
        assert hasattr(qr, name), f"query_router missing import: {name}"
