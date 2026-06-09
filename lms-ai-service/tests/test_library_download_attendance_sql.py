import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.attendance_sql import is_student_attendance_report_question
from services.download_center_sql import (
    is_content_shared_summary_question,
    is_content_types_question,
)
from services.library_sql import (
    _extract_member_name,
    is_book_issued_to_question,
    is_books_returned_question,
)


def test_student_attendance_april():
    q = "Show student attendance april"
    assert is_student_attendance_report_question(q)


def test_content_types():
    assert is_content_types_question("Show all content types available.")


def test_content_shared_summary():
    assert is_content_shared_summary_question(
        "Summarize content shared in the last month."
    )


def test_library_book_issued():
    q = "which book is issued to Layan Al-Otaibi"
    assert is_book_issued_to_question(q)
    assert _extract_member_name(q) == "Layan Al-Otaibi"


def test_books_returned_week():
    assert is_books_returned_question("Show books returned this week.")
