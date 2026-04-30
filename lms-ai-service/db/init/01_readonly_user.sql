-- db/init/01_readonly_user.sql
-- Runs automatically when the MySQL container starts for the first time.
-- Ensures the read-only user has ONLY SELECT privilege.

-- Revoke any broader permissions that MYSQL_USER might have been granted
-- by default (MySQL 8 auto-grants all privs to MYSQL_USER on MYSQL_DATABASE)
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'lms_readonly'@'%';

-- Grant ONLY SELECT on the LMS database
GRANT SELECT ON lms_db.* TO 'lms_readonly'@'%';
FLUSH PRIVILEGES;
