import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.online_exam_question_bank_sql import (
    build_question_bank_count_sql,
    build_question_bank_list_sql,
    is_online_exam_question_bank_question,
    is_question_count_question,
    parse_grades_from_question,
)
from services.online_course_sql import try_online_course_sql


def test_grade1_question_bank_list():
    q = "Show all questions from question bank online exams of grade 1"
    assert is_online_exam_question_bank_question(q)
    assert parse_grades_from_question(q) == ["1"]
    sql = build_question_bank_list_sql(["1"])
    assert "FROM questions q" in sql
    assert "onlineexam_results" not in sql
    assert "online_course_exam_question" not in sql


def test_grade1_or_2():
    q = "give me all questions from questions bank of online exams of grade 1 or 2"
    grades = parse_grades_from_question(q)
    assert "1" in grades and "2" in grades
    sql = build_question_bank_list_sql(grades)
    assert "Grade 1" in sql and "Grade 2" in sql


def test_how_many_questions_grade1():
    q = "How many questions exist for Grade 1"
    assert is_online_exam_question_bank_question(q)
    assert is_question_count_question(q)
    sql = build_question_bank_count_sql(["1"])
    assert "COUNT(*)" in sql
    assert "questions q" in sql


def test_not_online_course_question_bank():
    q = "Show all questions from question bank online exams of grade 1"
    assert try_online_course_sql(q) is None
