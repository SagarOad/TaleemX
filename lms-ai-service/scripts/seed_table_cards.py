#!/usr/bin/env python3
"""
scripts/seed_table_cards.py

Introspect the live MySQL database and build a table card per table:
    - table name
    - human-written purpose (from data/table_purpose_hints.json)
    - column list with types
    - foreign key joins
    - 3 sample rows

These cards are embedded into Chroma so the agent retrieves only the
~14 most relevant tables for each question instead of stuffing the
entire schema into the prompt.

Usage:
    python scripts/seed_table_cards.py
    python scripts/seed_table_cards.py --force

Run inside the container:
    docker compose exec lms-ai python scripts/seed_table_cards.py --force
"""

from __future__ import annotations

import argparse
import logging
import os
import sys

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
sys.path.insert(0, ROOT)

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")
log = logging.getLogger("seed_table_cards")


def main():
    parser = argparse.ArgumentParser(description="Seed table cards into Chroma.")
    parser.add_argument("--force", action="store_true", help="Delete existing cards and reseed.")
    parser.add_argument(
        "--samples",
        type=int,
        default=3,
        help="Sample rows per table (0 to skip).",
    )
    args = parser.parse_args()

    from config import Config
    from services.db_service import DBService
    from services.vector_store import VectorStore
    from services.schema_retriever import SchemaRetriever

    db = DBService()
    if not db.ping():
        log.error("Database is unreachable; cannot introspect schema.")
        sys.exit(1)

    store = VectorStore(
        persist_dir=Config.CHROMA_PERSIST_DIR,
        qa_collection=Config.CHROMA_QA_COLLECTION,
        schema_collection=Config.CHROMA_SCHEMA_COLLECTION,
        embedding_provider=Config.EMBEDDING_PROVIDER,
        gemini_api_key=Config.GEMINI_API_KEY,
        gemini_embed_model=Config.EMBEDDING_MODEL_GEMINI,
    )
    if args.force:
        log.warning(
            "Force mode — wiping Chroma (stop the running API first: "
            "docker compose stop lms-ai-service)."
        )
        store.wipe_and_reinit()
    retriever = SchemaRetriever(
        store=store,
        db_service=db,
        db_name=Config.DB_NAME,
        table_hints_file=Config.TABLE_HINTS_FILE,
        sample_rows_per_table=args.samples,
    )
    inserted = retriever.seed_if_empty(force=args.force)
    log.info(
        "Done — %d table cards inserted (collection total: %d).",
        inserted,
        store.schema_count(),
    )


if __name__ == "__main__":
    main()
