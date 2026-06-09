"""tests/test_exam_results_sql.py"""

import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.exam_results_sql import (
    build_exam_groups_list_sql,
    is_exam_group_list_question,
    is_exam_results_question,
    is_exam_schedule_question,
    is_grade_distribution_question,
    parse_grade_from_question,
    resolve_exam_groups_sql,
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


def test_exam_group_list_detection():
    assert is_exam_group_list_question("Show all active exam groups.")
    assert not is_exam_group_list_question("show all available online exams")


def test_exam_groups_sql_uses_exam_groups_table():
    sql = build_exam_groups_list_sql(active_only=True)
    assert "exam_groups" in sql
    assert "is_active = 1" in sql
    assert "onlineexam" not in sql


def test_grade_distribution_detection():
    q = "Explain overall grade distribution for Grade 1 final exam"
    assert is_grade_distribution_question(q)
    assert not is_exam_results_question(q)


class _FakeDB:
    def __init__(self, tables=None):
        self._tables = set(tables or [])

    def table_exists(self, name):
        return name in self._tables


def test_resolve_exam_groups_requires_table():
    assert resolve_exam_groups_sql(_FakeDB(), "Show all active exam groups.") is None
    sql = resolve_exam_groups_sql(_FakeDB(["exam_groups"]), "Show all active exam groups.")
    assert sql and "exam_groups" in sql
