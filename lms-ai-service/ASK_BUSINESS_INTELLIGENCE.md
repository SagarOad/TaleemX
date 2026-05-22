# Ask AI — School performance & business intelligence

This document explains what **business / performance / risk** questions Ask AI can answer from your TaleemX database, how the data is wired, and how to extend it.

## What was added

| Asset | Purpose |
|-------|---------|
| `data/qa_pairs_business.jsonl` | Curated **question → SQL** pairs for KPIs, risk/improvement, HR, profit, growth, academics |
| `data/business_question_catalog.json` | Plain-English question list for admins (not used at runtime) |
| `QA_PAIRS_EXTRA_FILES` | Config/env: extra JSONL files merged into Chroma on boot |
| `insights.py` module `school_performance` | Better follow-up chips after business answers |
| Ask AI welcome screen | Suggested starters for performance / risk / profit |

The existing agent, RAG, and UI are unchanged — business examples are **additional** training data, not a separate app.

## Executive briefing (big-scope questions)

Questions like **“how is our school performing overall?”** or **“give me school performance overall”** use a dedicated **executive briefing** path (`services/executive_briefing.py`), not a single SQL row.

It automatically:

1. Runs **5 verified queries** — KPIs, risk, gaps, admissions trend, weakest classes  
2. Computes a **health score (0–100)** using TaleemX benchmarks  
3. Returns a **detailed narrative** + **dashboard UI**: score, KPI tiles, charts, risk table, gaps table  

### What “good performing” means (in your LMS)

| Area | Strong | Watch | Critical |
|------|--------|-------|----------|
| Attendance (month) | ≥ 90% | 75–89% | &lt; 75% |
| Finance (month) | Surplus ≥ 0 | — | Deficit |
| Fees | Collections keeping up with outstanding | Low collection ratio | Large outstanding |
| Growth | Admissions ≥ last month | Slower month | — |
| Risk | No high-severity signals | Some medium | Multiple high |
| Operations | 0 open complaints | 1–2 | 3+ |

Rating labels: **Strong** (80+), **Good** (65–79), **Needs improvement** (50–64), **Critical** (&lt;50).

## How it works (data flow)

```mermaid
flowchart LR
  Q[Admin question] --> RAG[Chroma QA search]
  RAG --> FP[Trusted fast-path SQL]
  RAG --> Agent[SQL agent + Gemini]
  FP --> DB[(TaleemX MySQL)]
  Agent --> DB
  DB --> Insights[KPI / table / chart]
  Insights --> UI[Ask AI chat UI]
```

1. Your question is embedded and matched against **curated** pairs (`qa_pairs.jsonl` + `qa_pairs_business.jsonl`) and **learned** pairs (👍 feedback).
2. If the match is close enough, the stored SQL runs immediately (fast, no LLM).
3. Otherwise the SQL agent plans, retrieves schema cards, and generates SQL with Gemini.
4. `insights.py` picks presentation (KPI row, table, bar/line chart) and suggests follow-ups.

**Risk analysis** here means **data-driven risk indicators** (counts of low attendance, outstanding fees, open complaints, etc.) — not a separate ML model. Wording like “where are we lacking?” maps to SQL that ranks concern areas by issue count.

## Database connections (what we can measure)

| Theme | Main tables | Example metrics |
|-------|-------------|-----------------|
| Enrollment & growth | `students`, `enquiry`, `online_admissions` | New admissions by month, enquiry conversion, online pipeline |
| Academic | `mark_sheet`, `exam_schedule`, `classes` | Average marks / % by class |
| Attendance | `student_attendences`, `attendence_type`, `student_session` | School-wide %, weakest classes |
| Fees | `student_fees_master`, `student_fees_deposite` | Collected vs outstanding, monthly trend |
| Finance | `income`, `expenses`, `staff_payroll` | Income, expense heads, payroll %, estimated surplus |
| HR | `staff`, `staff_roles` | Headcount, new hires, staff:student ratio |
| Behaviour & discipline | `student_incidents`, `student_behaviour` | Negative points, incident lists |
| Operations | `complaint`, `homework`, `submit_assignment`, `staff_leave_request` | Open complaints, homework gaps, pending leave |

### Profit / surplus (important)

There is no single `profit` column. **Estimated net surplus** in the business pack is:

`income (month) − expenses (month) − payroll (month)`

Fee collections are reported separately (they may also appear as `income` depending on how your school records them). For a full P&L you may need custom heads — add more JSONL examples after you confirm your chart of accounts.

## Questions you can ask today (trained examples)

See `data/business_question_catalog.json` for the full list. Highlights:

- **Overview:** “How is our school performing overall?”, “Give me school performance report”
- **Risk & improve:** “Give me risk analysis of the school”, “What are our top risks?”, “What can we improve?”, “Where is our school lacking?”
- **HR:** “What departments do we have?”, “How many staff in each department?”, “HR headcount summary”, “What designations do we have?”
- **Finance:** “What is our profit this month?”, expense/income breakdowns, payroll % of income
- **Growth:** enrollment trend, this month vs last month admissions, new staff, fee collection trend
- **Academic:** average marks by class, lowest attendance classes

You can also ask **variations** in natural language; Chroma retrieval + the agent will still try to answer. 👍 on a good answer teaches the system your phrasing.

## Deploy / reindex

**Existing server (Chroma already seeded):** restart the AI service — extra pairs are **upserted** on boot without wiping Chroma:

```bash
docker compose up -d --no-deps --build lms-ai-service
```

**Full rebuild of curated QA** (main + business files):

```bash
docker compose exec lms-ai-service python scripts/seed_qa_pairs.py --force
```

Or POST to your admin reindex endpoint with `{ "qa": true }`.

Env (optional):

```env
QA_PAIRS_EXTRA_FILES=/app/data/qa_pairs_business.jsonl
```

Comma-separate multiple files if you add more packs later.

## Adding your own business questions

1. Copy a line from `qa_pairs_business.jsonl` (fields: `id`, `question`, `sql`, `tags`).
2. Run the SQL once in MySQL to verify rows/columns.
3. Append to `qa_pairs_business.jsonl` (or a new file listed in `QA_PAIRS_EXTRA_FILES`).
4. Restart the service or run `seed_qa_pairs.py` / reindex.

Prefer stable `id` values (e.g. `biz_my_metric`) so upserts replace the same pair.

## Limits & honesty

- Answers reflect **what is in the database** (active flags, date filters, session scope).
- Cross-school benchmarks, external market data, and subjective “quality” judgments are **not** available unless you store them.
- Very long multi-step reports may still use the LLM path (slower, uses API quota).
- Arabic replies follow the UI toggle; curated SQL is the same.

## Possible next steps (not implemented)

- Session-scoped filters (`sessions.is_active`) on all business SQL
- Composite “executive dashboard” single endpoint
- Export PDF / scheduled email of KPIs
- Separate `qa_pairs_finance_advanced.jsonl` per school’s fee/income head names
