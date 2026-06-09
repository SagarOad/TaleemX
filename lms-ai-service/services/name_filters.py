"""
Extract person names from questions and validate result relevance.
Reduces hallucinated full-table dumps when a specific name was requested.
"""

from __future__ import annotations

import re
from typing import Optional

_FAKE_MARKERS = (
    "notreal", "fake", "xyz", "does not exist", "doesn't exist",
    "nonexistent", "non-existent", "999", "sample fake",
)

_NAME_PATTERNS = (
    r"(?:student|applicant)\s+named?\s+([A-Za-z][A-Za-z\s\-']{2,60})",
    r"(?:named|for)\s+([A-Za-z][A-Za-z\s\-']{2,60})",
    r"([A-Za-z][A-Za-z\s\-']{2,60})\s+notreal\b",
    r"(?:details?|info|information|profile)\s+(?:of|for)\s+(?:student\s+)?([A-Za-z][A-Za-z\s\-']{2,60})",
    r"(?:show|display)\s+student\s+details?\s+for\s+([A-Za-z][A-Za-z\s\-']{2,60})",
    r"(?:payment\s+history|fee\s+history|fees?\s+for)\s+(?:of|for)\s+([A-Za-z][A-Za-z\s\-']{2,60})",
    r"(?:full\s+)?fee\s+ledger\s+of\s+([A-Za-z][A-Za-z\s\-']{2,60})",
    r"(?:of|for)\s+student\s+([A-Za-z][A-Za-z\s\-']{2,60})",
    r"student\s+([A-Za-z][A-Za-z\s\-']{2,60})\s+(?:is|in)\s+",
)

_SKIP_NAMES = frozenset({
    "grade", "class", "section", "april", "june", "this month", "current month",
    "transport fees", "fee group", "quick fees", "the school", "all students",
})

# Strip trailing question noise after a captured name.
_NAME_TRAIL_RE = re.compile(
    r"\s+(?:is\s+)?(?:(?:paid|unpaid|due|pending)(?:\s+or\s+(?:paid|unpaid|due|pending))?.*)$",
    re.IGNORECASE,
)


def normalize_person_name(raw: str) -> str:
    name = " ".join((raw or "").split()).strip("?.!,;:")
    name = _NAME_TRAIL_RE.sub("", name).strip()
    return name


def extract_person_name(question: str) -> Optional[str]:
    q = question or ""
    for pat in _NAME_PATTERNS:
        m = re.search(pat, q, re.IGNORECASE)
        if m:
            name = normalize_person_name(m.group(1))
            low = name.lower()
            if len(name) >= 3 and low not in _SKIP_NAMES:
                return name
    return None


def extract_student_name(question: str) -> Optional[str]:
    """Best-effort student name for fee / transport questions."""
    q = question or ""
    for pat in (
        r"student\s+([A-Za-z][A-Za-z\s\-']+?)\s+is\s+(?:paid|unpaid)",
        r"(?:of|for)\s+student\s+([A-Za-z][A-Za-z\s\-']+?)(?:\s+is\b|\?|$)",
        r"(?:for|of)\s+([A-Za-z][A-Za-z\s\-']+?)(?:\s+is\b|\?|$)",
    ):
        m = re.search(pat, q, re.IGNORECASE)
        if m:
            name = normalize_person_name(m.group(1))
            low = name.lower()
            if len(name) >= 3 and low not in _SKIP_NAMES:
                return name
    return extract_person_name(question)


def is_likely_fake_name(name: str) -> bool:
    if not name:
        return False
    low = name.lower()
    return any(m in low for m in _FAKE_MARKERS)


_NAME_COLUMN_KEYS = frozenset({
    "name", "student_name", "firstname", "middlename", "lastname",
    "complainant", "student_firstname", "student_middlename", "student_lastname",
})


def normalize_name_text(text: str) -> str:
    """Lowercase, unify hyphens/spaces for fuzzy Arabic/Western name matching."""
    t = (text or "").lower().replace("-", " ").replace("'", " ")
    return " ".join(t.split())


_TOKEN_ALIASES: dict[str, tuple[str, ...]] = {
    "muhammad": ("muhammad", "mohammed", "mohamed"),
    "mohammed": ("muhammad", "mohammed", "mohamed"),
    "mohamed": ("muhammad", "mohammed", "mohamed"),
}


def _token_match_variants(token: str) -> tuple[str, ...]:
    low = token.lower()
    return _TOKEN_ALIASES.get(low, (low,))


def name_query_tokens(name: str) -> list[str]:
    return [t for t in normalize_name_text(name).split() if len(t) >= 2]


def student_full_name_expr(alias: str = "s") -> str:
    return (
        f"LOWER(CONCAT_WS(' ', {alias}.firstname, {alias}.middlename, "
        f"{alias}.lastname))"
    )


def student_name_sql_filter(name: str, alias: str = "s") -> str:
    """
    SQL predicate: every token in the query must appear in the student's full name.
    Handles middlename, hyphenated surnames (Al-Harbi), and partial names (Omar Harbi).
    """
    tokens = name_query_tokens(name)
    full = student_full_name_expr(alias)
    if not tokens:
        sn = normalize_name_text(name).replace("'", "''")
        return f"{full} LIKE '%{sn}%'"
    parts = []
    for token in tokens:
        likes = [
            f"{full} LIKE '%{v.replace(chr(39), chr(39)+chr(39))}%'"
            for v in dict.fromkeys(_token_match_variants(token))
        ]
        parts.append("(" + " OR ".join(likes) + ")")
    return "(" + " AND ".join(parts) + ")"


def _full_name_from_row(columns: list, row: tuple) -> str:
    colmap = {str(c).lower(): i for i, c in enumerate(columns)}
    parts: list[str] = []
    for key in (
        "firstname", "middlename", "lastname",
        "student_firstname", "student_middlename", "student_lastname",
    ):
        idx = colmap.get(key)
        if idx is not None and idx < len(row) and row[idx]:
            parts.append(str(row[idx]))
    if parts:
        return normalize_name_text(" ".join(parts))
    for key in ("student_name", "name"):
        idx = colmap.get(key)
        if idx is not None and idx < len(row) and row[idx]:
            return normalize_name_text(str(row[idx]))
    return ""


def _tokens_match_full_name(full: str, tokens: list[str]) -> bool:
    if not full or not tokens:
        return False
    return all(t in full for t in tokens)


def message_no_match_for_name(name: str, topic: str = "records") -> str:
    if is_likely_fake_name(name):
        return f"No **{topic}** were found for **{name}** — that name does not appear in your school data."
    return (
        f"No **{topic}** matched **{name}**. "
        "Try the exact spelling from the student or enquiry profile."
    )


def rows_match_name(columns: list, rows: list[tuple], name: str) -> bool:
    """True if at least one row contains the requested name (fuzzy)."""
    if not name or not rows:
        return False
    tokens = name_query_tokens(name)
    if not tokens:
        return False
    for row in rows:
        full = _full_name_from_row(columns, row)
        if full and _tokens_match_full_name(full, tokens):
            return True
        name_cols = {
            i for i, c in enumerate(columns)
            if str(c).lower() in _NAME_COLUMN_KEYS
        }
        for i in name_cols:
            if str(columns[i]).lower() in {"father_name", "guardian_name", "mother_name"}:
                continue
            text = normalize_name_text(str(row[i] or ""))
            if text and _tokens_match_full_name(text, tokens):
                return True
    return False
