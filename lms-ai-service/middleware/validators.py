"""
middleware/validators.py
Input validation helpers for each endpoint.

Validators return (data, error) tuples where:
  - data  is the cleaned payload dict  (None on failure)
  - error is a (message: str, status: int) tuple  (None on success)

Keeping validators Flask-agnostic (no jsonify) makes them unit-testable
without an application context and usable in any framework.
Routes call jsonify() themselves on the returned error tuple.
"""

import re

MAX_QUESTION_LEN = 1000
MAX_CAPTION_LEN  = 50_000   # ~30 min of dense subtitles
MAX_URL_LEN      = 2048
MAX_TITLE_LEN    = 500
MAX_SEGMENTS     = 1200     # hard cap so a malicious payload can't blow up memory
MAX_HISTORY      = 12       # last N conversation turns kept for follow-ups

_YOUTUBE_RE = re.compile(
    r"(youtu\.be/|youtube\.com/(watch\?.*v=|embed/|shorts/|v/))[A-Za-z0-9_-]{11}",
    re.IGNORECASE,
)


def validate_ask(data: dict):
    """
    Validate POST /ask payload.
    Returns: (cleaned_data, None) on success
             (None, (error_str, status_code)) on failure
    """
    if not data:
        return None, ("JSON body is required.", 400)

    question = data.get("question", "")
    if not isinstance(question, str) or not question.strip():
        return None, ("'question' must be a non-empty string.", 400)

    if len(question) > MAX_QUESTION_LEN:
        return None, (
            f"'question' exceeds maximum length of {MAX_QUESTION_LEN} characters.", 400
        )

    respond_arabic = data.get("respond_arabic", False)
    if not isinstance(respond_arabic, bool):
        respond_arabic = str(respond_arabic).lower() in ("1", "true", "yes", "on")

    return {"question": question.strip(), "respond_arabic": respond_arabic}, None


def validate_caption_ai(data: dict):
    """
    Validate POST /caption-ai payload.
    Returns: (cleaned_data, None) on success
             (None, (error_str, status_code)) on failure
    """
    if not data:
        return None, ("JSON body is required.", 400)

    action = data.get("action", "")
    if action not in ("summarize", "explain"):
        return None, ("'action' must be either 'summarize' or 'explain'.", 400)

    text = data.get("text", "")
    if not isinstance(text, str) or not text.strip():
        return None, ("'text' (captions) must be a non-empty string.", 400)

    if len(text) > MAX_CAPTION_LEN:
        return None, (
            f"'text' exceeds maximum length of {MAX_CAPTION_LEN} characters.", 400
        )

    question = data.get("question", "")
    if not isinstance(question, str):
        return None, ("'question' must be a string.", 400)

    # ── Optional enrichment context (all best-effort, never hard-fail) ────────
    segments = _clean_segments(data.get("segments"))
    history  = _clean_history(data.get("history"))

    def _clean_str(value, limit=MAX_TITLE_LEN):
        if not isinstance(value, str):
            return ""
        return value.strip()[:limit]

    level = _clean_str(data.get("level"), 32).lower()
    if level not in ("", "simple", "standard", "advanced", "exam"):
        level = ""

    return {
        "action":        action,
        "text":          text.strip(),
        "question":      question.strip(),
        "segments":      segments,
        "history":       history,
        "lesson_title":  _clean_str(data.get("lesson_title")),
        "lesson_summary": _clean_str(data.get("lesson_summary"), 4000),
        "course_title":  _clean_str(data.get("course_title")),
        "level":         level,
    }, None


def _clean_segments(raw):
    """
    Normalise an optional timed-segment list into
    [{"start": float, "end": float, "text": str}, ...].
    Silently drops malformed entries; returns [] when nothing usable.
    """
    if not isinstance(raw, list):
        return []
    out = []
    for seg in raw[:MAX_SEGMENTS]:
        if not isinstance(seg, dict):
            continue
        text = seg.get("text")
        if not isinstance(text, str) or not text.strip():
            continue
        try:
            start = float(seg.get("start", 0) or 0)
        except (TypeError, ValueError):
            start = 0.0
        try:
            end = float(seg.get("end", start) or start)
        except (TypeError, ValueError):
            end = start
        out.append({
            "start": round(max(0.0, start), 3),
            "end":   round(max(0.0, end), 3),
            "text":  text.strip()[:600],
        })
    return out


def _clean_history(raw):
    """
    Normalise an optional conversation history into
    [{"role": "user"|"assistant", "content": str}, ...], keeping the last
    MAX_HISTORY turns.
    """
    if not isinstance(raw, list):
        return []
    out = []
    for turn in raw:
        if not isinstance(turn, dict):
            continue
        role = turn.get("role")
        content = turn.get("content")
        if role not in ("user", "assistant"):
            continue
        if not isinstance(content, str) or not content.strip():
            continue
        out.append({"role": role, "content": content.strip()[:MAX_QUESTION_LEN]})
    return out[-MAX_HISTORY:]


def validate_youtube_url(url: str):
    """Returns error string if URL is invalid, else None."""
    if not url or not isinstance(url, str):
        return "'url' is required."
    if len(url) > MAX_URL_LEN:
        return "'url' is too long."
    if not _YOUTUBE_RE.search(url):
        return "The URL does not appear to be a valid YouTube link."
    return None
