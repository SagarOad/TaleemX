import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.student_sql import (
    _extract_house_color,
    _extract_student_name_for_house,
    _is_named_student_house_question,
    try_student_sql,
)


def test_abdullah_red_house_name_extract():
    q = "Abdullah Bin Ahmed IS IN RED HOUSE?"
    assert _extract_student_name_for_house(q) == "Abdullah Bin Ahmed"
    assert _extract_house_color(q) == "red"
    assert _is_named_student_house_question(q)


def test_is_abdullah_in_red_house():
    q = "Is Abdullah Bin Ahmed in red house?"
    assert _extract_student_name_for_house(q) == "Abdullah Bin Ahmed"


def test_which_house():
    q = "Which house is Abdullah Bin Ahmed in?"
    assert _extract_student_name_for_house(q) == "Abdullah Bin Ahmed"
    sql = try_student_sql(q)
    assert sql and "house_name" in sql


def test_house_sql_no_replace():
    sql = try_student_sql("Abdullah Bin Ahmed IS IN RED HOUSE?")
    assert sql
    assert "REPLACE" not in sql.upper()
    assert "in_red_house" in sql
