"""
Online Course addon SQL — online_courses, question bank, tags.
Distinct from onlineexam (school online exams) and legacy `courses` table.
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
    """Straighten curly/smart quotes so title patterns match UI copy-paste."""
    q = question or ""
    return q.translate({
        ord("\u201c"): '"',
        ord("\u201d"): '"',
        ord("\u2018"): "'",
        ord("\u2019"): "'",
    })


def is_online_course_lms_question(question: str) -> bool:
    """True when the user means the Online Course addon (not onlineexam / school exams)."""
    q = (question or "").lower()
    if re.search(r"\bonline\s+exams?\b", q):
        return False
    if "online course" in q or "online courses" in q:
        return True
    if "question bank" in q and "online" in q:
        return True
    if "online_course" in q:
        return True
    if re.search(r"\bonlinecourse\b", q):
        return True
    return False


def _extract_question_tag(question: str) -> Optional[str]:
    q = question or ""
    for pat in (
        r"question\s+tag\s*:\s*([A-Za-z0-9_\-\s]+?)(?:\s+from|\?|$)",
        r"tag\s*:\s*([A-Za-z0-9_\-\s]+?)(?:\s+from|\?|$)",
        r"question\s+tag\s+([A-Za-z0-9_\-\s]+?)(?:\s+from|\?|$)",
        r"with\s+tag\s+([A-Za-z0-9_\-\s]+?)(?:\s+from|\?|$)",
    ):
        m = re.search(pat, q, re.IGNORECASE)
        if m:
            tag = " ".join(m.group(1).split()).strip("?.!,;:")
            if tag and len(tag) >= 2:
                return tag
    return None


def _normalize_question_type(question: str) -> Optional[str]:
    q = (question or "").lower()
    if re.search(r"\bsingle[\s-]?choice\b", q):
        return "singlechoice"
    if re.search(r"\bmulti[\s-]?choice\b", q):
        return "multichoice"
    if re.search(r"\btrue[\s/_-]?false\b", q):
        return "true_false"
    if "descriptive" in q:
        return "descriptive"
    return None


@dataclass
class OnlineCourseEnrollmentReport:
    """Result of an online-course enrollment / completion lookup."""

    message: str = ""
    columns: list[str] = field(default_factory=list)
    rows: list = field(default_factory=list)
    sql_note: str = ""


def is_online_course_enrollment_question(question: str) -> bool:
    """Enrollment / completion reports for a named Online Course addon course."""
    q = (question or "").lower()
    if "course" not in q:
        return False
    cues = (
        "enrollment",
        "enrolment",
        "enrolled",
        "completion rate",
        "completion rates",
        "completed the course",
        "enrollment report",
        "enrollment numbers",
    )
    if not any(c in q for c in cues):
        return False
    if _extract_course_title(question):
        return True
    if "online course" in q or "online courses" in q:
        return True
    return "enrollment report" in q or "completion rate" in q


def _extract_course_title(question: str) -> Optional[str]:
    q = _normalize_question_text(question)
    m = re.search(r'["\']([^"\']{2,80})["\']', q)
    if m:
        return " ".join(m.group(1).split()).strip("?.!,;:")
    for pat in (
        r"\bshow\s+course\s+(.+?)(?:\?|$|\.)",
        r"\bdisplay\s+course\s+(.+?)(?:\?|$|\.)",
        r"(?:enrollment|enrolment|completion)(?:\s+\w+){0,4}\s+(?:for|of)\s+"
        r"(?!the\b|all\b|each\b|every\b)([A-Za-z0-9][A-Za-z0-9\s&\-\.:]{1,80}?)\s+course\b",
        r"(?:for|of)\s+[\"']?([A-Za-z0-9][A-Za-z0-9\s&\-\.:]{1,80}?)[\"']?\s+course\b",
        r"online courses?\s+([A-Za-z0-9][A-Za-z0-9\s&\-\.:]{1,80}?)(?:\?|$|\.)",
        r"course\s+(?:named|called|titled)\s+([A-Za-z0-9][A-Za-z0-9\s&\-\.:]{1,80}?)(?:\?|$|\.)",
    ):
        m = re.search(pat, q, re.IGNORECASE)
        if m:
            title = " ".join(m.group(1).split()).strip('"\'?.!,;:')
            if title and len(title) >= 2:
                if re.match(r"^(all|active|every|online)\b", title, re.IGNORECASE):
                    continue
                return title
    return None


def is_named_course_show_question(question: str) -> bool:
    """Show details for one named course — not a list-all request."""
    ql = (question or "").lower()
    if not re.search(r"\b(?:show|display|get|find)\b", ql):
        return False
    if "course" not in ql:
        return False
    if re.search(r"\b(?:all|active|list|every|online courses?)\b", ql):
        return False
    if is_online_course_enrollment_question(question):
        return False
    return bool(_extract_course_title(question))


def resolve_named_course_lookup(
    db: "DBService",
    question: str,
) -> Optional[OnlineCourseEnrollmentReport]:
    """Return one online course row, or a clear not-found message."""
    if not is_named_course_show_question(question):
        return None
    if not db.table_exists("online_courses"):
        return None

    title = _extract_course_title(question)
    if not title:
        return None

    esc = _esc(title)
    match_sql = (
        "SELECT oc.id, oc.title, oc.status, oc.course_provider, oc.free_course, "
        "oc.updated_date, cc.category_name, "
        "CONCAT_WS(' ', st.name, st.surname) AS teacher_name "
        "FROM online_courses oc "
        "LEFT JOIN course_category cc ON cc.id = oc.category_id "
        "LEFT JOIN staff st ON st.id = oc.teacher_id "
        f"WHERE LOWER(oc.title) LIKE LOWER('%{esc}%') "
        f"ORDER BY CASE WHEN LOWER(oc.title) = LOWER('{esc}') THEN 0 "
        f"WHEN LOWER(oc.title) LIKE LOWER('{esc}%') THEN 1 ELSE 2 END, oc.title "
        "LIMIT 5"
    )
    matches, _, err = db.execute(match_sql, max_rows=5)
    if err:
        return None

    if not matches:
        list_sql = (
            "SELECT title FROM online_courses WHERE status = '1' "
            "ORDER BY title LIMIT 15"
        )
        available, _, _ = db.execute(list_sql, max_rows=15)
        names = [r[0] for r in (available or []) if r and r[0]]
        msg = f'No course named **"{title}"** exists in the Online Course module.'
        if names:
            shown = ", ".join(f"*{n}*" for n in names[:10])
            extra = f" (+{len(names) - 10} more)" if len(names) > 10 else ""
            msg += f"\n\n**Available courses:** {shown}{extra}"
        else:
            msg += "\n\nThere are no published online courses in the system yet."
        return OnlineCourseEnrollmentReport(
            message=msg,
            sql_note=f"-- course lookup: {title!r} (0 matches)",
        )

    best = matches[0]
    for row in matches:
        if str(row[1]).lower() == title.lower():
            best = row
            break

    course_id, course_title, status, provider, free, updated, category, teacher = best
    status_label = "Active" if str(status) == "1" else "Inactive"
    columns = [
        "course_id",
        "course_title",
        "status",
        "category",
        "course_provider",
        "free_course",
        "teacher_name",
        "updated_date",
    ]
    rows = [[
        course_id,
        course_title,
        status_label,
        category or "",
        provider or "",
        "Yes" if str(free) == "1" else "No",
        teacher or "",
        updated,
    ]]
    return OnlineCourseEnrollmentReport(
        message=f'Course **"{course_title}"** ({status_label}).',
        columns=columns,
        rows=rows,
        sql_note=f"-- named course lookup: {course_title!r} (id={course_id})",
    )


def resolve_online_course_enrollment_report(
    db: "DBService",
    question: str,
) -> Optional[OnlineCourseEnrollmentReport]:
    """
    Validate the course exists in online_courses, then summarize enrollment
    and completion. Returns a plain message when the course name is unknown.
    """
    if not is_online_course_enrollment_question(question):
        return None

    title = _extract_course_title(question)
    if not title:
        return None

    esc = _esc(title)
    match_sql = (
        "SELECT id, title, status, free_course FROM online_courses "
        f"WHERE LOWER(title) LIKE LOWER('%{esc}%') "
        f"ORDER BY CASE WHEN LOWER(title) = LOWER('{esc}') THEN 0 "
        f"WHEN LOWER(title) LIKE LOWER('{esc}%') THEN 1 ELSE 2 END, title "
        "LIMIT 5"
    )
    matches, _, err = db.execute(match_sql, max_rows=5)
    if err:
        return None

    if not matches:
        list_sql = (
            "SELECT title FROM online_courses WHERE status = '1' "
            "ORDER BY title LIMIT 15"
        )
        available, _, _ = db.execute(list_sql, max_rows=15)
        names = [r[0] for r in (available or []) if r and r[0]]
        msg = (
            f'No online course named **"{title}"** exists in the Online Course module.'
        )
        if names:
            shown = ", ".join(f"*{n}*" for n in names[:10])
            extra = f" (+{len(names) - 10} more)" if len(names) > 10 else ""
            msg += f"\n\n**Available courses:** {shown}{extra}"
        else:
            msg += "\n\nThere are no published online courses in the system yet."
        return OnlineCourseEnrollmentReport(
            message=msg,
            sql_note=f"-- course lookup: {title!r} (0 matches)",
        )

    course_id, course_title, status, _free = matches[0]
    for row in matches:
        if str(row[1]).lower() == title.lower():
            course_id, course_title, status, _free = row
            break

    items_sql = (
        "SELECT ("
        "  (SELECT COUNT(*) FROM online_course_lesson ocl "
        "   JOIN online_course_section ocs ON ocs.id = ocl.course_section_id "
        f"   WHERE ocs.online_course_id = {course_id})"
        "  +"
        "  (SELECT COUNT(*) FROM online_course_quiz ocq "
        "   JOIN online_course_section ocs ON ocs.id = ocq.course_section_id "
        f"   WHERE ocs.online_course_id = {course_id})"
        "  +"
        "  (SELECT COUNT(*) FROM online_course_assignment oca "
        "   JOIN online_course_section ocs ON ocs.id = oca.course_section_id "
        f"   WHERE ocs.online_course_id = {course_id})"
        "  +"
        "  (SELECT COUNT(*) FROM online_course_exam oce "
        f"   WHERE oce.course_id = {course_id})"
        ") AS total_items"
    )
    item_rows, _, _ = db.execute(items_sql, max_rows=1)
    total_items = int((item_rows[0][0] if item_rows else 0) or 0)

    stats_sql = (
        "SELECT "
        f"  (SELECT COUNT(*) FROM ("
        f"    SELECT cp.student_id AS uid FROM course_progress cp "
        f"    WHERE cp.course_id = {course_id} AND cp.student_id IS NOT NULL AND cp.student_id > 0 "
        f"    UNION "
        f"    SELECT ocp.student_id FROM online_course_payment ocp "
        f"    WHERE ocp.online_courses_id = {course_id} AND ocp.student_id IS NOT NULL AND ocp.student_id > 0 "
        f"    UNION "
        f"    SELECT cp.guest_id FROM course_progress cp "
        f"    WHERE cp.course_id = {course_id} AND cp.guest_id IS NOT NULL AND cp.guest_id > 0 "
        f"    UNION "
        f"    SELECT ocp.guest_id FROM online_course_payment ocp "
        f"    WHERE ocp.online_courses_id = {course_id} AND ocp.guest_id IS NOT NULL AND ocp.guest_id > 0"
        f"  ) learners) AS total_enrolled, "
        f"  (SELECT COUNT(DISTINCT cp.student_id) FROM course_progress cp "
        f"   WHERE cp.course_id = {course_id} AND cp.student_id IS NOT NULL AND cp.student_id > 0) AS students, "
        f"  (SELECT COUNT(DISTINCT cp.guest_id) FROM course_progress cp "
        f"   WHERE cp.course_id = {course_id} AND cp.guest_id IS NOT NULL AND cp.guest_id > 0) AS guests"
    )
    stat_rows, _, err = db.execute(stats_sql, max_rows=1)
    if err or not stat_rows:
        return None

    total_enrolled = int(stat_rows[0][0] or 0)
    students = int(stat_rows[0][1] or 0)
    guests = int(stat_rows[0][2] or 0)

    completed = 0
    if total_items > 0:
        complete_sql = (
            "SELECT COUNT(*) FROM ("
            f"  SELECT cp.student_id AS learner_id FROM course_progress cp "
            f"  WHERE cp.course_id = {course_id} AND cp.student_id IS NOT NULL AND cp.student_id > 0 "
            f"  GROUP BY cp.student_id HAVING COUNT(*) >= {total_items} "
            f"  UNION ALL "
            f"  SELECT cp.guest_id FROM course_progress cp "
            f"  WHERE cp.course_id = {course_id} AND cp.guest_id IS NOT NULL AND cp.guest_id > 0 "
            f"  GROUP BY cp.guest_id HAVING COUNT(*) >= {total_items}"
            ") completed"
        )
        cr, _, _ = db.execute(complete_sql, max_rows=1)
        completed = int(cr[0][0] if cr else 0)

    completion_rate = (
        round(100.0 * completed / total_enrolled, 1) if total_enrolled else 0.0
    )
    status_label = "Active" if str(status) == "1" else "Inactive"

    columns = [
        "course_title",
        "course_status",
        "total_enrolled",
        "students",
        "guests",
        "completed_learners",
        "completion_rate_pct",
    ]
    rows = [[
        course_title,
        status_label,
        total_enrolled,
        students,
        guests,
        completed,
        completion_rate,
    ]]

    if total_enrolled == 0:
        lead = (
            f'Course **"{course_title}"** exists ({status_label}), '
            "but **no learners are enrolled** yet (no payments or progress recorded)."
        )
    else:
        lead = (
            f'Enrollment summary for **"{course_title}"** ({status_label}): '
            f"**{total_enrolled}** enrolled ({students} students, {guests} guests), "
            f"**{completed}** completed ({completion_rate}% completion rate)."
        )

    return OnlineCourseEnrollmentReport(
        message=lead,
        columns=columns,
        rows=rows,
        sql_note=(
            f"-- online course enrollment report: {course_title!r} "
            f"(id={course_id}, items={total_items})"
        ),
    )


def try_online_course_sql(question: str) -> Optional[str]:
    q = (question or "").lower().strip()
    if not q:
        return None

    if not is_online_course_lms_question(question):
        return None

    # --- Question bank ---
    from services.online_exam_question_bank_sql import (
        is_online_exam_question_bank_question,
    )

    if (
        not is_online_exam_question_bank_question(question)
        and ("question bank" in q or "question tag" in q or re.search(r"\bquestions?\b", q))
    ):
        qtype = _normalize_question_type(question)
        tag = _extract_question_tag(question)
        parts = ["1=1"]
        if qtype:
            parts.append(f"q.question_type = '{qtype}'")
        if tag:
            sn = _esc(tag)
            parts.append(f"LOWER(t.tag_name) LIKE LOWER('%{sn}%')")
        return (
            "SELECT q.id, q.question, q.question_type, q.level, "
            "q.opt_a, q.opt_b, q.opt_c, q.opt_d, q.opt_e, q.correct, "
            "t.tag_name AS question_tag, "
            "CONCAT_WS(' ', st.name, st.surname) AS created_by "
            "FROM online_course_exam_question q "
            "LEFT JOIN online_course_tag t ON t.id = q.question_tag "
            "LEFT JOIN staff st ON st.id = q.staff_id "
            f"WHERE {' AND '.join(parts)} "
            "ORDER BY q.id DESC LIMIT 50"
        )

    # --- Active / all courses list ---
    if (
        re.search(r"\b(?:list|show|give|display|all)\b", q)
        and "course" in q
        and not is_online_course_enrollment_question(question)
        and not is_named_course_show_question(question)
    ):
        status_filter = ""
        if "active" in q:
            status_filter = " AND oc.status = '1'"
        elif "inactive" in q or "disabled" in q:
            status_filter = " AND oc.status = '0'"
        return (
            "SELECT oc.id, oc.title, oc.status, oc.course_provider, oc.free_course, "
            "oc.updated_date, cc.category_name, "
            "CONCAT_WS(' ', st.name, st.surname) AS teacher_name "
            "FROM online_courses oc "
            "LEFT JOIN course_category cc ON cc.id = oc.category_id "
            "LEFT JOIN staff st ON st.id = oc.teacher_id "
            f"WHERE 1=1{status_filter} "
            "ORDER BY oc.title ASC LIMIT 50"
        )

    return None
