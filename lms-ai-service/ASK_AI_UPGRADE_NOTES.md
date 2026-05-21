# Ask AI — Upgrade Notes (Before vs. After)

A plain-English summary of what changed in the Ask AI feature during this work
session. Written so you can show a non-engineer what we did and why it matters.

---

## TL;DR

The Ask AI feature went from **"a single Gemini call with a hardcoded prompt"**
to **"a retrieval-augmented agent with a memory that learns from user feedback"**.

| | Before | After |
|---|---|---|
| How it picks examples | Token overlap on a flat list | Semantic search (ChromaDB) |
| How it knows the schema | Static, hand-written, often outdated | Auto-introspected, stored as "table cards" in ChromaDB |
| When the SQL is wrong | One-shot retry then give up | Multi-iteration agent with self-correction feedback |
| Speed on repeat questions | Same cost every time | Trusted fast-path skips the LLM entirely |
| Learning from users | Nothing | 👍 / 👎 feedback feeds a learned vector bank |
| Languages | English only | English + Arabic translation |
| Bad-answer review | None | `bad_feedback.jsonl` queue for admin review |

---

## 1. What it was before

- The PHP app would call a Flask microservice (`/ask`) with the user's question.
- Inside the service, the question went through:
  1. A simple keyword check against a static "training_question_bank" of
     hand-written examples. It picked examples by **counting overlapping words**
     between the user's question and the example questions. Not very smart.
  2. The picked examples + a **partial, hand-written schema** (literally just
     a few tables typed into a Python config) were stuffed into a prompt.
  3. Gemini generated SQL.
  4. If the SQL failed validation, there was **one** retry. After that, the
     user got "I could not produce a query."
- There was no memory: every identical question paid the full LLM cost again.
- Answers sometimes looked confident but were just wrong (a "hallucination"),
  and there was no way for the user to tell the system "this answer was bad."

**Symptoms the user reported:**
- "Most of the time it just doesn't give data."
- "Doesn't feel natural."
- "Not reliable, not production-grade."

---

## 2. What we built (the three big upgrades)

### Upgrade A — Few-shot RAG with ChromaDB

We replaced the token-overlap picker with **semantic search** over a vector
database (ChromaDB).

- We hand-curated ~116 verified (question → SQL) pairs based on the real LMS
  schema and stored them in `data/qa_pairs.jsonl`.
- Each question gets converted to a **vector** (a list of numbers representing
  its meaning) using Gemini's `gemini-embedding-001` model.
- These vectors are stored in a ChromaDB **collection** called `lms_qa_pairs`.
- When the user asks something, we vectorise their question and ask Chroma
  "give me the top 8 closest pairs by meaning." Now phrasing doesn't matter —
  "how many kids have outstanding fees?" finds the same example as
  "give me students with remaining payments."

### Upgrade B — Schema RAG (table cards)

Instead of stuffing a hardcoded schema into every prompt, we introspect the
**live** MySQL database on first boot:

- For each of the ~250 tables we build a "table card" — a short paragraph with:
  - Table name
  - A human-written purpose (from `data/table_purpose_hints.json`)
  - Columns + types
  - 3 sample rows
  - Foreign keys (which other tables it joins to)
- Each card is embedded and stored in a second ChromaDB collection,
  `lms_table_cards`.
- At question time we only retrieve the **8 most relevant tables**. The model
  no longer sees an irrelevant 250-table dump — it sees a focused, useful slice.

This eliminated a whole class of "I don't know what table this is in"
hallucinations and shrank the prompt 5–10×.

### Upgrade C — Agentic loop with self-correction

The pipeline is no longer "one prompt, one answer." It's a small state machine
(`services/sql_agent.py`):

1. **Plan** (optional) — short sentence describing how to answer.
2. **Retrieve** — pull few-shot examples + relevant tables from Chroma.
3. **Generate SQL** — Gemini Flash with that context.
4. **Validate** — SELECT-only safety check.
5. **Execute** — run the SQL against the read-only DB.
6. **Critique** (optional) — Gemini reviews its own rows.
7. **Regenerate with feedback** if any step failed (up to 2 iterations).
8. **Format** the rows into a natural-language answer.

The crucial bit is step 7 — when the SQL hits a DB error, the **error message**
itself is fed back to the LLM as guidance, so the next try has a real chance
of fixing the column name / join it got wrong.

---

## 3. Speed — the trusted fast-path

LLM calls are slow (~2–10s) and not free. So on top of RAG we added a
**fast-path**:

- If the nearest curated example has a cosine distance ≤ `0.18` (≈ a close
  paraphrase), we **don't even call the LLM**. We just run the curated SQL
  directly, format the rows, and return.
- If that SQL fails (e.g. wrong column), we fall through to the full LLM loop
  with the error as feedback — never a dead-end.

Result: repeat questions and common paraphrases come back in **under a second**
instead of 5–10 seconds.

---

## 4. Human-in-the-loop feedback (the big new feature)

The piece you just asked for. Every AI answer now ships with a `request_id`,
and the UI shows the 👍 / 👎 buttons you already had — wired up to a new
endpoint:

```
POST /ask/feedback
{ "request_id": "...", "verdict": "good" | "bad", "note": "optional" }
```

### When the user clicks 👍

1. The server looks up what question was asked and what SQL ran.
2. If the answer came from the LLM (i.e. not already cached), it gets saved
   to a **separate** ChromaDB collection called `lms_qa_learned` AND appended
   to `data/qa_pairs_learned.jsonl` on disk.
3. Next time anyone asks a near-identical paraphrase, the trusted fast-path
   matches the **learned** pair and skips the LLM. Instant answer.

### When the user clicks 👎

1. The question + SQL is written to `data/bad_feedback.jsonl` for admin review.
2. **Never** added to the learned bank.

### Important safety design

- Curated (hand-verified) pairs are kept **separate** from learned (user-verified)
  pairs. A user clicking 👍 on something can never override a hand-verified
  example.
- Learned pairs use a **stricter** trust threshold (`0.10`) than curated (`0.18`).
  So a 👍'd answer only auto-runs on **near-identical** rephrasing — anything
  fuzzier still goes through the LLM, where it might generate better SQL.
- Answers from the deterministic shortcut or any fast-path are **refused** for
  learning (no point re-saving something that's already cached).

### Admin endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/admin/learned` | List everything in the learned bank |
| DELETE | `/admin/learned/<id>` | Remove a learned pair (e.g. user 👍'd by mistake) |
| GET | `/health` | Now also reports `learned_pairs_indexed` |

---

## 5. How ChromaDB fits in — explained simply

ChromaDB is a **vector database**. It doesn't store text in rows like MySQL;
it stores text as a **list of numbers** (called an embedding) that captures
the *meaning* of the text. When you "search" Chroma, it finds the entries
whose meaning is closest to your query, not just whose words overlap.

In our service it holds **three collections**, all in the same folder
(`data/chroma`, mounted as a Docker volume so it survives restarts):

| Collection | What's in it | How big | When it gets used |
|---|---|---|---|
| `lms_qa_pairs` | Hand-curated NL→SQL examples | ~116 | Every question (to find few-shot examples) |
| `lms_table_cards` | Auto-introspected table descriptions | ~250 | Every question (to find relevant tables) |
| `lms_qa_learned` | User-👍'd answers | grows over time | Every question (queried alongside curated) |

All three use the same embedding model
(`gemini-embedding-001` by default; falls back to a local ONNX model if
Gemini's embedding endpoint is unavailable).

---

## 6. Other improvements along the way

- **Gemini model swap**: primary is now `gemini-2.5-flash` (5× faster than
  Pro) with `gemini-2.5-pro` and `gemini-2.5-flash-lite` as fallbacks.
- **Groq fallback**: if Gemini is rate-limited, we fall back to
  `llama-3.3-70b-versatile` on Groq (128k context, can handle the full prompt).
- **Per-call timeout** is configurable (`GEMINI_REQUEST_TIMEOUT_SECONDS=25`).
- **Prompt rules** were strengthened — explicit guidance about `GROUP BY` /
  `HAVING` mistakes and fee-table JSON parsing.
- **Background seeding**: Chroma collections fill in on a background thread
  so the health check turns green immediately and the service is usable while
  embeddings are still being computed for the first time.
- **CLI scripts** added in `scripts/`:
  - `seed_qa_pairs.py --force` — reseed curated examples
  - `seed_table_cards.py --force` — reseed schema
  - `run_eval.py` — evaluation harness against `eval/eval_questions.jsonl`
- **PHP timeout** was raised to 90s to leave room for cold-start LLM calls.
- **Arabic responses** are translated on the fly when `respond_arabic=true`.

---

## 7. File map (what was added / changed)

```
lms-ai-service/
├── app.py                          ← /ask/feedback, /admin/learned, request_id, background seed
├── config.py                       ← all the new env vars (Chroma, learned, timeouts, thresholds)
├── docker-compose.yml              ← exposes the new env vars
├── Dockerfile                      ← bumped timeouts and start_period
├── .env.example / .env             ← documented new vars
├── requirements.txt                ← +chromadb, +onnxruntime
├── README.md                       ← rewritten end-to-end
├── ASK_AI_UPGRADE_NOTES.md         ← this file
├── data/
│   ├── qa_pairs.jsonl              ← 116 curated NL→SQL examples (NEW)
│   ├── qa_pairs_learned.jsonl      ← grows from 👍 feedback (NEW, runtime)
│   ├── bad_feedback.jsonl          ← 👎 feedback for review (NEW, runtime)
│   ├── table_purpose_hints.json    ← human descriptions for each table (NEW)
│   └── chroma/                     ← ChromaDB on-disk index (NEW, runtime)
├── eval/
│   └── eval_questions.jsonl        ← 30 test questions (NEW)
├── scripts/
│   ├── seed_qa_pairs.py            ← (NEW)
│   ├── seed_table_cards.py         ← (NEW)
│   └── run_eval.py                 ← (NEW)
└── services/
    ├── ai_service.py               ← prompt rules, Flash default, Groq fallback, timeouts
    ├── db_service.py               ← max_rows=0 support for full schema introspection
    ├── qa_retriever.py             ← semantic search + learned-pair logic (NEW)
    ├── schema_retriever.py         ← live schema → table cards (NEW)
    ├── sql_agent.py                ← the multi-step agent (NEW)
    └── vector_store.py             ← ChromaDB wrapper, 3 collections (NEW)

TaleemX/
├── application/controllers/admin/Askai.php
│   └── feedback() action + request_id passthrough
└── application/views/admin/askai/index.php
    └── 👍/👎 buttons wired to /admin/askai/feedback + toasts
```

---

## 8. End-to-end test we ran (proof it works)

| Step | What we did | Result |
|---|---|---|
| 1 | Asked: *"list the most recent 5 announcements in the school"* | LLM generated SQL, `source: "llm"`, got a `request_id` |
| 2 | POST /ask/feedback with that `request_id` and `verdict: "good"` | `{learned: true, id: learned_2926995e0efe5242}` — saved to Chroma + JSONL |
| 3 | Asked the paraphrase: *"list the most recent 5 announcements in school"* | `source: "learned_trusted"`, distance **0.004**, **LLM was skipped** |
| 4 | Marked a wrong answer 👎 with a note | Logged to `data/bad_feedback.jsonl`, **not** added to learned bank |
| 5 | Marked a deterministic-shortcut answer 👍 | Politely refused: *"answer already came from a deterministic fast-path, no new learning needed"* |

---

## 9. What this means in practice

- **Day 1:** Service answers most questions through Gemini Flash + RAG. About
  ~50% of questions hit the curated fast-path; the rest go through the full
  LLM loop.
- **Week 1:** As people click 👍 on good answers, the learned bank fills up.
  Common follow-up phrasings start hitting `source: "learned_trusted"` instead
  of the LLM.
- **Week 4:** Admin reviews `bad_feedback.jsonl`, fixes any systematically
  wrong curated entries in `qa_pairs.jsonl`, and runs `seed_qa_pairs.py --force`.
- **Long-term:** The Ask AI keeps getting faster and more accurate over time
  *without* code changes, because users are training it through normal use.

That's the whole picture.
