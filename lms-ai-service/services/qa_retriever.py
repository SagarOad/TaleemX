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
    ):
        self.store = store
        self.qa_pairs_file = qa_pairs_file
        self.learned_qa_file = learned_qa_file
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

        path = Path(self.qa_pairs_file)
        if not path.exists():
            logger.warning("QA pairs file not found at %s — skipping seed.", path)
            return 0

        ids: list[str] = []
        questions: list[str] = []
        sqls: list[str] = []
        tags: list[list[str]] = []

        with path.open("r", encoding="utf-8") as fh:
            for line_num, line in enumerate(fh, start=1):
                line = line.strip()
                if not line or line.startswith("#"):
                    continue
                try:
                    row = json.loads(line)
                except json.JSONDecodeError as exc:
                    logger.warning("Invalid JSON in qa_pairs.jsonl line %d: %s", line_num, exc)
                    continue

                row_id = str(row.get("id") or f"qa_{line_num}")
                question = (row.get("question") or "").strip()
                sql = (row.get("sql") or "").strip()
                row_tags = row.get("tags") or []
                if not question or not sql:
                    continue

                ids.append(row_id)
                questions.append(question)
                sqls.append(sql)
                tags.append([str(t) for t in row_tags])

        if force:
            self.store.reset_qa()

        if not ids:
            logger.warning("No QA rows parsed from %s.", path)
            return 0

        # Upsert in batches to keep embed requests small.
        batch_size = 32
        for start in range(0, len(ids), batch_size):
            end = start + batch_size
            self.store.upsert_qa(
                ids=ids[start:end],
                questions=questions[start:end],
                sqls=sqls[start:end],
                tags=tags[start:end],
            )

        logger.info("Seeded %d QA pairs from %s.", len(ids), path)
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
        return merged[:top_k]

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
