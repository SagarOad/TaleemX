"""
Library module SQL — book issues, returns, member lookup.
Uses TaleemX tables: books, book_issues, libarary_members (typo preserved).
"""

from __future__ import annotations

import re
from dataclasses import dataclass, field
from typing import TYPE_CHECKING, Optional

if TYPE_CHECKING:
    from services.db_service import DBService

from services.question_normalize import routing_question


def _esc(s: str) -> str:
    return (s or "").replace("'", "''")


@dataclass
class LibraryReport:
    message: str = ""
    columns: list[str] = field(default_factory=list)
    rows: list = field(default_factory=list)
    sql_note: str = ""


def _extract_member_name(question: str) -> Optional[str]:
    q = question or ""
    for pat in (
        r"\bissued\s+to\s+(.+?)(?:\?|$|\.)",
        r"\bissued\s+for\s+(.+?)(?:\?|$|\.)",
        r"\bbook(?:s)?\s+(?:issued|lent)\s+to\s+(.+?)(?:\?|$|\.)",
        r"\bwhich\s+book(?:s)?\s+(?:is|are)\s+issued\s+to\s+(.+?)(?:\?|$|\.)",
        r"\bmember\s+(.+?)\s+(?:has|have)\s+(?:issued|borrowed)",
    ):
        m = re.search(pat, q, re.IGNORECASE)
        if m:
            name = " ".join(m.group(1).split()).strip("?.!,;:")
            if name and len(name) >= 3:
                return name
    return None


def is_book_issued_to_question(question: str) -> bool:
    rq = routing_question(question).lower()
    return bool(
        re.search(r"\bbooks?\b", rq)
        and re.search(r"\bissued\b|\bborrowed\b|\blent\b", rq)
        and (_extract_member_name(question) or re.search(r"\bissued to\b|\bissued for\b", rq))
    )


def is_books_returned_question(question: str) -> bool:
    rq = routing_question(question).lower()
    return bool(
        re.search(r"\bbooks?\b", rq)
        and re.search(r"\breturned\b|\breturns\b", rq)
    )


def resolve_library_report(db: "DBService", question: str) -> Optional[LibraryReport]:
    if not db.table_exists("book_issues"):
        return None

    if is_book_issued_to_question(question):
        name = _extract_member_name(question)
        if not name:
            return LibraryReport(
                message="Please specify the **student or staff member** name for book lookup.",
                sql_note="-- library: missing member name",
            )
        esc = _esc(name)
        sql = (
            "SELECT b.book_title, b.book_no, b.author, bi.issue_date, bi.duereturn_date, "
            "bi.return_date, "
            "CASE WHEN bi.is_returned IN ('1', 1) THEN 'Returned' ELSE 'Issued' END AS issue_status, "
            "lm.library_card_no, lm.member_type, "
            "CONCAT_WS(' ', s.firstname, s.middlename, s.lastname) AS student_name, "
            "s.admission_no, "
            "CONCAT_WS(' ', st.name, st.surname) AS staff_name, st.employee_id "
            "FROM book_issues bi "
            "JOIN books b ON b.id = bi.book_id "
            "JOIN libarary_members lm ON lm.id = bi.member_id "
            "LEFT JOIN students s ON s.id = lm.member_id AND lm.member_type = 'student' "
            "LEFT JOIN staff st ON st.id = lm.member_id AND lm.member_type = 'teacher' "
            f"WHERE ("
            f"LOWER(CONCAT_WS(' ', s.firstname, s.middlename, s.lastname)) LIKE LOWER('%{esc}%') "
            f"OR LOWER(CONCAT_WS(' ', s.firstname, s.lastname)) LIKE LOWER('%{esc}%') "
            f"OR LOWER(CONCAT_WS(' ', st.name, st.surname)) LIKE LOWER('%{esc}%')"
            f") "
            "AND bi.is_returned IN ('0', 0) "
            "ORDER BY bi.issue_date DESC "
            "LIMIT 50"
        )
        rows, cols, err = db.execute(sql, max_rows=50)
        if err:
            return LibraryReport(message=f"Could not load book issues: {err}", sql_note=sql)
        if not rows:
            return LibraryReport(
                message=f"No **currently issued books** found for **{name}**.",
                sql_note=sql,
            )
        return LibraryReport(
            message=f"**{len(rows)}** book(s) currently issued to **{name}**:",
            columns=cols,
            rows=rows,
            sql_note=sql,
        )

    if is_books_returned_question(question):
        rq = routing_question(question).lower()
        if re.search(r"\bthis week\b|\bpast week\b|\blast 7 days\b", rq):
            date_sql = "bi.return_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
            period = "**this week**"
        elif re.search(r"\btoday\b", rq):
            date_sql = "DATE(bi.return_date) = CURDATE()"
            period = "**today**"
        elif re.search(r"\bthis month\b|\bcurrent month\b", rq):
            date_sql = (
                "MONTH(bi.return_date) = MONTH(CURDATE()) "
                "AND YEAR(bi.return_date) = YEAR(CURDATE())"
            )
            period = "**this month**"
        else:
            date_sql = "bi.return_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
            period = "**this week**"

        sql = (
            "SELECT b.book_title, b.book_no, b.author, bi.issue_date, bi.return_date, "
            "lm.library_card_no, lm.member_type, "
            "CONCAT_WS(' ', s.firstname, s.middlename, s.lastname) AS student_name, "
            "s.admission_no, "
            "CONCAT_WS(' ', st.name, st.surname) AS staff_name, st.employee_id "
            "FROM book_issues bi "
            "JOIN books b ON b.id = bi.book_id "
            "JOIN libarary_members lm ON lm.id = bi.member_id "
            "LEFT JOIN students s ON s.id = lm.member_id AND lm.member_type = 'student' "
            "LEFT JOIN staff st ON st.id = lm.member_id AND lm.member_type = 'teacher' "
            f"WHERE bi.is_returned IN ('1', 1) AND {date_sql} "
            "ORDER BY bi.return_date DESC "
            "LIMIT 100"
        )
        rows, cols, err = db.execute(sql, max_rows=100)
        if err:
            return LibraryReport(message=f"Could not load returned books: {err}", sql_note=sql)
        if not rows:
            return LibraryReport(
                message=f"No **books returned** {period}.",
                sql_note=sql,
            )
        return LibraryReport(
            message=f"**{len(rows)}** book(s) returned {period}:",
            columns=cols,
            rows=rows,
            sql_note=sql,
        )

    return None
