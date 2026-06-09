"""
Lesson plan / syllabus status SQL — mirrors admin/syllabus/status (Manage Lesson Plan).

Tables: lesson, topic, subject_group_class_sections, subject_group_subjects,
        subject_groups, subjects, classes, sections, sessions.
"""

from __future__ import annotations

import re
from dataclasses import dataclass, field
from typing import TYPE_CHECKING, Optional

if TYPE_CHECKING:
    from services.db_service import DBService


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


def _grade(question: str) -> Optional[str]:
    m = re.search(r"\b(?:grade|class)\s*(\d+)\b", (question or "").lower())
    return m.group(1) if m else None


def _section(question: str) -> Optional[str]:
    m = re.search(r"\bsection\s+([A-Za-z0-9]+)\b", question or "", re.IGNORECASE)
    return m.group(1).upper() if m else None


def _parse_subject_label(text: str) -> tuple[str, Optional[str]]:
    """Parse 'English (Eng)' → ('English', 'Eng')."""
    raw = " ".join((text or "").split()).strip("?.!,;:")
    m = re.match(r"^(.+?)\s*\(([^)]+)\)\s*$", raw)
    if m:
        return m.group(1).strip(), m.group(2).strip()
    return raw, None


def is_lesson_plan_question(question: str) -> bool:
    q = (question or "").lower()
    if any(
        c in q
        for c in (
            "lesson plan",
            "lessonplan",
            "syllabus status",
            "syllabus for",
            "lesson topic",
            "topic completion",
            "manage lesson plan",
        )
    ):
        return True
    if "syllabus" in q and any(h in q for h in ("grade", "class", "section", "subject")):
        return True
    return "lesson" in q and "plan" in q


@dataclass
class LessonPlanFilters:
    grade: Optional[str] = None
    section: Optional[str] = None
    subject_group: Optional[str] = None
    subject_name: Optional[str] = None
    subject_code: Optional[str] = None


@dataclass
class LessonPlanReport:
    message: str = ""
    columns: list[str] = field(default_factory=list)
    rows: list = field(default_factory=list)
    sql_note: str = ""


def parse_lesson_plan_filters(question: str) -> LessonPlanFilters:
    q = _normalize_question_text(question)
    ql = q.lower()
    flt = LessonPlanFilters(
        grade=_grade(q),
        section=_section(q),
    )

    m = re.search(r"subject\s+group\s+(.+?)\s+subject\s+", q, re.IGNORECASE)
    if m:
        flt.subject_group = " ".join(m.group(1).split()).strip()

    subject_raw: Optional[str] = None
    m = re.search(r"\bsubject\s+(.+?)(?:\s+syllabus\b)", q, re.IGNORECASE)
    if m:
        subject_raw = m.group(1).strip()
    m = re.search(r"syllabus status for:\s*(.+?)(?:\?|$|\.)", q, re.IGNORECASE)
    if m:
        subject_raw = m.group(1).strip()
    if not subject_raw:
        m = re.search(
            r"(?:for|in)\s+([A-Za-z][A-Za-z\s&\-\.]{1,40}?)\s+(?:subject|syllabus)\b",
            q,
            re.IGNORECASE,
        )
        if m:
            subject_raw = m.group(1).strip()

    if subject_raw:
        name, code = _parse_subject_label(subject_raw)
        flt.subject_name = name or None
        flt.subject_code = code

    return flt


def _session_subquery() -> str:
    """TaleemX stores the active session on sch_settings.session_id (not sessions.is_active)."""
    return "(SELECT session_id FROM sch_settings LIMIT 1)"


def _grade_sql(grade: str) -> str:
    g = _esc(grade)
    return f"(c.`class` = '{g}' OR c.`class` = 'Grade {g}' OR c.`class` = 'Class {g}')"


def resolve_lesson_plan_report(
    db: "DBService",
    question: str,
) -> Optional[LessonPlanReport]:
    if not is_lesson_plan_question(question):
        return None

    flt = parse_lesson_plan_filters(question)
    missing = []
    if not flt.grade:
        missing.append("grade/class (e.g. Grade 1)")
    if not flt.section:
        missing.append("section (e.g. Section A)")
    if not flt.subject_name and not flt.subject_code:
        missing.append("subject (e.g. English or English (Eng))")
    if missing:
        return LessonPlanReport(
            message=(
                "To show a **lesson plan / syllabus status**, please include: "
                + ", ".join(missing)
                + ".\n\nExample: *Show lesson plan for Grade 1 Section A "
                "Subject Group Core Subjects Subject English (Eng)*"
            ),
            sql_note="-- lesson plan: missing filters",
        )

    where = [
        f"sgcs.session_id = {_session_subquery()}",
        f"t.session_id = {_session_subquery()}",
        _grade_sql(flt.grade),
        f"UPPER(sec.section) = '{_esc(flt.section)}'",
    ]
    if flt.subject_group:
        sg = _esc(flt.subject_group)
        where.append(f"LOWER(sg.name) LIKE LOWER('%{sg}%')")

    subj_parts = []
    if flt.subject_name:
        sn = _esc(flt.subject_name)
        subj_parts.append(f"LOWER(subj.name) LIKE LOWER('%{sn}%')")
    if flt.subject_code:
        sc = _esc(flt.subject_code)
        subj_parts.append(f"LOWER(subj.code) LIKE LOWER('%{sc}%')")
    if subj_parts:
        where.append(f"({' OR '.join(subj_parts)})")

    sql = (
        "SELECT c.`class` AS grade, sec.section, sg.name AS subject_group, "
        "subj.name AS subject_name, subj.code AS subject_code, "
        "l.name AS lesson_name, t.name AS topic_name, "
        "CASE WHEN t.status = '1' THEN 'Completed' ELSE 'Incomplete' END AS topic_status, "
        "CASE WHEN t.complete_date IS NOT NULL AND t.complete_date > '1000-01-01' "
        "THEN t.complete_date ELSE NULL END AS completion_date "
        "FROM lesson l "
        "INNER JOIN topic t ON t.lesson_id = l.id "
        "INNER JOIN subject_group_subjects sgs ON sgs.id = l.subject_group_subject_id "
        "INNER JOIN subjects subj ON subj.id = sgs.subject_id "
        "INNER JOIN subject_groups sg ON sg.id = sgs.subject_group_id "
        "INNER JOIN subject_group_class_sections sgcs "
        "  ON sgcs.id = l.subject_group_class_sections_id "
        "INNER JOIN class_sections cs ON cs.id = sgcs.class_section_id "
        "INNER JOIN classes c ON c.id = cs.class_id "
        "INNER JOIN sections sec ON sec.id = cs.section_id "
        f"WHERE {' AND '.join(where)} "
        "ORDER BY l.id, t.id LIMIT 200"
    )

    rows, columns, err = db.execute(sql, max_rows=200)
    if err:
        if "doesn't exist" in err.lower():
            return LessonPlanReport(
                message=(
                    "The lesson plan tables are not available in this database "
                    "(Lesson Plan module may not be installed)."
                ),
                sql_note=sql,
            )
        return LessonPlanReport(
            message=f"Could not load lesson plan data: {err}",
            sql_note=sql,
        )

    label = (
        f"Grade {flt.grade} Section {flt.section}"
        + (f", {flt.subject_group}" if flt.subject_group else "")
        + f", {flt.subject_name or ''}"
        + (f" ({flt.subject_code})" if flt.subject_code else "")
    ).strip(", ")

    if not rows:
        # Distinguish: class/section/subject combo not set up vs no topics yet.
        probe_sql = (
            "SELECT sg.name AS subject_group, subj.name AS subject_name, subj.code AS subject_code "
            "FROM subject_group_subjects sgs "
            "INNER JOIN subjects subj ON subj.id = sgs.subject_id "
            "INNER JOIN subject_groups sg ON sg.id = sgs.subject_group_id "
            "INNER JOIN subject_group_class_sections sgcs "
            "  ON sgcs.subject_group_id = sg.id "
            "INNER JOIN class_sections cs ON cs.id = sgcs.class_section_id "
            "INNER JOIN classes c ON c.id = cs.class_id "
            "INNER JOIN sections sec ON sec.id = cs.section_id "
            f"WHERE sgcs.session_id = {_session_subquery()} "
            f"AND {_grade_sql(flt.grade)} "
            f"AND UPPER(sec.section) = '{_esc(flt.section)}' "
            + (
                f"AND LOWER(sg.name) LIKE LOWER('%{_esc(flt.subject_group)}%') "
                if flt.subject_group
                else ""
            )
            + f"AND ({' OR '.join(subj_parts)}) "
            "LIMIT 5"
        )
        probe, _, _ = db.execute(probe_sql, max_rows=5)
        if not probe:
            avail_sql = (
                "SELECT DISTINCT subj.name, subj.code FROM subject_group_subjects sgs "
                "INNER JOIN subjects subj ON subj.id = sgs.subject_id "
                "INNER JOIN subject_group_class_sections sgcs "
                "  ON sgcs.subject_group_id = sgs.subject_group_id "
                "INNER JOIN class_sections cs ON cs.id = sgcs.class_section_id "
                "INNER JOIN classes c ON c.id = cs.class_id "
                "INNER JOIN sections sec ON sec.id = cs.section_id "
                f"WHERE sgcs.session_id = {_session_subquery()} "
                f"AND {_grade_sql(flt.grade)} "
                f"AND UPPER(sec.section) = '{_esc(flt.section)}' "
                "ORDER BY subj.name LIMIT 15"
            )
            avail, _, _ = db.execute(avail_sql, max_rows=15)
            names = [
                f"{r[0]} ({r[1]})" if len(r) > 1 and r[1] else str(r[0])
                for r in (avail or [])
                if r and r[0]
            ]
            msg = (
                f'No lesson plan found for **{label}**. '
                "Check that the subject is assigned to this class/section "
                "in **Subject Group** settings."
            )
            if names:
                msg += "\n\n**Subjects assigned here:** " + ", ".join(f"*{n}*" for n in names)
            return LessonPlanReport(message=msg, sql_note=sql)

        return LessonPlanReport(
            message=(
                f'**{label}** is configured, but **no lessons or topics** have been added yet '
                "in **Manage Lesson Plan**."
            ),
            sql_note=sql,
        )

    total = len(rows)
    completed = sum(1 for r in rows if len(r) > 7 and r[7] == "Completed")
    incomplete = total - completed
    pct = round(100.0 * completed / total, 1) if total else 0.0

    lead = (
        f"**Syllabus status for {label}** — "
        f"{total} topic(s): **{completed}** completed, **{incomplete}** incomplete "
        f"({pct}% complete)."
    )

    return LessonPlanReport(
        message=lead,
        columns=columns or [
            "grade", "section", "subject_group", "subject_name", "subject_code",
            "lesson_name", "topic_name", "topic_status", "completion_date",
        ],
        rows=rows,
        sql_note=sql,
    )
