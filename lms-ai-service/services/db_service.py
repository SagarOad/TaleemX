"""
services/db_service.py
MySQL read-only connection pool and safe query execution.
"""

import logging
import re
from typing import Optional

import pymysql
import pymysql.cursors
from pymysql.connections import Connection
from dbutils.pooled_db import PooledDB

from config import Config

logger = logging.getLogger(__name__)


class DBService:
    def __init__(self):
        self._pool: Optional[PooledDB] = None
        self._init_pool()

    def _init_pool(self):
        """Initialize the connection pool. Logs an error if DB is unreachable at startup."""
        try:
            self._pool = PooledDB(
                creator=pymysql,
                maxconnections=10,
                mincached=1,
                maxcached=5,
                blocking=True,
                host=Config.DB_HOST,
                port=Config.DB_PORT,
                user=Config.DB_USER,
                password=Config.DB_PASSWORD,
                db=Config.DB_NAME,
                charset="utf8mb4",
                connect_timeout=Config.DB_CONNECT_TIMEOUT,
                cursorclass=pymysql.cursors.DictCursor,
                autocommit=False,
            )
            logger.info(
                "DB connection pool initialised → %s:%s/%s",
                Config.DB_HOST,
                Config.DB_PORT,
                Config.DB_NAME,
            )
        except Exception as exc:
            logger.error("Failed to initialise DB pool: %s", exc)
            self._pool = None

    def _get_connection(self) -> Connection:
        if self._pool is None:
            raise RuntimeError("Database connection pool is not available.")
        return self._pool.connection()

    def execute(
        self, sql: str
    ) -> tuple[list[tuple], list[str], Optional[str]]:
        """
        Execute a pre-validated SELECT query.

        Returns:
            (rows, columns, error_message)
            rows    — list of tuples (empty on failure/no data)
            columns — list of column name strings
            error   — error message string or None
        """
        if not self._pool:
            return [], [], "Database is not connected."

        conn = None
        try:
            conn = self._get_connection()
            with conn.cursor() as cursor:
                # Set per-query execution timeout (MySQL ≥ 5.7.8)
                timeout_ms = Config.DB_QUERY_TIMEOUT * 1000
                try:
                    cursor.execute(f"SET SESSION MAX_EXECUTION_TIME={timeout_ms}")
                except Exception:
                    pass  # Older MySQL versions don't support this; non-fatal

                cursor.execute(sql)
                raw_rows = cursor.fetchmany(Config.DB_MAX_ROWS)

            if not raw_rows:
                return [], [], None

            columns = list(raw_rows[0].keys())
            rows = [tuple(row.values()) for row in raw_rows]

            logger.info(
                "Query returned %d row(s) across %d column(s).", len(rows), len(columns)
            )
            return rows, columns, None

        except pymysql.err.OperationalError as exc:
            error_code = exc.args[0]
            if error_code == 3024:
                msg = "Query timed out (exceeded maximum execution time)."
            else:
                msg = f"Database operational error ({error_code})."
            logger.error("DB OperationalError: %s | SQL: %s", exc, sql)
            return [], [], msg

        except pymysql.err.ProgrammingError as exc:
            logger.error("DB ProgrammingError: %s | SQL: %s", exc, sql)
            return [], [], "SQL syntax or schema error."

        except pymysql.err.InterfaceError as exc:
            logger.error("DB InterfaceError: %s", exc)
            return [], [], "Database connection lost. Please retry."

        except Exception as exc:
            logger.exception("Unexpected DB error: %s | SQL: %s", exc, sql)
            return [], [], "Unexpected database error."

        finally:
            if conn:
                try:
                    conn.close()
                except Exception:
                    pass

    def ping(self) -> bool:
        """Health-check the database connection."""
        try:
            _, _, error = self.execute("SELECT 1")
            return error is None
        except Exception:
            return False

    def get_schema_summary(
        self,
        question: str = "",
        max_chars: int = 12000,
    ) -> tuple[str, Optional[str]]:
        """
        Build a compact schema snapshot from information_schema for the
        currently configured database.

        Returns:
            (schema_text, error_message)
        """
        if not self._pool:
            return "", "Database is not connected."

        conn = None
        try:
            conn = self._get_connection()
            with conn.cursor() as cursor:
                cursor.execute(
                    """
                    SELECT
                        TABLE_NAME,
                        COLUMN_NAME,
                        COLUMN_TYPE,
                        IS_NULLABLE,
                        COLUMN_KEY
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = %s
                    ORDER BY TABLE_NAME, ORDINAL_POSITION
                    """,
                    (Config.DB_NAME,),
                )
                rows = cursor.fetchall()

            if not rows:
                return "", f"No tables found in database '{Config.DB_NAME}'."

            by_table: dict[str, list[dict]] = {}
            for row in rows:
                by_table.setdefault(row["TABLE_NAME"], []).append(row)

            question_terms = set(
                t for t in re.findall(r"[a-zA-Z0-9_]+", (question or "").lower()) if len(t) >= 3
            )
            scored_tables: list[tuple[int, str]] = []
            for table_name, cols in by_table.items():
                score = 0
                table_l = table_name.lower()
                if table_l in question_terms:
                    score += 5
                for term in question_terms:
                    if term in table_l:
                        score += 2
                for col in cols:
                    col_l = str(col["COLUMN_NAME"]).lower()
                    if col_l in question_terms:
                        score += 2
                    for term in question_terms:
                        if term in col_l:
                            score += 1
                scored_tables.append((score, table_name))

            scored_tables.sort(key=lambda x: (-x[0], x[1]))
            ordered_table_names = [name for _, name in scored_tables]

            header = [f"Database: {Config.DB_NAME}", "", "Tables and columns:"]
            lines = list(header)
            used = len("\n".join(lines))
            included_any = False
            for table_name in ordered_table_names:
                cols = by_table[table_name]
                col_names = ", ".join(col["COLUMN_NAME"] for col in cols)
                entry = f"- {table_name}: {col_names}"
                projected = used + 1 + len(entry)
                if projected > max_chars and included_any:
                    continue
                lines.append(entry)
                used = projected
                included_any = True
                if used >= max_chars:
                    break

            if not included_any:
                return "", "Live schema is too large to package for prompting."

            return "\n".join(lines), None
        except Exception as exc:
            logger.error("Failed to load live schema from information_schema: %s", exc)
            return "", "Unable to load live database schema."
        finally:
            if conn:
                try:
                    conn.close()
                except Exception:
                    pass
