import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.academic_sql import try_academic_sql
from services.sql_validator import SQLValidator

_VALIDATOR = SQLValidator()


def _assert_valid(sql):
    ok, err = _VALIDATOR.validate(sql)
    assert ok, f"SQL failed validation: {err}\n{sql}"


class _FakeDB:
    def __init__(self, tables=()):
        self._tables = {t.lower() for t in tables}

    def table_exists(self, table):
        return table.lower() in self._tables


def test_timetable_for_teacher():
    db = _FakeDB()
    sql = try_academic_sql(db, "Show weekly timetable for Mr. Ahmed Al-Harbi.")
    assert sql
    assert "subject_timetable" in sql
    assert "ahmed al-harbi" in sql.lower()
    assert "mr" not in sql.lower().split("'")[1]  # title stripped from filter
    _assert_valid(sql)


def test_class_teacher_grade_section():
    db = _FakeDB()
    sql = try_academic_sql(db, "Show assigned class teacher for Grade 6-B.")
    assert sql
    assert "class_teacher" in sql
    assert "Grade 6" in sql
    assert "LOWER('B')" in sql
    _assert_valid(sql)


def test_sections_in_grade():
    db = _FakeDB()
    sql = try_academic_sql(db, "Show all sections in Grade 5.")
    assert sql
    assert "class_sections" in sql
    assert "Grade 5" in sql
    _assert_valid(sql)


def test_sections_does_not_hijack_student_section_query():
    db = _FakeDB()
    # This must NOT be treated as a "list sections" request.
    assert try_academic_sql(db, "show students of class 5 section A") is None


def test_incident_students():
    db = _FakeDB()
    sql = try_academic_sql(db, "Show students assigned to incident ID 6")
    assert sql
    assert "student_incidents" in sql
    assert "si.incident_id = 6" in sql
    _assert_valid(sql)


def test_incomplete_guardian():
    db = _FakeDB()
    sql = try_academic_sql(
        db, "Show all students with incomplete guardian contact information"
    )
    assert sql
    assert "guardian_phone" in sql
    assert "TRIM(s.guardian_name)" in sql
    _assert_valid(sql)


def test_class_performance_requires_exam_tables():
    no_exam = _FakeDB()
    assert try_academic_sql(no_exam, "Show class performance report for Grade 1-A.") is None

    with_exam = _FakeDB(tables=["exam_group_exam_results"])
    sql = try_academic_sql(with_exam, "Show class performance report for Grade 1-A.")
    assert sql
    assert "avg_percentage" in sql
    assert "Grade 1" in sql
    assert "LOWER('A')" in sql
    _assert_valid(sql)


def test_timetable_for_grade_not_treated_as_teacher():
    db = _FakeDB()
    # "for grade 5" is not a teacher name → should not build a teacher timetable.
    assert try_academic_sql(db, "show timetable for grade 5") is None
