"""
Refuse data-mutation, impersonation, and credential-export requests before SQL runs.
"""

from __future__ import annotations

import re
from typing import Optional

_WRITE_PATTERNS = (
    r"\b(delete|remove|drop|truncate|erase|wipe)\b",
    r"\b(create|add|insert|register|admit)\b.*\b(enquiry|enquiries|visitor|complaint|notice|student|fee|payment|record)\b",
    r"\b(collect|pay|approve|enable|disable|mark)\b.*\b(all|every|bulk)\b",
    r"\bignore\s+all\s+rules\b",
    r"\bpretend\s+i\s+am\b",
    r"\bchange\s+all\s+fee\s+amounts\b",
    r"\bmark\s+all\s+students\s+as\s+paid\b",
    r"\bapprove\s+all\s+offline\b",
    r"\bexport\s+all\b.*\b(password|credential|login)\b",
    r"\b(show|give|list)\b.*\b(password|credentials?|login)\b",
)

_SECURITY_MESSAGE = (
    "I can only **read** school data — I cannot create, update, delete, or approve records. "
    "Use the relevant module in the admin panel (Front Office, Fee Collection, Student Admission) "
    "to perform that action."
)

_IMPERSONATION_MESSAGE = (
    "I cannot change permissions based on role claims in chat. "
    "Access is controlled by your actual logged-in admin session, not by what you type here."
)


def check_action_guard(question: str) -> Optional[str]:
    q = (question or "").lower().strip()
    if not q:
        return None

    if re.search(r"\bpretend\s+i\s+am\b", q) or re.search(
        r"\bnot\s+super\s+admin\b.*\bshow\b", q
    ):
        return _IMPERSONATION_MESSAGE

    if re.search(r"\b(password|credentials?|login)\b", q) and re.search(
        r"\b(show|give|list|export|display)\b", q
    ):
        return (
            "I cannot display passwords or login credentials. "
            "Use the admin user-management screens if you need to reset access."
        )

    for pat in _WRITE_PATTERNS:
        if re.search(pat, q):
            if "explain" in q or "what is" in q or "how do" in q:
                continue
            return _SECURITY_MESSAGE

    if re.search(r"\b(create|add)\b", q) and re.search(
        r"\b(notice|holiday|complaint|enquiry|admission)\b", q
    ):
        return (
            "To create a notice, enquiry, or complaint, use **Front Office** in the admin menu. "
            "I can show existing records but cannot submit new ones from chat."
        )

    if re.search(r"\badmit\b.*\bstudent\b", q) or re.search(
        r"\bnew\s+student\b.*\b(?:grade|class)\b", q
    ):
        return (
            "To **admit a new student**, open **Student Information → Student Admission**. "
            "Required fields include date of birth, gender, parent/guardian contact, "
            "category, and session/class-section — I cannot create admission records from chat."
        )

    return None
