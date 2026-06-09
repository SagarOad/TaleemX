import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.lesson_plan_sql import (
    is_lesson_plan_question,
    parse_lesson_plan_filters,
)


def test_lesson_plan_ui_style_question():
    q = (
        "Show lesson plan for Grade 1 Section A Subject Group Core Subjects "
        "Subject English (Eng) Syllabus Status For: English (Eng)"
    )
    assert is_lesson_plan_question(q)
    flt = parse_lesson_plan_filters(q)
    assert flt.grade == "1"
    assert flt.section == "A"
    assert flt.subject_group == "Core Subjects"
    assert flt.subject_name == "English"
    assert flt.subject_code == "Eng"


def test_syllabus_status_shorthand():
    q = "Show syllabus status for grade 2 section B subject Mathematics"
    assert is_lesson_plan_question(q)
    flt = parse_lesson_plan_filters(q)
    assert flt.grade == "2"
    assert flt.section == "B"
