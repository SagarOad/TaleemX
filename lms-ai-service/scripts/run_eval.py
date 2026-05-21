#!/usr/bin/env python3
"""
scripts/run_eval.py

Lightweight eval harness — runs each question in eval/eval_questions.jsonl
through the live /ask endpoint and checks that:

  - the response is 200,
  - the answer is non-empty,
  - (when provided) the returned SQL touches at least one of the expected tables,
  - (when provided) the answer is not the "no records found" string.

Prints a pass/fail summary and exits non-zero if the success rate falls
below --threshold (default 0.7).

Usage:
    python scripts/run_eval.py
    python scripts/run_eval.py --base-url http://localhost:5050
    python scripts/run_eval.py --threshold 0.8

Run inside the container:
    docker compose exec lms-ai python scripts/run_eval.py
"""

from __future__ import annotations

import argparse
import json
import logging
import os
import sys
import time

import requests

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
sys.path.insert(0, ROOT)

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")
log = logging.getLogger("eval")


def load_cases(path: str) -> list[dict]:
    cases: list[dict] = []
    with open(path, "r", encoding="utf-8") as fh:
        for line_num, line in enumerate(fh, start=1):
            line = line.strip()
            if not line or line.startswith("#"):
                continue
            try:
                cases.append(json.loads(line))
            except json.JSONDecodeError as exc:
                log.warning("Skipping line %d: %s", line_num, exc)
    return cases


def evaluate(case: dict, answer: str, sql: str, status: str) -> tuple[bool, str]:
    if not answer:
        return False, "empty answer"
    if status not in ("ok", "manual", "empty"):
        return False, f"status={status}"
    if case.get("expect_data", True):
        if status == "empty":
            return False, "expected data but got 'empty'"
        if "no records were found" in answer.lower() and case.get("expect_data", True):
            return False, "answer says no records but case expects data"
    expected_tables = [t.lower() for t in case.get("expected_tables") or []]
    if expected_tables and sql:
        sql_l = sql.lower()
        if not any(t in sql_l for t in expected_tables):
            return False, f"SQL did not reference any expected table {expected_tables}"
    expected_keywords = [k.lower() for k in case.get("expected_keywords") or []]
    if expected_keywords:
        if not any(k in answer.lower() for k in expected_keywords):
            return False, f"answer missing any of expected keywords {expected_keywords}"
    return True, "ok"


def main():
    parser = argparse.ArgumentParser(description="Run NL→SQL eval against /ask endpoint.")
    parser.add_argument("--base-url", default=os.getenv("EVAL_BASE_URL", "http://localhost:5000"))
    parser.add_argument(
        "--cases",
        default=os.path.join(ROOT, "eval", "eval_questions.jsonl"),
    )
    parser.add_argument("--threshold", type=float, default=0.7)
    parser.add_argument("--timeout", type=int, default=60)
    parser.add_argument("--debug", action="store_true", help="Request agent trace.")
    args = parser.parse_args()

    cases = load_cases(args.cases)
    if not cases:
        log.error("No eval cases loaded from %s.", args.cases)
        sys.exit(2)

    log.info("Loaded %d eval cases. Target: %s", len(cases), args.base_url)

    passed = 0
    fails: list[tuple[dict, str]] = []
    started = time.time()

    for idx, case in enumerate(cases, start=1):
        question = case["question"]
        try:
            r = requests.post(
                f"{args.base_url}/ask",
                json={"question": question, "debug": bool(args.debug)},
                timeout=args.timeout,
            )
        except requests.RequestException as exc:
            log.error("[%d/%d] HTTP error: %s", idx, len(cases), exc)
            fails.append((case, f"http error: {exc}"))
            continue

        if r.status_code != 200:
            fails.append((case, f"HTTP {r.status_code}"))
            log.error("[%d/%d] FAIL — HTTP %d: %s", idx, len(cases), r.status_code, r.text[:200])
            continue

        body = r.json()
        ok, reason = evaluate(case, body.get("answer", ""), body.get("sql", ""), body.get("status", ""))
        if ok:
            passed += 1
            log.info("[%d/%d] PASS  %s", idx, len(cases), question)
        else:
            fails.append((case, reason))
            log.warning("[%d/%d] FAIL  %s — %s", idx, len(cases), question, reason)

    elapsed = time.time() - started
    total = len(cases)
    rate = passed / total if total else 0.0
    log.info("──────────────────────────────────────────────")
    log.info("Passed:    %d/%d (%.1f%%)", passed, total, 100 * rate)
    log.info("Failures:  %d", len(fails))
    log.info("Elapsed:   %.1fs", elapsed)
    if fails:
        log.info("Failure breakdown:")
        for case, reason in fails:
            log.info("  - %s :: %s", case.get("id") or case.get("question"), reason)

    sys.exit(0 if rate >= args.threshold else 1)


if __name__ == "__main__":
    main()
