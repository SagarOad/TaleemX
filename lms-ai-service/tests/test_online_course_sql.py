import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.exam_results_sql import is_exam_schedule_question
from services.online_course_sql import (
    _extract_course_title,
    is_named_course_show_question,
    is_online_course_enrollment_question,
    try_online_course_sql,
)


def test_active_online_courses_sql():
    sql = try_online_course_sql("Show all active online courses")
    assert sql
    assert "online_courses" in sql
    assert "oc.status = '1'" in sql
    assert "onlineexam" not in sql


def test_question_bank_grammar_singlechoice():
    q = (
        "show all the single choice question with question tag : grammar "
        "from question bank of online courses"
    )
    sql = try_online_course_sql(q)
    assert sql
    assert "online_course_exam_question" in sql
    assert "tag_name" in sql
    assert "singlechoice" in sql
    assert "grammar" in sql.lower()
    assert "T2.tag" not in sql


def test_online_course_not_exam_schedule():
    assert not is_exam_schedule_question("Show all active online courses")


def test_enrollment_question_detection():
    q = 'Show enrollment report for "Python Basics" course.'
    assert is_online_course_enrollment_question(q)
    assert _extract_course_title(q) == "Python Basics"


def test_enrollment_report_not_course_list():
    q = 'Show enrollment report for "Python Basics" course.'
    assert try_online_course_sql(q) is None


def test_extract_course_title_unquoted():
    q = "Show enrollment report for Python Basics course"
    assert _extract_course_title(q) == "Python Basics"


def test_extract_course_title_smart_quotes():
    q = "Show enrollment report for \u201cPython Basics\u201d course."
    assert _extract_course_title(q) == "Python Basics"
    assert is_online_course_enrollment_question(q)


def test_named_course_show_detection():
    q = 'Show course "Quantum Engineering 2099"'
    assert is_named_course_show_question(q)
    assert _extract_course_title(q) == "Quantum Engineering 2099"
    assert try_online_course_sql(q) is None


def test_named_course_show_unquoted():
    q = "Show course Quantum Engineering 2099"
    assert is_named_course_show_question(q)
    assert _extract_course_title(q) == "Quantum Engineering 2099"
