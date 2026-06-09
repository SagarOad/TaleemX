"""
services/qa_retriever.py
Semantic few-shot retrieval over curated AND learned (question → SQL) pairs.

Two collections live in Chroma:
  - lms_qa_pairs    → curated, hand-verified (loaded from data/qa_pairs.jsonl)
  - lms_qa_learned  → grown at runtime from 👍 feedback (data/qa_pairs_learned.jsonl)

At query time both are searched; results carry a `source` field so the
agent can apply a stricter trust threshold to learned pairs. Curated wins
on ties so a user-marked pair never overrides a hand-verified example.
"""

from __future__ import annotations

import hashlib
import json
import logging
import os
import threading
import time
from pathlib import Path
from typing import Iterable

from services.vector_store import VectorStore

logger = logging.getLogger(__name__)


class QARetriever:
    """Few-shot example retriever backed by the QA + learned collections in Chroma."""

    def __init__(
        self,
        store: VectorStore,
        qa_pairs_file: str,
        learned_qa_file: str | None = None,
        extra_qa_files: list[str] | None = None,
    ):
        self.store = store
        self.qa_pairs_file = qa_pairs_file
        self.learned_qa_file = learned_qa_file
        self.extra_qa_files = [p for p in (extra_qa_files or []) if p]
        self._learned_lock = threading.Lock()

    # ── Bootstrap ────────────────────────────────────────────────────────────

    def seed_if_empty(self, force: bool = False) -> int:
        """
        Load curated QA pairs from JSONL into Chroma. No-op if collection is
        already populated, unless force=True.
        Returns the number of pairs inserted (0 if skipped).
        """
        if not force and self.store.qa_count() > 0:
            logger.info(
                "QA collection already populated (%d) — skipping seed.",
                self.store.qa_count(),
            )
            return 0

        paths = [self.qa_pairs_file, *self.extra_qa_files]
        ids, questions, sqls, tags = self._load_rows_from_files(paths)
        if not ids:
            logger.warning("No QA rows parsed from %s.", paths)
            return 0

        if force:
            self.store.reset_qa()

        self._upsert_qa_batches(ids, questions, sqls, tags)
        logger.info("Seeded %d QA pair(s) from %d file(s).", len(ids), len(paths))
        return len(ids)

    def upsert_extra_files(self) -> int:
        """
        Upsert business/extra JSONL pairs without resetting the collection.
        Safe to run on every startup so new examples reach existing deployments.
        """
        if not self.extra_qa_files:
            return 0
        ids, questions, sqls, tags = self._load_rows_from_files(self.extra_qa_files)
        if not ids:
            return 0
        self._upsert_qa_batches(ids, questions, sqls, tags)
        logger.info("Upserted %d extra QA pair(s) from %s.", len(ids), self.extra_qa_files)
        return len(ids)

    # ── Retrieval ────────────────────────────────────────────────────────────

    def retrieve(self, question: str, top_k: int = 12) -> list[dict]:
        """
        Return up to top_k semantically similar (question, sql) examples,
        drawn from BOTH the curated and learned collections. Each item has
        a `source` field so callers can apply different trust thresholds.

        Shape: {id, question, sql, tags, distance, source: "curated"|"learned"}.
        Curated wins ties with learned at the same distance.
        """
        question = (question or "").strip()
        if not question:
            return []

        # Fan out the retrieval: curated gets the main quota, learned gets a
        # smaller side-channel quota so it can still influence the top-K but
        # never floods it.
        learned_quota = max(2, top_k // 3)
        curated_results = self.store.search_qa(query=question, top_k=top_k)
        learned_results = self.store.search_learned(query=question, top_k=learned_quota)

        merged: list[dict] = []
        for r in curated_results:
            meta = r.get("metadata") or {}
            sql = (meta.get("sql") or "").strip()
            if not sql:
                continue
            merged.append({
                "id": r.get("id"),
                "question": r.get("document") or "",
                "sql": sql,
                "tags": [t for t in (meta.get("tags") or "").split(",") if t],
                "distance": r.get("distance"),
                "source": "curated",
            })
        for r in learned_results:
            meta = r.get("metadata") or {}
            sql = (meta.get("sql") or "").strip()
            if not sql:
                continue
            merged.append({
                "id": r.get("id"),
                "question": r.get("document") or "",
                "sql": sql,
                "tags": [t for t in (meta.get("tags") or "").split(",") if t],
                "distance": r.get("distance"),
                "source": "learned",
            })

        # Sort by distance ascending. On exact-tie distances, curated wins
        # (key tuple: (distance, 0_if_curated_else_1)). Practically distances
        # rarely tie, but the rule makes the policy explicit.
        def _sort_key(ex: dict):
            d = ex.get("distance")
            if not isinstance(d, (int, float)):
                d = float("inf")
            tiebreak = 0 if ex.get("source") == "curated" else 1
            return (d, tiebreak)

        merged.sort(key=_sort_key)
        merged = self._boost_by_keywords(question, merged)
        return merged[:top_k]

    def _boost_by_keywords(self, question: str, examples: list[dict]) -> list[dict]:
        q = (question or "").lower()
        if not q:
            return examples
        scored = []
        for ex in examples:
            d = ex.get("distance")
            if not isinstance(d, (int, float)):
                d = float("inf")
            boost = 0.0
            tags = set(ex.get("tags") or [])
            if "student" in q and "staff" not in q and "staff" in tags and "students" not in tags:
                boost -= 0.06
            if "attendance" in q and tags & {"attendance", "summary", "students"}:
                boost += 0.08
            if any(
                k in q
                for k in (
                    "exam result", "exam results", "results of grade",
                    "result of grade", "marksheet", "obtain marks",
                )
            ):
                if tags & {"exam", "results", "grade", "exam_group"}:
                    boost += 0.12
                if tags & {"schedule"} and "result" not in q:
                    boost -= 0.10
            if "leave" in q and tags & {"leave", "approved"}:
                boost += 0.08
            if any(
                k in q
                for k in (
                    "school performing", "risk analysis", "risk factor", "top risk",
                    "operational risk", "lacking", "what can we improve", "areas to improve",
                    "should we improve", "what to improve",
                )
            ):
                if tags & {"business", "performance", "risk", "gaps", "improve"}:
                    boost += 0.07
            if any(k in q for k in ("department", "departments", "designation", "human resource", " hr ", "headcount")):
                if tags & {"hr", "department", "designation", "staff", "headcount"}:
                    boost += 0.10
                if "teacher" in tags and "count" in tags and "department" not in tags:
                    boost -= 0.14
            if ("we have" in q or "do we have" in q) and "department" in q:
                if tags & {"department", "hr"}:
                    boost += 0.12
                if tags & {"teacher", "count"} and "department" not in tags:
                    boost -= 0.15
            if "online course" in q or "online courses" in q:
                if tags & {"online_course", "question_bank"}:
                    boost += 0.15
                if tags & {"exam", "schedule"} and "online_course" not in tags:
                    boost -= 0.12
            tiebreak = 0 if ex.get("source") == "curated" else 1
            scored.append((d - boost, tiebreak, ex))
        scored.sort(key=lambda t: (t[0], t[1]))
        return [ex for _, _, ex in scored]

    # ── Human-in-the-loop (learned pairs from 👍 feedback) ──────────────────

    def add_learned_pair(
        self,
        question: str,
        sql: str,
        source_request_id: str = "",
        verdict_note: str = "",
        original_source: str = "llm",
    ) -> dict:
        """
        Append a (question, sql) pair to the learned Chroma collection AND
        to the on-disk learned JSONL file. Idempotent on (question, sql)
        hash so multiple 👍 clicks on the same answer don't duplicate.

        Returns {ok, learned, id, reason}.
        """
        question = (question or "").strip()
        sql = (sql or "").strip()
        if not question or not sql:
            return {"ok": False, "learned": False, "reason": "empty question or sql"}

        # Deterministic ID = stable hash of question+sql so re-feedback is a no-op.
        pair_hash = hashlib.sha1(
            (question.lower() + "||" + sql).encode("utf-8")
        ).hexdigest()[:16]
        pair_id = f"learned_{pair_hash}"

        metadata = {
            "sql": sql,
            "source_request_id": source_request_id,
            "verdict_note": verdict_note,
            "original_source": original_source,
            "learned_at": int(time.time()),
        }

        with self._learned_lock:
            # 1) Write into Chroma so it's searchable immediately.
            self.store.upsert_learned(
                ids=[pair_id],
                questions=[question],
                sqls=[sql],
                metadatas=[metadata],
            )
            # 2) Append to JSONL so it survives chroma volume wipes.
            self._append_to_learned_file(pair_id, question, sql, metadata)

        logger.info("Learned new pair id=%s for question=%r", pair_id, question[:80])
        return {"ok": True, "learned": True, "id": pair_id}

    def seed_learned_from_file(self) -> int:
        """
        Re-load any persisted learned pairs from disk into the Chroma
        collection. Called once at startup if the Chroma volume was wiped
        but the on-disk JSONL log survived.
        """
        if not self.learned_qa_file:
            return 0
        path = Path(self.learned_qa_file)
        if not path.exists() or self.store.learned_count() > 0:
            return 0

        ids: list[str] = []
        questions: list[str] = []
        sqls: list[str] = []
        metadatas: list[dict] = []
        with path.open("r", encoding="utf-8") as fh:
            for line in fh:
                line = line.strip()
                if not line:
                    continue
                try:
                    row = json.loads(line)
                except json.JSONDecodeError:
                    continue
                q = (row.get("question") or "").strip()
                sql = (row.get("sql") or "").strip()
                pid = str(row.get("id") or "").strip()
                if not (q and sql and pid):
                    continue
                ids.append(pid)
                questions.append(q)
                sqls.append(sql)
                metadatas.append(row.get("metadata") or {"sql": sql})

        if not ids:
            return 0
        self.store.upsert_learned(
            ids=ids, questions=questions, sqls=sqls, metadatas=metadatas
        )
        logger.info("Rehydrated %d learned pair(s) from %s.", len(ids), path)
        return len(ids)

    # ── Internal ─────────────────────────────────────────────────────────────

    def _load_rows_from_files(
        self, paths: Iterable[str]
    ) -> tuple[list[str], list[str], list[str], list[list[str]]]:
        ids: list[str] = []
        questions: list[str] = []
        sqls: list[str] = []
        tags: list[list[str]] = []
        seen: set[str] = set()
        for file_path in paths:
            path = Path(file_path)
            if not path.exists():
                logger.warning("QA pairs file not found at %s — skipping.", path)
                continue
            for row_id, question, sql, row_tags in self._parse_jsonl(path):
                if row_id in seen:
                    continue
                seen.add(row_id)
                ids.append(row_id)
                questions.append(question)
                sqls.append(sql)
                tags.append(row_tags)
        return ids, questions, sqls, tags

    def _parse_jsonl(self, path: Path) -> Iterable[tuple[str, str, str, list[str]]]:
        with path.open("r", encoding="utf-8") as fh:
            for line_num, line in enumerate(fh, start=1):
                line = line.strip()
                if not line or line.startswith("#"):
                    continue
                try:
                    row = json.loads(line)
                except json.JSONDecodeError as exc:
                    logger.warning("Invalid JSON in %s line %d: %s", path.name, line_num, exc)
                    continue
                row_id = str(row.get("id") or f"qa_{path.stem}_{line_num}")
                question = (row.get("question") or "").strip()
                sql = (row.get("sql") or "").strip()
                row_tags = [str(t) for t in (row.get("tags") or [])]
                if question and sql:
                    yield row_id, question, sql, row_tags

    def _upsert_qa_batches(
        self,
        ids: list[str],
        questions: list[str],
        sqls: list[str],
        tags: list[list[str]],
    ) -> None:
        batch_size = 32
        for start in range(0, len(ids), batch_size):
            end = start + batch_size
            self.store.upsert_qa(
                ids=ids[start:end],
                questions=questions[start:end],
                sqls=sqls[start:end],
                tags=tags[start:end],
            )

    def _append_to_learned_file(
        self, pair_id: str, question: str, sql: str, metadata: dict
    ):
        if not self.learned_qa_file:
            return
        try:
            path = Path(self.learned_qa_file)
            path.parent.mkdir(parents=True, exist_ok=True)
            entry = {
                "id": pair_id,
                "question": question,
                "sql": sql,
                "metadata": metadata,
            }
            with path.open("a", encoding="utf-8") as fh:
                fh.write(json.dumps(entry, ensure_ascii=False) + "\n")
        except Exception as exc:
            logger.warning("Could not append learned pair to %s: %s",
                           self.learned_qa_file, exc)

    def format_examples_for_prompt(self, examples: list[dict], max_chars: int = 4500) -> str:
        """Render examples as a compact NL → SQL prompt block."""
        if not examples:
            return "(no similar examples available)"

        lines: list[str] = []
        used = 0
        for ex in examples:
            block = (
                f"# Q: {ex['question']}\n"
                f"# SQL:\n{ex['sql']}\n"
            )
            if used + len(block) > max_chars and lines:
                break
            lines.append(block)
            used += len(block)
        return "\n".join(lines).strip()
