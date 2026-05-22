"""
services/sql_agent.py

Agentic NL→SQL loop with:

    plan_query → retrieve context (QA + schema)
              → generate_sql
              → validate
              → execute
              → critique (does this answer the question?)
              → if not satisfied: regenerate with critique feedback (max N iters)
              → format answer

Designed to fail soft: every step has a deterministic fallback so the user
never sees a stack trace, and the SQL validator is the security backstop.
"""

from __future__ import annotations

import logging
import re
from dataclasses import dataclass, field
from typing import Optional

from config import Config
from services.answer_composer import compose_answer
from services.executive_briefing import is_executive_scope_question, run_executive_briefing
from services.insights import (
    build_structured_data,
    detect_presentation,
    generate_followup_suggestions,
    infer_intent,
    infer_module,
)

logger = logging.getLogger(__name__)


@dataclass
class AgentTrace:
    """Per-request debug trace. Useful for logging and the /admin/trace endpoint."""

    question: str
    plan: str = ""
    retrieved_tables: list[str] = field(default_factory=list)
    retrieved_examples: list[str] = field(default_factory=list)
    iterations: list[dict] = field(default_factory=list)
    final_sql: str = ""
    final_status: str = "pending"

    def to_dict(self) -> dict:
        return {
            "question": self.question,
            "plan": self.plan,
            "retrieved_tables": self.retrieved_tables,
            "retrieved_examples": self.retrieved_examples,
            "iterations": self.iterations,
            "final_sql": self.final_sql,
            "final_status": self.final_status,
        }


@dataclass
class AgentResult:
    """What the agent hands back to the caller."""

    answer: str
    sql: str = ""
    columns: list[str] = field(default_factory=list)
    rows: list[tuple] = field(default_factory=list)
    status: str = "ok"     # ok | empty | invalid_sql | db_error | not_data | error
    # How the SQL was produced. Drives whether the human-in-the-loop feedback
    # endpoint will accept this answer for the learned bank:
    #   curated_trusted | learned_trusted | llm | deterministic | manual
    source: str = "llm"
    # UI hints filled in after the agent has rows in hand. The frontend uses
    # these to decide whether to render a chart, KPI card, table, etc.
    presentation: str = "text"   # text | kpi | cards | table | bar_chart | line_chart | pie_chart
    structured_data: Optional[dict] = None
    intent: str = "general"
    module: str = "general"
    module_label: str = "General"
    suggestions: list[str] = field(default_factory=list)
    trace: Optional[AgentTrace] = None


class SQLAgent:
    """
    Orchestrates the multi-step reasoning loop. Depends on:

    - ai_service: LLM caller (Gemini + Groq fallback) + critique + formatting
    - db_service: read-only MySQL executor
    - sql_validator: SELECT-only safety net
    - qa_retriever: semantic few-shot retriever (Chroma)
    - schema_retriever: table-card retriever (Chroma)
    """

    def __init__(
        self,
        ai_service,
        db_service,
        sql_validator,
        qa_retriever,
        schema_retriever,
    ):
        self.ai = ai_service
        self.db = db_service
        self.validator = sql_validator
        self.qa = qa_retriever
        self.schema = schema_retriever

        self.max_iterations = max(1, Config.AGENT_MAX_ITERATIONS)
        self.enable_critic = Config.AGENT_ENABLE_CRITIC
        self.enable_plan = Config.AGENT_ENABLE_PLAN

    # ── Public entry ─────────────────────────────────────────────────────────

    def answer(self, question: str) -> AgentResult:
        trace = AgentTrace(question=question)

        # 1) Fast path: app-flow / how-to questions go to the manual.
        if self.ai.is_app_flow_question(question):
            answer = self.ai.answer_from_manual(question)
            trace.final_status = "manual"
            return self._enrich(
                AgentResult(answer=answer, status="manual", source="manual", trace=trace)
            )

        # 2) Executive briefing — broad school-performance questions (multi-query dashboard).
        if is_executive_scope_question(question):
            return self._executive_briefing_answer(question, trace)

        # 3) Deterministic shortcut for high-confidence patterns.
        deterministic_sql = self.ai.deterministic_sql(question)
        if deterministic_sql:
            return self._execute_and_format(
                question=question,
                sql=deterministic_sql,
                trace=trace,
                source="deterministic",
            )

        # 4) Retrieve few-shot + relevant schema.
        examples = self.qa.retrieve(question, top_k=Config.QA_TOP_K)
        tables = self.schema.retrieve(question, top_k=Config.SCHEMA_TOP_K)

        trace.retrieved_examples = [ex.get("question", "") for ex in examples]
        trace.retrieved_tables = [t.get("table", "") for t in tables]

        # 3a) Trusted few-shot fast-path: if our nearest example is a very
        #     close semantic match, try its SQL directly. Curated and learned
        #     pairs get DIFFERENT trust thresholds (curated is more lenient,
        #     learned is stricter). On failure, fall through to the LLM with
        #     the error as feedback — never dead-end.
        feedback_for_next_attempt = ""
        trusted_failure_sql = ""

        if examples:
            trust_threshold = Config.QA_TRUST_MATCH_DISTANCE
            learned_threshold = Config.QA_LEARNED_TRUST_DISTANCE
            for rank, top in enumerate(examples[:5]):
                top_distance = top.get("distance")
                top_source = top.get("source", "curated")
                threshold = (
                    learned_threshold if top_source == "learned" else trust_threshold
                )
                if not (
                    isinstance(top_distance, (int, float))
                    and top_distance <= threshold
                    and top.get("sql")
                ):
                    continue
                trusted_sql = top["sql"]
                if not self._trusted_sql_matches_question(question, trusted_sql):
                    logger.info(
                        "Skipping trusted match id=%s — SQL does not match question intent.",
                        top.get("id"),
                    )
                    continue
                trusted_id = top.get("id")
                logger.info(
                    "Trusted %s match #%d (distance=%.3f, id=%s).",
                    top_source, rank + 1, top_distance, trusted_id,
                )
                outcome = self._try_execute(
                    question, trusted_sql, trace,
                    source=f"{top_source}_trusted:{trusted_id}",
                    result_source=("learned_trusted" if top_source == "learned"
                                   else "curated_trusted"),
                )
                if outcome["status"] == "ok":
                    return outcome["result"]
                if rank == 0:
                    trusted_failure_sql = trusted_sql
                    if outcome["status"] == "db_error":
                        feedback_for_next_attempt = (
                            f"A {top_source} example failed: {outcome['error']}. "
                            f"SQL: {trusted_sql}. Fix using schema."
                        )
                    elif outcome["status"] == "empty":
                        feedback_for_next_attempt = (
                            f"Example SQL returned 0 rows: {trusted_sql}. "
                            "Broaden filters or match attendence_type codes."
                        )
                    logger.info("Trusted fast-path %s — next candidate or LLM.", outcome["status"])

        examples_text = self.qa.format_examples_for_prompt(examples)
        schema_text = self.schema.format_schema_for_prompt(tables)

        # 4) Plan (optional): one short paragraph explaining how we will satisfy
        #    the question. Off by default since it costs another LLM round-trip.
        plan = ""
        if self.enable_plan:
            plan = self._plan(question, schema_text, examples_text)
        trace.plan = plan

        # 5) Generate → validate → execute → critique loop.
        last_sql = trusted_failure_sql
        last_rows: list[tuple] = []
        last_columns: list[str] = []
        last_status = "error"

        for iteration in range(1, self.max_iterations + 1):
            sql = self.ai.generate_sql_with_context(
                question=question,
                plan=plan,
                schema_text=schema_text,
                examples_text=examples_text,
                prior_feedback=feedback_for_next_attempt,
            )
            iter_log = {"iteration": iteration, "sql": sql, "status": "generated"}

            if not sql:
                iter_log["status"] = "model_returned_empty"
                trace.iterations.append(iter_log)
                feedback_for_next_attempt = (
                    "Previous attempt returned NOT_DATA_QUESTION but the user clearly "
                    "wants data. Pick the single best table from the schema and write a SELECT."
                )
                continue

            is_valid, error_msg = self.validator.validate(sql)
            if not is_valid:
                iter_log["status"] = "invalid_sql"
                iter_log["error"] = error_msg
                trace.iterations.append(iter_log)
                feedback_for_next_attempt = (
                    f"The previous SQL was rejected by the safety validator: {error_msg}. "
                    "Return ONLY a single SELECT statement, no markdown, balanced parentheses."
                )
                continue

            sql = self.validator.sanitize_limit(sql, Config.DB_MAX_ROWS)
            rows, columns, db_error = self.db.execute(sql)
            iter_log["sql"] = sql

            if db_error:
                iter_log["status"] = "db_error"
                iter_log["error"] = db_error
                trace.iterations.append(iter_log)
                feedback_for_next_attempt = (
                    f"The previous SQL failed with database error: {db_error}. "
                    "Re-read the schema carefully and use only columns that exist."
                )
                last_status = "db_error"
                last_sql = sql
                continue

            last_sql = sql
            last_rows = rows
            last_columns = columns

            if not rows:
                iter_log["status"] = "empty"
                trace.iterations.append(iter_log)
                # Broaden the search on next attempt (LIKE, fewer filters).
                feedback_for_next_attempt = (
                    "The previous SQL executed but returned 0 rows. "
                    "Try a broader query: use LIKE with wildcards on text filters, "
                    "drop overly restrictive date filters, and double-check column names."
                )
                last_status = "empty"
                continue

            # We have rows. Optionally critique.
            if self.enable_critic and iteration < self.max_iterations:
                verdict = self.ai.critique_result(
                    question=question,
                    sql=sql,
                    columns=columns,
                    rows=rows,
                )
                iter_log["status"] = verdict.get("decision", "ok")
                iter_log["critique"] = verdict.get("reason", "")
                trace.iterations.append(iter_log)

                if verdict.get("decision") == "regenerate":
                    feedback_for_next_attempt = (
                        f"Critique of previous SQL: {verdict.get('reason', '')}. "
                        "Produce an improved SELECT that addresses this."
                    )
                    continue

            iter_log["status"] = "satisfied"
            trace.iterations.append(iter_log)
            trace.final_sql = sql
            trace.final_status = "ok"
            answer = self._render_answer(question, columns, rows)
            return self._enrich(AgentResult(
                answer=answer,
                sql=sql,
                columns=columns,
                rows=rows,
                status="ok",
                source="llm",
                trace=trace,
            ))

        # Loop exhausted without success.
        trace.final_sql = last_sql
        trace.final_status = last_status

        if last_status == "empty":
            msg = self.ai.append_capability_suggestions(
                "No records were found for your query. "
                "Try the exact spelling of a student or staff name, "
                "or broaden the filter (class, date range).",
                question,
            )
            return self._enrich(AgentResult(
                answer=msg, sql=last_sql, columns=last_columns, rows=last_rows,
                status="empty", trace=trace,
            ))
        if last_status == "db_error":
            msg = self.ai.append_capability_suggestions(
                "I ran into a database error while fetching that information.",
                question,
            )
            return self._enrich(
                AgentResult(answer=msg, sql=last_sql, status="db_error", trace=trace)
            )

        msg = self.ai.append_capability_suggestions(
            "I could not produce a reliable answer for that request. "
            "Please rephrase using clearer names, grades, or module names.",
            question,
        )
        return self._enrich(AgentResult(answer=msg, status="error", trace=trace))

    # ── Internal helpers ────────────────────────────────────────────────────

    def _plan(self, question: str, schema_text: str, examples_text: str) -> str:
        """Ask the model to produce a 1–3 line plan. Cheap, helps grounding."""
        try:
            return self.ai.plan_query(
                question=question, schema_text=schema_text, examples_text=examples_text
            )
        except Exception as exc:
            logger.warning("Plan step failed (continuing without): %s", exc)
            return ""

    def _try_execute(
        self,
        question: str,
        sql: str,
        trace: AgentTrace,
        source: str,
        result_source: str = "llm",
    ) -> dict:
        """
        Try a single SQL without committing to it as the final answer.
        Returns a dict: {status: ok|empty|db_error|invalid_sql, error?, result?}
        On `ok`, `result` holds a ready-to-return AgentResult; otherwise the
        caller decides whether to fall through to an LLM loop.
        """
        iter_log = {"iteration": 0, "source": source, "sql": sql, "status": "pending"}

        is_valid, error_msg = self.validator.validate(sql)
        if not is_valid:
            iter_log["status"] = "invalid_sql"
            iter_log["error"] = error_msg
            trace.iterations.append(iter_log)
            return {"status": "invalid_sql", "error": error_msg}

        sanitized = self.validator.sanitize_limit(sql, Config.DB_MAX_ROWS)
        iter_log["sql"] = sanitized
        rows, columns, db_error = self.db.execute(sanitized)

        if db_error:
            iter_log["status"] = "db_error"
            iter_log["error"] = db_error
            trace.iterations.append(iter_log)
            return {"status": "db_error", "error": db_error}

        if not rows:
            iter_log["status"] = "empty"
            trace.iterations.append(iter_log)
            return {"status": "empty"}

        iter_log["status"] = "satisfied"
        trace.iterations.append(iter_log)
        trace.final_sql = sanitized
        trace.final_status = "ok"
        result = self._enrich(AgentResult(
            answer=self._render_answer(question, columns, rows),
            sql=sanitized, columns=columns, rows=rows, status="ok",
            source=result_source, trace=trace,
        ))
        return {"status": "ok", "result": result}

    def _execute_and_format(
        self, question: str, sql: str, trace: AgentTrace, source: str
    ) -> AgentResult:
        """Execute a single SQL (e.g., deterministic) without the agent loop."""
        iter_log = {"iteration": 1, "source": source, "sql": sql}
        is_valid, error_msg = self.validator.validate(sql)
        if not is_valid:
            iter_log["status"] = "invalid_sql"
            iter_log["error"] = error_msg
            trace.iterations.append(iter_log)
            trace.final_status = "invalid_sql"
            msg = self.ai.append_capability_suggestions(
                "I could not produce a valid query for that request. Please rephrase.",
                question,
            )
            return self._enrich(
                AgentResult(answer=msg, sql=sql, status="invalid_sql", trace=trace)
            )

        sql = self.validator.sanitize_limit(sql, Config.DB_MAX_ROWS)
        rows, columns, db_error = self.db.execute(sql)
        iter_log["sql"] = sql

        if db_error:
            iter_log["status"] = "db_error"
            iter_log["error"] = db_error
            trace.iterations.append(iter_log)
            trace.final_sql = sql
            trace.final_status = "db_error"
            msg = self.ai.append_capability_suggestions(
                "I ran into a database error while fetching that information.",
                question,
            )
            return self._enrich(
                AgentResult(answer=msg, sql=sql, status="db_error", trace=trace)
            )

        if not rows:
            iter_log["status"] = "empty"
            trace.iterations.append(iter_log)
            trace.final_sql = sql
            trace.final_status = "empty"
            msg = self.ai.append_capability_suggestions(
                "No records were found for your query.",
                question,
            )
            return self._enrich(AgentResult(
                answer=msg, sql=sql, columns=columns, rows=rows,
                status="empty", trace=trace,
            ))

        iter_log["status"] = "satisfied"
        trace.iterations.append(iter_log)
        trace.final_sql = sql
        trace.final_status = "ok"
        # The only caller passes source="deterministic"; carry it through.
        result_source = source if source in ("deterministic",) else "llm"
        return self._enrich(AgentResult(
            answer=self._render_answer(question, columns, rows),
            sql=sql, columns=columns, rows=rows, status="ok",
            source=result_source, trace=trace,
        ))

    # If the LLM disclaims having the data, we should NOT show the SQL's
    # row count as a KPI / table — that's misleading (e.g. the fast-path
    # ran "teachers count" for "how many branches", got 5, and the LLM
    # correctly refused). Detect a few common refusal phrases.
    _REFUSAL_PATTERNS = (
        "cannot answer", "can't answer", "do not have", "don't have",
        "no information", "no records were found", "is not available",
        "not enough information", "i'm sorry, i cannot", "i am sorry, i cannot",
    )

    def _looks_like_refusal(self, answer: str) -> bool:
        a = (answer or "").lower()
        return any(p in a for p in self._REFUSAL_PATTERNS)

    @staticmethod
    def _trusted_sql_matches_question(question: str, sql: str) -> bool:
        """Block obvious wrong fast-path matches (e.g. teacher count for departments)."""
        q = (question or "").lower()
        s = (sql or "").lower()
        if "department" in q or "departments" in q:
            if "teacher_count" in s and "department" not in s:
                return False
            if "staff_roles" in s and "department" not in s and "department_name" not in s:
                return False
        if ("how many" in q and "teacher" in q and "department" not in q):
            if "department_name" in s and "teacher_count" not in s:
                return False
        return True

    def _enrich(self, result: AgentResult) -> AgentResult:
        """
        Decorate a successful result with UI hints + follow-up suggestions.
        Pure-Python and deterministic — no extra LLM calls. Safe to call on
        every success path.
        """
        question = (result.trace.question if result.trace else "") or ""
        module = infer_module(question)
        intent = infer_intent(question)
        result.module = module.get("id", "general")
        result.module_label = module.get("label", "General")
        result.intent = intent

        if result.presentation == "executive_briefing" and result.structured_data:
            pass  # Already fully built by executive briefing runner.
        elif (
            result.status == "ok"
            and result.rows
            and not self._looks_like_refusal(result.answer)
        ):
            try:
                result.presentation = detect_presentation(question, result.columns, result.rows)
                result.structured_data = build_structured_data(
                    result.columns, result.rows, result.presentation,
                )
            except Exception as exc:
                logger.debug("Presentation detection failed: %s", exc)
                result.presentation = "text"
                result.structured_data = None
        elif result.presentation != "executive_briefing":
            result.presentation = "text"
            if not result.structured_data:
                result.structured_data = None

        try:
            result.suggestions = generate_followup_suggestions(
                question=question,
                module=module,
                qa_retriever=getattr(self, "qa", None),
                intent=intent,
                max_items=4,
            )
        except Exception as exc:
            logger.debug("Follow-up generation failed: %s", exc)
            result.suggestions = []

        # When the UI will render a table/cards block, keep the text bubble short
        # instead of duplicating every row in an LLM paragraph (common with HTML
        # homework descriptions).
        if result.structured_data and result.structured_data.get("kind") in (
            "table", "cards",
        ):
            if not self._answer_already_informative(result.answer):
                result.answer = self._compact_answer_for_rich_ui(result)
        return result

    @staticmethod
    def _answer_already_informative(answer: str) -> bool:
        """Keep composed narratives (departments list, risk summary) instead of generic row counts."""
        a = (answer or "").strip()
        if len(a) < 30:
            return False
        low = a.lower()
        if "**" in a and any(
            k in low
            for k in (
                "department", "risk analysis", "top gap", "school has",
                "performance snapshot", "attendance",
            )
        ):
            return True
        if low.startswith("the school has") and "department" in low:
            return True
        return False

    def _executive_briefing_answer(self, question: str, trace: AgentTrace) -> AgentResult:
        """Multi-query executive dashboard for whole-school performance questions."""
        briefing = run_executive_briefing(self.db, self.validator, question)
        trace.final_status = briefing.status
        if briefing.status != "ok":
            msg = self.ai.append_capability_suggestions(
                briefing.narrative or "Could not build the school performance briefing.",
                question,
            )
            return self._enrich(
                AgentResult(answer=msg, status="error", source="executive_briefing", trace=trace)
            )

        trace.final_sql = (briefing.sql_used[0] if briefing.sql_used else "")
        trace.iterations.append({
            "iteration": 0,
            "source": "executive_briefing",
            "status": "satisfied",
            "queries": len(briefing.sql_used),
        })

        module = infer_module(question)
        result = AgentResult(
            answer=briefing.narrative,
            sql="; ".join(briefing.sql_used[:2]),
            status="ok",
            source="executive_briefing",
            presentation="executive_briefing",
            structured_data=briefing.structured_data,
            module=module.get("id", "school_performance"),
            module_label=module.get("label", "School Performance & Risk"),
            intent="summary",
            suggestions=[
                "give me risk analysis of the school",
                "where is our school lacking",
                "what is our profit this month",
                "show attendance summary per student this month",
            ],
            trace=trace,
        )
        return self._enrich(result)

    def _compact_answer_for_rich_ui(self, result: AgentResult) -> str:
        sd = result.structured_data or {}
        n = int(sd.get("row_count") or sd.get("shown_count") or 0)
        cols = [str(c).lower() for c in (sd.get("columns") or result.columns or [])]
        if n <= 0:
            return "No records were found for your query."
        if "department_name" in cols:
            if n == 1:
                return "The school has **1** department (see below)."
            return f"The school has **{n}** departments. Names are listed below."
        label = (result.module_label or "record").strip().lower()
        if n == 1:
            return f"I found 1 {label} record. Details are below."
        return f"I found {n} {label} records. Details are in the table below."

    def _render_answer(self, question: str, columns: list[str], rows: list[tuple]) -> str:
        """Pick deterministic composer, structured list, or LLM narrative."""
        composed = compose_answer(question, columns, rows)
        if composed:
            return composed
        if self.ai.should_use_structured_answer(question):
            return self.ai.structured_answer_from_rows(question, columns, rows)
        try:
            return self.ai.format_results(question, columns, rows)
        except Exception as exc:
            logger.warning("Narrative formatting failed; falling back: %s", exc)
            return self.ai.structured_answer_from_rows(question, columns, rows)
