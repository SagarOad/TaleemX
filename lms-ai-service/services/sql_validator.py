"""
services/sql_validator.py
Validates that a generated SQL statement is a safe, read-only SELECT query.
"""

import logging
import re

logger = logging.getLogger(__name__)

# Statements that must never be executed
_BLOCKED_KEYWORDS = frozenset(
    [
        "INSERT",
        "UPDATE",
        "DELETE",
        "DROP",
        "ALTER",
        "TRUNCATE",
        "CREATE",
        "REPLACE",
        "RENAME",
        "GRANT",
        "REVOKE",
        "LOCK",
        "UNLOCK",
        "CALL",
        "EXEC",
        "EXECUTE",
        "LOAD",
        "OUTFILE",
        "DUMPFILE",
        "INTO",
        "INFORMATION_SCHEMA",
        "PERFORMANCE_SCHEMA",
        "MYSQL",
        "SLEEP",
        "BENCHMARK",
        "WAIT",
    ]
)

# Patterns that indicate injection attempts
_INJECTION_PATTERNS = [
    r";\s*(SELECT|INSERT|UPDATE|DELETE|DROP|ALTER)",  # Stacked queries
    r"--\s",  # SQL comment attack
    r"/\*.*?\*/",  # Block comment injection
    r"\bUNION\b.*\bSELECT\b",  # UNION-based injection
    r"\bXP_\w+",  # MSSQL stored procedures
]

_INJECTION_RE = [re.compile(p, re.IGNORECASE | re.DOTALL) for p in _INJECTION_PATTERNS]


class SQLValidator:
    def _has_balanced_parentheses(self, sql: str) -> bool:
        depth = 0
        in_single = False
        in_double = False
        for ch in sql:
            if ch == "'" and not in_double:
                in_single = not in_single
                continue
            if ch == '"' and not in_single:
                in_double = not in_double
                continue
            if in_single or in_double:
                continue
            if ch == "(":
                depth += 1
            elif ch == ")":
                depth -= 1
                if depth < 0:
                    return False
        return depth == 0 and not in_single and not in_double

    def _has_incomplete_structure(self, sql: str) -> bool:
        clean = sql.strip().rstrip(";")
        upper = clean.upper()
        bad_endings = ("JOIN (", "FROM (", "LEFT JOIN (", "RIGHT JOIN (", "INNER JOIN (")
        if upper.endswith(bad_endings):
            return True
        if re.search(r"\b(JOIN|FROM|WHERE|GROUP BY|ORDER BY|HAVING)\s*$", upper):
            return True
        if re.search(r"\(\s*$", clean):
            return True
        return False

    def _is_aggregate_query(self, sql: str) -> bool:
        return bool(re.search(r"\b(COUNT|SUM|AVG|MIN|MAX)\s*\(", sql.upper()))

    def validate(self, sql: str) -> tuple[bool, str]:
        """
        Validate a SQL string.

        Returns:
            (is_valid: bool, error_message: str)
            error_message is empty string when valid.
        """
        if not sql or not sql.strip():
            return False, "Empty SQL statement."

        clean = sql.strip()

        # Must start with SELECT
        first_token = clean.split()[0].upper()
        if first_token != "SELECT":
            logger.warning("SQL rejected — does not start with SELECT: %s", first_token)
            return False, f"Only SELECT statements are allowed. Got: {first_token}"

        # Check for blocked keywords (as whole words)
        upper_sql = clean.upper()
        for keyword in _BLOCKED_KEYWORDS:
            pattern = r"\b" + re.escape(keyword) + r"\b"
            if re.search(pattern, upper_sql):
                logger.warning("SQL rejected — blocked keyword detected: %s", keyword)
                return False, f"Blocked keyword detected: {keyword}"

        # Check for injection patterns
        for pattern in _INJECTION_RE:
            if pattern.search(clean):
                logger.warning("SQL rejected — injection pattern detected: %s", pattern.pattern)
                return False, "Potential SQL injection pattern detected."

        # Sanity: must contain FROM (all useful SELECTs do)
        if "FROM" not in upper_sql:
            logger.warning("SQL rejected — no FROM clause: %s", clean)
            return False, "SELECT statement must contain a FROM clause."

        if not self._has_balanced_parentheses(clean):
            logger.warning("SQL rejected — unbalanced parentheses/quotes: %s", clean)
            return False, "SQL appears incomplete (unbalanced parentheses or quotes)."

        if self._has_incomplete_structure(clean):
            logger.warning("SQL rejected — incomplete SQL fragment: %s", clean)
            return False, "SQL appears incomplete (truncated JOIN/FROM/WHERE clause)."

        logger.debug("SQL passed validation: %s", clean)
        return True, ""

    def sanitize_limit(self, sql: str, max_rows: int = 50) -> str:
        """
        Ensure the SQL has a LIMIT clause capped at max_rows.
        If LIMIT already exists and is within bounds, keep it.
        Otherwise, append LIMIT max_rows for non-aggregate queries.
        """
        upper = sql.upper()
        limit_match = re.search(r"\bLIMIT\s+(\d+)", upper)

        if limit_match:
            existing_limit = int(limit_match.group(1))
            if existing_limit > max_rows:
                # Replace with capped value
                sql = re.sub(r"\bLIMIT\s+\d+", f"LIMIT {max_rows}", sql, flags=re.IGNORECASE)
            return sql

        if self._is_aggregate_query(sql):
            return sql.rstrip().rstrip(";")

        # No LIMIT — append one
        sql = sql.rstrip().rstrip(";")
        return f"{sql} LIMIT {max_rows}"
