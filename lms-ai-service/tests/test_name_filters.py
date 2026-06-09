import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.name_filters import extract_person_name, extract_student_name, is_likely_fake_name


def test_fake_name_detection():
    assert is_likely_fake_name("XYZ NotReal")
    assert is_likely_fake_name("Fake Student 999")


def test_extract_transport_student_name():
    name = extract_student_name(
        "Transport Fees (April) of student huda al zahrani is paid or unpaid?"
    )
    assert name and name.lower() == "huda al zahrani"


def test_normalize_name_strips_paid_unpaid():
    from services.name_filters import normalize_person_name

    assert normalize_person_name("huda al zahrani is paid or unpaid") == "huda al zahrani"


def test_rows_match_student_with_middlename():
    from services.name_filters import rows_match_name

    cols = [
        "admission_no", "roll_no", "admission_date", "firstname", "middlename",
        "lastname", "mobileno", "email",
    ]
    row = (
        "SA-100011", "1", "2026-04-28", "Omar", "Ahmed", "Al-Harbi",
        "966501234567", "omar@example.com",
    )
    assert rows_match_name(cols, [row], "Ahmed Al-Harbi")
    assert rows_match_name(cols, [row], "Omar Ahmed Al-Harbi")
    assert rows_match_name(cols, [row], "Omar Harbi")


def test_student_name_sql_filter_tokens():
    from services.name_filters import student_name_sql_filter

    sql = student_name_sql_filter("Omar Harbi")
    assert "firstname" in sql
    assert "LIKE '%omar%'" in sql
    assert "LIKE '%harbi%'" in sql
    assert "REPLACE" not in sql.upper()


def test_student_name_sql_passes_validator():
    from services.name_filters import student_name_sql_filter
    from services.sql_validator import SQLValidator

    filt = student_name_sql_filter("Ahmed Al-Harbi")
    sql = (
        "SELECT s.firstname, s.middlename, s.lastname FROM students s "
        f"WHERE {filt} LIMIT 10"
    )
    ok, err = SQLValidator().validate(sql)
    assert ok, err
