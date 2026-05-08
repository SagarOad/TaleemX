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
    GEMINI_MODEL: str = os.getenv("GEMINI_MODEL", "gemini-2.5-pro")
    GEMINI_FALLBACK_MODELS: list[str] = [
        m.strip()
        for m in os.getenv("GEMINI_FALLBACK_MODELS", "gemini-2.5-flash")
        .split(",")
        if m.strip()
    ]
    GEMINI_MAX_RETRIES: int = int(os.getenv("GEMINI_MAX_RETRIES", 1))
    GEMINI_MIN_CALL_INTERVAL_SECONDS: float = float(
        os.getenv("GEMINI_MIN_CALL_INTERVAL_SECONDS", 6.5)
    )
    GEMINI_429_COOLDOWN_SECONDS: float = float(
        os.getenv("GEMINI_429_COOLDOWN_SECONDS", 20)
    )
    GROQ_API_KEY: str = os.getenv("GROQ_API_KEY", "")
    GROQ_MODEL: str = os.getenv("GROQ_MODEL", "llama-3.1-8b-instant")

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

    # ── LMS DB Schema ────────────────────────────────────────────────────────
    # Predefined schema passed to Gemini for SQL generation.
    # Edit this to match your actual LMS schema exactly.
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
