# ── Build stage ────────────────────────────────────────────────────────────────
FROM python:3.11-slim AS builder

WORKDIR /build

# Install build tools
RUN apt-get update && apt-get install -y --no-install-recommends \
    gcc \
    default-libmysqlclient-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --upgrade pip && \
    pip install --prefix=/install --no-cache-dir -r requirements.txt


# ── Runtime stage ──────────────────────────────────────────────────────────────
FROM python:3.11-slim

LABEL maintainer="LMS Team"
LABEL description="LMS AI Microservice — NL→SQL, subtitle extraction, caption AI"

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

# Create directories
RUN mkdir -p /tmp/lms_uploads /app/logs /home/appuser/.cache

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
    UPLOAD_FOLDER=/tmp/lms_uploads \
    WHISPER_MODEL=base \
    GEMINI_MODEL=gemini-2.5-pro

EXPOSE 5000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=15s --retries=3 \
    CMD curl -f http://localhost:5000/health || exit 1

# Production server. Default to a single worker so Gemini rate pacing
# remains predictable on free-tier quotas (can be overridden via env).
CMD ["sh", "-c", "gunicorn --bind 0.0.0.0:5000 --workers ${GUNICORN_WORKERS:-1} --timeout 120 --access-logfile - --error-logfile - --log-level info app:app"]
