# LMS AI Microservice

A production-grade Flask microservice that acts as the **AI layer** for the TaleemX / Smart School LMS. It answers natural-language questions about your school's data, extracts subtitles from videos, and summarises / explains caption content.

The Ask AI feature uses a **RAG-grounded agentic pipeline** with human-in-the-loop learning:

```
PHP LMS  ──HTTP──►  Flask AI Service
                       │
                       ├──► Chroma vector store
                       │      ├─ lms_qa_pairs      (curated NL→SQL gold examples)
                       │      ├─ lms_qa_learned    (grown at runtime from 👍 feedback)
                       │      └─ lms_table_cards   (per-table semantic descriptions)
                       │
                       ├──► Gemini (planner, SQL generator, critic, formatter)
                       │      └─ Groq fallback when Gemini is rate-limited
                       │
                       ├──► MySQL (read-only)
                       │
                       └──► Whisper / RapidAPI (subtitles)
```

The agent loops `plan → generate → validate → execute → critique` for up to three iterations and falls back to a deterministic SQL library for high-confidence patterns. Users grade answers with 👍 / 👎, and the good ones populate a separate learned bank so the next paraphrase skips the LLM entirely.

---

## Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET    | `/health` | Health + vector-store / agent status |
| POST   | `/ask` | Natural language → SQL → human answer (agentic). Returns `request_id` for feedback. |
| POST   | `/ask/feedback` | 👍 / 👎 a previous answer. Good ones populate the learned bank. |
| POST   | `/extract-subtitles` | Extract subtitles from video file or YouTube |
| POST   | `/caption-ai` | Summarise or explain caption text |
| POST   | `/admin/reindex` | Rebuild the QA pairs and/or table-card collections |
| GET    | `/admin/learned` | List all learned pairs currently in Chroma |
| DELETE | `/admin/learned/<id>` | Remove a single learned pair |
| GET    | `/docs` | Swagger UI |

### Human-in-the-loop feedback

Every successful `/ask` response carries a `request_id`. The client UI shows 👍 / 👎 buttons; clicking either calls:

```
POST /ask/feedback
{ "request_id": "...", "verdict": "good"|"bad", "note": "optional" }
```

- **👍** appends `(question, sql)` to `data/qa_pairs_learned.jsonl` AND to the `lms_qa_learned` Chroma collection. The next paraphrase of that question (cosine distance ≤ `QA_LEARNED_TRUST_DISTANCE`, default 0.10) skips the LLM and reuses the SQL directly.
- **👎** persists to `data/bad_feedback.jsonl` for offline review. Bad answers are never auto-learned.
- Answers that came from a curated or learned fast-path are **not** re-learned (they're already in the bank).
- Curated pairs always outrank learned pairs on equal-distance ties, so a user-marked answer can never override a hand-verified one.

---

## Quick Start

```bash
git clone <repo>
cd lms-ai-service
cp .env.example .env
# Fill in GEMINI_API_KEY, DB_* etc.

docker compose up -d --build
```

On first boot the service will automatically:

1. Create the persistent Chroma directory (`data/chroma`).
2. Seed the `lms_qa_pairs` collection from `data/qa_pairs.jsonl` (~113 examples).
3. Introspect your MySQL schema and seed `lms_table_cards` (one card per table, with columns + foreign keys + 3 sample rows + a human description from `data/table_purpose_hints.json`).

Both seed steps are idempotent — they no-op once populated. To rebuild them, call `POST /admin/reindex`.

---

## How the agent answers a question

Take the question *"give me detailed attendance report of grade 2 this month"*.

1. **App-flow check** — is the user asking *how to navigate the app*? If yes, answer from `sms.txt`. Otherwise continue.
2. **Deterministic fast path** — a small hand-tuned set of regex patterns for the very most common intents. Returns a known-good SQL immediately when matched.
3. **Retrieve few-shot examples** — embed the question and pull the top-K similar (NL, SQL) pairs from Chroma (`QA_TOP_K`, default 12).
4. **Retrieve relevant schema** — embed the question and pull the top-K most relevant table cards (`SCHEMA_TOP_K`, default 14). Only those tables go into the prompt, not the full schema.
5. **Plan** — one short LLM call asking *which tables, which columns, which filters* (no SQL yet).
6. **Generate SQL** — LLM writes a single `SELECT` using the retrieved schema + examples + plan.
7. **Validate** — `SQLValidator` enforces SELECT-only and blocks injection patterns.
8. **Execute** — read-only MySQL.
9. **Critique** — LLM reviews the first 5 result rows: *do they actually answer the user's question?* If `regenerate`, loop back to step 6 with the critic's reason as feedback.
10. **Format** — narrative for analytic questions, structured cards for list/detail intents.

Up to `AGENT_MAX_ITERATIONS` (default 3) regenerations are allowed per request.

---

## Vector store data files

| Path | Purpose |
|------|---------|
| `data/qa_pairs.jsonl` | Curated NL → SQL gold examples. Each line: `{"id", "question", "sql", "tags"}`. Add your own real-traffic examples here over time. |
| `data/table_purpose_hints.json` | One-line human description per table (e.g. `"student_incidents": "Behaviour incidents logged against a student..."`). The schema retriever bakes these into the embedded table card so semantic search ranks the right table. |
| `data/chroma/` | Chroma's persistent files. Mounted as a Docker volume; rebuilt on `POST /admin/reindex`. Gitignored. |

---

## Configuration (env vars)

### LLM
| Variable | Default | Notes |
|---|---|---|
| `GEMINI_API_KEY` | — | **Required.** |
| `GEMINI_MODEL` | `gemini-2.5-pro` | Primary SQL / plan / critic model. |
| `GEMINI_FALLBACK_MODELS` | `gemini-2.5-flash` | Comma-separated fallbacks if primary is rate-limited. |
| `GROQ_API_KEY` | — | Optional second-provider fallback. |
| `GROQ_MODEL` | `llama-3.1-8b-instant` | Used when Gemini fails. |

### Vector store / RAG
| Variable | Default | Notes |
|---|---|---|
| `EMBEDDING_PROVIDER` | `gemini` | `gemini` uses `text-embedding-004` (reuses `GEMINI_API_KEY`). `local` uses ONNX `all-MiniLM-L6-v2` (offline, ~80 MB). |
| `EMBEDDING_MODEL_GEMINI` | `text-embedding-004` | |
| `CHROMA_PERSIST_DIR` | `/app/data/chroma` | Mount as a Docker volume. |
| `CHROMA_QA_COLLECTION` | `lms_qa_pairs` | |
| `CHROMA_SCHEMA_COLLECTION` | `lms_table_cards` | |
| `QA_PAIRS_FILE` | `/app/data/qa_pairs.jsonl` | |
| `TABLE_HINTS_FILE` | `/app/data/table_purpose_hints.json` | |
| `QA_TOP_K` | `12` | Few-shot examples per request. |
| `SCHEMA_TOP_K` | `14` | Tables per request. |
| `AUTO_SEED_QA` | `true` | Seed QA pairs on first boot if collection is empty. |
| `AUTO_SEED_SCHEMA` | `true` | Seed table cards on first boot if DB reachable. |

### Agent loop
| Variable | Default | Notes |
|---|---|---|
| `AGENT_MAX_ITERATIONS` | `3` | Maximum regeneration attempts. |
| `AGENT_ENABLE_CRITIC` | `true` | Disable to save one LLM call per request. |
| `AGENT_PLAN_TEMPERATURE` | `0.1` | |
| `AGENT_SQL_TEMPERATURE` | `0.0` | Determinism — same question → same SQL. |
| `AGENT_CRITIC_TEMPERATURE` | `0.0` | |

### Database / other
See `.env.example`.

---

## Admin operations

### Force-rebuild the vector store

```bash
curl -X POST http://localhost:5050/admin/reindex \
  -H "Content-Type: application/json" \
  -d '{"qa": true, "schema": true}'
```

Returns counts:

```json
{
  "status": "ok",
  "qa_inserted": 113,
  "table_cards_inserted": 184,
  "qa_total": 113,
  "schema_total": 184
}
```

You can also run these directly inside the container:

```bash
docker compose exec lms-ai python scripts/seed_qa_pairs.py --force
docker compose exec lms-ai python scripts/seed_table_cards.py --force
```

### Run the eval harness

A 30-question regression suite lives at `eval/eval_questions.jsonl`. Run it after any prompt / model / data change:

```bash
docker compose exec lms-ai python scripts/run_eval.py --threshold 0.7
```

It calls the live `/ask` endpoint and checks that each answer references the expected table(s) and isn't a "no records found" stub. Exits non-zero if pass rate falls below `--threshold`. Wire this into CI to catch regressions.

### Debug a single request

Pass `"debug": true` in the body to get the full agent trace back in the response:

```bash
curl -X POST http://localhost:5050/ask \
  -H "Content-Type: application/json" \
  -d '{"question": "give me behaviour record of Abdullah Bin Ahmed", "debug": true}'
```

Response includes the plan, retrieved tables, retrieved examples, per-iteration SQL + status + critique reason, and the final SQL.

---

## API reference

### POST `/ask`

```json
{ "question": "give me list of subjects for grade 1", "respond_arabic": false, "debug": false }
```

Response:

```json
{
  "answer": "I found 8 record(s): 1. Subject Name: English ...",
  "status": "ok",
  "sql": "SELECT DISTINCT sub.name AS subject_name, ..."
}
```

When `debug=true`, an additional `trace` field is included.

### POST `/extract-subtitles`

YouTube:
```json
{ "type": "youtube", "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ" }
```

File upload:
```bash
curl -X POST http://localhost:5000/extract-subtitles -F "file=@lecture.mp4"
```

### POST `/caption-ai`

```json
{ "action": "summarize", "text": "..." }
```
or
```json
{ "action": "explain", "text": "...", "question": "What is photosynthesis?" }
```

---

## Project structure

```
lms-ai-service/
├── app.py                       # Flask routes + agent wiring
├── config.py                    # All env vars + the static schema fallback
├── requirements.txt
├── Dockerfile
├── docker-compose.yml
├── .env.example
├── README.md
├── startup_check.py             # Pre-flight checks (DB, Gemini, Chroma, etc.)
├── sms.txt                      # App manual (used for "how-to" questions)
├── data/
│   ├── qa_pairs.jsonl           # ~113 curated NL→SQL gold examples
│   ├── table_purpose_hints.json # Human descriptions per table
│   └── chroma/                  # Persistent vector store (volume-mounted)
├── eval/
│   └── eval_questions.jsonl     # 30-question regression suite
├── scripts/
│   ├── seed_qa_pairs.py
│   ├── seed_table_cards.py
│   └── run_eval.py
├── services/
│   ├── ai_service.py            # LLM calls (Gemini + Groq)
│   ├── db_service.py            # MySQL pool + read-only execute
│   ├── sql_validator.py         # SELECT-only safety net
│   ├── subtitle_service.py      # YouTube + Whisper transcription
│   ├── vector_store.py          # ChromaDB wrapper + Gemini embedder
│   ├── qa_retriever.py          # Few-shot retrieval
│   ├── schema_retriever.py      # Table-card retrieval + schema introspection
│   ├── sql_agent.py             # plan → generate → execute → critique loop
│   └── training_question_bank.py # (legacy) token-overlap retriever, kept for tests
├── middleware/
│   ├── rate_limiter.py
│   └── validators.py
└── db/
    └── init/
        └── 01_readonly_user.sql # MySQL init: grant SELECT only
```

---

## Improving accuracy over time

The whole point of this architecture is that **you can keep getting better without changing code**. The two highest-leverage moves:

1. **Add more gold examples** to `data/qa_pairs.jsonl`. Mine them from real questions that succeeded (or that you manually fixed). Restart the container or call `/admin/reindex` to pick them up.
2. **Annotate tables** in `data/table_purpose_hints.json` whenever the agent picks the wrong table for a question. One descriptive sentence per table is enough.

The critic step will catch many edge cases automatically, but the retrieval quality is fundamentally bottlenecked by how good your seed data is. Treat `qa_pairs.jsonl` as a living artefact.

---

## Security notes

- The DB user must be **read-only** (`SELECT` only — no INSERT, UPDATE, DELETE).
- `SQLValidator` enforces `SELECT`-only and blocks injection patterns (stacked queries, UNION-based injection, `SLEEP`, `BENCHMARK`, `INTO OUTFILE`, etc.).
- Uploaded video files are saved to a temp path and deleted immediately after transcription.
- The Flask app runs as a non-root user inside Docker.
- Do not expose port 5000 publicly — keep this service on an internal Docker network and reach it only from your PHP app.
- The agent's plan / critic / generation prompts never include raw row values from the database in plaintext beyond the first 5 rows of an executed query, which are needed to evaluate the answer.

---

## Enabling Whisper (Video File Transcription)

Whisper is **disabled by default** to keep the Docker image lightweight. To enable it, see `requirements.txt` and switch to `faster-whisper` or `openai-whisper`, then rebuild the image.

RAM requirements: `base` ~1.5 GB, `small` ~2 GB, `medium` ~5 GB.

---

## Troubleshooting

**`/health` shows `vector_store: unavailable`** — Chroma failed to initialise. Check container logs; the most common causes are a missing `EMBEDDING_PROVIDER` env var or a broken `data/chroma` mount. The `/ask` endpoint will silently fall back to the legacy single-shot pipeline.

**`table_cards_indexed: 0` after first boot** — the DB was unreachable when the service started. Once DB is healthy, call `POST /admin/reindex` (or restart the container).

**Agent keeps regenerating and exhausts iterations** — turn on `"debug": true` and inspect the `trace.iterations` field. The most common cause is a missing or poorly-described table in `data/table_purpose_hints.json` — add a hint and reindex.

**429 errors from Gemini** — set `GROQ_API_KEY` so the service can fall back when Gemini is rate-limited, or raise `GEMINI_MIN_CALL_INTERVAL_SECONDS`.
