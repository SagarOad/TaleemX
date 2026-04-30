"""
LMS AI Microservice — Flask Entry Point
Handles: NL→SQL querying, subtitle extraction, caption AI
"""

import logging
import sys
from flask import Flask, request, jsonify, render_template, send_from_directory
from flask_cors import CORS
from flask_swagger_ui import get_swaggerui_blueprint

from config import Config
from services.ai_service import AIService
from services.db_service import DBService
from services.sql_validator import SQLValidator
from services.subtitle_service import SubtitleService
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
    return jsonify({
        "status": "ok",
        "service": "LMS AI Microservice",
        "database": "connected" if db_ok else "unreachable",
        "gemini_configured": bool(Config.GEMINI_API_KEY),
    }), 200


# ── Endpoint A: Natural Language → SQL → Human Answer ────────────────────────
@app.route("/ask", methods=["POST"])
@limit
def ask():
    """
    POST /ask
    Body:    { "question": "How many students are enrolled?" }
    Returns: { "answer": "There are 342 students currently enrolled." }
    """
    payload, err = validate_ask(request.get_json(silent=True))
    if err:
        return jsonify({"error": err[0]}), err[1]

    question = payload["question"]
    logger.info("POST /ask | question=%r", question)

    try:
        # Route UI navigation / how-to questions to app manual guidance.
        if ai_service.is_app_flow_question(question):
            answer = ai_service.answer_from_manual(question)
            return jsonify({"answer": answer}), 200

        # Keep the LLM grounded in the actual connected database schema.
        live_schema, schema_error = db_service.get_schema_summary(question=question)
        if live_schema:
            ai_service.set_runtime_schema(live_schema)
        elif schema_error:
            logger.warning("Using static schema fallback for SQL generation: %s", schema_error)

        # Step 1: Generate SQL via Gemini
        sql = ai_service.generate_sql(question)
        if not sql:
            return jsonify({"answer": "I could not generate a valid query for that question."}), 200

        logger.info("Generated SQL: %s", sql)

        # Step 2: Validate SQL (SELECT only, block dangerous statements)
        is_valid, error_msg = sql_validator.validate(sql)
        if not is_valid:
            logger.warning("SQL validation failed (first pass): %s", error_msg)
            repaired_sql = ai_service.repair_sql(question, sql, error_msg)
            if repaired_sql:
                logger.info("Trying repaired SQL: %s", repaired_sql)
                sql = repaired_sql
                is_valid, error_msg = sql_validator.validate(sql)
            if not is_valid:
                logger.warning("SQL validation failed (final): %s", error_msg)
                return jsonify({"answer": "I could not produce a valid query for that request. Please rephrase and try again."}), 200

        # Step 2b: Enforce row cap
        sql = sql_validator.sanitize_limit(sql, Config.DB_MAX_ROWS)

        # Step 3: Execute against read-only DB
        rows, columns, db_error = db_service.execute(sql)
        if db_error:
            logger.error("DB execution error (first pass): %s", db_error)
            repaired_sql = ai_service.repair_sql(question, sql, db_error)
            if repaired_sql:
                logger.info("Trying DB-error repaired SQL: %s", repaired_sql)
                is_valid, error_msg = sql_validator.validate(repaired_sql)
                if is_valid:
                    repaired_sql = sql_validator.sanitize_limit(repaired_sql, Config.DB_MAX_ROWS)
                    rows, columns, db_error = db_service.execute(repaired_sql)
                    sql = repaired_sql
            if db_error:
                logger.error("DB execution error (final): %s", db_error)
                return jsonify({"answer": "I ran into a database error while fetching that information."}), 200

        if not rows:
            retry_sql = ai_service.retry_sql_for_empty_results(question, sql)
            if retry_sql:
                logger.info("Trying empty-result retry SQL: %s", retry_sql)
                is_valid, error_msg = sql_validator.validate(retry_sql)
                if is_valid:
                    retry_sql = sql_validator.sanitize_limit(retry_sql, Config.DB_MAX_ROWS)
                    retry_rows, retry_columns, retry_db_error = db_service.execute(retry_sql)
                    if not retry_db_error and retry_rows:
                        rows, columns = retry_rows, retry_columns
                        sql = retry_sql
            if not rows:
                return jsonify({"answer": "No records were found for your query."}), 200

        # Step 4: For list/detail style requests, return deterministic full data.
        if ai_service.should_use_structured_answer(question):
            answer = ai_service.structured_answer_from_rows(question, columns, rows)
        else:
            # Otherwise, format via LLM for concise narrative.
            answer = ai_service.format_results(question, columns, rows)
        return jsonify({"answer": answer}), 200

    except Exception as exc:
        logger.exception("Unexpected error in /ask: %s", exc)
        return jsonify({"error": "Internal server error. Please try again later."}), 500


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
    # YouTube path (JSON body)
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

    # File upload path (multipart)
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
