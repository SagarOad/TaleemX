# ── Build stage ────────────────────────────────────────────────────────────────
FROM python:3.11-slim AS builder

WORKDIR /build

# Build tools for native wheels (pymysql cryptography, chromadb, onnxruntime).
RUN apt-get update && apt-get install -y --no-install-recommends \
    gcc \
    g++ \
    default-libmysqlclient-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --upgrade pip && \
    pip install --prefix=/install --no-cache-dir -r requirements.txt


# ── Runtime stage ──────────────────────────────────────────────────────────────
FROM python:3.11-slim

LABEL maintainer="LMS Team"
LABEL description="LMS AI Microservice — RAG-grounded NL→SQL, subtitles, caption AI"

WORKDIR /app

# Runtime system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    # MySQL client libs (required by pymysql native extensions)
    default-libmysqlclient-dev \
    # ffmpeg — required by Whisper for audio decoding
    ffmpeg \
    # Curl for health-check probes
    curl \
    && rm -rf /var/lib/apt/lists/*

# Copy installed Python packages from builder
COPY --from=builder /install /usr/local

# Copy application source
COPY . .

# Create writable directories:
#  - upload cache  (Whisper temp files)
#  - logs          (gunicorn / app logs)
#  - cache         (HF / chroma model downloads)
#  - data/chroma   (persistent vector store)
RUN mkdir -p /tmp/lms_uploads /app/logs /app/data/chroma /home/appuser/.cache

# Non-root user for security
RUN useradd --create-home --home-dir /home/appuser --shell /bin/false appuser && \
    chown -R appuser:appuser /app /tmp/lms_uploads /home/appuser
USER appuser

# Environment defaults (override in docker-compose or k8s)
ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    FLASK_ENV=production \
    GUNICORN_WORKERS=1 \
    HOME=/home/appuser \
    XDG_CACHE_HOME=/home/appuser/.cache \
    HF_HOME=/home/appuser/.cache/hf \
    CHROMA_PERSIST_DIR=/app/data/chroma \
    QA_PAIRS_FILE=/app/data/qa_pairs.jsonl \
    TABLE_HINTS_FILE=/app/data/table_purpose_hints.json \
    UPLOAD_FOLDER=/tmp/lms_uploads \
    WHISPER_MODEL=base \
    GEMINI_MODEL=gemini-2.5-flash \
    EMBEDDING_PROVIDER=gemini \
    ANONYMIZED_TELEMETRY=False \
    CHROMA_TELEMETRY_IMPL=none

EXPOSE 5000

# Health check. start_period is generous because first-boot seeding of
# the Chroma collections can take a minute or two; the app reports healthy
# as soon as Flask is up — seeding runs in a background thread.
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=5 \
    CMD curl -f http://localhost:5000/health || exit 1

# Production server. Default to a single worker so Gemini rate pacing and
# the in-process Chroma client remain consistent (override via env).
CMD ["sh", "-c", "gunicorn --bind 0.0.0.0:5000 --workers ${GUNICORN_WORKERS:-1} --timeout 180 --access-logfile - --error-logfile - --log-level info app:app"]
