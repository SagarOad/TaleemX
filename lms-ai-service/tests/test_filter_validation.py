"""
tests/test_filter_validation.py
"""

import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.filter_validation import (
    grades_in_result_rows,
    message_grade_not_configured,
    normalize_class_value,
    parse_requested_grade,
    resolve_filter_message,
)


class _FakeDB:
    def __init__(self, grades=None, has_classes=True):
        self._grades = grades or ["1", "2"]
        self._has_classes = has_classes

    def table_exists(self, table: str) -> bool:
        return table == "classes" and self._has_classes

    def execute(self, sql, max_rows=None):
        if "classes" in sql.lower():
            rows = [(f"Grade {g}",) for g in self._grades]
            return rows, ["class_name"], None
        return [], [], None


class TestParseGrade:
    def test_grade_6(self):
        assert parse_requested_grade("show exam results for grade 6") == "6"

    def test_class_2(self):
        assert parse_requested_grade("list students in class 2") == "2"

    def test_no_grade(self):
        assert parse_requested_grade("how many teachers") is None


class TestNormalize:
    def test_variants(self):
        assert normalize_class_value("Grade 1") == "1"
        assert normalize_class_value("Class 2") == "2"
        assert normalize_class_value("3") == "3"


class TestResolveFilter:
    def test_grade_not_configured_empty_rows(self):
        db = _FakeDB(grades=["1", "2"])
        msg = resolve_filter_message(
            db, "show exam results for grade 6", [], []
        )
        assert msg is not None
        assert "Grade 6" in msg
        assert "not set up" in msg
        assert "Grade 1" in msg
        assert "Grade 2" in msg

    def test_mismatch_rows_other_grades(self):
        db = _FakeDB(grades=["1", "2"])
        cols = ["student_name", "class_name", "exam_marks"]
        rows = [
            ("Ali", "Grade 1", 80),
            ("Sara", "Grade 2", 90),
        ]
        msg = resolve_filter_message(
            db, "show exam results for grade 6", cols, rows
        )
        assert msg is not None
        assert "Grade 6" in msg

    def test_matching_grade_ok(self):
        db = _FakeDB(grades=["1", "2"])
        cols = ["student_name", "class_name"]
        rows = [("Ali", "Grade 1",)]
        msg = resolve_filter_message(
            db, "show exam results for grade 1", cols, rows
        )
        assert msg is None

    def test_grades_in_result_rows(self):
        cols = ["class_name", "x"]
        rows = [("Grade 1", 1), ("2", 2)]
        assert grades_in_result_rows(cols, rows) == {"1", "2"}
