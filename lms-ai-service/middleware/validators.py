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

    return {
        "action":   action,
        "text":     text.strip(),
        "question": question.strip(),
    }, None


def validate_youtube_url(url: str):
    """Returns error string if URL is invalid, else None."""
    if not url or not isinstance(url, str):
        return "'url' is required."
    if len(url) > MAX_URL_LEN:
        return "'url' is too long."
    if not _YOUTUBE_RE.search(url):
        return "The URL does not appear to be a valid YouTube link."
    return None
