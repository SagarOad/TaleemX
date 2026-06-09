"""
services/vector_store.py
ChromaDB wrapper holding two collections:

 - lms_qa_pairs   → curated (question → SQL) gold examples for few-shot RAG
 - lms_table_cards → table cards (table name + purpose + columns + sample rows + join hints)

Embedding backend is pluggable:
 - 'gemini' (default): calls Google text-embedding-004 via the existing GEMINI_API_KEY
 - 'local': uses Chroma's default ONNX embedder (all-MiniLM-L6-v2, ~80 MB)

The vector store is process-local but persists to disk (CHROMA_PERSIST_DIR),
so re-embedding only happens on first seed or explicit reindex.
"""

from __future__ import annotations

import logging
import os
import threading
import time
from typing import Iterable, Optional

import requests

logger = logging.getLogger(__name__)

# Chroma 0.5.x ships a buggy posthog telemetry wrapper that floods the logs
# with `capture() takes 1 positional argument but 3 were given`. We disable
# telemetry via Settings(anonymized_telemetry=False) below, but Chroma still
# constructs the client and logs the broken call once per query. Silence the
# specific logger so it doesn't drown out real errors.
logging.getLogger("chromadb.telemetry.product.posthog").setLevel(logging.ERROR + 10)


# ── Embedding functions ──────────────────────────────────────────────────────


class _GeminiEmbeddingFunction:
    """
    Chroma-compatible embedding function backed by Google's Generative Language
    embedding endpoint. Selects the first reachable model name at init time so
    that wrong-name / no-access (404) errors are surfaced immediately instead
    of silently poisoning the vector store with zero vectors.

    On any embed failure we raise — never write a zero vector — so the vector
    store cannot get corrupted.
    """

    _BASE_URL = "https://generativelanguage.googleapis.com/v1beta/models"
    # Known model names, in preference order. The first one whose probe
    # returns a real vector is selected as the active model.
    _DEFAULT_CANDIDATES = [
        "text-embedding-004",
        "gemini-embedding-001",
        "embedding-001",
    ]

    def __init__(self, api_key: str, model: str = "text-embedding-004"):
        if not api_key:
            raise RuntimeError(
                "EMBEDDING_PROVIDER=gemini but GEMINI_API_KEY is not set."
            )
        self._api_key = api_key
        self._lock = threading.Lock()
        self._last_call_at = 0.0
        self._min_interval = 0.05  # safety pacing for embed RPM

        candidates = [model] + [m for m in self._DEFAULT_CANDIDATES if m != model]
        self._model, self._dim = self._select_working_model(candidates)
        logger.info(
            "Gemini embedder selected model=%s (dim=%d)", self._model, self._dim
        )

    # Chroma's EmbeddingFunction interface: __call__(input: list[str]) -> list[list[float]]
    def __call__(self, input):  # noqa: A002
        if not input:
            return []
        return [self._embed_one(text) for text in input]

    # Chroma >=0.5 calls .name() in some code paths.
    def name(self) -> str:  # type: ignore[override]
        return f"gemini-{self._model}"

    # ── Internal ─────────────────────────────────────────────────────────────

    def _select_working_model(self, candidates: list[str]) -> tuple[str, int]:
        """Probe each candidate with a tiny payload; return the first that works."""
        seen: set[str] = set()
        last_error: Exception | None = None
        for name in candidates:
            if not name or name in seen:
                continue
            seen.add(name)
            try:
                values = self._raw_embed(name, "probe")
                if values:
                    return name, len(values)
            except Exception as exc:
                last_error = exc
                logger.warning("Gemini embed probe failed for model=%s: %s", name, exc)
                continue
        raise RuntimeError(
            f"No reachable Gemini embedding model. Last error: {last_error}"
        )

    def _embed_one(self, text: str) -> list[float]:
        values = self._raw_embed(self._model, text)
        if not values:
            raise RuntimeError("Empty embedding payload from Gemini.")
        return values

    def _raw_embed(self, model: str, text: str) -> list[float]:
        url = f"{self._BASE_URL}/{model}:embedContent?key={self._api_key}"
        payload = {
            "model": f"models/{model}",
            "content": {"parts": [{"text": (text or "")[:8000]}]},
            "taskType": "RETRIEVAL_DOCUMENT",
        }
        with self._lock:
            wait = self._min_interval - (time.monotonic() - self._last_call_at)
            if wait > 0:
                time.sleep(wait)
            self._last_call_at = time.monotonic()

        backoff = 0.8
        for attempt in range(1, 4):
            try:
                response = requests.post(url, json=payload, timeout=20)
                # 404 / 401 / 403 are permanent — do not retry.
                if response.status_code in (401, 403, 404):
                    raise RuntimeError(
                        f"HTTP {response.status_code} for {model}: {response.text[:200]}"
                    )
                if response.status_code == 429:
                    time.sleep(backoff * attempt)
                    continue
                response.raise_for_status()
                data = response.json()
                values = (data.get("embedding") or {}).get("values") or []
                return values
            except requests.RequestException as exc:
                if attempt == 3:
                    raise RuntimeError(f"Gemini embed error: {exc}") from exc
                time.sleep(backoff * attempt)
        raise RuntimeError("Gemini embedding retries exhausted.")


def _resolve_embedding_function(provider: str, api_key: str, gemini_model: str):
    """
    Build the correct Chroma embedding function for the configured provider.
    Falls back to local ONNX on any failure so the service keeps working,
    even when the Gemini embedding endpoint is not enabled on the API key.
    """
    provider = (provider or "gemini").lower()

    if provider == "gemini":
        try:
            fn = _GeminiEmbeddingFunction(api_key=api_key, model=gemini_model)
            logger.info("Using Gemini embedding backend: %s", fn.name())
            return fn
        except Exception as exc:
            logger.warning(
                "Gemini embedding init failed (%s). "
                "Falling back to local ONNX embedder (all-MiniLM-L6-v2). "
                "Existing Chroma collections built with Gemini will need a reindex "
                "(POST /admin/reindex).",
                exc,
            )

    from chromadb.utils import embedding_functions

    logger.info("Using local ONNX embedding backend (all-MiniLM-L6-v2).")
    return embedding_functions.DefaultEmbeddingFunction()


# ── Vector store ─────────────────────────────────────────────────────────────


class VectorStore:
    """Thin wrapper around Chroma's PersistentClient with two named collections."""

    def __init__(
        self,
        persist_dir: str,
        qa_collection: str,
        schema_collection: str,
        embedding_provider: str,
        gemini_api_key: str,
        gemini_embed_model: str,
        learned_collection: str = "lms_qa_learned",
    ):
        os.makedirs(persist_dir, exist_ok=True)

        import chromadb
        from chromadb.config import Settings

        self._client = chromadb.PersistentClient(
            path=persist_dir,
            settings=Settings(anonymized_telemetry=False, allow_reset=True),
        )

        self._embed_fn = _resolve_embedding_function(
            provider=embedding_provider,
            api_key=gemini_api_key,
            gemini_model=gemini_embed_model,
        )

        self._persist_dir = persist_dir
        self._qa_name = qa_collection
        self._schema_name = schema_collection
        self._learned_name = learned_collection

        self._qa = self._get_or_create(self._qa_name)
        self._schema = self._get_or_create(self._schema_name)
        self._learned = self._get_or_create(self._learned_name)
        logger.info(
            "VectorStore ready | persist_dir=%s | qa=%d | schema=%d | learned=%d | embed=%s",
            persist_dir,
            self.qa_count(),
            self.schema_count(),
            self.learned_count(),
            getattr(self._embed_fn, "name", lambda: type(self._embed_fn).__name__)(),
        )

    def _get_or_create(self, name: str):
        return self._client.get_or_create_collection(
            name=name,
            embedding_function=self._embed_fn,
            metadata={"hnsw:space": "cosine"},
        )

    def _is_stale_collection_error(self, exc: BaseException) -> bool:
        return exc.__class__.__name__ == "InvalidCollectionException"

    def _repair_collection(self, attr: str, name: str):
        """Recreate a collection when Chroma's on-disk metadata is stale."""
        logger.warning("Recreating stale Chroma collection %s (%s).", name, attr)
        try:
            for meta in self._client.list_collections():
                if getattr(meta, "name", None) == name:
                    try:
                        self._client.delete_collection(meta.name)
                    except Exception:
                        try:
                            self._client.delete_collection(meta.id)
                        except Exception:
                            pass
        except Exception:
            pass
        try:
            self._client.delete_collection(name)
        except Exception:
            pass
        try:
            coll = self._client.create_collection(
                name=name,
                embedding_function=self._embed_fn,
                metadata={"hnsw:space": "cosine"},
            )
        except Exception:
            coll = self._get_or_create(name)
        setattr(self, attr, coll)
        return coll

    def _reload_collections(self):
        self._qa = self._get_or_create(self._qa_name)
        self._schema = self._get_or_create(self._schema_name)
        self._learned = self._get_or_create(self._learned_name)

    def wipe_and_reinit(self):
        """
        Wipe the Chroma persist store and recreate empty collections.

        Stop the running API process before calling this — two PersistentClient
        instances on the same path corrupt SQLite segment metadata.
        """
        logger.warning("Wiping Chroma persist store at %s.", self._persist_dir)
        try:
            self._client.reset()
        except Exception as exc:
            logger.warning("client.reset() failed (%s); deleting collections individually.", exc)
            try:
                for meta in self._client.list_collections():
                    try:
                        self._client.delete_collection(meta.name)
                    except Exception:
                        try:
                            self._client.delete_collection(meta.id)
                        except Exception:
                            pass
            except Exception:
                pass
        self._reload_collections()
        logger.info(
            "Chroma wiped (qa=%d, schema=%d, learned=%d).",
            self.qa_count(),
            self.schema_count(),
            self.learned_count(),
        )

    def repair_all_collections(self):
        """Force-recreate QA, schema, and learned collections (use before --force seed)."""
        for attr, name in (
            ("_qa", self._qa_name),
            ("_schema", self._schema_name),
            ("_learned", self._learned_name),
        ):
            self._repair_collection(attr, name)
        logger.info(
            "All Chroma collections repaired (qa=%d, schema=%d, learned=%d).",
            self.qa_count(),
            self.schema_count(),
            self.learned_count(),
        )

    def _count_safe(self, attr: str, name: str) -> int:
        try:
            return getattr(self, attr).count()
        except Exception as exc:
            if not self._is_stale_collection_error(exc):
                logger.warning("Chroma count failed for %s: %s", name, exc)
            self._repair_collection(attr, name)
            try:
                return getattr(self, attr).count()
            except Exception:
                return 0

    def _with_collection(self, attr: str, name: str, fn):
        """Run fn(collection) once; repair and retry on stale collection refs."""
        try:
            return fn(getattr(self, attr))
        except Exception as exc:
            if not self._is_stale_collection_error(exc):
                raise
            coll = self._repair_collection(attr, name)
            try:
                return fn(coll)
            except Exception as exc2:
                if not self._is_stale_collection_error(exc2):
                    raise
                logger.error(
                    "Chroma collection %s still invalid after repair — "
                    "stop lms-ai-service and run seed with --force again.",
                    name,
                )
                self.wipe_and_reinit()
                return fn(getattr(self, attr))

    # ── QA collection ────────────────────────────────────────────────────────

    def qa_count(self) -> int:
        return self._count_safe("_qa", self._qa_name)

    def upsert_qa(
        self,
        ids: list[str],
        questions: list[str],
        sqls: list[str],
        tags: list[list[str]] | None = None,
    ):
        if not ids:
            return
        metadatas = [
            {
                "sql": sqls[i],
                "tags": ",".join(tags[i]) if tags and i < len(tags) else "",
            }
            for i in range(len(ids))
        ]

        def _upsert(coll):
            coll.upsert(ids=ids, documents=questions, metadatas=metadatas)

        self._with_collection("_qa", self._qa_name, _upsert)
        logger.info("Upserted %d QA pairs (total=%d).", len(ids), self.qa_count())

    def search_qa(self, query: str, top_k: int = 12) -> list[dict]:
        def _search(coll):
            if coll.count() == 0:
                return []
            k = max(1, min(top_k, coll.count()))
            res = coll.query(query_texts=[query], n_results=k)
            return self._unpack(res)

        return self._with_collection("_qa", self._qa_name, _search)

    def reset_qa(self):
        self._repair_collection("_qa", self._qa_name)
        logger.info("QA collection reset.")

    # ── Schema collection ────────────────────────────────────────────────────

    def schema_count(self) -> int:
        return self._count_safe("_schema", self._schema_name)

    def upsert_tables(
        self,
        table_names: list[str],
        cards: list[str],
        ddls: list[str],
    ):
        if not table_names:
            return
        metadatas = [{"table": table_names[i], "ddl": ddls[i]} for i in range(len(table_names))]

        def _upsert(coll):
            coll.upsert(ids=table_names, documents=cards, metadatas=metadatas)

        self._with_collection("_schema", self._schema_name, _upsert)
        logger.info(
            "Upserted %d table cards (total=%d).", len(table_names), self.schema_count()
        )

    def search_tables(self, query: str, top_k: int = 14) -> list[dict]:
        def _search(coll):
            if coll.count() == 0:
                return []
            k = max(1, min(top_k, coll.count()))
            res = coll.query(query_texts=[query], n_results=k)
            return self._unpack(res)

        return self._with_collection("_schema", self._schema_name, _search)

    def reset_schema(self):
        self._repair_collection("_schema", self._schema_name)
        logger.info("Schema collection reset.")

    # ── Learned collection (human-in-the-loop feedback) ──────────────────────

    def learned_count(self) -> int:
        return self._count_safe("_learned", self._learned_name)

    def upsert_learned(
        self,
        ids: list[str],
        questions: list[str],
        sqls: list[str],
        metadatas: list[dict] | None = None,
    ):
        if not ids:
            return
        metas = []
        for i in range(len(ids)):
            base = {"sql": sqls[i]}
            if metadatas and i < len(metadatas) and metadatas[i]:
                for k, v in metadatas[i].items():
                    base[k] = "" if v is None else str(v)
            metas.append(base)

        def _upsert(coll):
            coll.upsert(ids=ids, documents=questions, metadatas=metas)

        self._with_collection("_learned", self._learned_name, _upsert)
        logger.info("Upserted %d learned pair(s) (total=%d).", len(ids), self.learned_count())

    def search_learned(self, query: str, top_k: int = 4) -> list[dict]:
        def _search(coll):
            if coll.count() == 0:
                return []
            k = max(1, min(top_k, coll.count()))
            res = coll.query(query_texts=[query], n_results=k)
            return self._unpack(res)

        return self._with_collection("_learned", self._learned_name, _search)

    def list_learned(self, limit: int = 200) -> list[dict]:
        """Return raw learned pairs (for admin review)."""
        def _list(coll):
            if coll.count() == 0:
                return []
            res = coll.get(limit=limit)
            ids = res.get("ids") or []
            docs = res.get("documents") or []
            metas = res.get("metadatas") or []
            out: list[dict] = []
            for i, _id in enumerate(ids):
                out.append({
                    "id": _id,
                    "question": docs[i] if i < len(docs) else "",
                    "metadata": metas[i] if i < len(metas) else {},
                })
            return out

        return self._with_collection("_learned", self._learned_name, _list)

    def delete_learned(self, ids: list[str]):
        if not ids:
            return

        def _delete(coll):
            coll.delete(ids=ids)

        self._with_collection("_learned", self._learned_name, _delete)
        logger.info("Deleted %d learned pair(s) (remaining=%d).", len(ids), self.learned_count())

    def reset_learned(self):
        self._repair_collection("_learned", self._learned_name)
        logger.info("Learned collection reset.")

    # ── Internal ─────────────────────────────────────────────────────────────

    @staticmethod
    def _unpack(res: dict) -> list[dict]:
        ids = (res.get("ids") or [[]])[0]
        docs = (res.get("documents") or [[]])[0]
        metas = (res.get("metadatas") or [[]])[0]
        dists = (res.get("distances") or [[]])[0]
        out: list[dict] = []
        for i, _id in enumerate(ids):
            out.append(
                {
                    "id": _id,
                    "document": docs[i] if i < len(docs) else "",
                    "metadata": metas[i] if i < len(metas) else {},
                    "distance": dists[i] if i < len(dists) else None,
                }
            )
        return out
