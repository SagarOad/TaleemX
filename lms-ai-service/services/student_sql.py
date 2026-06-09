"""
Student-module SQL patterns (Student Information menu).
"""

from __future__ import annotations

import re
from typing import Optional

from services.date_filters import sql_date_filter


def _esc(s: str) -> str:
    return (s or "").replace("'", "''")


def _grade(question: str) -> Optional[str]:
    m = re.search(r"\b(?:grade|class)\s*(\d+)\b", (question or "").lower())
    return m.group(1) if m else None


def _section(question: str) -> Optional[str]:
    m = re.search(r"\bsection\s+([A-Za-z0-9]+)\b", question or "", re.IGNORECASE)
    return m.group(1).upper() if m else None


from services.name_filters import (
    extract_person_name,
    is_likely_fake_name,
    normalize_person_name,
    student_name_sql_filter,
)

_HOUSE_COLORS = ("red", "blue", "green", "yellow")


def _extract_house_color(question: str) -> Optional[str]:
    ql = (question or "").lower()
    m = re.search(r"\b(red|blue|green|yellow)\s+house\b", ql)
    if m:
        return m.group(1)
    if "house" in ql:
        m = re.search(r"\b(red|blue|green|yellow)\b", ql)
        if m:
            return m.group(1)
    return None


def _extract_student_name_for_house(question: str) -> Optional[str]:
    q = (question or "").strip()
    patterns = (
        r"(?:which|what)\s+house\s+(?:is|does)\s+(.+?)\s+(?:in|belong|\?|$)",
        r"is\s+(.+?)\s+in\s+(?:the\s+)?(?:(?:red|blue|green|yellow)\s+)?house",
        r"^\s*(.+?)\s+is\s+in\s+(?:the\s+)?(?:(?:red|blue|green|yellow)\s+)?house",
        r"does\s+(.+?)\s+belong\s+to\s+(?:the\s+)?(?:(?:red|blue|green|yellow)\s+)?house",
    )
    for pat in patterns:
        m = re.search(pat, q, re.IGNORECASE)
        if m:
            name = normalize_person_name(m.group(1))
            if name.lower().startswith("is "):
                name = name[3:].strip()
            low = name.lower()
            if len(name) >= 3 and low not in {"which", "what", "the", "student"}:
                return name
    return None


def _is_students_in_house_list(q: str) -> bool:
    return bool(
        "house" in q
        and _extract_house_color(q)
        and re.search(r"\b(?:list|show|all|students?)\b", q)
        and not _extract_student_name_for_house(q)
    )


def _is_named_student_house_question(question: str) -> bool:
    q = (question or "").lower()
    if "house" not in q:
        return False
    if _extract_student_name_for_house(question):
        return True
    return bool(
        re.search(r"\b(?:which|what)\s+house\b", q)
        and re.search(r"\bstudent\b|\bin\b", q)
    )


def _student_house_sql(name: str, house_color: Optional[str] = None) -> str:
    name_filter = student_name_sql_filter(name)
    house_match_col = ""
    if house_color:
        esc = _esc(house_color)
        house_match_col = (
            f", CASE WHEN LOWER(COALESCE(sh.house_name, '')) LIKE '%{esc}%' "
            f"THEN 'Yes' ELSE 'No' END AS in_{esc}_house"
        )
    return (
        "SELECT CONCAT_WS(' ', s.firstname, s.middlename, s.lastname) AS student_name, "
        "s.admission_no, sh.house_name, c.class AS class_name, sec.section AS section_name"
        f"{house_match_col} "
        "FROM students s "
        "LEFT JOIN school_houses sh ON sh.id = s.school_house_id "
        "LEFT JOIN student_session ss ON ss.student_id = s.id "
        "LEFT JOIN classes c ON c.id = ss.class_id "
        "LEFT JOIN sections sec ON sec.id = ss.section_id "
        f"WHERE {name_filter} "
        "ORDER BY ss.id DESC, s.id DESC LIMIT 10"
    )


def _students_in_house_sql(house_color: str) -> str:
    esc = _esc(house_color)
    return (
        "SELECT CONCAT_WS(' ', s.firstname, s.middlename, s.lastname) AS student_name, "
        "s.admission_no, sh.house_name, c.class AS class_name, sec.section AS section_name "
        "FROM students s "
        "LEFT JOIN school_houses sh ON sh.id = s.school_house_id "
        "LEFT JOIN student_session ss ON ss.student_id = s.id "
        "LEFT JOIN classes c ON c.id = ss.class_id "
        "LEFT JOIN sections sec ON sec.id = ss.section_id "
        f"WHERE s.is_active = 'yes' AND LOWER(COALESCE(sh.house_name, '')) LIKE '%{esc}%' "
        "ORDER BY student_name LIMIT 100"
    )


def _latest_admitted_count(question: str) -> Optional[int]:
    q = (question or "").lower()
    for pat in (
        r"\b(?:latest|recent|last|newest)\s+(\d+)\s+(?:admitted|admission|admit(?:ted)?)\b",
        r"\b(\d+)\s+(?:latest|recent|newest|most recent)\s+(?:admitted|admission|admit(?:ted)?)\b",
        r"\b(?:latest|recent|last|newest)\s+(\d+)\s+(?:new|newly)\s+(?:admitted|admission|students?)\b",
    ):
        m = re.search(pat, q)
        if m:
            return min(max(int(m.group(1)), 1), 50)
    if re.search(
        r"\b(?:latest|recent|last|newest)\s+(?:admitted|admission|admit(?:ted)?)\b",
        q,
    ) and re.search(r"\bstudents?\b", q):
        return 5
    return None


def _latest_admitted_sql(n: int) -> str:
    return (
        "SELECT s.admission_no, s.firstname, s.middlename, s.lastname, "
        "COALESCE(s.admission_date, DATE(s.created_at)) AS admission_date, "
        "(SELECT c.class FROM student_session ss "
        "JOIN classes c ON c.id = ss.class_id "
        "WHERE ss.student_id = s.id ORDER BY ss.id DESC LIMIT 1) AS class_name, "
        "(SELECT sec.section FROM student_session ss "
        "JOIN sections sec ON sec.id = ss.section_id "
        "WHERE ss.student_id = s.id ORDER BY ss.id DESC LIMIT 1) AS section_name "
        "FROM students s "
        "WHERE s.is_active = 'yes' "
        "ORDER BY COALESCE(s.admission_date, DATE(s.created_at)) DESC, s.id DESC "
        f"LIMIT {n}"
    )


def try_student_sql(question: str) -> Optional[str]:
    q = (question or "").lower().strip()
    if not q:
        return None

    if ("admission number" in q or "admission no" in q) and (
        is_likely_fake_name(question) or re.search(r"\bfake\b|\b999\b", q)
    ):
        return (
            "SELECT s.admission_no, s.firstname, s.lastname "
            "FROM students s WHERE 1=0 LIMIT 0"
        )

    if re.search(r"(?:show|display|get)\s+student\s+details?\s+for\s+(.+)$", q):
        name = " ".join(re.search(
            r"(?:show|display|get)\s+student\s+details?\s+for\s+(.+)$", q
        ).group(1).split()).strip("?.!,;:")
        if name and len(name) >= 3:
            name_filter = student_name_sql_filter(name)
            return (
                "SELECT s.admission_no, s.roll_no, s.admission_date, s.firstname, s.middlename, "
                "s.lastname, s.mobileno, s.email, s.gender, s.father_name, s.guardian_phone, "
                "c.class AS class_name, sec.section AS section_name, sh.house_name "
                "FROM students s "
                "LEFT JOIN student_session ss ON ss.student_id = s.id "
                "LEFT JOIN classes c ON c.id = ss.class_id "
                "LEFT JOIN sections sec ON sec.id = ss.section_id "
                "LEFT JOIN school_houses sh ON sh.id = s.school_house_id "
                f"WHERE {name_filter} "
                "ORDER BY s.is_active DESC, ss.id DESC LIMIT 10"
            )

    name = extract_person_name(question)
    if name and is_likely_fake_name(name) and ("student" in q or "admission" in q):
        return "SELECT s.admission_no, s.firstname, s.lastname FROM students s WHERE 1=0 LIMIT 0"

    if re.search(r"\b(?:total|count|how many)\b.*\bactive\b.*\bstudent", q):
        return (
            "SELECT COUNT(*) AS active_student_count "
            "FROM students WHERE is_active = 'yes'"
        )

    if "disabled student" in q or "disable reason" in q:
        from services.concept_answers import is_explain_concept_question

        if is_explain_concept_question(question):
            return None
        return (
            "SELECT s.admission_no, s.firstname, s.lastname, s.dis_reason AS disable_reason, "
            "s.dis_note AS disable_remarks, s.is_active "
            "FROM students s "
            "WHERE s.is_active = 'no' "
            "ORDER BY s.updated_at DESC LIMIT 50"
        )

    if "grouped by house" in q or "by house" in q and "student" in q:
        return (
            "SELECT sh.house_name, COUNT(DISTINCT s.id) AS student_count "
            "FROM students s "
            "LEFT JOIN school_houses sh ON sh.id = s.school_house_id "
            "WHERE s.is_active = 'yes' "
            "GROUP BY sh.house_name "
            "ORDER BY student_count DESC LIMIT 50"
        )

    if _is_students_in_house_list(q):
        color = _extract_house_color(question)
        if color:
            return _students_in_house_sql(color)

    if _is_named_student_house_question(question):
        student_name = _extract_student_name_for_house(question)
        if student_name:
            return _student_house_sql(student_name, _extract_house_color(question))

    admit_n = _latest_admitted_count(question)
    if admit_n:
        return _latest_admitted_sql(admit_n)

    grade = _grade(question)
    section = _section(question)
    if grade and ("student" in q or "list" in q or "show" in q):
        sec_sql = ""
        if section:
            sec_sql = f" AND UPPER(sec.section) = '{_esc(section)}'"
        return (
            "SELECT s.firstname, s.lastname, s.admission_no, c.class AS class_name, "
            "sec.section AS section_name "
            "FROM students s "
            "JOIN student_session ss ON ss.student_id = s.id "
            "JOIN classes c ON c.id = ss.class_id "
            "JOIN sections sec ON sec.id = ss.section_id "
            f"WHERE s.is_active = 'yes' "
            f"AND (c.class = '{grade}' OR c.class = 'Grade {grade}' OR c.class = 'Class {grade}')"
            f"{sec_sql} "
            "ORDER BY s.firstname, s.lastname LIMIT 50"
        )

    # Named student profile
    m = re.search(
        r"(?:details?|info|information|profile)\s+(?:of|for)\s+(?:student\s+)?(.+)$",
        q,
    )
    if m:
        name = " ".join(m.group(1).split()).strip("?.!,;:")
        if name and "fake" not in name and len(name) >= 3:
            name_filter = student_name_sql_filter(name)
            return (
                "SELECT s.admission_no, s.roll_no, s.admission_date, s.firstname, s.middlename, "
                "s.lastname, s.mobileno, s.email, s.gender, s.father_name, s.guardian_phone, "
                "c.class AS class_name, sec.section AS section_name, sh.house_name "
                "FROM students s "
                "LEFT JOIN student_session ss ON ss.student_id = s.id "
                "LEFT JOIN classes c ON c.id = ss.class_id "
                "LEFT JOIN sections sec ON sec.id = ss.section_id "
                "LEFT JOIN school_houses sh ON sh.id = s.school_house_id "
                f"WHERE {name_filter} "
                "ORDER BY s.is_active DESC, ss.id DESC LIMIT 10"
            )

    return None
