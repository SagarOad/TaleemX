import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from services.hr_sql import (
    is_disabled_staff_question,
    is_holiday_types_question,
    is_hr_module_question,
    is_payroll_question,
    is_staff_attendance_question,
    is_staff_leave_question,
    is_teacher_ratings_question,
)


def test_hr_module_detection():
    assert is_hr_module_question("Show april's staff attendance")
    assert is_hr_module_question("Show payroll for april 2026")
    assert is_hr_module_question("Show leaves applied by teachers in april")
    assert is_hr_module_question("Show disabled staff")
    assert is_hr_module_question("Show all configured holiday types")
    assert is_hr_module_question("Show teacher ratings for Grade 5 teachers")
    assert not is_hr_module_question("give me list of available staff")


def test_individual_hr_intents():
    assert is_staff_attendance_question("Show staff attendance on 1 June 2026")
    assert is_payroll_question("Show payroll for april 2026")
    assert is_staff_leave_question("Show leaves applied by teachers in april")
    assert is_disabled_staff_question("Show disabled staff")
    assert is_holiday_types_question("Show all configured holiday types")
    assert is_teacher_ratings_question("Show teacher ratings for Grade 5 teachers")
    assert not is_holiday_types_question("list all languages configured in the system")
