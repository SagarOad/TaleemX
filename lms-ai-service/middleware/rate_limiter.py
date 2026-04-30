"""
middleware/rate_limiter.py
Simple in-process token-bucket rate limiter.
For multi-worker deployments, swap the in-memory store for Redis.
"""

import time
import threading
import logging
from functools import wraps
from flask import request, jsonify

logger = logging.getLogger(__name__)


class TokenBucket:
    """Thread-safe token bucket for a single key."""

    def __init__(self, capacity: int, refill_rate: float):
        """
        capacity    — max burst (tokens)
        refill_rate — tokens added per second
        """
        self.capacity = capacity
        self.refill_rate = refill_rate
        self._tokens = float(capacity)
        self._last_refill = time.monotonic()
        self._lock = threading.Lock()

    def consume(self, tokens: int = 1) -> bool:
        with self._lock:
            now = time.monotonic()
            elapsed = now - self._last_refill
            self._tokens = min(
                self.capacity,
                self._tokens + elapsed * self.refill_rate,
            )
            self._last_refill = now

            if self._tokens >= tokens:
                self._tokens -= tokens
                return True
            return False


class RateLimiter:
    """
    Per-IP rate limiter backed by in-memory buckets.
    Buckets are lazily created and periodically pruned.
    """

    def __init__(self, capacity: int = 30, refill_rate: float = 0.5):
        """
        capacity    — requests allowed in a burst (default 30)
        refill_rate — sustained requests per second (default 0.5 = 30/min)
        """
        self.capacity = capacity
        self.refill_rate = refill_rate
        self._buckets: dict[str, TokenBucket] = {}
        self._lock = threading.Lock()
        self._last_prune = time.monotonic()

    def _get_key(self) -> str:
        """Use X-Forwarded-For if behind a proxy, else remote_addr."""
        forwarded = request.headers.get("X-Forwarded-For", "")
        if forwarded:
            return forwarded.split(",")[0].strip()
        return request.remote_addr or "unknown"

    def _get_bucket(self, key: str) -> TokenBucket:
        with self._lock:
            # Prune stale buckets every 5 minutes
            now = time.monotonic()
            if now - self._last_prune > 300:
                self._buckets.clear()
                self._last_prune = now
            if key not in self._buckets:
                self._buckets[key] = TokenBucket(self.capacity, self.refill_rate)
            return self._buckets[key]

    def limit(self, f):
        """Decorator: apply rate limiting to a Flask route."""
        @wraps(f)
        def decorated(*args, **kwargs):
            key = self._get_key()
            bucket = self._get_bucket(key)
            if not bucket.consume():
                logger.warning("Rate limit exceeded for IP: %s", key)
                return jsonify({
                    "error": "Too many requests. Please slow down and try again shortly."
                }), 429
            return f(*args, **kwargs)
        return decorated


# Default shared limiter instance
# Adjust capacity/refill_rate to match your expected traffic
default_limiter = RateLimiter(capacity=30, refill_rate=0.5)
