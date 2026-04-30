"""
tests/test_validators.py
Unit tests for middleware/validators.py — pure logic, no network or DB.
Run with: pytest tests/test_validators.py -v
"""

import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

import pytest
from middleware.validators import validate_ask, validate_caption_ai, validate_youtube_url


class TestValidateAsk:
    def test_valid(self):
        data, err = validate_ask({"question": "How many students?"})
        assert err is None
        assert data["question"] == "How many students?"

    def test_strips_whitespace(self):
        data, err = validate_ask({"question": "  hello  "})
        assert err is None
        assert data["question"] == "hello"

    def test_none_body(self):
        _, err = validate_ask(None)
        assert err is not None
        assert isinstance(err, tuple) and len(err) == 2  # (message, status_code)
        assert err[1] == 400

    def test_empty_question(self):
        _, err = validate_ask({"question": ""})
        assert err is not None
        assert err[1] == 400

    def test_whitespace_question(self):
        _, err = validate_ask({"question": "   "})
        assert err is not None
        assert err[1] == 400

    def test_too_long(self):
        _, err = validate_ask({"question": "q" * 1001})
        assert err is not None
        assert err[1] == 400

    def test_exactly_max_length(self):
        data, err = validate_ask({"question": "q" * 1000})
        assert err is None


class TestValidateCaptionAI:
    def test_valid_summarize(self):
        data, err = validate_caption_ai({"action": "summarize", "text": "some text"})
        assert err is None
        assert data["action"] == "summarize"
        assert data["question"] == ""

    def test_valid_explain_with_question(self):
        data, err = validate_caption_ai({
            "action": "explain", "text": "captions", "question": "What is X?"
        })
        assert err is None
        assert data["question"] == "What is X?"

    def test_invalid_action(self):
        _, err = validate_caption_ai({"action": "translate", "text": "text"})
        assert err is not None
        assert err[1] == 400

    def test_missing_text(self):
        _, err = validate_caption_ai({"action": "summarize"})
        assert err is not None
        assert err[1] == 400

    def test_empty_text(self):
        _, err = validate_caption_ai({"action": "summarize", "text": ""})
        assert err is not None
        assert err[1] == 400

    def test_text_too_long(self):
        _, err = validate_caption_ai({"action": "summarize", "text": "t" * 50_001})
        assert err is not None
        assert err[1] == 400

    def test_none_body(self):
        _, err = validate_caption_ai(None)
        assert err is not None
        assert err[1] == 400


class TestValidateYouTubeUrl:
    @pytest.mark.parametrize("url", [
        "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
        "https://youtu.be/dQw4w9WgXcQ",
        "https://www.youtube.com/embed/dQw4w9WgXcQ",
        "https://www.youtube.com/shorts/dQw4w9WgXcQ",
        "http://youtube.com/watch?v=dQw4w9WgXcQ&t=30",
    ])
    def test_valid_urls(self, url):
        assert validate_youtube_url(url) is None

    @pytest.mark.parametrize("url", [
        "",
        "https://example.com/video",
        "https://vimeo.com/123456",
        "not-a-url",
        None,
    ])
    def test_invalid_urls(self, url):
        assert validate_youtube_url(url) is not None

    def test_too_long_url(self):
        url = "https://youtu.be/" + "a" * 2048
        assert validate_youtube_url(url) is not None
