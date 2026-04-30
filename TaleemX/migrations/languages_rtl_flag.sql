-- =========================================================================
-- Migration: Flag right-to-left (RTL) languages in `languages`
-- Purpose : Ensure that when an admin or student switches the UI language
--           to Arabic (or any other RTL language), the pre-existing RTL
--           CSS + `dir="rtl"` wiring actually activates.
--
-- Background:
--   The `languages.is_rtl` column already drives the session `is_rtl`
--   flag (see application/controllers/Site.php, admin/Language.php,
--   user/User.php and libraries/Customlib.php::getRTL). However in the
--   seed data every language ships with `is_rtl = 0`, so switching to
--   Arabic never flipped the UI to RTL.
--
--   This migration sets `is_rtl = 1` for the well-known RTL scripts
--   (matched by ISO 639-1 short_code). It is idempotent - running it
--   multiple times is safe.
--
-- Run once against the application database, for example:
--   mysql -u <user> -p <database> < migrations/languages_rtl_flag.sql
-- =========================================================================

UPDATE `languages`
SET `is_rtl` = 1
WHERE `short_code` IN (
    'ar', -- Arabic
    'he', -- Hebrew
    'fa', -- Persian / Farsi
    'ur', -- Urdu
    'ps', -- Pashto
    'sd', -- Sindhi
    'ku', -- Kurdish (Sorani uses Arabic script)
    'yi', -- Yiddish
    'dv', -- Divehi / Dhivehi
    'ug'  -- Uyghur
);
