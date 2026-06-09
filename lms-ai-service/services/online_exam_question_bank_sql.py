"""
Online examination question bank — `questions` table used by /admin/onlineexam.

NOT online_course_exam_question (Online Course addon).
NOT exam_group_exam_results (school exam marks).
"""

from __future__ import annotations

import re
from dataclasses import dataclass, field
from typing import TYPE_CHECKING, Optional

if TYPE_CHECKING:
    from services.db_service import DBService

from services.online_course_sql import is_online_course_lms_question
from services.question_normalize import routing_question


def _esc(s: str) -> str:
    return (s or "").replace("'", "''")


_GRADE_RE = re.compile(r"\b(?:grade|class)\s*(\d+)\b", re.IGNORECASE)


def parse_grades_from_question(question: str) -> list[str]:
    rq = routing_question(question)
    found = _GRADE_RE.findall(rq)
    if not found:
        return []
    grades = list(dict.fromkeys(found))
    m = re.search(
        r"\b(?:grade|class)\s*(\d+)\s+or\s+(\d+)\b",
        rq,
        re.IGNORECASE,
    )
    if m:
        for g in (m.group(1), m.group(2)):
            if g not in grades:
                grades.append(g)
    return grades


def _grade_where(grades: list[str]) -> str:
    if not grades:
        return "1=1"
    parts = []
    for g in grades:
        esc = _esc(g)
        parts.append(
            f"(c.class = '{esc}' OR c.class = 'Grade {esc}' OR c.class = 'Class {esc}')"
        )
    return f"({' OR '.join(parts)})"


def is_question_count_question(question: str) -> bool:
    rq = routing_question(question).lower()
    return bool(
        re.search(r"\bhow many\b.*\bquestions?\b", rq)
        or re.search(r"\bquestions?\s+exist\b", rq)
        or re.search(r"\bcount\b.*\bquestions?\b", rq)
        or re.search(r"\bnumber of questions\b", rq)
        or re.search(r"\btotal\b.*\bquestions?\b", rq)
    )


def is_online_exam_question_bank_question(question: str) -> bool:
    """Question bank for Online Examination module (questions + classes)."""
    if is_online_course_lms_question(question):
        return False

    rq = routing_question(question).lower()
    has_qbank = "question bank" in rq or "questions bank" in rq
    has_online_exam = bool(re.search(r"\bonline\s+exams?\b", rq))
    has_questions = bool(re.search(r"\bquestions?\b", rq))
    has_grade = bool(_GRADE_RE.search(rq))

    if has_qbank and has_online_exam:
        return True
    if has_questions and has_online_exam and has_grade:
        return True
    if has_qbank and has_grade and "online course" not in rq:
        return True
    if is_question_count_question(question) and has_grade:
        return True
    return False


def build_question_bank_list_sql(grades: list[str], *, limit: int = 200) -> str:
    gw = _grade_where(grades)
    return (
        "SELECT q.id AS question_id, q.question, q.question_type, q.level, "
        "subj.name AS subject_name, subj.code AS subject_code, "
        "c.class AS grade, sec.section AS section_name, "
        "q.opt_a, q.opt_b, q.opt_c, q.opt_d, q.opt_e, q.correct, "
        "CONCAT_WS(' ', st.name, st.surname) AS created_by, "
        "(SELECT COUNT(*) FROM onlineexam_questions oeq "
        " WHERE oeq.question_id = q.id) AS used_in_online_exams "
        "FROM questions q "
        "LEFT JOIN subjects subj ON subj.id = q.subject_id "
        "LEFT JOIN classes c ON c.id = q.class_id "
        "LEFT JOIN sections sec ON sec.id = q.section_id "
        "LEFT JOIN staff st ON st.id = q.staff_id "
        f"WHERE {gw} "
        "ORDER BY c.class, subj.name, q.id DESC "
        f"LIMIT {int(limit)}"
    )


def build_question_bank_count_sql(grades: list[str]) -> str:
    gw = _grade_where(grades)
    if len(grades) <= 1:
        return (
            "SELECT COUNT(*) AS question_count "
            "FROM questions q "
            "INNER JOIN classes c ON c.id = q.class_id "
            f"WHERE {gw}"
        )
    return (
        "SELECT c.class AS grade, COUNT(*) AS question_count "
        "FROM questions q "
        "INNER JOIN classes c ON c.id = q.class_id "
        f"WHERE {gw} "
        "GROUP BY c.class "
        "ORDER BY c.class"
    )


@dataclass
class OnlineExamQuestionBankReport:
    message: str = ""
    columns: list[str] = field(default_factory=list)
    rows: list = field(default_factory=list)
    sql_note: str = ""


def _grade_label(grades: list[str]) -> str:
    if not grades:
        return "all grades"
    if len(grades) == 1:
        return f"Grade {grades[0]}"
    return "Grades " + ", ".join(grades)


def resolve_online_exam_question_bank(
    db: "DBService",
    question: str,
) -> Optional[OnlineExamQuestionBankReport]:
    if not is_online_exam_question_bank_question(question):
        return None
    if not db.table_exists("questions"):
        return None

    grades = parse_grades_from_question(question)
    if not grades:
        return OnlineExamQuestionBankReport(
            message=(
                "Please specify a **grade/class** for the online exam question bank.\n\n"
                "Example: *Show all questions from question bank of online exams "
                "for Grade 1*"
            ),
            sql_note="-- online exam question bank: missing grade",
        )

    label = _grade_label(grades)

    if is_question_count_question(question):
        sql = build_question_bank_count_sql(grades)
        rows, columns, err = db.execute(sql, max_rows=20)
        if err:
            return OnlineExamQuestionBankReport(
                message=f"Could not count questions for **{label}**: {err}",
                sql_note=sql,
            )
        if not rows:
            return OnlineExamQuestionBankReport(
                message=f"No questions found in the question bank for **{label}**.",
                sql_note=sql,
            )
        if len(grades) == 1:
            count = int(rows[0][0] if rows[0] else 0)
            return OnlineExamQuestionBankReport(
                message=(
                    f"**{count}** question(s) exist in the **online exam question bank** "
                    f"for **{label}**."
                ),
                columns=columns or ["question_count"],
                rows=rows,
                sql_note=sql,
            )
        total = sum(int(r[1] or 0) for r in rows)
        return OnlineExamQuestionBankReport(
            message=(
                f"**{total}** question(s) in the **online exam question bank** "
                f"for **{label}** (breakdown by grade below)."
            ),
            columns=columns or ["grade", "question_count"],
            rows=rows,
            sql_note=sql,
        )

    sql = build_question_bank_list_sql(grades)
    rows, columns, err = db.execute(sql, max_rows=200)
    if err:
        return OnlineExamQuestionBankReport(
            message=f"Could not load question bank for **{label}**: {err}",
            sql_note=sql,
        )
    if not rows:
        return OnlineExamQuestionBankReport(
            message=(
                f"No questions found in the **online exam question bank** for **{label}**. "
                "Add questions under **Online Examination → Question Bank**."
            ),
            sql_note=sql,
        )

    return OnlineExamQuestionBankReport(
        message=(
            f"**{len(rows)}** question(s) from the **online exam question bank** "
            f"for **{label}**:"
        ),
        columns=columns,
        rows=rows,
        sql_note=sql,
    )
