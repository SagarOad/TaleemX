-- =========================================================================
-- Migration: Create `video_transcripts` table
-- Purpose : Store extracted subtitles (timed segments + full transcript)
--           for online course preview videos and lesson videos.
--
-- Run once against the application database, for example:
--   mysql -u <user> -p <database> < migrations/video_transcripts.sql
--
-- Design notes:
--   * `entity_type` + `entity_id` is a polymorphic link to either an
--     `online_courses.id` (entity_type='course') or an
--     `online_course_lesson.id` (entity_type='lesson'). Application-level
--     validation is responsible for ensuring the referenced row exists.
--   * Unique key on (entity_type, entity_id) keeps one active transcript
--     per video target; re-extraction overwrites the existing row.
--   * `segments_json` powers time-synced subtitle playback, while
--     `full_transcript` powers future summarize-from-transcript features.
--   * `video_fingerprint` lets the UI detect when the video URL/video id
--     changed after extraction, so the "subtitles extracted" indicator can
--     be reset until the admin re-extracts.
-- =========================================================================

CREATE TABLE IF NOT EXISTS `video_transcripts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(20) NOT NULL COMMENT 'course|lesson',
  `entity_id` int(11) NOT NULL,
  `video_provider` varchar(50) DEFAULT NULL,
  `video_url` text,
  `video_id` varchar(100) DEFAULT NULL,
  `video_fingerprint` varchar(191) DEFAULT NULL COMMENT 'hash of provider+url+video_id used to detect changes',
  `source` varchar(50) DEFAULT NULL COMMENT 'extractor engine, eg faster_whisper',
  `segment_count` int(11) NOT NULL DEFAULT 0,
  `full_transcript` longtext,
  `segments_json` longtext COMMENT 'JSON array of {start,end,text,...}',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending|success|failed',
  `error_message` text,
  `last_extracted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_entity` (`entity_type`, `entity_id`),
  KEY `idx_video_fingerprint` (`video_fingerprint`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
