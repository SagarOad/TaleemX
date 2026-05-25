"""tests/test_exam_results_sql.py"""

import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.exam_results_sql import (
    is_exam_results_question,
    is_exam_schedule_question,
    parse_grade_from_question,
)


def test_results_not_schedule():
    assert is_exam_results_question("show me exam results of grade 2")
    assert not is_exam_schedule_question("show me exam results of grade 2")


def test_schedule_not_results():
    assert is_exam_schedule_question("show exam schedule for grade 2")
    assert not is_exam_results_question("show exam schedule for grade 2")


def test_show_exam_alone_is_not_auto_schedule():
    """Regression: 'show' + 'exam' alone must not imply schedule."""
    assert not is_exam_schedule_question("show me exam results of grade 2")


def test_parse_grade():
    assert parse_grade_from_question("result of grade 2") == "2"
