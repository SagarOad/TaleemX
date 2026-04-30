#!/usr/bin/env python3
"""
startup_check.py
Pre-flight checks before running the service in production.
Run manually or add as a Docker CMD wrapper:

    python startup_check.py && gunicorn ...

Exit codes:
    0 — all checks passed
    1 — one or more critical checks failed
"""

import os
import sys
import logging

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
)
log = logging.getLogger("startup")

ERRORS = []
WARNINGS = []


def check(label: str, condition: bool, message: str, critical: bool = True):
    if condition:
        log.info("  ✓  %s", label)
    else:
        if critical:
            log.error("  ✗  %s — %s", label, message)
            ERRORS.append(label)
        else:
            log.warning("  ⚠  %s — %s", label, message)
            WARNINGS.append(label)


# ── 1. Environment variables ──────────────────────────────────────────────────
log.info("── Environment Variables ──────────────────────────────")

check("GEMINI_API_KEY set",
      bool(os.getenv("GEMINI_API_KEY")),
      "Set GEMINI_API_KEY in your .env file.")

check("RAPIDAPI_KEY set",
      bool(os.getenv("RAPIDAPI_KEY")),
      "Set RAPIDAPI_KEY in your .env file for YouTube transcript extraction.")

check("DB_PASSWORD set",
      bool(os.getenv("DB_PASSWORD")),
      "Set DB_PASSWORD in your .env file.")

check("DB_HOST set",
      bool(os.getenv("DB_HOST")),
      "Set DB_HOST in your .env file.")

check("DB_NAME set",
      bool(os.getenv("DB_NAME")),
      "Set DB_NAME in your .env file.")

whisper_model = os.getenv("WHISPER_MODEL", "base")
check("WHISPER_MODEL valid",
      whisper_model in {"tiny", "base", "small", "medium", "large"},
      f"WHISPER_MODEL='{whisper_model}' is not a recognised size.",
      critical=False)


# ── 2. Python dependencies ────────────────────────────────────────────────────
log.info("── Python Dependencies ────────────────────────────────")

def try_import(module: str, package: str = None, critical: bool = True):
    try:
        __import__(module)
        check(package or module, True, "")
    except ImportError:
        check(package or module, False,
              f"Run: pip install {package or module}", critical=critical)

try_import("flask", "flask")
try_import("flask_cors", "flask-cors")
try_import("pymysql", "pymysql")
try_import("dbutils", "dbutils")
try_import("requests", "requests")
try_import("dotenv", "python-dotenv")
try_import("whisper", "openai-whisper", critical=False)   # optional


# ── 3. System tools ───────────────────────────────────────────────────────────
log.info("── System Tools ───────────────────────────────────────")

import shutil
check("ffmpeg binary present",
      shutil.which("ffmpeg") is not None,
      "Install ffmpeg (required for Whisper). On Debian: apt-get install -y ffmpeg",
      critical=False)


# ── 4. Database connectivity ──────────────────────────────────────────────────
log.info("── Database Connectivity ──────────────────────────────")

try:
    import pymysql
    conn = pymysql.connect(
        host=os.getenv("DB_HOST", "localhost"),
        port=int(os.getenv("DB_PORT", 3306)),
        user=os.getenv("DB_USER", "lms_readonly"),
        password=os.getenv("DB_PASSWORD", ""),
        db=os.getenv("DB_NAME", "lms_db"),
        connect_timeout=5,
    )
    conn.close()
    check("MySQL connection", True, "")
except Exception as exc:
    check("MySQL connection", False, str(exc))


# ── 5. Gemini API reachability ────────────────────────────────────────────────
log.info("── Gemini API ─────────────────────────────────────────")

try:
    import requests as req
    api_key = os.getenv("GEMINI_API_KEY", "")
    if api_key:
        model = os.getenv("GEMINI_MODEL", "gemini-2.5-pro")
        url = f"https://generativelanguage.googleapis.com/v1beta/models/{model}?key={api_key}"
        r = req.get(url, timeout=8)
        check("Gemini API reachable", r.status_code == 200,
              f"HTTP {r.status_code} — check your API key and model name.")
    else:
        check("Gemini API reachable", False, "GEMINI_API_KEY is not set.")
except Exception as exc:
    check("Gemini API reachable", False, str(exc))


# ── 6. Upload directory ────────────────────────────────────────────────────────
log.info("── Upload Directory ───────────────────────────────────")

upload_dir = os.getenv("UPLOAD_FOLDER", "/tmp/lms_uploads")
try:
    os.makedirs(upload_dir, exist_ok=True)
    test_file = os.path.join(upload_dir, ".write_test")
    with open(test_file, "w") as f:
        f.write("ok")
    os.remove(test_file)
    check("Upload directory writable", True, "")
except Exception as exc:
    check("Upload directory writable", False, str(exc))


# ── Summary ───────────────────────────────────────────────────────────────────
log.info("───────────────────────────────────────────────────────")

if WARNINGS:
    log.warning("Warnings (%d): %s", len(WARNINGS), ", ".join(WARNINGS))

if ERRORS:
    log.error("FAILED — %d critical check(s) did not pass: %s", len(ERRORS), ", ".join(ERRORS))
    log.error("Fix the above issues before starting the service.")
    sys.exit(1)

log.info("All checks passed. Service is ready to start.")
sys.exit(0)
