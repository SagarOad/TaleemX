import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.online_exam_sql import (
    build_online_exam_schedule_lookup_sql,
    build_online_exam_schedule_list_sql,
    extract_online_exam_title,
    is_named_online_exam_schedule_question,
    is_online_exam_schedule_list_question,
    is_upcoming_online_exam_list_question,
)


def test_islamiyat_named_schedule():
    q = "Show exams scheduled of online exam Islamiyat exam Grade 1"
    assert is_named_online_exam_schedule_question(q)
    assert not is_online_exam_schedule_list_question(q)
    title = extract_online_exam_title(q)
    assert title
    assert "islamiyat" in title.lower()
    assert "grade 1" in title.lower()


def test_list_all_still_list():
    q = "give me list of all exams scheduled"
    assert is_online_exam_schedule_list_question(q)
    assert not is_named_online_exam_schedule_question(q)


def test_lookup_sql_has_title_filter():
    sql = build_online_exam_schedule_lookup_sql("Islamiyat exam Grade 1")
    assert "onlineexam" in sql
    assert "islamiyat" in sql.lower()
    assert "WHERE oe.session_id" in sql
    assert "exam_from >= CURDATE()" not in sql  # include closed exams


def test_upcoming_online_exam_list():
    q = "show all upcoming online exam"
    assert is_upcoming_online_exam_list_question(q)
    assert is_online_exam_schedule_list_question(q)
    sql = build_online_exam_schedule_list_sql(upcoming_only=True)
    assert "exam_to >= NOW()" in sql
    assert "ORDER BY oe.exam_from ASC" in sql
