"""
Validate user-requested filters (grade/class) against live school data.

Prevents misleading answers when the user asks for Grade 6 but only Grades 1–2
exist, or when SQL returns rows for the wrong grades.
"""

from __future__ import annotations

import re
import threading
import time
from typing import TYPE_CHECKING, Optional

if TYPE_CHECKING:
    from services.db_service import DBService

_GRADE_RE = re.compile(r"\b(?:grade|class)\s*(\d+)\b", re.IGNORECASE)
_CLASS_COL_NAMES = frozenset({
    "class_name", "class", "grade", "classname", "class_name",
})

_cache_lock = threading.Lock()
_cache_grades: list[str] = []
_cache_ts: float = 0.0
_CACHE_TTL_SEC = 60.0


def parse_requested_grade(question: str) -> Optional[str]:
    """Extract a numeric grade/class id from the question, e.g. 'grade 6' -> '6'."""
    m = _GRADE_RE.search(question or "")
    return m.group(1) if m else None


def normalize_class_value(value) -> Optional[str]:
    """Map DB class labels ('Grade 1', '1', 'Class 2') to a numeric string."""
    if value is None:
        return None
    s = str(value).strip()
    if not s:
        return None
    m = re.search(r"(\d+)", s)
    return m.group(1) if m else None


def _format_grade_list(grades: list[str]) -> str:
    if not grades:
        return "(none configured)"
    parts = []
    for g in grades:
        parts.append(f"Grade {g}" if g.isdigit() else g)
    if len(parts) == 1:
        return parts[0]
    if len(parts) == 2:
        return f"{parts[0]} and {parts[1]}"
    return ", ".join(parts[:-1]) + f", and {parts[-1]}"


def _topic_hint(question: str) -> str:
    q = (question or "").lower()
    if "exam" in q and "result" in q:
        return " for exam results"
    if "marksheet" in q:
        return " on the marksheet"
    if "attendance" in q or "attendence" in q:
        return " for attendance"
    if "homework" in q:
        return " for homework"
    if "timetable" in q:
        return " for the timetable"
    if "behaviour" in q or "behavior" in q or "incident" in q:
        return " for behaviour records"
    if "student" in q:
        return " for students"
    if "teacher" in q:
        return " for teachers"
    if "subject" in q:
        return " for subjects"
    return ""


def fetch_available_grades(db: "DBService") -> list[str]:
    """Distinct numeric grade ids configured in `classes`, cached briefly."""
    global _cache_grades, _cache_ts
    now = time.monotonic()
    with _cache_lock:
        if _cache_grades and (now - _cache_ts) < _CACHE_TTL_SEC:
            return list(_cache_grades)

    if not db.table_exists("classes"):
        return []

    rows, _, err = db.execute(
        "SELECT DISTINCT c.class AS class_name FROM classes c "
        "WHERE c.class IS NOT NULL AND TRIM(c.class) <> '' "
        "ORDER BY c.class",
        max_rows=0,
    )
    if err or not rows:
        return []

    seen: list[str] = []
    for row in rows:
        g = normalize_class_value(row[0])
        if g and g not in seen:
            seen.append(g)

    with _cache_lock:
        _cache_grades = seen
        _cache_ts = now
    return list(seen)


def _class_column_index(columns: list) -> Optional[int]:
    for i, col in enumerate(columns):
        if str(col).lower().strip() in _CLASS_COL_NAMES:
            return i
    return None


def grades_in_result_rows(columns: list, rows: list[tuple]) -> set[str]:
    idx = _class_column_index(columns)
    if idx is None:
        return set()
    found: set[str] = set()
    for row in rows:
        g = normalize_class_value(row[idx] if idx < len(row) else None)
        if g:
            found.add(g)
    return found


def message_grade_not_configured(
    requested: str, available: list[str], question: str = ""
) -> str:
    topic = _topic_hint(question)
    avail_txt = _format_grade_list(available)
    return (
        f"**Grade {requested}** is not set up in your school"
        f"{topic}. "
        f"Available grades in your system: **{avail_txt}**.\n\n"
        f"I am not showing results for other grades because you asked specifically "
        f"for Grade {requested}."
    )


def message_grade_no_records(requested: str, question: str) -> str:
    topic = _topic_hint(question)
    return (
        f"**Grade {requested}** exists in your school, but I found **no records**"
        f"{topic} matching your question."
    )


def message_grade_result_mismatch(
    requested: str, result_grades: set[str], available: list[str]
) -> str:
    shown = _format_grade_list(sorted(result_grades, key=lambda x: int(x) if x.isdigit() else x))
    if available and requested not in available:
        avail_txt = _format_grade_list(available)
        return (
            f"You asked for **Grade {requested}**, which is **not configured** in your school. "
            f"Available grades: **{avail_txt}**.\n\n"
            f"The query would have shown data for **{shown}** instead — "
            f"that would be misleading, so no results are shown."
        )
    return (
        f"You asked for **Grade {requested}**, but the data returned is only for **{shown}**. "
        f"Please check the grade number or ask for one of the configured grades."
    )


def resolve_filter_message(
    db: "DBService",
    question: str,
    columns: list,
    rows: list[tuple],
) -> Optional[str]:
    """
    If the user asked for a specific grade/class, return a clear message when:
    - that grade is not in `classes`, or
    - rows belong to other grades, or
    - the grade exists but there are no rows.

    Returns None when results are consistent with the question (or no grade filter).
    """
    requested = parse_requested_grade(question)
    if not requested:
        return None

    available = fetch_available_grades(db)

    if available and requested not in available:
        return message_grade_not_configured(requested, available, question)

    if not rows:
        if available and requested in available:
            return message_grade_no_records(requested, question)
        if available:
            return message_grade_not_configured(requested, available, question)
        return (
            f"No records were found for **Grade {requested}**"
            f"{_topic_hint(question)}."
        )

    result_grades = grades_in_result_rows(columns, rows)
    if not result_grades:
        return None

    if requested not in result_grades:
        return message_grade_result_mismatch(requested, result_grades, available)

    return None
