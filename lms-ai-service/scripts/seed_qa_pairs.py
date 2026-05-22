#!/usr/bin/env python3
"""
scripts/seed_qa_pairs.py

Bootstrap (or force-rebuild) the QA pairs collection in Chroma from
data/qa_pairs.jsonl.

Usage:
    python scripts/seed_qa_pairs.py            # seed only if empty
    python scripts/seed_qa_pairs.py --force    # delete + rebuild

Run inside the container:
    docker compose exec lms-ai python scripts/seed_qa_pairs.py --force
"""

from __future__ import annotations

import argparse
import logging
import os
import sys

# Make project root importable when running from anywhere.
ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
sys.path.insert(0, ROOT)

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")
log = logging.getLogger("seed_qa_pairs")


def main():
    parser = argparse.ArgumentParser(description="Seed curated QA pairs into Chroma.")
    parser.add_argument("--force", action="store_true", help="Delete existing pairs and reseed.")
    args = parser.parse_args()

    from config import Config
    from services.vector_store import VectorStore
    from services.qa_retriever import QARetriever

    store = VectorStore(
        persist_dir=Config.CHROMA_PERSIST_DIR,
        qa_collection=Config.CHROMA_QA_COLLECTION,
        schema_collection=Config.CHROMA_SCHEMA_COLLECTION,
        embedding_provider=Config.EMBEDDING_PROVIDER,
        gemini_api_key=Config.GEMINI_API_KEY,
        gemini_embed_model=Config.EMBEDDING_MODEL_GEMINI,
    )
    retriever = QARetriever(
        store=store,
        qa_pairs_file=Config.QA_PAIRS_FILE,
        extra_qa_files=Config.qa_pairs_extra_paths(),
    )
    inserted = retriever.seed_if_empty(force=args.force)
    extra = retriever.upsert_extra_files() if inserted == 0 else 0
    log.info(
        "Done — %d pairs inserted, %d extra upserted (collection total: %d).",
        inserted,
        extra,
        store.qa_count(),
    )


if __name__ == "__main__":
    main()
