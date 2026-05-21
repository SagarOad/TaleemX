"""
LMS AI Microservice — Flask Entry Point
Handles:
  - NL→SQL querying (RAG-grounded, agentic loop with self-correction)
  - Subtitle extraction
  - Caption AI summarisation / explanation
  - Admin re-index endpoints for the vector store
"""

import logging
import os
import sys
import threading
import time
import uuid
from flask import Flask, request, jsonify, render_template, send_from_directory
from flask_cors import CORS
from flask_swagger_ui import get_swaggerui_blueprint

from config import Config
from services.ai_service import AIService
from services.db_service import DBService
from services.sql_validator import SQLValidator
from services.subtitle_service import SubtitleService
from services.vector_store import VectorStore
from services.qa_retriever import QARetriever
from services.schema_retriever import SchemaRetriever
from services.sql_agent import SQLAgent
from middleware.rate_limiter import default_limiter
from middleware.validators import validate_ask, validate_caption_ai, validate_youtube_url

# ── Logging ──────────────────────────────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
    handlers=[
        logging.StreamHandler(sys.stdout),
        logging.FileHandler("lms_ai.log"),
    ],
)
logger = logging.getLogger(__name__)

# ── App setup ─────────────────────────────────────────────────────────────────
app = Flask(__name__)
CORS(app)
app.config.from_object(Config)

# ── API Docs (Swagger UI) ─────────────────────────────────────────────────────
SWAGGER_URL = "/docs"
OPENAPI_URL = "/openapi.json"
swaggerui_blueprint = get_swaggerui_blueprint(
    SWAGGER_URL,
    OPENAPI_URL,
    config={"app_name": "LMS AI Microservice"},
)
app.register_blueprint(swaggerui_blueprint, url_prefix=SWAGGER_URL)

# ── Service singletons ────────────────────────────────────────────────────────
ai_service = AIService()
db_service = DBService()
sql_validator = SQLValidator()
subtitle_service = SubtitleService()

# ── Vector store + RAG retrievers ────────────────────────────────────────────
vector_store: VectorStore | None = None
qa_retriever: QARetriever | None = None
schema_retriever: SchemaRetriever | None = None
sql_agent: SQLAgent | None = None
_seed_status: dict = {"qa": "pending", "schema": "pending", "errors": []}

# ── In-memory feedback cache ─────────────────────────────────────────────────
# Maps request_id → {question, sql, source, status, ts}. Used by the
# /ask/feedback endpoint so a 👍 click can look up what was actually run.
# Bounded TTL keeps memory flat. Not durable — that's intentional; if a
# worker restarts, in-flight feedback windows just expire.
_feedback_cache: dict = {}
_feedback_lock = threading.Lock()


def _remember_request(question: str, sql: str, source: str, status: str) -> str:
    """Stash a successful answer so the user can later thumbs-up/down it."""
    request_id = uuid.uuid4().hex
    now = time.time()
    cutoff = now - Config.FEEDBACK_CACHE_TTL_SECONDS
    with _feedback_lock:
        # Opportunistic GC: clear anything older than TTL on each write.
        stale = [k for k, v in _feedback_cache.items() if v.get("ts", 0) < cutoff]
        for k in stale:
            _feedback_cache.pop(k, None)
        _feedback_cache[request_id] = {
            "question": question,
            "sql": sql,
            "source": source,
            "status": status,
            "ts": now,
        }
    return request_id


def _lookup_request(request_id: str) -> dict | None:
    if not request_id:
        return None
    with _feedback_lock:
        entry = _feedback_cache.get(request_id)
    if not entry:
        return None
    if time.time() - entry.get("ts", 0) > Config.FEEDBACK_CACHE_TTL_SECONDS:
        with _feedback_lock:
            _feedback_cache.pop(request_id, None)
        return None
    return entry


def _background_seed():
    """
    Seed Chroma collections off the request-handling thread so the Flask
    process can become healthy immediately, even if first-time embedding
    takes a few minutes. Idempotent — no-op once populated.
    """
    if qa_retriever is None or schema_retriever is None:
        return
    if Config.AUTO_SEED_QA:
        try:
            inserted = qa_retriever.seed_if_empty()
            _seed_status["qa"] = f"done ({inserted} inserted, total={vector_store.qa_count()})"
            logger.info("Background QA seed complete: %s", _seed_status["qa"])
        except Exception as exc:
            _seed_status["qa"] = f"failed: {exc}"
            _seed_status["errors"].append(f"qa: {exc}")
            logger.error("Background QA seed failed: %s", exc)
    else:
        _seed_status["qa"] = "disabled"

    if Config.AUTO_SEED_SCHEMA:
        try:
            inserted = schema_retriever.seed_if_empty()
            _seed_status["schema"] = (
                f"done ({inserted} inserted, total={vector_store.schema_count()})"
            )
            logger.info("Background schema seed complete: %s", _seed_status["schema"])
        except Exception as exc:
            _seed_status["schema"] = f"failed: {exc}"
            _seed_status["errors"].append(f"schema: {exc}")
            logger.error("Background schema seed failed: %s", exc)
    else:
        _seed_status["schema"] = "disabled"

    # Rehydrate learned pairs from disk if Chroma's learned collection is
    # empty (e.g. after a volume wipe). On-disk JSONL is the source of truth
    # for human-verified feedback.
    try:
        rehydrated = qa_retriever.seed_learned_from_file()
        if rehydrated:
            logger.info("Rehydrated %d learned pair(s) from disk.", rehydrated)
    except Exception as exc:
        logger.warning("Failed to rehydrate learned pairs: %s", exc)


try:
    vector_store = VectorStore(
        persist_dir=Config.CHROMA_PERSIST_DIR,
        qa_collection=Config.CHROMA_QA_COLLECTION,
        schema_collection=Config.CHROMA_SCHEMA_COLLECTION,
        embedding_provider=Config.EMBEDDING_PROVIDER,
        gemini_api_key=Config.GEMINI_API_KEY,
        gemini_embed_model=Config.EMBEDDING_MODEL_GEMINI,
        learned_collection=Config.CHROMA_LEARNED_COLLECTION,
    )
    qa_retriever = QARetriever(
        store=vector_store,
        qa_pairs_file=Config.QA_PAIRS_FILE,
        learned_qa_file=Config.LEARNED_QA_FILE,
    )
    schema_retriever = SchemaRetriever(
        store=vector_store,
        db_service=db_service,
        db_name=Config.DB_NAME,
        table_hints_file=Config.TABLE_HINTS_FILE,
    )

    sql_agent = SQLAgent(
        ai_service=ai_service,
        db_service=db_service,
        sql_validator=sql_validator,
        qa_retriever=qa_retriever,
        schema_retriever=schema_retriever,
    )

    # Fire-and-forget background seed so /health turns green immediately
    # even when the embedding backend is slow on first call.
    threading.Thread(target=_background_seed, name="vector-seed", daemon=True).start()
    logger.info("RAG agent initialised. Vector store seeding running in background.")
except Exception as exc:
    logger.error(
        "RAG agent initialisation failed; /ask will fall back to legacy single-shot pipeline. Error: %s",
        exc,
    )


def _maybe_arabic_response(question: str, answer: str, respond_arabic: bool) -> str:
    if respond_arabic and answer:
        return ai_service.translate_answer_to_arabic(question, answer)
    return answer


# ── Shorthand ─────────────────────────────────────────────────────────────────
limit = default_limiter.limit


# ── Test UI ───────────────────────────────────────────────────────────────────
@app.route("/", methods=["GET"])
def index():
    return render_template("index.html")

@app.route("/openapi.json", methods=["GET"])
def openapi_spec():
    return send_from_directory("static", "openapi.json")


# ── Health check ──────────────────────────────────────────────────────────────
@app.route("/health", methods=["GET"])
def health():
    db_ok = db_service.ping()
    vs_ok = vector_store is not None
    qa_count = vector_store.qa_count() if vs_ok else 0
    schema_count = vector_store.schema_count() if vs_ok else 0
    learned_count = vector_store.learned_count() if vs_ok else 0
    return jsonify({
        "status": "ok",
        "service": "LMS AI Microservice",
        "database": "connected" if db_ok else "unreachable",
        "gemini_configured": bool(Config.GEMINI_API_KEY),
        "vector_store": "ready" if vs_ok else "unavailable",
        "qa_pairs_indexed": qa_count,
        "learned_pairs_indexed": learned_count,
        "table_cards_indexed": schema_count,
        "agent_mode": bool(sql_agent),
        "seed_status": _seed_status,
    }), 200


# ── Admin: reindex Chroma collections ─────────────────────────────────────────
@app.route("/admin/reindex", methods=["POST"])
def admin_reindex():
    """
    Force-rebuild the vector store collections.
    Body: { "qa": true, "schema": true }
    """
    if vector_store is None or qa_retriever is None or schema_retriever is None:
        return jsonify({"error": "Vector store is not initialised."}), 503

    payload = request.get_json(silent=True) or {}
    do_qa = bool(payload.get("qa", True))
    do_schema = bool(payload.get("schema", True))

    result = {"qa_inserted": 0, "table_cards_inserted": 0}
    try:
        if do_qa:
            result["qa_inserted"] = qa_retriever.seed_if_empty(force=True)
        if do_schema:
            result["table_cards_inserted"] = schema_retriever.seed_if_empty(force=True)
        result["qa_total"] = vector_store.qa_count()
        result["schema_total"] = vector_store.schema_count()
        return jsonify({"status": "ok", **result}), 200
    except Exception as exc:
        logger.exception("Reindex failed: %s", exc)
        return jsonify({"error": f"Reindex failed: {exc}"}), 500


# ── Endpoint: Human-in-the-loop feedback ─────────────────────────────────────
@app.route("/ask/feedback", methods=["POST"])
@limit
def ask_feedback():
    """
    POST /ask/feedback
    Body: { "request_id": "...", "verdict": "good"|"bad", "note"?: "..." }

    When verdict=good and the original answer came from the LLM (not already
    a curated/learned fast-path), the (question, sql) is appended to the
    learned Chroma collection AND to data/qa_pairs_learned.jsonl so the
    next paraphrase of the question hits the trusted fast-path.
    """
    payload = request.get_json(silent=True) or {}
    request_id = (payload.get("request_id") or "").strip()
    verdict = (payload.get("verdict") or "").strip().lower()
    note = (payload.get("note") or "").strip()

    if verdict not in ("good", "bad"):
        return jsonify({"error": "verdict must be 'good' or 'bad'."}), 400
    if not request_id:
        return jsonify({"error": "request_id is required."}), 400

    entry = _lookup_request(request_id)
    if not entry:
        return jsonify({
            "ok": False,
            "learned": False,
            "reason": "request_id expired or unknown (feedback window is 1 hour).",
        }), 410

    # Always log the feedback signal — bad ones too. Acts as a review queue.
    logger.info(
        "Feedback: verdict=%s | source=%s | question=%r | sql=%r | note=%r",
        verdict, entry.get("source"), entry["question"][:120], entry["sql"][:200], note[:200],
    )

    if verdict == "bad":
        # Bad answers are NOT added to the learned bank. We just record the
        # signal so a human can review later (logs + optional JSONL queue).
        _append_bad_feedback(entry, note)
        return jsonify({"ok": True, "learned": False, "reason": "noted as bad"}), 200

    # verdict == "good": only learn from answers the LLM had to generate.
    # If the answer already came from curated/learned fast-path, there's
    # nothing new to learn.
    source = entry.get("source") or ""
    if source in ("curated_trusted", "learned_trusted", "deterministic"):
        return jsonify({
            "ok": True,
            "learned": False,
            "reason": f"answer already came from a {source} fast-path, no new learning needed.",
        }), 200

    if qa_retriever is None:
        return jsonify({"error": "QA retriever not initialised."}), 503

    try:
        outcome = qa_retriever.add_learned_pair(
            question=entry["question"],
            sql=entry["sql"],
            source_request_id=request_id,
            verdict_note=note,
            original_source=source or "llm",
        )
        return jsonify(outcome), 200
    except Exception as exc:
        logger.exception("Adding learned pair failed: %s", exc)
        return jsonify({"error": f"Could not save feedback: {exc}"}), 500


def _append_bad_feedback(entry: dict, note: str):
    """Persist 👎 feedback to data/bad_feedback.jsonl for offline review."""
    try:
        path = os.path.join(os.path.dirname(Config.LEARNED_QA_FILE), "bad_feedback.jsonl")
        os.makedirs(os.path.dirname(path), exist_ok=True)
        import json as _json
        with open(path, "a", encoding="utf-8") as fh:
            fh.write(_json.dumps({
                "question": entry.get("question"),
                "sql": entry.get("sql"),
                "source": entry.get("source"),
                "note": note,
                "ts": int(time.time()),
            }, ensure_ascii=False) + "\n")
    except Exception as exc:
        logger.warning("Could not write bad feedback log: %s", exc)


# ── Admin: review / curate the learned bank ──────────────────────────────────
@app.route("/admin/learned", methods=["GET"])
def admin_list_learned():
    """List all learned pairs currently in Chroma."""
    if vector_store is None:
        return jsonify({"error": "Vector store unavailable."}), 503
    items = vector_store.list_learned(limit=500)
    return jsonify({"count": len(items), "items": items}), 200


@app.route("/admin/learned/<pair_id>", methods=["DELETE"])
def admin_delete_learned(pair_id: str):
    """Remove a single learned pair (e.g. user marked good by mistake)."""
    if vector_store is None:
        return jsonify({"error": "Vector store unavailable."}), 503
    vector_store.delete_learned([pair_id])
    return jsonify({"ok": True, "deleted": pair_id,
                    "remaining": vector_store.learned_count()}), 200


# ── Endpoint A: Natural Language → SQL → Human Answer ────────────────────────
@app.route("/ask", methods=["POST"])
@limit
def ask():
    """
    POST /ask
    Body:    { "question": "...", "respond_arabic": false, "debug": false }
    Returns: { "answer": "...", "sql"?: "...", "trace"?: {...} }
    """
    payload, err = validate_ask(request.get_json(silent=True))
    if err:
        return jsonify({"error": err[0]}), err[1]

    question = payload["question"]
    respond_arabic = bool(payload.get("respond_arabic"))
    raw_body = request.get_json(silent=True) or {}
    debug = bool(raw_body.get("debug", False))
    logger.info(
        "POST /ask | question=%r | respond_arabic=%s | debug=%s",
        question, respond_arabic, debug,
    )

    try:
        # Path A: full agentic RAG pipeline (only once collections are populated).
        if sql_agent is not None and vector_store is not None and vector_store.qa_count() > 0:
            return _handle_ask_with_agent(question, respond_arabic, debug)

        # Path B: legacy single-shot fallback.
        # Hit while the vector store is still seeding in the background, or
        # if RAG initialisation failed altogether. Service stays usable.
        logger.info(
            "Using legacy pipeline for /ask (vector store not yet ready: qa=%d, schema=%d)",
            vector_store.qa_count() if vector_store else -1,
            vector_store.schema_count() if vector_store else -1,
        )
        return _handle_ask_legacy(question, respond_arabic)

    except Exception as exc:
        logger.exception("Unexpected error in /ask: %s", exc)
        return jsonify({"error": "Internal server error. Please try again later."}), 500


def _handle_ask_with_agent(question: str, respond_arabic: bool, debug: bool):
    """Run the question through the SQLAgent (RAG + critique loop)."""
    result = sql_agent.answer(question)
    answer = _maybe_arabic_response(question, result.answer, respond_arabic)

    # Only cache for feedback when we actually produced a successful answer.
    request_id = ""
    if result.status == "ok" and result.sql:
        request_id = _remember_request(
            question=question,
            sql=result.sql,
            source=result.source,
            status=result.status,
        )

    body = {
        "answer": answer,
        "status": result.status,
        # UI-driving fields (always present so the client can rely on them).
        "presentation": getattr(result, "presentation", "text"),
        "structured_data": getattr(result, "structured_data", None),
        "intent": getattr(result, "intent", "general"),
        "module": getattr(result, "module", "general"),
        "module_label": getattr(result, "module_label", "General"),
        "suggestions": getattr(result, "suggestions", []) or [],
        "source": getattr(result, "source", ""),
    }
    if result.sql:
        body["sql"] = result.sql
    if request_id:
        body["request_id"] = request_id
    if debug and result.trace is not None:
        body["trace"] = result.trace.to_dict()
    return jsonify(body), 200


def _handle_ask_legacy(question: str, respond_arabic: bool):
    """Original single-shot pipeline. Fallback when the agent is unavailable."""
    if ai_service.is_app_flow_question(question):
        answer = ai_service.answer_from_manual(question)
        return jsonify({"answer": _maybe_arabic_response(question, answer, respond_arabic)}), 200

    live_schema, schema_error = db_service.get_schema_summary(question=question)
    if live_schema:
        ai_service.set_runtime_schema(live_schema)
    elif schema_error:
        logger.warning("Using static schema fallback for SQL generation: %s", schema_error)

    sql = ai_service.generate_sql(question)
    if not sql:
        msg = ai_service.append_capability_suggestions(
            "I could not map that question to a safe database query yet.",
            question,
        )
        return jsonify({"answer": _maybe_arabic_response(question, msg, respond_arabic)}), 200

    is_valid, error_msg = sql_validator.validate(sql)
    if not is_valid:
        repaired_sql = ai_service.repair_sql(question, sql, error_msg)
        if repaired_sql:
            sql = repaired_sql
            is_valid, error_msg = sql_validator.validate(sql)
        if not is_valid:
            msg = ai_service.append_capability_suggestions(
                "I could not produce a valid query for that request.",
                question,
            )
            return jsonify({"answer": _maybe_arabic_response(question, msg, respond_arabic)}), 200

    sql = sql_validator.sanitize_limit(sql, Config.DB_MAX_ROWS)
    rows, columns, db_error = db_service.execute(sql)
    if db_error:
        msg = ai_service.append_capability_suggestions(
            "I ran into a database error while fetching that information.",
            question,
        )
        return jsonify({"answer": _maybe_arabic_response(question, msg, respond_arabic)}), 200

    if not rows:
        msg = ai_service.append_capability_suggestions(
            "No records were found for your query.",
            question,
        )
        return jsonify({"answer": _maybe_arabic_response(question, msg, respond_arabic)}), 200

    if ai_service.should_use_structured_answer(question):
        answer = ai_service.structured_answer_from_rows(question, columns, rows)
    else:
        answer = ai_service.format_results(question, columns, rows)
    return jsonify({"answer": _maybe_arabic_response(question, answer, respond_arabic)}), 200


# ── Endpoint B: Subtitle Extraction ──────────────────────────────────────────
@app.route("/extract-subtitles", methods=["POST"])
@limit
def extract_subtitles():
    """
    POST /extract-subtitles
    YouTube: JSON  { "type": "youtube", "url": "https://youtu.be/..." }
    File:    multipart/form-data with 'file' field
    Returns: { "subtitles": "full extracted text" }
    """
    if request.is_json:
        data = request.get_json(silent=True) or {}
        if data.get("type") == "youtube":
            url = data.get("url", "").strip()
            url_error = validate_youtube_url(url)
            if url_error:
                return jsonify({"error": url_error}), 400

            logger.info("POST /extract-subtitles | youtube url=%s", url)
            subtitles, segments, source, error = subtitle_service.extract_from_youtube(url)
            if error:
                return jsonify({"error": error}), 422
            return jsonify({
                "subtitles": subtitles,
                "segments": segments,
                "segment_count": len(segments),
                "source": source,
            }), 200

        return jsonify({"error": "JSON body must include 'type': 'youtube' and 'url'."}), 400

    if "file" not in request.files:
        return jsonify({
            "error": "No file found. Send 'file' via multipart/form-data, "
                     "or JSON with type='youtube' and 'url'."
        }), 400

    video_file = request.files["file"]
    if not video_file.filename:
        return jsonify({"error": "Uploaded file has no filename."}), 400

    logger.info("POST /extract-subtitles | file=%s", video_file.filename)
    subtitles, segments, source, error = subtitle_service.extract_from_file(video_file)
    if error:
        return jsonify({"error": error}), 422

    return jsonify({
        "subtitles": subtitles,
        "segments": segments,
        "segment_count": len(segments),
        "source": source,
    }), 200


# ── Endpoint C: Caption AI (Summarize / Explain) ─────────────────────────────
@app.route("/caption-ai", methods=["POST"])
@limit
def caption_ai():
    """
    POST /caption-ai
    Body:    { "action": "summarize"|"explain", "text": "...", "question": "(optional)" }
    Returns: { "answer": "AI response" }
    """
    payload, err = validate_caption_ai(request.get_json(silent=True))
    if err:
        return jsonify({"error": err[0]}), err[1]

    action   = payload["action"]
    text     = payload["text"]
    question = payload["question"]

    logger.info("POST /caption-ai | action=%s | text_len=%d", action, len(text))

    try:
        if action == "summarize":
            answer = ai_service.summarize_captions(text)
        else:
            answer = ai_service.explain_captions(text, question)
        return jsonify({"answer": answer}), 200
    except Exception as exc:
        logger.exception("Unexpected error in /caption-ai: %s", exc)
        return jsonify({"error": "Internal server error. Please try again later."}), 500


# ── Error handlers ────────────────────────────────────────────────────────────
@app.errorhandler(404)
def not_found(_):
    return jsonify({"error": "Endpoint not found."}), 404

@app.errorhandler(405)
def method_not_allowed(_):
    return jsonify({"error": "Method not allowed."}), 405

@app.errorhandler(413)
def request_too_large(_):
    return jsonify({"error": "File too large. Maximum allowed size is 200 MB."}), 413

@app.errorhandler(429)
def too_many_requests(_):
    return jsonify({"error": "Too many requests. Please slow down."}), 429


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=False)
