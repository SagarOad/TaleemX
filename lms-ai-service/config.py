"""
config.py — All environment variables and app-level constants.
Never hardcode secrets here. Use a .env file or docker-compose environment section.
"""

import os
from dotenv import load_dotenv

load_dotenv()


class Config:
    # ── Gemini ──────────────────────────────────────────────────────────────
    GEMINI_API_KEY: str = os.getenv("GEMINI_API_KEY", "")
    # Flash is the right default for this workload: ~5x faster than Pro, and
    # plenty accurate for SQL generation when grounded with few-shot examples
    # + retrieved schema. Pro is kept as a fallback for tricky cases.
    GEMINI_MODEL: str = os.getenv("GEMINI_MODEL", "gemini-2.5-flash")
    GEMINI_FALLBACK_MODELS: list[str] = [
        m.strip()
        for m in os.getenv("GEMINI_FALLBACK_MODELS", "gemini-2.5-pro,gemini-2.5-flash-lite")
        .split(",")
        if m.strip()
    ]
    GEMINI_MAX_RETRIES: int = int(os.getenv("GEMINI_MAX_RETRIES", 1))
    # Pacing between Gemini calls. 6.5s = free-tier safe; 0.5s is comfortable
    # for the paid tier (1500 RPM on gemini-2.5-flash). Override via env if
    # you are on the free tier.
    GEMINI_MIN_CALL_INTERVAL_SECONDS: float = float(
        os.getenv("GEMINI_MIN_CALL_INTERVAL_SECONDS", 0.5)
    )
    GEMINI_429_COOLDOWN_SECONDS: float = float(
        os.getenv("GEMINI_429_COOLDOWN_SECONDS", 20)
    )
    # Per-call HTTP timeout for a single Gemini request. Big enough for
    # multi-thousand-token RAG prompts, small enough that a stalled model
    # doesn't block the whole pipeline.
    GEMINI_REQUEST_TIMEOUT_SECONDS: int = int(
        os.getenv("GEMINI_REQUEST_TIMEOUT_SECONDS", 25)
    )
    # Fast model used for short, latency-sensitive calls (plan, critic, format).
    GEMINI_FAST_MODEL: str = os.getenv("GEMINI_FAST_MODEL", "gemini-2.5-flash")
    GROQ_API_KEY: str = os.getenv("GROQ_API_KEY", "")
    # llama-3.3-70b-versatile has a 128k context window, so it can handle the
    # full RAG prompt as a fallback. llama-3.1-8b-instant only takes ~8k.
    GROQ_MODEL: str = os.getenv("GROQ_MODEL", "llama-3.3-70b-versatile")

    # ── MySQL ────────────────────────────────────────────────────────────────
    DB_HOST: str = os.getenv("DB_HOST", "localhost")
    DB_PORT: int = int(os.getenv("DB_PORT", 3306))
    DB_USER: str = os.getenv("DB_USER", "lms_readonly")
    DB_PASSWORD: str = os.getenv("DB_PASSWORD", "")
    DB_NAME: str = os.getenv("DB_NAME", "lms_db")
    DB_CONNECT_TIMEOUT: int = int(os.getenv("DB_CONNECT_TIMEOUT", 10))
    DB_QUERY_TIMEOUT: int = int(os.getenv("DB_QUERY_TIMEOUT", 15))
    DB_MAX_ROWS: int = int(os.getenv("DB_MAX_ROWS", 50))

    # ── File uploads ─────────────────────────────────────────────────────────
    MAX_CONTENT_LENGTH: int = 200 * 1024 * 1024  # 200 MB
    UPLOAD_FOLDER: str = os.getenv("UPLOAD_FOLDER", "/tmp/lms_uploads")
    ALLOWED_VIDEO_EXTENSIONS: set = {"mp4", "mkv", "avi", "mov", "webm", "mp3", "wav", "m4a"}

    # ── Whisper ──────────────────────────────────────────────────────────────
    WHISPER_MODEL: str = os.getenv("WHISPER_MODEL", "base")   # tiny/base/small/medium/large

    # ── RapidAPI (YouTube transcripts/captions) ─────────────────────────────
    RAPIDAPI_KEY: str = os.getenv("RAPIDAPI_KEY", "")
    RAPIDAPI_HOST: str = os.getenv(
        "RAPIDAPI_HOST",
        "youtube-captions-transcript-subtitles-video-combiner.p.rapidapi.com",
    )
    RAPIDAPI_URL_TEMPLATE: str = os.getenv(
        "RAPIDAPI_URL_TEMPLATE",
        "https://youtube-captions-transcript-subtitles-video-combiner.p.rapidapi.com/download-webvtt/{video_id}",
    )
    RAPIDAPI_RESPONSE_MODE: str = os.getenv("RAPIDAPI_RESPONSE_MODE", "default")
    RAPIDAPI_TIMEOUT_SECONDS: int = int(os.getenv("RAPIDAPI_TIMEOUT_SECONDS", 30))
    RAPIDAPI_YT_LANG: str = os.getenv("RAPIDAPI_YT_LANG", "en")
    RAPIDAPI_YT_CHUNK_SIZE: int = int(os.getenv("RAPIDAPI_YT_CHUNK_SIZE", 500))
    RAPIDAPI_YT_TEXT_MODE: bool = os.getenv("RAPIDAPI_YT_TEXT_MODE", "false").lower() == "true"

    # ── Vector store / RAG ───────────────────────────────────────────────────
    CHROMA_PERSIST_DIR: str = os.getenv("CHROMA_PERSIST_DIR", "/app/data/chroma")
    CHROMA_QA_COLLECTION: str = os.getenv("CHROMA_QA_COLLECTION", "lms_qa_pairs")
    CHROMA_SCHEMA_COLLECTION: str = os.getenv("CHROMA_SCHEMA_COLLECTION", "lms_table_cards")
    # Human-in-the-loop learned pairs: same shape as curated, but kept in a
    # separate collection so curated never gets contaminated. The retriever
    # queries both and learned matches use a STRICTER trust threshold.
    CHROMA_LEARNED_COLLECTION: str = os.getenv("CHROMA_LEARNED_COLLECTION", "lms_qa_learned")

    # Embedding backend: 'gemini' (uses GEMINI_API_KEY + text-embedding-004)
    # or 'local' (ONNX all-MiniLM-L6-v2 via chromadb DefaultEmbeddingFunction).
    EMBEDDING_PROVIDER: str = os.getenv("EMBEDDING_PROVIDER", "gemini").lower()
    EMBEDDING_MODEL_GEMINI: str = os.getenv("EMBEDDING_MODEL_GEMINI", "text-embedding-004")

    # Retrieval knobs.
    QA_TOP_K: int = int(os.getenv("QA_TOP_K", 8))
    SCHEMA_TOP_K: int = int(os.getenv("SCHEMA_TOP_K", 8))
    # If the nearest curated QA example is within this cosine distance of the
    # user's question, we use its SQL directly without calling the LLM. Cosine
    # distance 0.15 ≈ similarity 0.85 (close paraphrase). Lower = stricter.
    QA_TRUST_MATCH_DISTANCE: float = float(
        os.getenv("QA_TRUST_MATCH_DISTANCE", 0.18)
    )
    # Learned pairs use a stricter (smaller) distance to fast-path. Even a
    # user-marked-good answer might have edge cases the user didn't notice,
    # so we only auto-execute the learned SQL on near-identical paraphrases.
    QA_LEARNED_TRUST_DISTANCE: float = float(
        os.getenv("QA_LEARNED_TRUST_DISTANCE", 0.10)
    )

    # Bootstrap data files (curated gold examples + table hints).
    QA_PAIRS_FILE: str = os.getenv("QA_PAIRS_FILE", "/app/data/qa_pairs.jsonl")
    # Learned pairs are appended here when users click 👍. Persisted on disk
    # so they survive Chroma volume wipes and can be reviewed by an admin.
    LEARNED_QA_FILE: str = os.getenv("LEARNED_QA_FILE", "/app/data/qa_pairs_learned.jsonl")
    TABLE_HINTS_FILE: str = os.getenv("TABLE_HINTS_FILE", "/app/data/table_purpose_hints.json")

    # Auto-bootstrap collections on service startup when they are empty.
    AUTO_SEED_QA: bool = os.getenv("AUTO_SEED_QA", "true").lower() == "true"
    AUTO_SEED_SCHEMA: bool = os.getenv("AUTO_SEED_SCHEMA", "true").lower() == "true"

    # How long the /ask request_id → (question, sql, ...) cache is kept in
    # memory so the user can click 👍/👎 after seeing the answer.
    FEEDBACK_CACHE_TTL_SECONDS: int = int(
        os.getenv("FEEDBACK_CACHE_TTL_SECONDS", 3600)
    )

    # ── Agent loop ──────────────────────────────────────────────────────────
    AGENT_MAX_ITERATIONS: int = int(os.getenv("AGENT_MAX_ITERATIONS", 2))
    # The plan step costs one LLM call (~3-6s) and helps the SQL model only
    # marginally when few-shot examples are strong. Off by default for latency.
    AGENT_ENABLE_PLAN: bool = os.getenv("AGENT_ENABLE_PLAN", "false").lower() == "true"
    # The critic step costs one LLM call per iteration. Off by default so the
    # default path is single-call (retrieve → generate → execute → respond).
    # Turn on if you want the agent to validate result quality before answering.
    AGENT_ENABLE_CRITIC: bool = os.getenv("AGENT_ENABLE_CRITIC", "false").lower() == "true"
    AGENT_PLAN_TEMPERATURE: float = float(os.getenv("AGENT_PLAN_TEMPERATURE", 0.1))
    AGENT_SQL_TEMPERATURE: float = float(os.getenv("AGENT_SQL_TEMPERATURE", 0.0))
    AGENT_CRITIC_TEMPERATURE: float = float(os.getenv("AGENT_CRITIC_TEMPERATURE", 0.0))

    # ── LMS DB Schema (compact fallback) ─────────────────────────────────────
    # This is the *static* fallback used when the live schema is unreachable.
    # In normal operation, the service introspects information_schema and uses
    # the schema retriever (table cards in Chroma) to pick relevant tables.
    DB_SCHEMA: str = """
Database: lms_db

Tables and columns:

users (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    role ENUM('student','teacher','admin'),
    created_at DATETIME,
    is_active TINYINT(1)
)

courses (
    id INT PRIMARY KEY,
    title VARCHAR(255),
    description TEXT,
    teacher_id INT REFERENCES users(id),
    category VARCHAR(100),
    status ENUM('draft','published','archived'),
    created_at DATETIME,
    updated_at DATETIME
)

enrollments (
    id INT PRIMARY KEY,
    student_id INT REFERENCES users(id),
    course_id INT REFERENCES courses(id),
    enrolled_at DATETIME,
    status ENUM('active','completed','dropped'),
    progress_percent DECIMAL(5,2)
)

lessons (
    id INT PRIMARY KEY,
    course_id INT REFERENCES courses(id),
    title VARCHAR(255),
    content TEXT,
    video_url VARCHAR(500),
    subtitles TEXT,
    order_index INT,
    duration_seconds INT,
    created_at DATETIME
)

assignments (
    id INT PRIMARY KEY,
    course_id INT REFERENCES courses(id),
    lesson_id INT REFERENCES lessons(id),
    title VARCHAR(255),
    description TEXT,
    due_date DATETIME,
    max_score DECIMAL(8,2)
)

submissions (
    id INT PRIMARY KEY,
    assignment_id INT REFERENCES assignments(id),
    student_id INT REFERENCES users(id),
    submitted_at DATETIME,
    score DECIMAL(8,2),
    feedback TEXT,
    status ENUM('pending','graded','late')
)

quiz_results (
    id INT PRIMARY KEY,
    student_id INT REFERENCES users(id),
    course_id INT REFERENCES courses(id),
    quiz_name VARCHAR(255),
    score DECIMAL(8,2),
    max_score DECIMAL(8,2),
    taken_at DATETIME
)

announcements (
    id INT PRIMARY KEY,
    course_id INT REFERENCES courses(id),
    author_id INT REFERENCES users(id),
    title VARCHAR(255),
    body TEXT,
    created_at DATETIME
)

-- Smart School behaviour / discipline (maps to student_behaviour, student_incidents)
student_behaviour (
    id INT PRIMARY KEY,
    point INT,
    description TEXT,
    title VARCHAR(255),
    created_at DATETIME
)

student_incidents (
    id INT PRIMARY KEY,
    session_id INT,
    student_id INT REFERENCES students(id),
    incident_id INT REFERENCES student_behaviour(id),
    assign_by INT,
    created_at DATETIME
)

student_incident_comments (
    id INT PRIMARY KEY,
    student_incident_id INT REFERENCES student_incidents(id),
    comment TEXT,
    type VARCHAR(50),
    staff_id INT,
    student_id INT,
    created_date DATETIME
)

Relationships:
- courses.teacher_id → users.id
- enrollments.student_id → users.id
- enrollments.course_id → courses.id
- lessons.course_id → courses.id
- assignments.course_id → courses.id
- assignments.lesson_id → lessons.id
- submissions.assignment_id → assignments.id
- submissions.student_id → users.id
- quiz_results.student_id → users.id
- quiz_results.course_id → courses.id
- announcements.course_id → courses.id
- announcements.author_id → users.id
- student_incidents.student_id → students.id
- student_incidents.incident_id → student_behaviour.id
- student_incident_comments.student_incident_id → student_incidents.id
"""
