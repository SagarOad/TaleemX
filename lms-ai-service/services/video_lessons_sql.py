"""
Video lessons — Download Center video tutorials + Online Course video lessons.
"""

from __future__ import annotations

import re
from dataclasses import dataclass, field
from typing import TYPE_CHECKING, Optional

if TYPE_CHECKING:
    from services.db_service import DBService

from services.date_filters import (
    month_english_name,
    parse_month_from_question,
    parse_year_from_question,
)
from services.exam_results_sql import parse_grade_from_question
from services.online_course_sql import is_online_course_lms_question
from services.question_normalize import routing_question


def _esc(s: str) -> str:
    return (s or "").replace("'", "''")


def _grade_where(grade: str, alias: str = "c") -> str:
    g = _esc(grade)
    return (
        f"({alias}.class = '{g}' OR {alias}.class = 'Grade {g}' "
        f"OR {alias}.class = 'Class {g}')"
    )


def is_video_lessons_question(question: str) -> bool:
    raw = question or ""
    rq = routing_question(question).lower()
    if re.search(r"[\u0600-\u06FF]", raw):
        if re.search(r"مصور|مرئ|فيديو", raw) and re.search(r"درس|دروس", raw):
            return True
    if not re.search(r"\bvideo\b", rq):
        return False
    if re.search(r"\b(?:lesson|lessons|tutorial|tutorials)\b", rq):
        return True
    if "video lesson" in rq or "video tutorials" in rq:
        return True
    return bool(re.search(r"\bvideo\b.*\b(?:week|month|today)\b", rq))


def _period_filter(date_column: str, question: str) -> tuple[str, str]:
    rq = routing_question(question).lower()
    if re.search(r"\btoday\b|\bاليوم\b", rq):
        return f"DATE({date_column}) = CURDATE()", "today"
    if re.search(r"\bthis week\b|\bcurrent week\b|\bthis week's\b", rq):
        return (
            f"YEARWEEK({date_column}, 1) = YEARWEEK(CURDATE(), 1)",
            "this week",
        )
    if re.search(r"\blast week\b|\bprevious week\b", rq):
        return (
            f"YEARWEEK({date_column}, 1) = "
            f"YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1)",
            "last week",
        )
    month_num = parse_month_from_question(question)
    if month_num == 0:
        return (
            f"MONTH({date_column}) = MONTH(CURDATE()) "
            f"AND YEAR({date_column}) = YEAR(CURDATE())",
            "this month",
        )
    if month_num and month_num > 0:
        year = parse_year_from_question(question) or "YEAR(CURDATE())"
        if isinstance(year, int):
            return (
                f"MONTH({date_column}) = {month_num} AND YEAR({date_column}) = {year}",
                month_english_name(month_num),
            )
        return (
            f"MONTH({date_column}) = {month_num} "
            f"AND YEAR({date_column}) = YEAR(CURDATE())",
            month_english_name(month_num),
        )
    return (
        f"YEARWEEK({date_column}, 1) = YEARWEEK(CURDATE(), 1)",
        "this week",
    )


@dataclass
class VideoLessonsReport:
    message: str = ""
    columns: list[str] = field(default_factory=list)
    rows: list = field(default_factory=list)
    sql_note: str = ""


def resolve_video_lessons_report(
    db: "DBService",
    question: str,
) -> Optional[VideoLessonsReport]:
    if not is_video_lessons_question(question):
        return None

    grade = parse_grade_from_question(question)
    grade_sql = f" AND {_grade_where(grade)}" if grade else ""
    online_only = is_online_course_lms_question(question)

    period_vt, period_label = _period_filter("vt.created_at", question)
    rows: list = []
    cols: list[str] = []
    sql_parts: list[str] = []

    if not online_only and db.table_exists("video_tutorial"):
        sql = (
            "SELECT vt.id AS video_id, vt.title, vt.video_link, vt.vid_title, "
            "vt.created_at AS uploaded_at, "
            "CONCAT_WS(' ', st.name, st.surname) AS uploaded_by, "
            "c.class AS grade, sec.section AS section_name, "
            "'Video Tutorial' AS module "
            "FROM video_tutorial vt "
            "LEFT JOIN staff st ON st.id = vt.created_by "
            "LEFT JOIN video_tutorial_class_sections vtcs "
            "  ON vtcs.video_tutorial_id = vt.id "
            "LEFT JOIN class_sections cs ON cs.id = vtcs.class_section_id "
            "LEFT JOIN classes c ON c.id = cs.class_id "
            "LEFT JOIN sections sec ON sec.id = cs.section_id "
            f"WHERE {period_vt}{grade_sql} "
            "GROUP BY vt.id, vt.title, vt.video_link, vt.vid_title, vt.created_at, "
            "uploaded_by, c.class, sec.section "
            "ORDER BY vt.created_at DESC "
            "LIMIT 100"
        )
        vt_rows, vt_cols, err = db.execute(sql, max_rows=100)
        sql_parts.append(sql)
        if err and "doesn't exist" not in err.lower():
            return VideoLessonsReport(
                message=f"Could not load video tutorials: {err}",
                sql_note=sql,
            )
        if vt_rows:
            rows.extend(vt_rows)
            cols = vt_cols or cols

    if db.table_exists("online_course_lesson"):
        period_oc, _ = _period_filter("ocl.created_date", question)
        grade_oc = grade_sql.replace("c.", "c2.") if grade_sql else ""
        sql = (
            "SELECT ocl.id AS video_id, ocl.lesson_title AS title, "
            "ocl.video_url AS video_link, ocl.video_provider, ocl.duration, "
            "ocl.created_date AS uploaded_at, oc.title AS course_title, "
            "c2.class AS grade, sec2.section AS section_name, "
            "'Online Course' AS module "
            "FROM online_course_lesson ocl "
            "JOIN online_course_section ocs ON ocs.id = ocl.course_section_id "
            "JOIN online_courses oc ON oc.id = ocs.online_course_id "
            "LEFT JOIN online_course_class_sections occs ON occs.course_id = oc.id "
            "LEFT JOIN class_sections cs2 ON cs2.id = occs.class_section_id "
            "LEFT JOIN classes c2 ON c2.id = cs2.class_id "
            "LEFT JOIN sections sec2 ON sec2.id = cs2.section_id "
            f"WHERE ocl.lesson_type = 'video' AND {period_oc}{grade_oc} "
            "GROUP BY ocl.id, ocl.lesson_title, ocl.video_url, ocl.video_provider, "
            "ocl.duration, ocl.created_date, oc.title, c2.class, sec2.section "
            "ORDER BY ocl.created_date DESC "
            "LIMIT 100"
        )
        oc_rows, oc_cols, err = db.execute(sql, max_rows=100)
        sql_parts.append(sql)
        if err and "doesn't exist" not in err.lower() and not rows:
            return VideoLessonsReport(
                message=f"Could not load online course video lessons: {err}",
                sql_note=sql,
            )
        if oc_rows:
            if not cols:
                cols = oc_cols
            rows.extend(oc_rows)

    if not rows:
        mod = "online course " if online_only else ""
        return VideoLessonsReport(
            message=(
                f"No **{mod}video lessons** found for **{period_label}**"
                + (f" (Grade {grade})" if grade else "")
                + "."
            ),
            sql_note="; ".join(sql_parts) or "-- video lessons",
        )

    lead = f"**{len(rows)}** video lesson(s) for **{period_label}**"
    if grade:
        lead += f" — **Grade {grade}**"
    lead += ":"
    return VideoLessonsReport(
        message=lead,
        columns=cols,
        rows=rows,
        sql_note="; ".join(sql_parts),
    )
