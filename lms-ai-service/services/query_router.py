"""
Central routing for high-confidence module SQL before LLM / vector fast-path.
"""

from __future__ import annotations

import re
from typing import TYPE_CHECKING, Optional

from services.action_guard import check_action_guard
from services.concept_answers import try_concept_answer
from services.date_filters import fetch_school_date_format, parse_invalid_day_question, sql_date_filter
from services.exam_results_sql import (
    resolve_exam_results_sql,
    resolve_exam_groups_sql,
    resolve_grade_distribution_report,
)
from services.online_exam_question_bank_sql import resolve_online_exam_question_bank
from services.online_exam_sql import (
    resolve_named_online_exam_schedule,
    resolve_online_exam_schedule_list_sql,
)
from services.fee_due_engine import (
    compute_fee_due_rows,
    matches_fee_due_question,
    resolve_payment_id_rows,
    resolve_transport_fee_status,
    try_fee_sql,
    fee_group_missing_message,
)
from services.attendance_sql import resolve_student_attendance_report
from services.video_lessons_sql import resolve_video_lessons_report
from services.download_center_sql import resolve_download_center_report
from services.gmeet_sql import resolve_gmeet_report
from services.inventory_sql import resolve_inventory_report
from services.library_sql import resolve_library_report
from services.hr_sql import resolve_hr_report
from services.front_office_sql import run_front_office_summary, try_front_office_sql
from services.lesson_plan_sql import resolve_lesson_plan_report
from services.question_normalize import routing_question
from services.teacher_lessons_sql import resolve_teacher_lessons_report
from services.online_course_sql import (
    resolve_named_course_lookup,
    resolve_online_course_enrollment_report,
    try_online_course_sql,
)
from services.student_sql import try_student_sql, _latest_admitted_count, _latest_admitted_sql

_FRONT_OFFICE_HINTS = (
    "enquir", "inquiry", "visitor", "complaint", "complain", "phone call",
    "call log", "postal", "front office", "income head", "expense head",
    "income versus", "income vs", "notice",
)

if TYPE_CHECKING:
    from services.db_service import DBService


class RoutedAnswer:
    """Either a refusal message or SQL to execute."""

    def __init__(
        self,
        *,
        message: str = "",
        sql: str = "",
        source: str = "deterministic",
        precomputed_columns: list | None = None,
        precomputed_rows: list | None = None,
    ):
        self.message = message
        self.sql = sql
        self.source = source
        self.precomputed_columns = precomputed_columns
        self.precomputed_rows = precomputed_rows


def route_question(db: "DBService", question: str) -> Optional[RoutedAnswer]:
    guard = check_action_guard(question)
    if guard:
        return RoutedAnswer(message=guard, source="guard")

    rq = routing_question(question)

    invalid = parse_invalid_day_question(rq)
    if invalid:
        return RoutedAnswer(message=invalid, source="guard")

    concept = try_concept_answer(rq)
    if concept:
        return RoutedAnswer(message=concept, source="manual")

    # Latest / recent admissions — no DB lookup required.
    admit_n = _latest_admitted_count(question)
    if admit_n:
        return RoutedAnswer(sql=_latest_admitted_sql(admit_n))

    q_lower = rq.lower()
    school_fmt = fetch_school_date_format(db)

    sa = resolve_student_attendance_report(db, question)
    if sa is not None:
        if sa.message and not sa.rows:
            return RoutedAnswer(message=sa.message, source="deterministic")
        return RoutedAnswer(
            message=sa.message,
            sql=sa.sql_note,
            source="deterministic",
            precomputed_columns=sa.columns,
            precomputed_rows=sa.rows,
        )

    # Library — book issues / returns.
    lib = resolve_library_report(db, question)
    if lib is not None:
        if lib.message and not lib.rows:
            return RoutedAnswer(message=lib.message, source="deterministic")
        return RoutedAnswer(
            message=lib.message,
            sql=lib.sql_note,
            source="deterministic",
            precomputed_columns=lib.columns,
            precomputed_rows=lib.rows,
        )

    # Inventory — item stock, issues, categories (not library books).
    inv = resolve_inventory_report(db, question)
    if inv is not None:
        if inv.message and not inv.rows:
            return RoutedAnswer(message=inv.message, source="deterministic")
        return RoutedAnswer(
            message=inv.message,
            sql=inv.sql_note,
            source="deterministic",
            precomputed_columns=inv.columns,
            precomputed_rows=inv.rows,
        )

    # Download Center — content types, shared content summary.
    dc = resolve_download_center_report(db, question)
    if dc is not None:
        if dc.message and not dc.rows:
            return RoutedAnswer(message=dc.message, source="deterministic")
        return RoutedAnswer(
            message=dc.message,
            sql=dc.sql_note,
            source="deterministic",
            precomputed_columns=dc.columns,
            precomputed_rows=dc.rows,
        )

    # Gmeet live classes (addon) — before timetable / generic schedule SQL.
    gm = resolve_gmeet_report(db, question)
    if gm is not None:
        if gm.message and not gm.rows:
            return RoutedAnswer(message=gm.message, source="deterministic")
        return RoutedAnswer(
            message=gm.message,
            sql=gm.sql_note,
            source="deterministic",
            precomputed_columns=gm.columns,
            precomputed_rows=gm.rows,
        )

    # HR module — attendance, payroll, leave, disabled staff, ratings, holiday types.
    hr = resolve_hr_report(db, question)
    if hr is not None:
        if hr.message and not hr.rows:
            return RoutedAnswer(message=hr.message, source="deterministic")
        return RoutedAnswer(
            message=hr.message,
            sql=hr.sql_note,
            source="deterministic",
            precomputed_columns=hr.columns,
            precomputed_rows=hr.rows,
        )

    # Video lessons (Download Center tutorials + Online Course video lessons).
    vl = resolve_video_lessons_report(db, question)
    if vl is not None:
        if vl.message and not vl.rows:
            return RoutedAnswer(message=vl.message, source="deterministic")
        return RoutedAnswer(
            message=vl.message,
            sql=vl.sql_note,
            source="deterministic",
            precomputed_columns=vl.columns,
            precomputed_rows=vl.rows,
        )

    # Teacher lesson plan list (by staff name — English or Arabic question).
    tl = resolve_teacher_lessons_report(db, question)
    if tl is not None:
        if tl.message and not tl.rows:
            return RoutedAnswer(message=tl.message, source="deterministic")
        return RoutedAnswer(
            message=tl.message,
            sql=tl.sql_note,
            source="deterministic",
            precomputed_columns=tl.columns,
            precomputed_rows=tl.rows,
        )

    # Lesson plan / syllabus status (Manage Lesson Plan module).
    lp = resolve_lesson_plan_report(db, question)
    if lp is not None:
        if lp.message and not lp.rows:
            return RoutedAnswer(message=lp.message, source="deterministic")
        return RoutedAnswer(
            message=lp.message,
            sql=lp.sql_note,
            source="deterministic",
            precomputed_columns=lp.columns,
            precomputed_rows=lp.rows,
        )

    # Online exam question bank (questions table by grade) — before courses / exam SQL.
    qb = resolve_online_exam_question_bank(db, question)
    if qb is not None:
        if qb.message and not qb.rows:
            return RoutedAnswer(message=qb.message, source="deterministic")
        return RoutedAnswer(
            message=qb.message,
            sql=qb.sql_note,
            source="deterministic",
            precomputed_columns=qb.columns,
            precomputed_rows=qb.rows,
        )

    # Named Online Course lookup (show course "X") — before list-all / enrollment.
    oc_lookup = resolve_named_course_lookup(db, question)
    if oc_lookup is not None:
        if oc_lookup.message and not oc_lookup.rows:
            return RoutedAnswer(message=oc_lookup.message, source="deterministic")
        return RoutedAnswer(
            message=oc_lookup.message,
            sql=oc_lookup.sql_note,
            source="deterministic",
            precomputed_columns=oc_lookup.columns,
            precomputed_rows=oc_lookup.rows,
        )

    # Online Course enrollment / completion (validate course exists first).
    oc_enroll = resolve_online_course_enrollment_report(db, question)
    if oc_enroll is not None:
        if oc_enroll.message and not oc_enroll.rows:
            return RoutedAnswer(message=oc_enroll.message, source="deterministic")
        return RoutedAnswer(
            message=oc_enroll.message,
            sql=oc_enroll.sql_note,
            source="deterministic",
            precomputed_columns=oc_enroll.columns,
            precomputed_rows=oc_enroll.rows,
        )

    # Online Course addon (not onlineexam / legacy courses table).
    oc_sql = try_online_course_sql(question)
    if oc_sql:
        return RoutedAnswer(sql=oc_sql)

    # Fee Collection → income / expense (not only Front Office phrasing).
    if re.search(r"\b(income|expense)\b", q_lower):
        fo_sql = try_front_office_sql(question, date_format=school_fmt)
        if fo_sql:
            return RoutedAnswer(sql=fo_sql)

    # Front office before fee paths when clearly front-office scoped.
    if any(h in q_lower for h in _FRONT_OFFICE_HINTS):
        fo_summary = run_front_office_summary(db, question)
        if fo_summary:
            cols, rows, note = fo_summary
            return RoutedAnswer(
                sql=note,
                source="deterministic",
                precomputed_columns=cols,
                precomputed_rows=rows,
            )
        fo_sql = try_front_office_sql(question, date_format=school_fmt)
        if fo_sql:
            return RoutedAnswer(sql=fo_sql)

    # Transport fee paid/unpaid for a named student + month.
    transport = resolve_transport_fee_status(db, question)
    if transport is not None:
        cols, rows, note = transport
        if not rows:
            return RoutedAnswer(message=note or "No transport fee records matched that question.", source="fee_due_engine")
        return RoutedAnswer(
            sql=note,
            source="fee_due_engine",
            precomputed_columns=cols,
            precomputed_rows=rows,
        )

    pay = resolve_payment_id_rows(db, question)
    if pay is not None:
        cols, rows, note = pay
        if not rows:
            return RoutedAnswer(
                message="No payment matched that **payment id**. Check the format `deposit_id/invoice_no` (e.g. `15/1`).",
                source="fee_due_engine",
            )
        return RoutedAnswer(
            sql=note,
            source="fee_due_engine",
            precomputed_columns=cols,
            precomputed_rows=rows,
        )

    # Fee due (Python JSON balance — matches feesearch)
    if matches_fee_due_question(question):
        cols, rows, sql = compute_fee_due_rows(db, question)
        if not rows:
            month = sql_date_filter("sfd.created_at", question)
            hint = ""
            if month.kind == "this_month":
                hint = " for **this month**"
            return RoutedAnswer(
                message=(
                    f"No students with **remaining fees**{hint} were found in the fee ledger "
                    "(same basis as **Search due fees**)."
                ),
                source="fee_due_engine",
            )
        return RoutedAnswer(
            sql=sql,
            source="fee_due_engine",
            precomputed_columns=cols,
            precomputed_rows=rows,
        )

    fee_sql = try_fee_sql(db, question)
    if fee_sql:
        return RoutedAnswer(sql=fee_sql)

    missing_fg = fee_group_missing_message(db, question)
    if missing_fg:
        return RoutedAnswer(message=missing_fg, source="guard")

    # Student Information module — before generic LLM / staff-list fallbacks.
    if re.search(
        r"\b(?:students?|admitted|admission|admit(?:ted)?|house|disabled|active students?)\b",
        q_lower,
    ) and not matches_fee_due_question(question):
        st_sql = try_student_sql(question)
        if st_sql:
            return RoutedAnswer(sql=st_sql)

    exam_groups_sql = resolve_exam_groups_sql(db, question)
    if exam_groups_sql:
        return RoutedAnswer(sql=exam_groups_sql)

    oe_schedule = resolve_named_online_exam_schedule(db, question)
    if oe_schedule is not None:
        if oe_schedule.message and not oe_schedule.rows:
            return RoutedAnswer(message=oe_schedule.message, source="deterministic")
        return RoutedAnswer(
            message=oe_schedule.message,
            sql=oe_schedule.sql_note,
            source="deterministic",
            precomputed_columns=oe_schedule.columns,
            precomputed_rows=oe_schedule.rows,
        )

    oe_list_sql = resolve_online_exam_schedule_list_sql(db, question)
    if oe_list_sql:
        return RoutedAnswer(sql=oe_list_sql)

    grade_dist = resolve_grade_distribution_report(db, question)
    if grade_dist is not None:
        if grade_dist.message and not grade_dist.rows:
            return RoutedAnswer(message=grade_dist.message, source="deterministic")
        return RoutedAnswer(
            message=grade_dist.message,
            sql=grade_dist.sql_note,
            source="deterministic",
            precomputed_columns=grade_dist.columns,
            precomputed_rows=grade_dist.rows,
        )

    exam_sql = resolve_exam_results_sql(db, question)
    if exam_sql:
        return RoutedAnswer(sql=exam_sql)

    return None
