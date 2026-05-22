"""
services/insights.py

Helpers that turn raw query results into rich, UI-renderable payloads:

  - detect_presentation(question, columns, rows)
        → "kpi" | "table" | "bar_chart" | "line_chart" | "pie_chart" | "cards" | "text"

  - build_structured_data(columns, rows, presentation)
        → JSON-friendly payload the frontend can render directly
          (rows-as-objects, chart config with labels + datasets, KPI value, etc.)

  - infer_module(question)
        → coarse module label used for follow-up generation and source badges

  - generate_followup_suggestions(question, module, qa_retriever)
        → up to 4 related questions we are CONFIDENT we can answer
          (drawn from the curated + learned QA vector bank, with intent-aware
          tweaks; never includes the question itself)

Designed to be fully deterministic and side-effect free so the SQL agent
can call them without extra LLM cost.
"""

from __future__ import annotations

import logging
import re
from datetime import date, datetime, time as dtime, timedelta
from decimal import Decimal
from typing import Iterable

from services.text_sanitize import sanitize_cell

logger = logging.getLogger(__name__)


# ── Module taxonomy ──────────────────────────────────────────────────────────
# Each module has a list of keywords + a list of "starter" follow-ups that we
# can fall back on when the QA vector bank doesn't surface anything relevant.

_MODULES: list[dict] = [
    {
        "id": "front_office",
        "label": "Front Office",
        "keywords": [
            "admission enquiry", "admission enquir", "admission inquiry",
            "enquiry", "enquiries", "visitor", "visitor book", "phone call",
            "phone log", "dispatch", "complaint", "front office", "front desk",
            "receive", "postal",
        ],
        "followups": [
            "list today's admission enquiries",
            "show visitor book entries this week",
            "how many phone calls were logged this month",
            "list pending admission enquiries",
        ],
    },
    {
        "id": "students",
        "label": "Students",
        "keywords": [
            "student", "students", "pupil", "learner", "admission", "admit",
            "roll number", "guardian", "parent",
        ],
        "followups": [
            "how many students do we have",
            "show students of grade 1",
            "list students who have remaining fees",
            "show students enrolled this month",
        ],
    },
    {
        "id": "staff",
        "label": "Staff & HR",
        "keywords": [
            "staff", "teacher", "teachers", "employee", "payroll", "salary",
            "designation", "department", "hr ",
        ],
        "followups": [
            "how many teachers do we have",
            "list staff by department",
            "show staff joined this year",
            "list staff salary this month",
        ],
    },
    {
        "id": "attendance",
        "label": "Attendance",
        "keywords": [
            "attendance", "attendence", "present", "absent", "late", "half day",
            "biometric", "punch",
        ],
        "followups": [
            "show attendance summary per student this month",
            "which students have the overall best attendance history",
            "list students absent today",
            "give me detailed attendance report of grade 1 this month",
        ],
    },
    {
        "id": "fees",
        "label": "Fees",
        "keywords": [
            "fee", "fees", "payment", "paid", "outstanding", "remaining",
            "due", "discount", "invoice", "transaction",
        ],
        "followups": [
            "how many students have remaining fees",
            "show fees collected this month",
            "list pending fees by class",
            "show top 10 outstanding fees",
        ],
    },
    {
        "id": "exams",
        "label": "Exams & Marks",
        "keywords": [
            "exam", "exams", "marks", "grade", "result", "results", "topper",
            "fail", "pass", "rank", "report card", "marksheet",
        ],
        "followups": [
            "list available exams",
            "show top 10 students by marks",
            "list students who failed the last exam",
            "show pass percentage by class",
        ],
    },
    {
        "id": "timetable",
        "label": "Timetable",
        "keywords": [
            "timetable", "time table", "period", "schedule", "lesson plan",
        ],
        "followups": [
            "show today's timetable for grade 1",
            "list periods this week",
            "which teacher is free this period",
        ],
    },
    {
        "id": "homework",
        "label": "Homework",
        "keywords": [
            "homework", "assignment", "submission",
        ],
        "followups": [
            "list homework assigned this week",
            "show homework submission rate by class",
            "list overdue homework",
        ],
    },
    {
        "id": "behaviour",
        "label": "Behaviour",
        "keywords": [
            "behaviour", "behavior", "incident", "discipline", "infraction",
        ],
        "followups": [
            "list behaviour incidents this month",
            "show students with negative behaviour points",
            "list incident comments this week",
            "any bad behaviour this month",
        ],
    },
    {
        "id": "library",
        "label": "Library",
        "keywords": ["library", "book", "books", "issue", "return"],
        "followups": [
            "list books issued this month",
            "show overdue book returns",
            "how many books do we have in the library",
        ],
    },
    {
        "id": "transport",
        "label": "Transport",
        "keywords": ["transport", "bus", "route", "vehicle", "driver"],
        "followups": [
            "list active bus routes",
            "show students using transport",
            "list vehicles by route",
        ],
    },
    {
        "id": "hostel",
        "label": "Hostel",
        "keywords": ["hostel", "dormitory", "room"],
        "followups": [
            "list students in hostel",
            "show hostel room occupancy",
        ],
    },
    {
        "id": "school_performance",
        "label": "School Performance & Risk",
        "keywords": [
            "school performing", "how is our school", "school performance",
            "performance report", "risk analysis", "risk indicator", "at risk",
            "where is our school lacking", "lacking", "weak area", "negative",
            "growth rate", "enrollment growth", "profit", "surplus", "net surplus",
            "overall performance", "business intelligence", "kpi dashboard",
        ],
        "followups": [
            "how is our school performing overall",
            "give me risk analysis of the school",
            "where is our school lacking",
            "what is our profit this month",
            "compare new student admissions this month vs last month",
            "list students at risk academically or financially",
        ],
    },
    {
        "id": "finance",
        "label": "Finance",
        "keywords": [
            "expense", "income", "budget", "transaction", "balance", "payroll",
            "profit", "loss", "revenue", "cost", "cash flow",
        ],
        "followups": [
            "show income expense and payroll summary this month",
            "show top 10 expense entries this month",
            "break down expenses by category this month",
            "show fee collection trend by month",
        ],
    },
    {
        "id": "online_admission",
        "label": "Online Admission",
        "keywords": [
            "online admission", "applicant", "application form",
        ],
        "followups": [
            "list online admission applications this month",
            "show online admission applications pending review",
        ],
    },
    {
        "id": "announcements",
        "label": "Announcements",
        "keywords": ["announcement", "notice", "notification", "circular"],
        "followups": [
            "show recent announcements",
            "list notifications sent this week",
        ],
    },
    {
        "id": "courses",
        "label": "Online Courses",
        "keywords": ["course", "courses", "module", "lesson", "video"],
        "followups": [
            "list active online courses",
            "show course enrolments this month",
        ],
    },
]


# ── Module + intent detection ────────────────────────────────────────────────

def infer_module(question: str) -> dict:
    """Return the best-matching module entry, or a generic fallback."""
    q = (question or "").lower()
    if not q:
        return {"id": "general", "label": "General", "keywords": [], "followups": []}
    best = None
    best_score = 0
    for mod in _MODULES:
        score = sum(1 for kw in mod["keywords"] if kw in q)
        if score > best_score:
            best = mod
            best_score = score
    if best is None or best_score == 0:
        return {"id": "general", "label": "General", "keywords": [], "followups": []}
    return best


_INTENT_PATTERNS = [
    ("comparison", [
        r"\bcompare\b", r"\bvs\b", r" versus ", r"difference between",
        r"this (week|month|year) (vs|compared to|against) (last|previous)",
    ]),
    ("performance", [
        r"performance", r"top \d+", r"best (student|teacher|staff)",
        r"ranking", r"leaderboard", r"how (well|good) is",
    ]),
    ("trend", [
        r"trend", r"over time", r"last \d+ (months|weeks|days)",
        r"by month", r"by week", r"by day", r"month[-\s]?wise", r"day[-\s]?wise",
        r"\bdaily\b", r"\bmonthly\b", r"\bweekly\b",
    ]),
    ("breakdown", [
        r"by (class|grade|section|department|teacher|subject|category|status|gender|role|type)",
        r"breakdown", r"distribution", r"split by", r"group(ed)? by",
    ]),
    ("summary", [
        r"summary", r"overview", r"snapshot", r"how is the school",
    ]),
    ("count", [
        r"how many", r"^count\b", r"\btotal (number|count)\b",
    ]),
    ("list", [
        r"^list\b", r"^show me\b", r"^show all\b", r"give me( the)? list",
        r"available", r"all the",
    ]),
    ("howto", [
        r"how (do|can) i", r"where (do|can) i", r"how to", r"navigate",
        r"create (an?|the)", r"add (an?|the)", r"update (an?|the)",
        r"delete (an?|the)", r"setting", r"configure",
    ]),
    ("explain", [
        r"what is ", r"what does ", r"explain ", r"tell me about",
        r"how does ", r"how it works",
    ]),
]


def infer_intent(question: str) -> str:
    """Classify the user's intent. Cheap regex-only; no LLM call."""
    q = (question or "").lower().strip()
    if not q:
        return "general"
    for label, patterns in _INTENT_PATTERNS:
        for pat in patterns:
            if re.search(pat, q):
                return label
    return "general"


# ── Presentation detection + structured payload ──────────────────────────────

def _is_numeric(value) -> bool:
    if value is None:
        return False
    if isinstance(value, bool):
        return False
    if isinstance(value, (int, float, Decimal)):
        return True
    if isinstance(value, str):
        try:
            float(value)
            return True
        except (TypeError, ValueError):
            return False
    return False


def _coerce_number(value) -> float | int | None:
    if value is None:
        return None
    if isinstance(value, bool):
        return int(value)
    if isinstance(value, (int, float)):
        return value
    if isinstance(value, Decimal):
        return float(value)
    if isinstance(value, str):
        try:
            f = float(value)
            return int(f) if f.is_integer() else f
        except (TypeError, ValueError):
            return None
    return None


def _jsonify(value, column_name: str = ""):
    """Make any DB value safe for json.dumps (strip HTML from text fields)."""
    if isinstance(value, (datetime, date, dtime)):
        return value.isoformat()
    if isinstance(value, timedelta):
        # MySQL TIME / interval columns often arrive as timedelta via PyMySQL.
        return str(value)
    if isinstance(value, Decimal):
        try:
            return float(value)
        except Exception:
            return str(value)
    if isinstance(value, bytes):
        try:
            value = value.decode("utf-8", errors="replace")
        except Exception:
            return str(value)
    if isinstance(value, str):
        return sanitize_cell(value, column_name=column_name)
    if isinstance(value, (bool, int, float)):
        return value
    return str(value)


def _column_is_numeric(rows: list[tuple], col_idx: int) -> bool:
    if not rows:
        return False
    sampled = 0
    numeric = 0
    for row in rows[:30]:
        if col_idx >= len(row):
            continue
        val = row[col_idx]
        if val is None or val == "":
            continue
        sampled += 1
        if _is_numeric(val):
            numeric += 1
    return sampled > 0 and (numeric / sampled) >= 0.8


_DATE_HEADER_TOKENS = ("date", "day", "month", "year", "created", "updated", "time", "period")


def _column_looks_like_date(column_name: str, rows: list[tuple], col_idx: int) -> bool:
    name = (column_name or "").lower()
    if any(tok in name for tok in _DATE_HEADER_TOKENS):
        return True
    if not rows:
        return False
    for row in rows[:5]:
        if col_idx >= len(row):
            continue
        val = row[col_idx]
        if isinstance(val, (datetime, date)):
            return True
        if isinstance(val, str) and re.match(r"^\d{4}[-/]\d{1,2}([-/]\d{1,2})?", val):
            return True
    return False


def detect_presentation(
    question: str,
    columns: list[str],
    rows: list[tuple],
) -> str:
    """Pick the best UI presentation for this result set."""
    if not rows or not columns:
        return "text"

    n_rows = len(rows)
    n_cols = len(columns)
    q = (question or "").lower()
    intent = infer_intent(q)

    # Single scalar — perfect for a KPI card.
    if n_rows == 1 and n_cols == 1 and _is_numeric(rows[0][0]):
        return "kpi"

    # Single row, few columns → KPI grid (treated as cards on the UI).
    if n_rows == 1 and n_cols <= 4:
        return "cards"

    if n_cols == 2:
        first_numeric = _column_is_numeric(rows, 0)
        second_numeric = _column_is_numeric(rows, 1)
        first_is_date = _column_looks_like_date(columns[0], rows, 0)
        # (label, numeric) — bar/pie/line.
        if not first_numeric and second_numeric:
            if first_is_date or intent in ("trend",):
                return "line_chart"
            # If there are very few groups and the question hints at distribution → pie.
            if n_rows <= 6 and intent in ("breakdown", "summary"):
                return "pie_chart"
            return "bar_chart"
        if first_is_date and second_numeric:
            return "line_chart"

    # Default detail/listing → cards if narrow, table if wide.
    if n_cols <= 3 and n_rows <= 20 and intent in ("list", "general"):
        return "cards"

    return "table"


def build_structured_data(
    columns: list[str],
    rows: list[tuple],
    presentation: str,
    max_rows: int = 50,
) -> dict | None:
    """
    Build a JSON-safe payload for the chosen presentation. Returns None for
    text-only answers (UI just renders the markdown answer in that case).
    """
    if not columns or not rows or presentation == "text":
        return None

    safe_rows = [
        [
            _jsonify(row[i], columns[i] if i < len(columns) else "")
            for i in range(len(row))
        ]
        for row in rows[:max_rows]
    ]

    if presentation == "kpi":
        value = _coerce_number(rows[0][0])
        return {
            "kind": "kpi",
            "label": str(columns[0]).replace("_", " ").title(),
            "value": value if value is not None else rows[0][0],
            "raw_value": _jsonify(rows[0][0], columns[0] if columns else ""),
        }

    if presentation == "cards":
        return {
            "kind": "cards",
            "columns": list(columns),
            "rows": safe_rows,
            "row_count": len(rows),
            "shown_count": len(safe_rows),
        }

    if presentation == "table":
        return {
            "kind": "table",
            "columns": list(columns),
            "rows": safe_rows,
            "row_count": len(rows),
            "shown_count": len(safe_rows),
        }

    if presentation in ("bar_chart", "line_chart", "pie_chart"):
        # Find a label col + value col.
        # If there are >2 columns, pick the first non-numeric as label and the
        # first numeric as value.
        label_idx = 0
        value_idx = 1 if len(columns) > 1 else 0
        for i, col in enumerate(columns):
            if not _column_is_numeric(rows, i):
                label_idx = i
                break
        for i, col in enumerate(columns):
            if i != label_idx and _column_is_numeric(rows, i):
                value_idx = i
                break

        labels = []
        values: list[float | int | None] = []
        for row in rows[:max_rows]:
            if label_idx < len(row):
                lab = row[label_idx]
                col = columns[label_idx] if label_idx < len(columns) else ""
                labels.append(_jsonify(lab, col) if lab is not None else "—")
            if value_idx < len(row):
                values.append(_coerce_number(row[value_idx]))

        return {
            "kind": presentation,
            "label_column": str(columns[label_idx]).replace("_", " ").title(),
            "value_column": str(columns[value_idx]).replace("_", " ").title(),
            "labels": labels,
            "datasets": [{
                "label": str(columns[value_idx]).replace("_", " ").title(),
                "data": values,
            }],
            "row_count": len(rows),
            "shown_count": len(labels),
        }

    return None


# ── Follow-up suggestions ────────────────────────────────────────────────────

def _normalise_text(s: str) -> str:
    return re.sub(r"\s+", " ", (s or "").lower().strip())


def generate_followup_suggestions(
    question: str,
    module: dict,
    qa_retriever=None,
    intent: str = "general",
    max_items: int = 4,
) -> list[str]:
    """
    Build a list of follow-up questions the system is confident it can answer.

    Strategy:
      1. Ask the QA vector retriever for nearby questions (covers everything
         in the curated + learned bank) — these come from REAL examples we can
         answer.
      2. Drop the user's own question and anything we've already shown.
      3. Top up from the module's hand-picked followups so the list is always
         full, even on rare modules.
    """
    asked = _normalise_text(question)
    seen: set[str] = {asked}
    out: list[str] = []

    candidates: list[str] = []
    if qa_retriever is not None:
        try:
            matches = qa_retriever.retrieve(question, top_k=10) or []
            for m in matches:
                q = (m.get("question") or "").strip()
                if q:
                    candidates.append(q)
        except Exception as exc:
            logger.debug("Follow-up retrieval skipped: %s", exc)

    candidates.extend(module.get("followups", []) or [])

    # Add some "compare/trend" upgrades if intent was simple list/count.
    if intent in ("list", "count") and module.get("id") not in (None, "general"):
        label = (module.get("label") or "this module").lower()
        candidates.append(f"show {label} trend over the last 6 months")
        candidates.append(f"compare {label} this month vs last month")

    for q in candidates:
        key = _normalise_text(q)
        if not key or key in seen:
            continue
        seen.add(key)
        out.append(q.rstrip("?") + "?")
        if len(out) >= max_items:
            break

    return out
