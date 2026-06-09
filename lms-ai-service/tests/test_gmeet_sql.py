import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.date_filters import parse_datetime_from_question, parse_exact_date_from_question
from services.gmeet_sql import is_gmeet_live_class_question


def test_gmeet_question_detection():
    assert is_gmeet_live_class_question("Show 04/28/2026 11:49:00 scheduled Gmeet live classes")
    assert is_gmeet_live_class_question("Show 04/28/2026 scheduled Gmeet live classes")
    assert not is_gmeet_live_class_question("show timetable for grade 1")


def test_gmeet_datetime_parse():
    dt = parse_datetime_from_question("Show 04/28/2026 11:49:00 scheduled Gmeet live classes")
    assert dt == "2026-04-28 11:49:00"
    assert parse_datetime_from_question("Show 04/28/2026 scheduled Gmeet live classes") is None
    d = parse_exact_date_from_question("Show 04/28/2026 scheduled Gmeet live classes")
    assert d and d.isoformat() == "2026-04-28"
