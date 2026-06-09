"""
Normalize Arabic (and mixed Arabic/English) admin questions for intent routing.

Answers stay in English unless respond_arabic is checked; this module only helps
pattern matchers understand Arabic phrasing.
"""

from __future__ import annotations

import re

# Arabic phrase → English routing token (order matters for multi-word phrases).
_ARABIC_REPLACEMENTS: tuple[tuple[str, str], ...] = (
    (r"الدروس\s+المصورة|دروس\s+مصورة|الدروس\s+المرئية|دروس\s+مرئية", " video lessons "),
    (r"المصورة|مصورة|مرئية|مصور", " video "),
    (r"فيديو|الفيديو|فيديوهات|مرئيات", " video "),
    (r"لهذا\s+الأسبوع|هذا\s+الأسبوع|الأسبوع\s+الحالي|للأسبوع", " this week "),
    (r"الأسبوع\s+الماضي|اسبوع\s+الماضي|الأسبوع\s+السابق", " last week "),
    (r"لهذا\s+الشهر|هذا\s+الشهر|الشهر\s+الحالي", " this month "),
    (r"لهذا\s+اليوم|هذا\s+اليوم|اليوم", " today "),
    (r"اعرض|أعرض|اظهر|أظهر|اعطني|أعطني|اعط|أعط|اذكر|أذكر|ورني|ورِّ", " show "),
    (r"جميع|كل|كافة", " all "),
    (r"الدروس|دروس|الدرس", " lessons "),
    (r"المعلم|معلم|المعلمة|معلمة|المعلمين|معلمين", " teacher "),
    (r"الموظف|موظف|الموظفين|موظفين|الموظفة|موظفة", " staff "),
    (r"قائمة|لائحة|سرد", " list "),
    (r"كم\s+عدد|كم|عدد", " how many "),
    (r"طلاب|الطلاب|طالب", " students "),
    (r"الصف|صف|الفصل|فصل", " grade "),
    (r"القسم|قسم", " section "),
    (r"الحضور|حضور", " attendance "),
    (r"الرسوم|رسوم", " fees "),
    (r"امتحان|امتحانات|الامتحان|الامتحانات", " exam "),
    (r"اون\s?لاين|أون\s?لاين|الكتروني|إلكتروني", " online "),
    (r"مجدول|موعد|موعد\s+ال", " scheduled "),
    (r"نتائج|النتائج|درجات|الدرجات", " results marks "),
    (r"مادة|مواد|المادة|المواد", " subject "),
    (r"جدول|الجدول|جدول\s+الحصص", " timetable "),
    (r"تفاصيل|معلومات|بيانات", " details "),
    (r"نشط|النشط|نشطين", " active "),
    (r"غائب|غائبين|الغائبين", " absent "),
    (r"بنك\s+الاسئلة|بنك\s+الأسئلة|الاسئلة|الأسئلة", " questions "),
    (r"question bank|questions bank", " question bank "),
)

_ARABIC_SCRIPT = re.compile(r"[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF]")


def contains_arabic(text: str) -> bool:
    return bool(_ARABIC_SCRIPT.search(text or ""))


def normalize_question_for_routing(question: str) -> str:
    """
    Return a string safe for English regex routers. Latin names, digits, and
    punctuation are preserved.
    """
    q = (question or "").strip()
    if not q or not contains_arabic(q):
        return q

    out = q
    for pattern, replacement in _ARABIC_REPLACEMENTS:
        out = re.sub(pattern, replacement, out, flags=re.IGNORECASE)

    # Drop remaining Arabic script (keeps Latin names like Fatima Al Qahtani).
    out = _ARABIC_SCRIPT.sub(" ", out)
    out = re.sub(r"\s+", " ", out).strip()
    return out


def routing_question(question: str) -> str:
    """Normalized question used for SQL routing; original kept for display."""
    normalized = normalize_question_for_routing(question)
    if normalized:
        return normalized
    return question or ""
