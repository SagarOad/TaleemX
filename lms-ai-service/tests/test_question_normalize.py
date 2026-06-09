import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.question_normalize import (
    contains_arabic,
    normalize_question_for_routing,
)


def test_english_unchanged():
    q = "Show all active exam groups"
    assert normalize_question_for_routing(q) == q
    assert not contains_arabic(q)


def test_arabic_show_students():
    q = "اعرض جميع الطلاب النشطين"
    norm = normalize_question_for_routing(q)
    assert "show" in norm.lower()
    assert "all" in norm.lower()
    assert "students" in norm.lower()
    assert "active" in norm.lower()


def test_arabic_video_lessons_this_week():
    q = "اعرض الدروس المصورة لهذا الأسبوع"
    norm = normalize_question_for_routing(q)
    assert "video" in norm.lower()
    assert "lessons" in norm.lower()
    assert "this week" in norm.lower()
    assert "show" in norm.lower()

    from services.video_lessons_sql import is_video_lessons_question
    from services.teacher_lessons_sql import (
        extract_teacher_name,
        is_teacher_lessons_question,
    )

    assert is_video_lessons_question(q)
    assert not is_teacher_lessons_question(q)
    assert extract_teacher_name(q) is None
