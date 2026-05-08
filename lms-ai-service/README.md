# LMS AI Microservice

A production-ready Flask microservice that acts as the **AI layer** for your PHP-based LMS. It provides natural language database querying, video subtitle extraction, and AI-powered explanation/summarization of course content.

---

## Architecture

```
PHP LMS  ──HTTP──►  Flask AI Service  ──►  MySQL (read-only)
                         │
                         └──►  Gemini API
                         └──►  Whisper (local)
                         └──►  RapidAPI YouTube Captions/Transcripts
```

---

## Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Health check |
| POST | `/ask` | Natural language → SQL → human answer |
| POST | `/extract-subtitles` | Extract subtitles from video file or YouTube |
| POST | `/caption-ai` | Summarize or explain caption text |

---

## Quick Start

### 1. Clone & configure

```bash
git clone <repo>
cd lms-ai-service
cp .env.example .env
# Edit .env with your GEMINI_API_KEY, DB credentials, etc.
```

### 2. Start with Docker Compose

```bash
docker compose up -d --build
```

The service will be available at `http://localhost:5000`.

> **Note:** If your LMS already has a running MySQL server, remove the `lms-mysql` service from `docker-compose.yml` and set `DB_HOST` to point to your existing server.

### 3. Verify

```bash
curl http://localhost:5000/health
# → {"status":"ok","service":"LMS AI Microservice"}
```

---

## API Reference

### POST `/ask`

Ask a natural language question about your LMS data.

**Request:**
```json
{ "question": "How many students are enrolled in active courses?" }
```

**Response:**
```json
{ "answer": "There are 487 students currently enrolled in active courses across 23 published courses." }
```

---

### POST `/extract-subtitles`

**Option A — YouTube URL:**
```json
{ "type": "youtube", "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ" }
```

**Option B — Video file upload:**
```bash
curl -X POST http://localhost:5000/extract-subtitles \
  -F "file=@lecture.mp4"
```

**Response:**
```json
{ "subtitles": "In this lesson we will cover the fundamentals of..." }
```

---

### POST `/caption-ai`

**Summarize:**
```json
{
  "action": "summarize",
  "text": "In this lesson we discuss photosynthesis. Plants convert sunlight..."
}
```

**Explain (with optional focus question):**
```json
{
  "action": "explain",
  "text": "...caption text here...",
  "question": "Why do plants need chlorophyll?"
}
```

**Response:**
```json
{ "answer": "Chlorophyll is the green pigment in plant cells that absorbs light energy..." }
```

---

## PHP Integration Examples

### Natural Language Query
```php
$response = Http::post('http://lms-ai:5000/ask', [
    'question' => 'Which students have not submitted assignment 5?'
]);
$answer = $response->json('answer');
```

### Subtitle Extraction (YouTube)
```php
$response = Http::post('http://lms-ai:5000/extract-subtitles', [
    'type' => 'youtube',
    'url'  => $lesson->video_url
]);
$subtitles = $response->json('subtitles');
// Store $subtitles in lessons.subtitles column
```

### Caption AI
```php
$response = Http::post('http://lms-ai:5000/caption-ai', [
    'action'   => 'explain',
    'text'     => $lesson->subtitles,
    'question' => $request->input('student_question')
]);
echo $response->json('answer');
```

---

## Configuration

All configuration is via environment variables (`.env` file):

| Variable | Default | Description |
|----------|---------|-------------|
| `GEMINI_API_KEY` | — | **Required.** Your Gemini API key |
| `GEMINI_MODEL` | `gemini-2.5-pro` | Gemini model to use |
| `DB_HOST` | `lms-mysql` | MySQL hostname |
| `DB_PORT` | `3306` | MySQL port |
| `DB_USER` | `lms_readonly` | Read-only DB user |
| `DB_PASSWORD` | — | **Required.** DB password |
| `DB_NAME` | `lms_db` | Database name |
| `DB_QUERY_TIMEOUT` | `15` | Query timeout in seconds |
| `DB_MAX_ROWS` | `50` | Maximum rows returned per query |
| `WHISPER_MODEL` | `base` | Whisper model size (tiny/base/small/medium/large) |
| `UPLOAD_FOLDER` | `/tmp/lms_uploads` | Temp storage for uploaded video files |
| `RAPIDAPI_KEY` | — | **Required for YouTube extraction.** RapidAPI key |
| `RAPIDAPI_HOST` | `youtube-captions-transcript-subtitles-video-combiner.p.rapidapi.com` | RapidAPI host for captions API |
| `RAPIDAPI_URL_TEMPLATE` | `https://youtube-captions-transcript-subtitles-video-combiner.p.rapidapi.com/download-webvtt/{video_id}` | URL template for RapidAPI YouTube caption endpoint |
| `RAPIDAPI_RESPONSE_MODE` | `default` | Response mode sent to captions endpoint |
| `RAPIDAPI_TIMEOUT_SECONDS` | `30` | Timeout for RapidAPI calls |
| `RAPIDAPI_YT_LANG` | `en` | Preferred transcript language |
| `RAPIDAPI_YT_CHUNK_SIZE` | `500` | Chunk size sent to transcript API |
| `RAPIDAPI_YT_TEXT_MODE` | `false` | If `true`, request plain text mode from API |

---

## Enabling Whisper (Video File Transcription)

Whisper is **disabled by default** to keep the Docker image lightweight.

To enable it:

1. Uncomment the Whisper lines in `requirements.txt`:
   ```
   openai-whisper==20231117
   torch==2.3.1
   ffmpeg-python==0.2.0
   ```

2. Rebuild the Docker image:
   ```bash
   docker compose up -d --build
   ```

3. Set `WHISPER_MODEL=base` (or `small`/`medium` for better accuracy).

> **RAM requirements:** `base` needs ~1.5 GB, `small` needs ~2 GB, `medium` needs ~5 GB.

---

## Updating the DB Schema

The schema string in `config.py` (`DB_SCHEMA`) is what Gemini uses to generate SQL.
Keep it accurate and up-to-date with your actual database. Include:
- All table names
- All column names and types
- Foreign key relationships

---

## Project Structure

```
lms-ai-service/
├── app.py                    # Flask routes
├── config.py                 # All configuration
├── requirements.txt
├── Dockerfile
├── docker-compose.yml
├── .env.example
├── services/
│   ├── ai_service.py         # All Gemini API calls
│   ├── db_service.py         # MySQL connection pool + query execution
│   ├── sql_validator.py      # SELECT-only enforcement
│   └── subtitle_service.py   # YouTube (RapidAPI) + Whisper extraction
└── db/
    └── init/
        └── 01_readonly_user.sql   # MySQL init: grant SELECT only
```

---

## Security Notes

- The DB user must be **read-only** (`SELECT` only — no INSERT, UPDATE, DELETE).
- The SQL validator enforces `SELECT`-only queries and blocks injection patterns.
- Uploaded files are saved to a temp path and deleted immediately after transcription.
- The Flask app runs as a non-root user inside Docker.
- Do not expose port 5000 publicly — keep this service on an internal Docker network and proxy only from your PHP app.
