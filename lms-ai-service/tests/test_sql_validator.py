"""
tests/test_sql_validator.py
Unit tests for SQLValidator — no DB or API calls required.
Run with: pytest tests/test_sql_validator.py -v
"""

import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

import pytest
from services.sql_validator import SQLValidator

v = SQLValidator()


class TestSelectAllowed:
    def test_simple_select(self):
        ok, msg = v.validate("SELECT * FROM users")
        assert ok, msg

    def test_select_with_where(self):
        ok, msg = v.validate("SELECT id, name FROM users WHERE role = 'student'")
        assert ok, msg

    def test_select_with_join(self):
        sql = "SELECT u.name, c.title FROM users u JOIN enrollments e ON u.id = e.student_id JOIN courses c ON c.id = e.course_id"
        ok, msg = v.validate(sql)
        assert ok, msg

    def test_select_aggregate(self):
        ok, msg = v.validate("SELECT COUNT(*) FROM enrollments WHERE status = 'active'")
        assert ok, msg

    def test_select_with_limit(self):
        ok, msg = v.validate("SELECT id FROM users LIMIT 10")
        assert ok, msg

    def test_select_case_insensitive(self):
        ok, msg = v.validate("select id from users")
        assert ok, msg


class TestBlockedStatements:
    @pytest.mark.parametrize("sql", [
        "INSERT INTO users (name) VALUES ('hacker')",
        "UPDATE users SET role='admin' WHERE id=1",
        "DELETE FROM users WHERE id=1",
        "DROP TABLE users",
        "ALTER TABLE users ADD COLUMN x INT",
        "TRUNCATE TABLE users",
        "CREATE TABLE evil (id INT)",
        "GRANT ALL ON *.* TO 'evil'@'%'",
        "REVOKE SELECT ON lms_db.* FROM 'user'@'%'",
    ])
    def test_blocked(self, sql):
        ok, msg = v.validate(sql)
        assert not ok
        assert msg

    def test_select_with_into_blocked(self):
        # SELECT INTO is a DDL variant — block it
        ok, _ = v.validate("SELECT * INTO backup_users FROM users")
        assert not ok


class TestInjectionPatterns:
    def test_stacked_query(self):
        ok, _ = v.validate("SELECT * FROM users; DROP TABLE users")
        assert not ok

    def test_union_injection(self):
        ok, _ = v.validate("SELECT id FROM users UNION SELECT password FROM admins")
        assert not ok

    def test_sleep_injection(self):
        ok, _ = v.validate("SELECT * FROM users WHERE SLEEP(5)=0")
        assert not ok


class TestEdgeCases:
    def test_empty_string(self):
        ok, msg = v.validate("")
        assert not ok

    def test_whitespace_only(self):
        ok, msg = v.validate("   ")
        assert not ok

    def test_no_from_clause(self):
        ok, msg = v.validate("SELECT 1+1")
        assert not ok

    def test_select_without_table(self):
        ok, msg = v.validate("SELECT NOW()")
        assert not ok


class TestSanitizeLimit:
    def test_adds_limit_when_missing(self):
        sql = v.sanitize_limit("SELECT * FROM users", max_rows=50)
        assert "LIMIT 50" in sql.upper()

    def test_keeps_existing_limit_within_bounds(self):
        sql = v.sanitize_limit("SELECT * FROM users LIMIT 10", max_rows=50)
        assert "LIMIT 10" in sql

    def test_caps_oversized_limit(self):
        sql = v.sanitize_limit("SELECT * FROM users LIMIT 999", max_rows=50)
        assert "LIMIT 50" in sql.upper()
        assert "999" not in sql

    def test_strips_semicolon_before_adding_limit(self):
        sql = v.sanitize_limit("SELECT * FROM users;", max_rows=50)
        assert sql.count(";") == 0
        assert "LIMIT 50" in sql.upper()
