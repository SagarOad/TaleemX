"""
Plain-text cleanup for DB fields that store rich HTML (homework descriptions, etc.).
"""

from __future__ import annotations

import html as html_module
import re
from html.parser import HTMLParser

# Column names that usually hold long HTML blobs — truncate after stripping.
_LONG_TEXT_COLUMN_HINTS = (
    "description", "desc", "message", "msg", "content", "body", "note", "notes",
    "homework", "detail", "details", "comment", "remarks", "narration",
)

_DEFAULT_MAX_CELL_CHARS = 220


class _HTMLTextExtractor(HTMLParser):
    def __init__(self):
        super().__init__()
        self._parts: list[str] = []

    def handle_data(self, data: str) -> None:
        if data:
            self._parts.append(data)

    def get_text(self) -> str:
        return " ".join(self._parts)


def strip_html(value: str | None) -> str:
    """Turn HTML-ish DB text into readable plain text."""
    if value is None:
        return ""
    if not isinstance(value, str):
        value = str(value)
    text = value.strip()
    if not text:
        return ""
    if "<" not in text and "&" not in text:
        return re.sub(r"\s+", " ", text).strip()

    parser = _HTMLTextExtractor()
    try:
        parser.feed(text)
        parser.close()
        plain = parser.get_text()
    except Exception:
        plain = re.sub(r"<[^>]+>", " ", text)

    plain = html_module.unescape(plain)
    plain = re.sub(r"\s+", " ", plain).strip()
    return plain


def _column_is_long_text(column_name: str) -> bool:
    col = (column_name or "").lower().replace(" ", "_")
    if col in _LONG_TEXT_COLUMN_HINTS:
        return True
    return any(hint in col for hint in ("desc", "content", "message", "homework"))


def sanitize_cell(value, column_name: str = "", max_chars: int = _DEFAULT_MAX_CELL_CHARS):
    """Strip HTML and optionally truncate verbose columns for tables/cards."""
    if value is None:
        return ""
    if isinstance(value, (int, float)) and not isinstance(value, bool):
        return value
    text = strip_html(str(value))
    if max_chars and _column_is_long_text(column_name) and len(text) > max_chars:
        return text[: max_chars - 1].rstrip() + "…"
    return text
