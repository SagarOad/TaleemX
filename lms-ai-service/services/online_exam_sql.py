"""
Online exam schedule SQL — /admin/onlineexam (onlineexam table).

Distinct from exam_groups (school exams) and online_courses addon.
"""

from __future__ import annotations

import re
from dataclasses import dataclass, field
from typing import TYPE_CHECKING, Optional

if TYPE_CHECKING:
    from services.db_service import DBService

from services.exam_results_sql import _RESULTS_RE, _SCHEDULE_RE, is_exam_results_question
from services.online_course_sql import is_online_course_lms_question
from services.question_normalize import routing_question


def _esc(s: str) -> str:
    return (s or "").replace("'", "''")


def _normalize_question_text(question: str) -> str:
    q = question or ""
    return q.translate({
        ord("\u201c"): '"',
        ord("\u201d"): '"',
        ord("\u2018"): "'",
        ord("\u2019"): "'",
    })


def _session_sq() -> str:
    return "(SELECT session_id FROM sch_settings LIMIT 1)"


def _clean_exam_title(raw: str) -> str:
    title = " ".join((raw or "").split()).strip(" ,?.!:;")
    title = re.sub(r"\s+schedule[d]?$", "", title, flags=re.IGNORECASE)
    title = re.sub(r"^(?:the|an|a)\s+", "", title, flags=re.IGNORECASE)
    return title.strip()


def extract_online_exam_title(question: str) -> Optional[str]:
    """Pull the online exam name from a schedule question."""
    q = _normalize_question_text(question)
    ql = q.lower()

    m = re.search(r'["\']([^"\']{3,120})["\']', q)
    if m:
        return _clean_exam_title(m.group(1))

    for pat in (
        r"\bonline\s+exam\s+(.+?)(?:\?|$|\.)",
        r"\bschedule[d]?\s+(?:of|for)\s+(?:the\s+)?(?:online\s+exam\s+)?(.+?)(?:\?|$|\.)",
        r"\bwhen\s+is\s+(?:the\s+)?(?:online\s+exam\s+)?(.+?)\s+scheduled",
        r"\b(?:show|give|get)\s+(?:me\s+)?(?:the\s+)?schedule\s+(?:of|for)\s+(?:online\s+exam\s+)?(.+?)(?:\?|$|\.)",
    ):
        m = re.search(pat, q, re.IGNORECASE)
        if m:
            title = _clean_exam_title(m.group(1))
            if title and len(title) >= 3:
                return title

    if _SCHEDULE_RE.search(ql) and "exam" in ql:
        stripped = q
        for noise in (
            r"\bshow\b", r"\bgive\b", r"\blist\b", r"\bget\b", r"\bdisplay\b",
            r"\bexams?\b", r"\bscheduled\b", r"\bschedule\b", r"\bonline\b",
            r"\bof\b", r"\bthe\b", r"\ba\b", r"\bme\b", r"\bfor\b",
        ):
            stripped = re.sub(noise, " ", stripped, flags=re.IGNORECASE)
        title = _clean_exam_title(stripped)
        low = title.lower()
        if (
            title
            and len(title) >= 5
            and "all" not in low.split()
            and "every" not in low.split()
            and "available" not in low
        ):
            return title

    return None


def is_named_online_exam_schedule_question(question: str) -> bool:
    """Schedule for one named online exam — not list-all."""
    if is_online_course_lms_question(question):
        return False
    if is_exam_results_question(question):
        return False

    rq = routing_question(question).lower()
    if "exam" not in rq and "online" not in rq:
        return False
    if re.search(r"\b(?:all|every|available)\s+(?:online\s+)?exams?\b", rq):
        return False
    if re.search(r"\b(?:list|show|give)\s+(?:all|every)\b", rq) and "exam" in rq:
        if not extract_online_exam_title(question):
            return False

    title = extract_online_exam_title(question)
    if title:
        return True

    if _SCHEDULE_RE.search(rq) and re.search(r"\bonline\s+exam\b", rq):
        return bool(title)

    return False


def is_upcoming_online_exam_list_question(question: str) -> bool:
    rq = routing_question(question).lower()
    return bool(re.search(r"\bupcoming\b", rq) and re.search(r"\bonline\s+exam|\bexams?\b", rq))


def is_online_exam_schedule_list_question(question: str) -> bool:
    """All scheduled online exams — no specific exam name."""
    if is_named_online_exam_schedule_question(question):
        return False
    if is_online_course_lms_question(question) or is_exam_results_question(question):
        return False
    rq = routing_question(question).lower()
    if is_upcoming_online_exam_list_question(question):
        return True
    if "online exam" not in rq and "onlineexam" not in rq:
        if not (_SCHEDULE_RE.search(rq) and "exam" in rq):
            return False
    return bool(
        _SCHEDULE_RE.search(rq)
        or "available exams" in rq
        or re.search(r"\bwhen\s+(?:is|are)\b.*\bexam", rq)
    )


def _title_where(title: str) -> str:
    esc = _esc(title)
    tokens = [t for t in re.split(r"\s+", title.strip()) if len(t) >= 2]
    parts = [f"LOWER(oe.exam) LIKE LOWER('%{_esc(title)}%')"]
    if len(tokens) >= 2:
        token_parts = [
            f"LOWER(oe.exam) LIKE LOWER('%{_esc(t)}%')" for t in tokens if len(t) >= 2
        ]
        parts.append(f"({' AND '.join(token_parts)})")
    return f"({' OR '.join(parts)})"


def build_online_exam_schedule_lookup_sql(title: str) -> str:
    tw = _title_where(title)
    esc = _esc(title)
    return (
        "SELECT oe.id AS exam_id, oe.exam AS exam_name, "
        "oe.exam_from AS schedule_start, oe.exam_to AS schedule_end, "
        "oe.time_from, oe.time_to, oe.duration, oe.attempt, oe.description, "
        "CASE WHEN oe.is_active = 1 THEN 'Published' ELSE 'Unpublished' END AS publish_status, "
        "CASE "
        "  WHEN oe.exam_to IS NOT NULL AND oe.exam_to < NOW() THEN 'Closed' "
        "  WHEN oe.exam_from IS NOT NULL AND oe.exam_from > NOW() THEN 'Upcoming' "
        "  ELSE 'Open' "
        "END AS exam_window_status, "
        "(SELECT COUNT(*) FROM onlineexam_questions oq WHERE oq.onlineexam_id = oe.id) "
        "AS total_questions "
        "FROM onlineexam oe "
        f"WHERE oe.session_id = {_session_sq()} AND {tw} "
        f"ORDER BY CASE WHEN LOWER(oe.exam) = LOWER('{esc}') THEN 0 "
        f"WHEN LOWER(oe.exam) LIKE LOWER('{esc}%') THEN 1 "
        f"WHEN LOWER(oe.exam) LIKE LOWER('%{esc}%') THEN 2 ELSE 3 END, "
        "oe.exam_from DESC "
        "LIMIT 10"
    )


def build_online_exam_schedule_list_sql(upcoming_only: bool = False) -> str:
    where = [
        f"oe.session_id = {_session_sq()}",
        "oe.exam_from IS NOT NULL",
    ]
    if upcoming_only:
        where.append("oe.exam_to >= NOW()")
    return (
        "SELECT oe.id AS exam_id, oe.exam AS exam_name, "
        "oe.exam_from AS schedule_start, oe.exam_to AS schedule_end, "
        "oe.time_from, oe.time_to, oe.duration, "
        "CASE WHEN oe.is_active = 1 THEN 'Published' ELSE 'Unpublished' END AS publish_status, "
        "CASE "
        "  WHEN oe.exam_to IS NOT NULL AND oe.exam_to < NOW() THEN 'Closed' "
        "  WHEN oe.exam_from IS NOT NULL AND oe.exam_from > NOW() THEN 'Upcoming' "
        "  ELSE 'Open' "
        "END AS exam_window_status "
        "FROM onlineexam oe "
        f"WHERE {' AND '.join(where)} "
        "ORDER BY oe.exam_from ASC, oe.exam "
        "LIMIT 50"
    )


@dataclass
class OnlineExamScheduleReport:
    message: str = ""
    columns: list[str] = field(default_factory=list)
    rows: list = field(default_factory=list)
    sql_note: str = ""


def resolve_named_online_exam_schedule(
    db: "DBService",
    question: str,
) -> Optional[OnlineExamScheduleReport]:
    if not is_named_online_exam_schedule_question(question):
        return None
    if not db.table_exists("onlineexam"):
        return None

    title = extract_online_exam_title(question)
    if not title:
        return None

    sql = build_online_exam_schedule_lookup_sql(title)
    rows, columns, err = db.execute(sql, max_rows=10)
    if err:
        return OnlineExamScheduleReport(
            message=f"Could not load online exam schedule: {err}",
            sql_note=sql,
        )

    if not rows:
        list_sql = (
            "SELECT oe.exam FROM onlineexam oe "
            f"WHERE oe.session_id = {_session_sq()} "
            "ORDER BY oe.exam LIMIT 20"
        )
        avail, _, _ = db.execute(list_sql, max_rows=20)
        names = [r[0] for r in (avail or []) if r and r[0]]
        msg = (
            f'No online exam named **"{title}"** was found in **Online Examination** '
            "(including closed exams)."
        )
        if names:
            msg += "\n\n**Online exams in the system:** " + ", ".join(
                f"*{n}*" for n in names[:12]
            )
        return OnlineExamScheduleReport(message=msg, sql_note=sql)

    best = rows[0]
    exam_name = best[1] if len(best) > 1 else title
    window = best[10] if len(best) > 10 else ""
    lead = f'**Schedule for online exam "{exam_name}"**'
    if window == "Closed":
        lead += " (this exam is in **Closed** exams — end date has passed)."
    elif window:
        lead += f" — status: **{window}**."

    return OnlineExamScheduleReport(
        message=lead,
        columns=columns,
        rows=rows,
        sql_note=sql,
    )


def resolve_online_exam_schedule_list_sql(
    db: "DBService",
    question: str,
) -> Optional[str]:
    if not is_online_exam_schedule_list_question(question):
        return None
    if not db.table_exists("onlineexam"):
        return None
    upcoming = is_upcoming_online_exam_list_question(question)
    return build_online_exam_schedule_list_sql(upcoming_only=upcoming)
