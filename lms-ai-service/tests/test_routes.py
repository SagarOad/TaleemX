"""
tests/test_routes.py
Integration tests for Flask endpoints using unittest.mock to isolate
external dependencies (Gemini, MySQL, YouTube, Whisper).

Run with: pytest tests/test_routes.py -v
"""

import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

import json
import pytest
from unittest.mock import patch, MagicMock

# Patch heavy imports before app loads
with patch.dict(os.environ, {
    "GEMINI_API_KEY": "test-key",
    "DB_HOST": "localhost",
    "DB_USER": "test",
    "DB_PASSWORD": "test",
    "DB_NAME": "test_db",
}):
    import app as flask_app

flask_app.app.config["TESTING"] = True
flask_app.app.config["WTF_CSRF_ENABLED"] = False


@pytest.fixture
def client():
    with flask_app.app.test_client() as c:
        yield c


@pytest.fixture(autouse=True)
def _disable_agent(monkeypatch):
    """
    Force the /ask route through the legacy single-shot pipeline so the
    pre-existing mocks of `ai_service.generate_sql` and `db_service.execute`
    still apply. Individual tests that exercise the agent path
    re-enable it explicitly.
    """
    monkeypatch.setattr(flask_app, "sql_agent", None, raising=False)


# ── /health ───────────────────────────────────────────────────────────────────
class TestHealth:
    def test_returns_ok(self, client):
        with patch.object(flask_app.db_service, "ping", return_value=True):
            resp = client.get("/health")
        assert resp.status_code == 200
        data = resp.get_json()
        assert data["status"] == "ok"
        assert data["database"] == "connected"

    def test_db_unreachable(self, client):
        with patch.object(flask_app.db_service, "ping", return_value=False):
            resp = client.get("/health")
        data = resp.get_json()
        assert data["database"] == "unreachable"


# ── /ask ──────────────────────────────────────────────────────────────────────
class TestAsk:
    def _post(self, client, payload):
        return client.post(
            "/ask",
            data=json.dumps(payload),
            content_type="application/json",
        )

    def test_missing_question(self, client):
        resp = self._post(client, {})
        assert resp.status_code == 400

    def test_empty_question(self, client):
        resp = self._post(client, {"question": "   "})
        assert resp.status_code == 400

    def test_question_too_long(self, client):
        resp = self._post(client, {"question": "x" * 1001})
        assert resp.status_code == 400

    def test_non_data_question(self, client):
        with patch.object(flask_app.ai_service, "generate_sql", return_value=""):
            resp = self._post(client, {"question": "What is the weather today?"})
        assert resp.status_code == 200
        assert "answer" in resp.get_json()

    def test_successful_query(self, client):
        with patch.object(flask_app.ai_service, "generate_sql",
                          return_value="SELECT id, name FROM users LIMIT 50"), \
             patch.object(flask_app.db_service, "execute",
                          return_value=([(1, "Alice"), (2, "Bob")], ["id", "name"], None)), \
             patch.object(flask_app.ai_service, "format_results",
                          return_value="There are 2 users: Alice and Bob."):
            resp = self._post(client, {"question": "List all users"})
        assert resp.status_code == 200
        assert resp.get_json()["answer"] == "There are 2 users: Alice and Bob."

    def test_invalid_sql_blocked(self, client):
        with patch.object(flask_app.ai_service, "generate_sql",
                          return_value="DROP TABLE users"):
            resp = self._post(client, {"question": "Delete everything"})
        assert resp.status_code == 200
        data = resp.get_json()
        assert "security" in data["answer"].lower() or "cannot" in data["answer"].lower()

    def test_db_error_handled(self, client):
        with patch.object(flask_app.ai_service, "generate_sql",
                          return_value="SELECT * FROM users LIMIT 50"), \
             patch.object(flask_app.db_service, "execute",
                          return_value=([], [], "Connection refused")):
            resp = self._post(client, {"question": "Show users"})
        assert resp.status_code == 200
        assert "error" in resp.get_json()["answer"].lower() or "database" in resp.get_json()["answer"].lower()

    def test_empty_result(self, client):
        with patch.object(flask_app.ai_service, "generate_sql",
                          return_value="SELECT * FROM users WHERE id=999 LIMIT 50"), \
             patch.object(flask_app.db_service, "execute",
                          return_value=([], [], None)):
            resp = self._post(client, {"question": "Find user 999"})
        assert resp.status_code == 200
        assert "no records" in resp.get_json()["answer"].lower()


# ── /ask via the agent (RAG + critique) ──────────────────────────────────────
class TestAskAgent:
    """
    Hits the RAG agent code path by injecting a fake SQLAgent into the
    Flask app module. Verifies request/response shaping only — the agent's
    internal behaviour is unit-tested elsewhere.
    """

    def _post(self, client, payload):
        return client.post(
            "/ask",
            data=json.dumps(payload),
            content_type="application/json",
        )

    def test_agent_success(self, client, monkeypatch):
        from services.sql_agent import AgentResult, AgentTrace

        class _FakeAgent:
            def answer(self, question):
                return AgentResult(
                    answer="There are 2 students: Alice and Bob.",
                    sql="SELECT firstname, lastname FROM students LIMIT 50",
                    columns=["firstname", "lastname"],
                    rows=[("Alice", "Smith"), ("Bob", "Khan")],
                    status="ok",
                    trace=AgentTrace(question=question, final_status="ok"),
                )

        monkeypatch.setattr(flask_app, "sql_agent", _FakeAgent(), raising=False)

        resp = self._post(client, {"question": "list students"})
        assert resp.status_code == 200
        body = resp.get_json()
        assert "alice" in body["answer"].lower()
        assert body["status"] == "ok"
        assert "SELECT" in body["sql"]

    def test_agent_debug_trace(self, client, monkeypatch):
        from services.sql_agent import AgentResult, AgentTrace

        class _FakeAgent:
            def answer(self, question):
                trace = AgentTrace(question=question, final_status="ok")
                trace.plan = "Use students table."
                trace.retrieved_tables = ["students"]
                return AgentResult(
                    answer="ok", sql="SELECT * FROM students LIMIT 1",
                    status="ok", trace=trace,
                )

        monkeypatch.setattr(flask_app, "sql_agent", _FakeAgent(), raising=False)

        resp = self._post(client, {"question": "list students", "debug": True})
        assert resp.status_code == 200
        body = resp.get_json()
        assert "trace" in body
        assert body["trace"]["final_status"] == "ok"


# ── /extract-subtitles ────────────────────────────────────────────────────────
class TestExtractSubtitles:
    def test_youtube_missing_url(self, client):
        resp = client.post(
            "/extract-subtitles",
            data=json.dumps({"type": "youtube", "url": ""}),
            content_type="application/json",
        )
        assert resp.status_code == 400

    def test_youtube_invalid_url(self, client):
        resp = client.post(
            "/extract-subtitles",
            data=json.dumps({"type": "youtube", "url": "https://example.com/not-youtube"}),
            content_type="application/json",
        )
        assert resp.status_code == 400

    def test_youtube_success(self, client):
        with patch.object(flask_app.subtitle_service, "extract_from_youtube",
                          return_value=("Hello world captions.", None)):
            resp = client.post(
                "/extract-subtitles",
                data=json.dumps({"type": "youtube", "url": "https://youtu.be/dQw4w9WgXcQ"}),
                content_type="application/json",
            )
        assert resp.status_code == 200
        assert resp.get_json()["subtitles"] == "Hello world captions."

    def test_youtube_extraction_error(self, client):
        with patch.object(flask_app.subtitle_service, "extract_from_youtube",
                          return_value=("", "Transcripts disabled.")):
            resp = client.post(
                "/extract-subtitles",
                data=json.dumps({"type": "youtube", "url": "https://youtu.be/dQw4w9WgXcQ"}),
                content_type="application/json",
            )
        assert resp.status_code == 422
        assert "error" in resp.get_json()

    def test_no_file_uploaded(self, client):
        resp = client.post("/extract-subtitles", data={})
        assert resp.status_code == 400

    def test_file_upload_success(self, client):
        with patch.object(flask_app.subtitle_service, "extract_from_file",
                          return_value=("Lecture transcription text.", None)):
            data = {"file": (MagicMock(filename="lecture.mp4"), "lecture.mp4")}
            from io import BytesIO
            resp = client.post(
                "/extract-subtitles",
                data={"file": (BytesIO(b"fake video bytes"), "lecture.mp4")},
                content_type="multipart/form-data",
            )
        assert resp.status_code == 200


# ── /caption-ai ───────────────────────────────────────────────────────────────
class TestCaptionAI:
    def _post(self, client, payload):
        return client.post(
            "/caption-ai",
            data=json.dumps(payload),
            content_type="application/json",
        )

    def test_missing_action(self, client):
        resp = self._post(client, {"text": "some text"})
        assert resp.status_code == 400

    def test_invalid_action(self, client):
        resp = self._post(client, {"action": "translate", "text": "some text"})
        assert resp.status_code == 400

    def test_missing_text(self, client):
        resp = self._post(client, {"action": "summarize", "text": ""})
        assert resp.status_code == 400

    def test_text_too_long(self, client):
        resp = self._post(client, {"action": "summarize", "text": "x" * 50_001})
        assert resp.status_code == 400

    def test_summarize_success(self, client):
        with patch.object(flask_app.ai_service, "summarize_captions",
                          return_value="This video covers Python basics."):
            resp = self._post(client, {"action": "summarize", "text": "In this video..."})
        assert resp.status_code == 200
        assert resp.get_json()["answer"] == "This video covers Python basics."

    def test_explain_success(self, client):
        with patch.object(flask_app.ai_service, "explain_captions",
                          return_value="Variables store data in Python."):
            resp = self._post(client, {
                "action": "explain",
                "text": "A variable is a container...",
                "question": "What is a variable?"
            })
        assert resp.status_code == 200
        assert "variable" in resp.get_json()["answer"].lower()

    def test_explain_without_question(self, client):
        with patch.object(flask_app.ai_service, "explain_captions",
                          return_value="Here is the explanation."):
            resp = self._post(client, {"action": "explain", "text": "Some caption text"})
        assert resp.status_code == 200


# ── 404 / 405 ─────────────────────────────────────────────────────────────────
class TestErrorHandlers:
    def test_404(self, client):
        resp = client.get("/nonexistent")
        assert resp.status_code == 404

    def test_405(self, client):
        resp = client.get("/ask")
        assert resp.status_code == 405
