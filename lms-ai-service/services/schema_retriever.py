"""
services/schema_retriever.py
Schema RAG: introspects the live MySQL database and stores per-table
'cards' (description + columns + sample rows + foreign keys) in Chroma.

At query time we embed the user question and retrieve only the top-K
most relevant tables, dramatically shrinking the prompt and eliminating
hallucinated table names.
"""

from __future__ import annotations

import json
import logging
from pathlib import Path
from typing import Optional

from services.vector_store import VectorStore

logger = logging.getLogger(__name__)


class SchemaRetriever:
    """Build, persist, and search table-level schema cards."""

    def __init__(
        self,
        store: VectorStore,
        db_service,
        db_name: str,
        table_hints_file: str,
        sample_rows_per_table: int = 3,
    ):
        self.store = store
        self.db = db_service
        self.db_name = db_name
        self.table_hints_file = table_hints_file
        self.sample_rows_per_table = max(0, sample_rows_per_table)
        self._hints: dict[str, str] = self._load_hints()

    # ── Hint file ────────────────────────────────────────────────────────────

    def _load_hints(self) -> dict[str, str]:
        path = Path(self.table_hints_file)
        if not path.exists():
            return {}
        try:
            data = json.loads(path.read_text(encoding="utf-8"))
            return {k: v for k, v in data.items() if not k.startswith("_") and isinstance(v, str)}
        except Exception as exc:
            logger.warning("Failed to parse %s: %s", path, exc)
            return {}

    # ── Bootstrap ────────────────────────────────────────────────────────────

    def seed_if_empty(self, force: bool = False) -> int:
        """Introspect live DB and populate schema collection. Returns # of tables seeded."""
        if not force and self.store.schema_count() > 0:
            logger.info(
                "Schema collection already populated (%d) — skipping seed.",
                self.store.schema_count(),
            )
            return 0

        try:
            columns_by_table = self._fetch_columns()
            fks_by_table = self._fetch_foreign_keys()
        except Exception as exc:
            logger.error("Schema introspection failed: %s", exc)
            return 0

        if not columns_by_table:
            logger.warning("No tables found in database %s.", self.db_name)
            return 0

        if force:
            self.store.reset_schema()

        names: list[str] = []
        cards: list[str] = []
        ddls: list[str] = []

        for table, cols in sorted(columns_by_table.items()):
            ddl = self._build_ddl(table, cols, fks_by_table.get(table, []))
            samples = self._fetch_sample_rows(table) if self.sample_rows_per_table else []
            card = self._build_card(
                table_name=table,
                columns=cols,
                fks=fks_by_table.get(table, []),
                sample_rows=samples,
            )
            names.append(table)
            cards.append(card)
            ddls.append(ddl)

        # Upsert in batches.
        batch_size = 16
        for start in range(0, len(names), batch_size):
            end = start + batch_size
            self.store.upsert_tables(
                table_names=names[start:end],
                cards=cards[start:end],
                ddls=ddls[start:end],
            )

        logger.info("Seeded %d table cards.", len(names))
        return len(names)

    # ── Retrieval ────────────────────────────────────────────────────────────

    def retrieve(self, question: str, top_k: int = 14) -> list[dict]:
        """
        Return top-K most relevant tables for the question.
        Each item: {table, card, ddl, distance}.
        """
        results = self.store.search_tables(query=question, top_k=top_k)
        out: list[dict] = []
        for r in results:
            meta = r.get("metadata") or {}
            out.append(
                {
                    "table": meta.get("table") or r.get("id"),
                    "card": r.get("document") or "",
                    "ddl": meta.get("ddl") or "",
                    "distance": r.get("distance"),
                }
            )
        return out

    def format_schema_for_prompt(self, tables: list[dict], max_chars: int = 7000) -> str:
        """Render retrieved tables as a compact DDL+description block."""
        if not tables:
            return f"Database: {self.db_name}\n(no schema available)"

        lines: list[str] = [f"Database: {self.db_name}", ""]
        used = len("\n".join(lines))

        for t in tables:
            ddl = (t.get("ddl") or "").strip()
            card = (t.get("card") or "").strip()
            block = f"{ddl}\n-- {card}\n" if ddl else f"{card}\n"
            if used + len(block) > max_chars and len(lines) > 2:
                break
            lines.append(block)
            used += len(block)

        return "\n".join(lines).strip()

    # ── Internal: DB introspection ───────────────────────────────────────────

    def _fetch_columns(self) -> dict[str, list[dict]]:
        sql = """
            SELECT
                TABLE_NAME,
                COLUMN_NAME,
                COLUMN_TYPE,
                IS_NULLABLE,
                COLUMN_KEY,
                COLUMN_DEFAULT,
                COLUMN_COMMENT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            ORDER BY TABLE_NAME, ORDINAL_POSITION
        """
        # `max_rows=0` → fetch all rows. information_schema.COLUMNS is huge
        # (one row per column per table), so the default 50-row cap would
        # silently truncate the schema to a handful of tables.
        rows, _columns, _err = self.db.execute(sql, max_rows=0)
        if _err:
            raise RuntimeError(_err)

        by_table: dict[str, list[dict]] = {}
        if rows:
            for row in rows:
                name = row[0]
                by_table.setdefault(name, []).append(
                    {
                        "name": row[1],
                        "type": row[2],
                        "nullable": row[3],
                        "key": row[4],
                        "default": row[5],
                        "comment": row[6] or "",
                    }
                )
        return by_table

    def _fetch_foreign_keys(self) -> dict[str, list[dict]]:
        sql = """
            SELECT
                TABLE_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY TABLE_NAME, COLUMN_NAME
        """
        rows, _columns, _err = self.db.execute(sql, max_rows=0)
        if _err:
            return {}

        out: dict[str, list[dict]] = {}
        if rows:
            for row in rows:
                out.setdefault(row[0], []).append(
                    {
                        "column": row[1],
                        "ref_table": row[2],
                        "ref_column": row[3],
                    }
                )
        return out

    def _fetch_sample_rows(self, table: str) -> list[dict]:
        """Pull a few representative rows. Best-effort only — failures are silent."""
        if self.sample_rows_per_table <= 0:
            return []
        # Backtick the table name to handle reserved words.
        sql = f"SELECT * FROM `{table}` LIMIT {self.sample_rows_per_table}"
        try:
            rows, columns, err = self.db.execute(sql)
        except Exception:
            return []
        if err or not rows:
            return []
        return [dict(zip(columns, row)) for row in rows]

    # ── Internal: rendering ─────────────────────────────────────────────────

    def _build_ddl(self, table: str, cols: list[dict], fks: list[dict]) -> str:
        col_lines = []
        for col in cols:
            line = f"    {col['name']} {col['type']}"
            if col["key"] == "PRI":
                line += " PRIMARY KEY"
            elif col["nullable"] == "NO":
                line += " NOT NULL"
            col_lines.append(line)
        body = ",\n".join(col_lines)
        ddl = f"CREATE TABLE {table} (\n{body}\n);"
        if fks:
            fk_lines = [
                f"-- FK: {table}.{fk['column']} → {fk['ref_table']}.{fk['ref_column']}"
                for fk in fks
            ]
            ddl += "\n" + "\n".join(fk_lines)
        return ddl

    def _build_card(
        self,
        table_name: str,
        columns: list[dict],
        fks: list[dict],
        sample_rows: list[dict],
    ) -> str:
        """
        The 'card' document embedded into Chroma. Designed so that
        keyword + semantic search both rank the right table for a question.
        """
        hint = self._hints.get(table_name, "").strip()
        column_names = [c["name"] for c in columns]
        column_summary = ", ".join(column_names)

        fk_summary = "; ".join(
            f"{fk['column']} → {fk['ref_table']}.{fk['ref_column']}" for fk in fks
        )

        sample_block = ""
        if sample_rows:
            preview = []
            for row in sample_rows:
                items = []
                for k, v in row.items():
                    sval = self._coerce_str(v)
                    if len(sval) > 60:
                        sval = sval[:57] + "..."
                    items.append(f"{k}={sval}")
                preview.append("{ " + ", ".join(items[:8]) + " }")
            sample_block = "Sample rows: " + " | ".join(preview)

        parts = [
            f"Table: {table_name}",
        ]
        if hint:
            parts.append(f"Purpose: {hint}")
        parts.append(f"Columns: {column_summary}")
        if fk_summary:
            parts.append(f"Joins: {fk_summary}")
        if sample_block:
            parts.append(sample_block)
        return "\n".join(parts)

    @staticmethod
    def _coerce_str(value) -> str:
        if value is None:
            return "NULL"
        if isinstance(value, (bytes, bytearray)):
            try:
                return value.decode("utf-8", errors="ignore")
            except Exception:
                return "<bytes>"
        return str(value)
