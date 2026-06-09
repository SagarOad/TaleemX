"""
Parse calendar dates and month phrases from natural-language questions.
Uses the school's configured date_format from sch_settings (same as TaleemX UI).
"""

from __future__ import annotations

import calendar
import re
import threading
import time
from dataclasses import dataclass
from datetime import date
from typing import TYPE_CHECKING, Optional

if TYPE_CHECKING:
    from services.db_service import DBService

_MONTHS = {
    "january": 1, "jan": 1, "february": 2, "feb": 2, "march": 3, "mar": 3,
    "april": 4, "apr": 4, "may": 5, "june": 6, "jun": 6,
    "july": 7, "jul": 7, "august": 8, "aug": 8, "september": 9, "sep": 9, "sept": 9,
    "october": 10, "oct": 10, "november": 11, "nov": 11, "december": 12, "dec": 12,
}

_DEFAULT_FORMAT = "m/d/Y"
_format_lock = threading.Lock()
_format_cache: str = ""
_format_ts: float = 0.0
_FORMAT_TTL = 120.0


@dataclass
class ParsedDate:
    kind: str  # exact | month | today | this_month | invalid
    sql_on_column: str = ""
    message: str = ""
    parsed: Optional[date] = None
    alt_sql_on_column: str = ""
    alt_parsed: Optional[date] = None


def _safe_date(y: int, m: int, d: int) -> Optional[date]:
    try:
        return date(y, m, d)
    except ValueError:
        return None


def fetch_school_date_format(db: "DBService") -> str:
    """Read sch_settings.date_format — mirrors Customlib::getSchoolDateFormat()."""
    global _format_cache, _format_ts
    now = time.monotonic()
    with _format_lock:
        if _format_cache and (now - _format_ts) < _FORMAT_TTL:
            return _format_cache

    fmt = _DEFAULT_FORMAT
    if db.table_exists("sch_settings"):
        rows, _, err = db.execute(
            "SELECT date_format FROM sch_settings ORDER BY id LIMIT 1",
            max_rows=1,
        )
        if not err and rows and rows[0][0]:
            fmt = str(rows[0][0]).strip() or _DEFAULT_FORMAT

    with _format_lock:
        _format_cache = fmt
        _format_ts = now
    return fmt


def _parts_from_format(a: int, b: int, y: int, date_format: str) -> Optional[date]:
    """Map two numeric parts to (year, month, day) using school format."""
    fmt = (date_format or _DEFAULT_FORMAT).strip()
    if fmt in ("d/m/Y", "d-m-Y", "d.m.Y", "d-M-Y"):
        day, month, year = a, b, y
    elif fmt in ("m/d/Y", "m-d-Y", "m.d.Y"):
        month, day, year = a, b, y
    elif fmt == "Y/m/d":
        year, month, day = a, b, y
    else:
        # Fallback: US-style when first segment looks like month
        if a <= 12:
            month, day, year = a, b, y
        else:
            day, month, year = a, b, y
    return _safe_date(year, month, day)


def _alternate_ambiguous(a: int, b: int, y: int, date_format: str) -> Optional[date]:
    """When both parts <= 12, try the other common interpretation."""
    if a > 12 or b > 12 or a == b:
        return None
    fmt = (date_format or _DEFAULT_FORMAT).strip()
    if fmt in ("d/m/Y", "d-m-Y", "d.m.Y", "d-M-Y"):
        return _safe_date(y, a, b)  # try m/d/Y
    if fmt in ("m/d/Y", "m-d-Y", "m.d.Y"):
        return _safe_date(y, b, a)  # try d/m/Y
    return None


def parse_literal_date(text: str, date_format: str = _DEFAULT_FORMAT) -> Optional[date]:
    """Parse MM/DD/YYYY or DD/MM/YYYY according to school date_format."""
    t = text or ""
    m = re.search(r"\b(\d{1,2})[/-](\d{1,2})[/-](\d{4})\b", t)
    if m:
        a, b, y = int(m.group(1)), int(m.group(2)), int(m.group(3))
        return _parts_from_format(a, b, y, date_format)
    m = re.search(r"\b(\d{4})-(\d{2})-(\d{2})\b", t)
    if m:
        return _safe_date(int(m.group(1)), int(m.group(2)), int(m.group(3)))
    return None


def format_display_date(d: date, date_format: str = _DEFAULT_FORMAT) -> str:
    """Format ISO date the way the school UI would show it."""
    fmt = (date_format or _DEFAULT_FORMAT).strip()
    if fmt in ("d/m/Y", "d-m-Y"):
        return d.strftime("%d/%m/%Y")
    if fmt in ("m/d/Y", "m-d-Y"):
        return d.strftime("%m/%d/%Y")
    if fmt == "d.m.Y":
        return d.strftime("%d.%m.%Y")
    if fmt == "m.d.Y":
        return d.strftime("%m.%d.%Y")
    return d.strftime("%Y-%m-%d")


def parse_invalid_day_question(question: str) -> Optional[str]:
    q = (question or "").lower()
    m = re.search(
        r"\b(\d{1,2})\s+(january|february|march|april|may|june|july|august|"
        r"september|october|november|december|jan|feb|mar|apr|jun|jul|aug|sep|oct|nov|dec)\b",
        q,
    )
    if not m:
        return None
    day = int(m.group(1))
    month_name = m.group(2)
    month = _MONTHS.get(month_name)
    if not month:
        return None
    max_day = calendar.monthrange(2024, month)[1]
    if day < 1 or day > max_day:
        month_label = calendar.month_name[month]
        return (
            f"**{month_label}** only has **{max_day}** days — "
            f"**{day} {month_label}** is not a valid date, so there is no data for that day."
        )
    return None


def parse_month_from_question(question: str) -> Optional[int]:
    q = (question or "").lower()
    q = re.sub(r"['']s\b", " ", q)
    if "this month" in q or "current month" in q:
        return 0
    for name, num in _MONTHS.items():
        if re.search(rf"\b{name}s?\b", q):
            return num
    return None


def parse_year_from_question(question: str) -> Optional[int]:
    m = re.search(r"\b(20\d{2})\b", question or "")
    return int(m.group(1)) if m else None


def month_english_name(month_num: int) -> str:
    return calendar.month_name[int(month_num)]


def parse_exact_date_from_question(
    question: str,
    date_format: str = _DEFAULT_FORMAT,
) -> Optional[date]:
    """Parse '1 June 2026', 'June 1 2026', or numeric dates."""
    exact = parse_literal_date(question, date_format)
    if exact:
        return exact
    q = (question or "").lower()
    m = re.search(
        r"\b(\d{1,2})\s+(january|february|march|april|may|june|july|august|"
        r"september|october|november|december|jan|feb|mar|apr|jun|jul|aug|sep|oct|nov|dec)"
        r"(?:\s+(20\d{2}))?\b",
        q,
    )
    if m:
        day = int(m.group(1))
        month = _MONTHS.get(m.group(2))
        year = int(m.group(3)) if m.group(3) else date.today().year
        return _safe_date(year, month, day)
    m = re.search(
        r"\b(january|february|march|april|may|june|july|august|"
        r"september|october|november|december|jan|feb|mar|apr|jun|jul|aug|sep|oct|nov|dec)"
        r"\s+(\d{1,2})(?:,?\s+(20\d{2}))?\b",
        q,
    )
    if m:
        month = _MONTHS.get(m.group(1))
        day = int(m.group(2))
        year = int(m.group(3)) if m.group(3) else date.today().year
        return _safe_date(year, month, day)
    return None


def parse_datetime_from_question(
    question: str,
    date_format: str = _DEFAULT_FORMAT,
) -> Optional[str]:
    """Return MySQL datetime string 'YYYY-MM-DD HH:MM:SS' if found."""
    q = question or ""
    m = re.search(
        r"\b(\d{1,2})[/-](\d{1,2})[/-](\d{4})\s+(\d{1,2}):(\d{2})(?::(\d{2}))?\b",
        q,
    )
    if m:
        a, b, y = int(m.group(1)), int(m.group(2)), int(m.group(3))
        d = _parts_from_format(a, b, y, date_format)
        if d:
            sec = int(m.group(6) or 0)
            return f"{d.isoformat()} {int(m.group(4)):02d}:{int(m.group(5)):02d}:{sec:02d}"
    return None


def sql_date_filter(
    column: str,
    question: str,
    date_format: str = _DEFAULT_FORMAT,
) -> ParsedDate:
    col = column.strip()
    invalid = parse_invalid_day_question(question)
    if invalid:
        return ParsedDate(kind="invalid", message=invalid)

    m = re.search(r"\b(\d{1,2})[/-](\d{1,2})[/-](\d{4})\b", question or "")
    if m:
        a, b, y = int(m.group(1)), int(m.group(2)), int(m.group(3))
        exact = _parts_from_format(a, b, y, date_format)
        if exact:
            alt = _alternate_ambiguous(a, b, y, date_format)
            pd = ParsedDate(
                kind="exact",
                sql_on_column=f"DATE({col}) = '{exact.isoformat()}'",
                parsed=exact,
            )
            if alt and alt != exact:
                pd.alt_parsed = alt
                pd.alt_sql_on_column = f"DATE({col}) = '{alt.isoformat()}'"
            return pd

    named = parse_exact_date_from_question(question, date_format)
    if named:
        return ParsedDate(
            kind="exact",
            sql_on_column=f"DATE({col}) = '{named.isoformat()}'",
            parsed=named,
        )

    exact = parse_literal_date(question, date_format)
    if exact:
        return ParsedDate(
            kind="exact",
            sql_on_column=f"DATE({col}) = '{exact.isoformat()}'",
            parsed=exact,
        )

    if re.search(r"\btoday\b|\bاليوم\b", (question or "").lower()):
        return ParsedDate(kind="today", sql_on_column=f"DATE({col}) = CURDATE()")

    month = parse_month_from_question(question)
    if month == 0:
        return ParsedDate(
            kind="this_month",
            sql_on_column=(
                f"MONTH({col}) = MONTH(CURDATE()) AND YEAR({col}) = YEAR(CURDATE())"
            ),
        )
    if month:
        year_m = re.search(r"\b(20\d{2})\b", question or "")
        year = int(year_m.group(1)) if year_m else "YEAR(CURDATE())"
        if isinstance(year, int):
            return ParsedDate(
                kind="month",
                sql_on_column=f"MONTH({col}) = {month} AND YEAR({col}) = {year}",
            )
        return ParsedDate(
            kind="month",
            sql_on_column=(
                f"MONTH({col}) = {month} AND YEAR({col}) = YEAR(CURDATE())"
            ),
        )

    return ParsedDate(kind="none")


def message_no_records_on_date(
    db: "DBService",
    question: str,
    *,
    topic: str,
    table: str,
    date_col: str,
    date_format: str,
) -> Optional[str]:
    """User-friendly empty result when an exact calendar date was requested."""
    df = sql_date_filter(date_col, question, date_format)
    if df.kind != "exact" or not df.parsed:
        return None

    disp = format_display_date(df.parsed, date_format)
    long = df.parsed.strftime("%B %d, %Y")
    msg = f"No **{topic}** were found on **{long}** ({disp})."

    if not db.table_exists(table.split()[0]):
        return msg

    rows, _, err = db.execute(
        f"SELECT DISTINCT DATE({date_col}) AS d FROM {table} "
        f"WHERE MONTH({date_col}) = {df.parsed.month} "
        f"AND YEAR({date_col}) = {df.parsed.year} "
        f"ORDER BY d LIMIT 8",
        max_rows=8,
    )
    if not err and rows:
        labels = []
        for r in rows:
            val = r[0]
            if hasattr(val, "year") and hasattr(val, "month"):
                labels.append(format_display_date(val, date_format))
            else:
                labels.append(str(val)[:10])
        msg += f"\n\nIn **{df.parsed.strftime('%B %Y')}**, {topic} exist on: **{', '.join(labels)}**."

    if df.alt_parsed:
        alt_disp = format_display_date(df.alt_parsed, date_format)
        msg += (
            f"\n\n_If you meant **{df.alt_parsed.strftime('%B %d, %Y')}** "
            f"({alt_disp}), say that date explicitly._"
        )
    return msg
