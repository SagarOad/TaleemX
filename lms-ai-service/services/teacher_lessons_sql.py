"""
Lesson plan lessons assigned to a teacher (via subject timetable / class teacher).

Mirrors Manage Lesson Plan + Academics timetable — not online course lessons.
"""

from __future__ import annotations

import re
from dataclasses import dataclass, field
from datetime import date
from typing import TYPE_CHECKING, Optional

if TYPE_CHECKING:
    from services.db_service import DBService

from services.lesson_plan_sql import is_lesson_plan_question, parse_lesson_plan_filters
from services.name_filters import normalize_person_name
from services.question_normalize import routing_question
from services.video_lessons_sql import is_video_lessons_question


_NOT_TEACHER_NAMES = frozenset({
    "show lessons", "show lesson", "show all lessons", "list lessons",
    "give lessons", "video lessons", "show video lessons", "this week",
    "last week", "this month", "today", "all lessons", "active lessons",
})


def _is_plausible_teacher_name(name: str) -> bool:
    low = (name or "").lower().strip()
    if not low or low in _NOT_TEACHER_NAMES:
        return False
    if low.startswith(("show ", "list ", "give ", "display ", "get ")):
        return False
    return True


def _esc(s: str) -> str:
    return (s or "").replace("'", "''")


def _session_sq() -> str:
    return "(SELECT session_id FROM sch_settings LIMIT 1)"


def _today_weekday_key() -> tuple[str, str]:
    """TaleemX timetable day keys are English weekday names (Monday, …)."""
    today = date.today()
    name = today.strftime("%A")
    # Some installs store numeric day ids (1=Monday … 7=Sunday).
    iso = today.isoweekday()
    return name, str(iso)


def extract_teacher_name(question: str) -> Optional[str]:
    """Best-effort staff/teacher name from English or mixed questions."""
    q = routing_question(question)
    ql = q.lower()

    for pat in (
        r"\blessons?\s+by\s+(.+?)(?:\?|$|\.)",
        r"\b(?:give|show|list|display|get)\s+(?:all\s+)?lessons?\s+(?:by|for|of)\s+(.+?)(?:\?|$|\.)",
        r"\b(?:teacher|staff|instructor)\s+(.+?)(?:\?|$|\.)",
        r"\btoday\s+(.+?)(?:\?|$|\.)",
        r"\bby\s+([A-Za-z][A-Za-z\s\-']{3,70})(?:\?|$|\.)",
        r"\bfor\s+([A-Za-z][A-Za-z\s\-']{3,70})(?:\?|$|\.)",
    ):
        m = re.search(pat, q, re.IGNORECASE)
        if m:
            name = normalize_person_name(m.group(1))
            if len(name) >= 3 and _is_plausible_teacher_name(name):
                return name

    # Trailing Latin name after Arabic normalization, e.g. "... today fatima al qahtani"
    m = re.search(
        r"\b([A-Za-z][A-Za-z\-']+(?:\s+[A-Za-z][A-Za-z\-']+){1,4})\s*$",
        q,
    )
    if m:
        name = normalize_person_name(m.group(1))
        if len(name) >= 3 and _is_plausible_teacher_name(name):
            return name

    # "fatima al qahtani" anywhere in mixed text
    m = re.search(
        r"\b([A-Za-z][A-Za-z\-']+\s+[A-Za-z][A-Za-z\-']+(?:\s+[A-Za-z][A-Za-z\-']+)?)\b",
        q,
    )
    if m and "lesson" in ql:
        name = normalize_person_name(m.group(1))
        if len(name) >= 5 and _is_plausible_teacher_name(name):
            return name

    return None


def is_teacher_lessons_question(question: str) -> bool:
    """List lesson-plan lessons for a named teacher — not grade syllabus status."""
    if is_video_lessons_question(question):
        return False
    rq = routing_question(question).lower()
    raw = (question or "").lower()

    if "lesson" not in rq and "دروس" not in raw:
        return False

    if is_lesson_plan_question(question):
        flt = parse_lesson_plan_filters(question)
        if flt.grade and flt.section:
            return False

    if extract_teacher_name(question):
        return True

    if re.search(r"\blessons?\b", rq) and re.search(
        r"\b(?:by|for|teacher|staff|today)\b", rq
    ):
        return True

    return False


def _wants_today(question: str) -> bool:
    rq = routing_question(question).lower()
    return bool(
        re.search(r"\b(?:today|todays)\b", rq)
        or "اليوم" in (question or "")
        or "لهذا اليوم" in (question or "")
    )


def _staff_name_where(name: str) -> str:
    tokens = [t for t in re.split(r"\s+", name.strip()) if len(t) >= 2]
    if not tokens:
        esc = _esc(name)
        return f"LOWER(CONCAT_WS(' ', st.name, st.surname)) LIKE LOWER('%{esc}%')"
    return " AND ".join(
        f"LOWER(CONCAT_WS(' ', st.name, st.surname)) LIKE LOWER('%{_esc(t)}%')"
        for t in tokens
    )


@dataclass
class TeacherLessonsReport:
    message: str = ""
    columns: list[str] = field(default_factory=list)
    rows: list = field(default_factory=list)
    sql_note: str = ""


def _lookup_staff(db: "DBService", teacher_name: str) -> list[tuple]:
    where = _staff_name_where(teacher_name)
    sql = (
        "SELECT st.id, st.name, st.surname, st.email, st.employee_id "
        "FROM staff st "
        f"WHERE st.is_active = 1 AND {where} "
        "ORDER BY "
        f"CASE WHEN LOWER(CONCAT_WS(' ', st.name, st.surname)) = LOWER('{_esc(teacher_name)}') "
        f"THEN 0 ELSE 1 END, st.name, st.surname "
        "LIMIT 10"
    )
    rows, _, err = db.execute(sql, max_rows=10)
    if err:
        return []
    return rows or []


def _lesson_plan_sql_for_staff(staff_id: int, *, today_only: bool) -> str:
    sess = _session_sq()
    today_filter = ""
    if today_only:
        day_name, day_num = _today_weekday_key()
        dn = _esc(day_name)
        today_filter = (
            f" AND (LOWER(CAST(tt.day AS CHAR)) = LOWER('{dn}') "
            f"OR tt.day = '{day_num}' OR tt.day = '{_esc(day_name.lower())}')"
        )

    topic_date_filter = ""
    if today_only:
        topic_date_filter = (
            " AND t.complete_date IS NOT NULL "
            "AND t.complete_date > '1000-01-01' "
            "AND DATE(t.complete_date) = CURDATE()"
        )

    return (
        "SELECT "
        "CONCAT_WS(' ', st.name, st.surname) AS teacher_name, "
        "c.class AS grade, sec.section AS section_name, "
        "subj.name AS subject_name, subj.code AS subject_code, "
        "l.id AS lesson_id, l.name AS lesson_name, "
        "t.id AS topic_id, t.name AS topic_name, "
        "CASE WHEN t.status = '1' THEN 'Completed' ELSE 'Incomplete' END AS topic_status, "
        "CASE WHEN t.complete_date IS NOT NULL AND t.complete_date > '1000-01-01' "
        "THEN t.complete_date ELSE NULL END AS completion_date, "
        "tt.day AS timetable_day, tt.time_from, tt.time_to "
        "FROM staff st "
        "INNER JOIN subject_timetable tt ON tt.staff_id = st.id "
        f"AND tt.session_id = {sess}{today_filter} "
        "INNER JOIN classes c ON c.id = tt.class_id "
        "INNER JOIN sections sec ON sec.id = tt.section_id "
        "INNER JOIN class_sections cs "
        "  ON cs.class_id = tt.class_id AND cs.section_id = tt.section_id "
        "INNER JOIN subject_group_class_sections sgcs "
        "  ON sgcs.class_section_id = cs.id "
        " AND sgcs.subject_group_id = tt.subject_group_id "
        f" AND sgcs.session_id = {sess} "
        "INNER JOIN subject_group_subjects sgs ON sgs.id = tt.subject_group_subject_id "
        "INNER JOIN subjects subj ON subj.id = sgs.subject_id "
        "INNER JOIN lesson l "
        "  ON l.subject_group_subject_id = tt.subject_group_subject_id "
        " AND l.subject_group_class_sections_id = sgcs.id "
        f" AND l.session_id = {sess} "
        "LEFT JOIN topic t ON t.lesson_id = l.id "
        f" AND t.session_id = {sess}{topic_date_filter} "
        f"WHERE st.id = {int(staff_id)} AND st.is_active = 1 "
        "ORDER BY c.class, sec.section, subj.name, l.id, t.id "
        "LIMIT 300"
    )


def _timetable_only_sql(staff_id: int) -> str:
    """Today's scheduled periods when lesson plan rows are empty."""
    sess = _session_sq()
    day_name, day_num = _today_weekday_key()
    dn = _esc(day_name)
    return (
        "SELECT "
        "CONCAT_WS(' ', st.name, st.surname) AS teacher_name, "
        "c.class AS grade, sec.section AS section_name, "
        "tt.day AS timetable_day, tt.time_from, tt.time_to, tt.room_no, "
        "subj.name AS subject_name, subj.code AS subject_code "
        "FROM staff st "
        "INNER JOIN subject_timetable tt ON tt.staff_id = st.id "
        f"AND tt.session_id = {sess} "
        f"AND (LOWER(CAST(tt.day AS CHAR)) = LOWER('{dn}') "
        f"OR tt.day = '{day_num}' OR tt.day = '{_esc(day_name.lower())}') "
        "INNER JOIN classes c ON c.id = tt.class_id "
        "INNER JOIN sections sec ON sec.id = tt.section_id "
        "INNER JOIN subject_group_subjects sgs ON sgs.id = tt.subject_group_subject_id "
        "INNER JOIN subjects subj ON subj.id = sgs.subject_id "
        f"WHERE st.id = {int(staff_id)} AND st.is_active = 1 "
        "ORDER BY tt.time_from, c.class, sec.section "
        "LIMIT 100"
    )


def resolve_teacher_lessons_report(
    db: "DBService",
    question: str,
) -> Optional[TeacherLessonsReport]:
    if not is_teacher_lessons_question(question):
        return None
    if not db.table_exists("lesson") or not db.table_exists("staff"):
        return None

    teacher_name = extract_teacher_name(question)
    if not teacher_name:
        return TeacherLessonsReport(
            message=(
                "To list **lessons for a teacher**, include the teacher's name.\n\n"
                "Example: *Give all lessons by Fatima Al Qahtani* or "
                "*Show today's lessons for Fatima Al Qahtani*."
            ),
            sql_note="-- teacher lessons: missing name",
        )

    matches = _lookup_staff(db, teacher_name)
    if not matches:
        teachers_sql = (
            "SELECT DISTINCT CONCAT_WS(' ', st.name, st.surname) AS teacher_name "
            "FROM staff st "
            "INNER JOIN staff_roles sr ON sr.staff_id = st.id "
            "INNER JOIN roles r ON r.id = sr.role_id "
            "WHERE st.is_active = 1 "
            "AND (LOWER(r.slug) = 'teacher' OR LOWER(r.name) LIKE '%teacher%') "
            "ORDER BY teacher_name LIMIT 15"
        )
        avail, _, _ = db.execute(teachers_sql, max_rows=15)
        names = [r[0] for r in (avail or []) if r and r[0]]
        msg = f'No active staff member named **"{teacher_name}"** was found.'
        if names:
            msg += "\n\n**Teachers in the system:** " + ", ".join(
                f"*{n}*" for n in names[:12]
            )
        return TeacherLessonsReport(message=msg, sql_note=f"-- staff lookup: {teacher_name!r}")

    staff_id, fname, surname, _email, _emp = matches[0]
    for row in matches:
        full = f"{row[1]} {row[2]}".strip()
        if full.lower() == teacher_name.lower():
            staff_id, fname, surname = row[0], row[1], row[2]
            break

    display_name = f"{fname} {surname}".strip()
    today_only = _wants_today(question)

    sql = _lesson_plan_sql_for_staff(staff_id, today_only=today_only)
    rows, columns, err = db.execute(sql, max_rows=300)
    if err:
        if "doesn't exist" in err.lower():
            return TeacherLessonsReport(
                message="Lesson plan / timetable tables are not available in this database.",
                sql_note=sql,
            )
        return TeacherLessonsReport(
            message=f"Could not load lessons for **{display_name}**: {err}",
            sql_note=sql,
        )

    if not rows and today_only and db.table_exists("subject_timetable"):
        sql = _timetable_only_sql(staff_id)
        rows, columns, err = db.execute(sql, max_rows=100)
        if rows:
            lead = (
                f"**Today's timetable** for **{display_name}** "
                f"({len(rows)} period(s) scheduled):"
            )
            return TeacherLessonsReport(
                message=lead,
                columns=columns,
                rows=rows,
                sql_note=sql,
            )

    if not rows:
        scope = "today" if today_only else "the current session"
        return TeacherLessonsReport(
            message=(
                f"No lesson plan entries found for **{display_name}** for {scope}. "
                "Check **Academics → Timetable** and **Manage Lesson Plan** assignments."
            ),
            sql_note=sql,
        )

    lesson_count = len({r[5] for r in rows if len(r) > 5 and r[5]})
    topic_count = len(rows)
    scope = "today" if today_only else "all assigned subjects"
    lead = (
        f"**Lessons for {display_name}** ({scope}) — "
        f"**{lesson_count}** lesson(s), **{topic_count}** topic row(s):"
    )
    return TeacherLessonsReport(
        message=lead,
        columns=columns,
        rows=rows,
        sql_note=sql,
    )
