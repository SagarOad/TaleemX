import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.video_lessons_sql import (
    _period_filter,
    is_video_lessons_question,
)


def test_video_lessons_this_week():
    q = "give me this week's video lessons"
    assert is_video_lessons_question(q)
    sql, label = _period_filter("vt.created_at", q)
    assert "YEARWEEK" in sql
    assert label == "this week"


def test_video_tutorials_phrase():
    assert is_video_lessons_question("list video tutorials this week")
