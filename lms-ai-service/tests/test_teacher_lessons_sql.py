import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.question_normalize import normalize_question_for_routing
from services.teacher_lessons_sql import (
    extract_teacher_name,
    is_teacher_lessons_question,
)


def test_arabic_lessons_today_mixed_name():
    q = "اعرض الدروس لهذا اليوم fatima al qahtani"
    norm = normalize_question_for_routing(q)
    assert "lessons" in norm.lower()
    assert "today" in norm.lower()
    assert "fatima al qahtani" in norm.lower()
    assert is_teacher_lessons_question(q)
    assert extract_teacher_name(q).lower() == "fatima al qahtani"


def test_english_lessons_by_teacher():
    q = "Give all lessons by Fatima Al Qahtani"
    assert is_teacher_lessons_question(q)
    assert extract_teacher_name(q) == "Fatima Al Qahtani"


def test_lesson_plan_not_teacher_list():
    q = (
        "Show lesson plan for Grade 1 Section A Subject Group Core Subjects "
        "Subject English (Eng)"
    )
    assert not is_teacher_lessons_question(q)
