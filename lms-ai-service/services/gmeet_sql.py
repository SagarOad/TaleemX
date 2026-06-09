"""
Gmeet live class SQL — addon tables gmeet, gmeet_sections, gmeet_history.
"""

from __future__ import annotations

import re
from dataclasses import dataclass, field
from typing import TYPE_CHECKING, Optional

if TYPE_CHECKING:
    from services.db_service import DBService

from services.date_filters import (
    fetch_school_date_format,
    parse_datetime_from_question,
    parse_exact_date_from_question,
    parse_literal_date,
)
from services.question_normalize import routing_question


def _esc(s: str) -> str:
    return (s or "").replace("'", "''")


def _session_sq() -> str:
    return "(SELECT session_id FROM sch_settings LIMIT 1)"


def is_gmeet_live_class_question(question: str) -> bool:
    rq = routing_question(question).lower()
    if re.search(r"\bgmeet\b|\bg\s*meet\b|google\s+meet", rq):
        return True
    if "live class" in rq and re.search(r"\bgmeet|google|meet\b", rq):
        return True
    return bool(
        re.search(r"\blive\s+classes?\b", rq)
        and re.search(r"\bgmeet|google meet", rq)
    )


@dataclass
class GmeetReport:
    message: str = ""
    columns: list[str] = field(default_factory=list)
    rows: list = field(default_factory=list)
    sql_note: str = ""


def resolve_gmeet_report(db: "DBService", question: str) -> Optional[GmeetReport]:
    if not is_gmeet_live_class_question(question):
        return None
    if not db.table_exists("gmeet"):
        return GmeetReport(
            message="Gmeet live class addon is not installed in this database.",
            sql_note="-- gmeet missing",
        )

    school_fmt = fetch_school_date_format(db)
    has_time = bool(
        re.search(
            r"\b\d{1,2}[/-]\d{1,2}[/-]\d{4}\s+\d{1,2}:\d{2}",
            question or "",
        )
    )
    dt_str = parse_datetime_from_question(question, school_fmt) if has_time else None
    exact = parse_exact_date_from_question(question, school_fmt)
    if not exact:
        exact = parse_literal_date(question, school_fmt)

    date_filter = ""
    if dt_str:
        esc = _esc(dt_str)
        date_filter = f" AND g.date = '{esc}'"
    elif exact:
        date_filter = f" AND DATE(g.date) = '{exact.isoformat()}'"

    sql = (
        "SELECT g.id AS gmeet_id, g.title, g.date AS scheduled_at, g.duration, g.url, "
        "CASE g.status WHEN 0 THEN 'Scheduled' WHEN 2 THEN 'Completed' ELSE CAST(g.status AS CHAR) END "
        "AS class_status, "
        "CONCAT_WS(' ', st.name, st.surname) AS host_teacher, "
        "c.class AS grade, sec.section AS section_name, "
        "(SELECT COUNT(DISTINCT gh.student_id) FROM gmeet_history gh "
        " WHERE gh.gmeet_id = g.id AND gh.student_id IS NOT NULL AND gh.student_id > 0) "
        "AS student_participants, "
        "(SELECT COUNT(DISTINCT gh.staff_id) FROM gmeet_history gh "
        " WHERE gh.gmeet_id = g.id AND gh.staff_id IS NOT NULL AND gh.staff_id > 0) "
        "AS staff_participants "
        "FROM gmeet g "
        "LEFT JOIN staff st ON st.id = g.staff_id "
        "LEFT JOIN gmeet_sections gs ON gs.gmeet_id = g.id "
        "LEFT JOIN class_sections cs ON cs.id = gs.cls_section_id "
        "LEFT JOIN classes c ON c.id = cs.class_id "
        "LEFT JOIN sections sec ON sec.id = cs.section_id "
        f"WHERE g.purpose = 'class' AND g.session_id = {_session_sq()}{date_filter} "
        "ORDER BY g.date ASC LIMIT 100"
    )
    rows, cols, err = db.execute(sql, max_rows=100)
    if err:
        if "doesn't exist" in err.lower():
            return GmeetReport(
                message="Gmeet live class addon tables are not installed.",
                sql_note=sql,
            )
        return GmeetReport(message=f"Could not load Gmeet classes: {err}", sql_note=sql)
    if not rows:
        when = dt_str or (exact.isoformat() if exact else "that date")
        return GmeetReport(
            message=f"No **Gmeet live classes** scheduled for **{when}**.",
            sql_note=sql,
        )
    lead = f"**{len(rows)}** Gmeet live class(es)"
    if dt_str or exact:
        lead += f" on **{dt_str or exact}**"
    lead += " (with participant counts):"
    return GmeetReport(message=lead, columns=cols, rows=rows, sql_note=sql)
