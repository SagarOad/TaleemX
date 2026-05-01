"""
services/training_question_bank.py
Large intent-oriented training bank for NL-to-SQL prompting.
"""

from __future__ import annotations

import re


def _normalize_tokens(text: str) -> set[str]:
    return {
        token
        for token in re.findall(r"[a-z0-9_]+", (text or "").lower())
        if len(token) >= 3
    }


def build_training_question_bank() -> list[str]:
    """
    Build 300+ natural-language variants that represent common LMS data requests.
    These are intent examples (not fixed SQL), so the model still relies on live schema.
    """
    starters = [
        "show",
        "list",
        "give me",
        "fetch",
        "find",
        "display",
    ]
    student_targets = [
        "all students",
        "students",
        "student names",
        "student list",
    ]
    grade_targets = [
        "grade 1 students",
        "students in grade 2",
        "grade 3 class students",
        "students of class 4",
        "students of grade 5",
        "grade 6 learners",
        "students in class 7",
        "class 8 students",
        "grade 9 students",
        "students of grade 10",
    ]
    staff_targets = [
        "all staff",
        "staff names",
        "teaching staff",
        "non-teaching staff",
        "staff list",
    ]
    student_name_slots = [
        "student name",
        "Ali Khan",
        "Ayesha Noor",
        "Muhammad Ahmed",
        "Fatima Zahra",
    ]
    staff_name_slots = [
        "staff name",
        "Sara Ali",
        "Usman Raza",
        "Hina Noor",
        "Bilal Ahmed",
    ]

    questions: list[str] = []

    # Core student/staff listing styles.
    for s in starters:
        for target in student_targets:
            questions.append(f"{s} {target}")
            questions.append(f"{s} me {target} with grade and section")
            questions.append(f"{s} active {target}")
        for target in staff_targets:
            questions.append(f"{s} {target}")
            questions.append(f"{s} me {target} with email and phone")
            questions.append(f"{s} active {target} with department")

    # Grade-specific variants.
    for s in starters:
        for target in grade_targets:
            questions.append(f"{s} {target}")
            questions.append(f"{s} me names of {target}")
            questions.append(f"{s} attendance report for {target}")
            questions.append(f"{s} behavior report for {target}")

    # Staff details by name variants.
    for slot in staff_name_slots:
        questions.extend(
            [
                f"give me details of staff with name {slot}",
                f"show staff profile for {slot}",
                f"find staff information for {slot}",
                f"provide staff contact details for {slot}",
                f"tell me designation and department of {slot}",
            ]
        )

    # Student profile / behavior / attendance variants.
    for slot in student_name_slots:
        questions.extend(
            [
                f"tell me behavior report of student {slot}",
                f"show behavior record for {slot}",
                f"give me behaviour record of {slot}",
                f"give me behaviour record of student {slot}",
                f"behaviour record for {slot}",
                f"behavior records of {slot}",
                f"list incidents for student {slot}",
                f"discipline record of {slot}",
                f"give complete behaviour report of {slot}",
                f"give me attendance report of student {slot}",
                f"show monthly attendance for {slot}",
                f"attendance summary for student {slot}",
                f"student profile details for {slot}",
                f"give me detail of student {slot}",
                f"give me details of student {slot}",
                f"show information of student {slot}",
                f"guardian and contact details of {slot}",
                f"fees status for {slot}",
                f"exam performance of {slot}",
                f"marksheet details of {slot}",
            ]
        )

    # General analytics / ops variants.
    analytics_questions = [
        "how many students are currently active",
        "how many students are in each grade",
        "count students by section",
        "how many staff members are active",
        "count teachers by department",
        "show top absentee students",
        "show students with low attendance",
        "students with no attendance record",
        "show students with pending fees",
        "fee collection summary by month",
        "show paid vs unpaid fee records",
        "upcoming exams by class",
        "recent behavior incidents",
        "students with behavior warnings",
        "list assignments due this week",
        "students who did not submit assignment",
        "top performing students in recent exams",
        "courses uploaded recently",
        "new admissions this month",
        "student transfer records",
        "timetable of classes for grade 1",
        "class timetable for grade 2",
        "show timetable for all classes",
        "subjects scheduled for class 1 today",
        "teacher timetable for this week",
        "hostel room occupancy report",
        "students assigned to hostel rooms",
        "inventory stock summary",
        "low stock inventory items",
        "items issued today",
        "communication log summary",
        "general calls report",
        "incoming and outgoing calls count",
        "monthly income and expense summary",
        "today income entries",
        "today expense entries",
        "transport route list",
        "vehicles and assigned routes",
        "library books issued report",
        "book issue status by student",
    ]
    for q in analytics_questions:
        questions.append(q)
        questions.append(f"please {q}")
        questions.append(f"can you {q}")
        questions.append(f"i need to know {q}")

    # Front office, admissions, attendance (student + staff), subjects, behaviour by class.
    lms_operational = [
        "show admission enquiries this month",
        "list admission enquiries for the current month",
        "give me front office enquiries from this week",
        "display new admission inquiries today",
        "fetch prospect enquiries submitted recently",
        "show online admission applications this month",
        "list pending online admission forms",
        "how many online applications were submitted this month",
        "give me detailed attendance report of grade 1 this month",
        "what is the attendance report of grade 2",
        "student attendance summary for class 3 this month",
        "show daily attendance for grade 4 students",
        "list absent students in grade 1 today",
        "give me attendance report of staff this month",
        "show staff attendance for the current month",
        "employee attendance records this week",
        "teacher punch in out report today",
        "faculty attendance summary",
        "give me list of subjects for grade 1",
        "list all subjects assigned to grade 2",
        "what subjects are taught in class 3",
        "show subject list for grade 5 section A",
        "curriculum subjects for year 6",
        "give me all available behavior records for grade 1",
        "list behaviour incidents for grade 2 students",
        "discipline records for class 4 this term",
        "show misconduct reports filtered by grade 3",
        "visitor book entries today",
        "list phone call log this week",
        "show postal dispatch records",
        "give me hostel room allocation report",
        "transport route assignments",
        "library books overdue",
        "fee defaulters list",
        "income and expense for this month",
    ]
    for s in starters:
        for phrase in (
            "admission enquiries this month",
            "staff attendance this month",
            "subjects for grade 1",
            "behavior records for grade 2",
            "online admission applications",
        ):
            questions.append(f"{s} {phrase}")
            questions.append(f"{s} me {phrase}")

    questions.extend(lms_operational)

    # Manual/how-to style examples to improve classifier boundaries.
    manual_questions = [
        "how to upload a course",
        "how do i create a new course",
        "where can i add course content",
        "how to add lesson material",
        "where to manage course settings",
        "how to enroll students in a course",
        "how can admin add staff user",
        "where to configure attendance settings",
        "how to generate report card",
        "how to take fee payment entry",
    ]
    questions.extend(manual_questions)

    # Deduplicate while preserving order.
    seen = set()
    unique_questions = []
    for q in questions:
        key = q.strip().lower()
        if key and key not in seen:
            seen.add(key)
            unique_questions.append(q.strip())

    return unique_questions


def pick_relevant_training_examples(
    question: str,
    training_bank: list[str],
    max_examples: int = 120,
) -> list[str]:
    """
    Rank intent examples by token overlap with incoming question.
    Keep prompt size bounded while remaining context-aware.
    """
    q_tokens = _normalize_tokens(question)
    if not training_bank:
        return []
    if not q_tokens:
        return training_bank[:max_examples]

    scored: list[tuple[int, str]] = []
    for item in training_bank:
        t = _normalize_tokens(item)
        overlap = len(q_tokens.intersection(t))
        bonus = 1 if ("student" in item.lower() and "student" in question.lower()) else 0
        bonus += 1 if ("staff" in item.lower() and "staff" in question.lower()) else 0
        qb = question.lower()
        if any(t in qb for t in ("behav", "behavior", "incident", "discipline")) and any(
            t in item.lower() for t in ("behav", "behavior", "incident", "discipline")
        ):
            bonus += 3
        if any(t in qb for t in ("attend", "attendence", "absent", "present")) and any(
            t in item.lower() for t in ("attend", "attendence", "absent", "present")
        ):
            bonus += 3
        if any(t in qb for t in ("subject", "curriculum", "syllabus")) and any(
            t in item.lower() for t in ("subject", "curriculum", "syllabus")
        ):
            bonus += 2
        if any(t in qb for t in ("enquir", "inquiry", "admission", "prospect")) and any(
            t in item.lower() for t in ("enquir", "inquiry", "admission", "prospect")
        ):
            bonus += 3
        if ("online" in qb and "admission" in qb) and ("online" in item.lower() and "admission" in item.lower()):
            bonus += 2
        scored.append((overlap + bonus, item))

    scored.sort(key=lambda x: (-x[0], x[1]))
    selected = [item for _, item in scored[:max_examples]]
    return selected
