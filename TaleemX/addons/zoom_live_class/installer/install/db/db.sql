-- Smart School Zoom Live Class DB
-- Version 8.0
-- https://smart-school.in
-- https://qdocs.net
-- Tables added: 5

-- --------------------------------------------------------

CREATE TABLE `conferences` (
  `id` int(11) primary key auto_increment NOT NULL,
  `purpose` varchar(20) NOT NULL DEFAULT 'class',
  `staff_id` int(11) DEFAULT NULL,
  `created_id` int(10) NOT NULL,
  `title` text,
  `date` datetime DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `subject` varchar(50) DEFAULT NULL,
  `class_id` int(10) DEFAULT NULL,
  `section_id` int(10) DEFAULT NULL,
  `session_id` int(10) NOT NULL,
  `host_video` int(1) NOT NULL DEFAULT '1',
  `client_video` int(1) NOT NULL DEFAULT '1',
  `description` text,
  `timezone` varchar(100) DEFAULT NULL,
  `return_response` text,
  `api_type` varchar(30) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `conference_staff` (
  `id` int(11) primary key auto_increment NOT NULL,
  `conference_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `conferences_history` (
 `id` int(11) primary key auto_increment NOT NULL,
  `conference_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `total_hit` int(10) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `zoom_settings` (
  `id` int(11) primary key auto_increment NOT NULL,
  `zoom_api_key` varchar(200) DEFAULT NULL,
  `zoom_api_secret` varchar(200) DEFAULT NULL,
  `use_teacher_api` int(1) DEFAULT '0',
  `use_zoom_app` int(1) DEFAULT '1',
  `use_zoom_app_user` int(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `conference_sections` (
  `id` int(11) primary key auto_increment NOT NULL,
  `conference_id` int(11) DEFAULT NULL,
  `cls_section_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)  ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `conferences_history`
  ADD CONSTRAINT `conferences_history_ibfk_1` FOREIGN KEY (`conference_id`) REFERENCES `conferences` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conferences_history_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conferences_history_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

ALTER TABLE `conference_sections`
  ADD CONSTRAINT `conference_sections_ibfk_1` FOREIGN KEY (`conference_id`) REFERENCES `conferences` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conference_sections_ibfk_2` FOREIGN KEY (`cls_section_id`) REFERENCES `class_sections` (`id`) ON DELETE CASCADE;

ALTER TABLE `conferences`
  ADD CONSTRAINT `conferences_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conferences_ibfk_2` FOREIGN KEY (`created_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

ALTER TABLE `conference_staff`
  ADD CONSTRAINT `conference_staff_ibfk_1` FOREIGN KEY (`conference_id`) REFERENCES `conferences` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conference_staff_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

INSERT INTO `notification_setting` (`id`, `type`, `is_mail`, `is_sms`, `is_notification`, `display_notification`, `display_sms`, `is_student_recipient`, `is_guardian_recipient`, `is_staff_recipient`, `display_student_recipient`, `display_guardian_recipient`, `display_staff_recipient`, `subject`, `template_id`, `template`, `variables`, `created_at`) VALUES
(NULL, 'online_classes', '1', '1', 1, 1, 1, 1, 1, NULL, 1, 1, NULL, 'Zoom Online Classes', '', 'Dear student, your live class {{title}} has been scheduled on {{date}} for the duration of {{duration}} minute, please do not share the link to any body.', '{{title}} {{date}} {{duration}}', '2022-07-13 08:01:48'),
(NULL, 'online_meeting', '1', '1', 0, 0, 1, NULL, NULL, 1, NULL, NULL, 1, 'Zoom Online Meeting', '', 'Dear staff, your live meeting {{title}} has been scheduled on {{date}} for the duration of {{duration}} minute, please do not share the link to any body.', '{{title}} {{date}} {{duration}} {{employee_id}} {{department}} {{designation}} {{name}} {{contact_no}} {{email}}', '2022-07-13 07:46:54'),
(NULL, 'zoom_online_classes_start', '1', '1', 1, 1, 1, 1, 1, NULL, 1, 1, NULL, 'Zoom Online  Classes Start', '', 'Dear student, your live class {{title}} has been started  for the duration of {{duration}} minute.', '{{title}} {{date}} {{duration}}', '2022-07-13 08:04:02'),
(NULL, 'zoom_online_meeting_start', '1', '1', 0, 0, 1, NULL, NULL, 1, NULL, NULL, 1, 'Zoom Live Meeting Start', '', 'Dear {{name}},  your live meeting {{title}}  has been started  for the duration of {{duration}} minute.', '{{title}} {{date}} {{duration}} {{employee_id}} {{department}} {{designation}} {{name}} {{contact_no}} {{email}}', '2022-07-11 07:39:06');

ALTER TABLE sch_settings
  ADD `zoom_api_key` varchar(100) DEFAULT NULL after app_logo,
  ADD `zoom_api_secret` varchar(100) DEFAULT NULL after zoom_api_key;
 
ALTER TABLE staff
  ADD `zoom_api_key` varchar(100) DEFAULT NULL after verification_code,
  ADD `zoom_api_secret` varchar(100) DEFAULT NULL after zoom_api_key;

INSERT INTO `permission_group` (`id`, `name`, `short_code`, `is_active`, `system`, `created_at`) VALUES
(500, 'Zoom Live Classes', 'zoom_live_classes', 1, 0, '2020-06-10 13:37:23');

INSERT INTO `permission_category` (`id`, `perm_group_id`, `name`, `short_code`, `enable_view`, `enable_add`, `enable_edit`, `enable_delete`, `created_at`) VALUES
(5001, 500, 'Setting', 'setting', 1, 0, 1, 0, '2020-06-10 13:39:04'),
(5002, 500, 'Live Classes', 'live_classes', 1, 1, 0, 1, '2020-05-31 15:41:32'),
(5003, 500, 'Live Meeting', 'live_meeting', 1, 1, 0, 1, '2020-06-01 12:41:41'),
(5004, 500, 'Live Meeting Report', 'live_meeting_report', 1, 0, 0, 0, '2020-06-10 05:07:40'),
(5005, 500, 'Live Classes Report', 'live_classes_report', 1, 0, 0, 0, '2020-06-10 06:29:53');

INSERT INTO `permission_student` (`id`, `name`, `short_code`, `system`, `student`, `parent`, `group_id`, `created_at`) VALUES (500, 'Zoom Live Classes', 'live_classes', '0', '1', '1', '500', CURRENT_TIMESTAMP);

INSERT INTO `roles_permissions` (`id`, `role_id`, `perm_cat_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `created_at`) VALUES
(null, 3, 5005, 1, 0, 0, 0, '2022-07-13 09:54:15'),
(null, 2, 5005, 1, 0, 0, 0, '2022-07-13 10:20:33'),
(null, 6, 5005, 1, 0, 0, 0, '2022-07-13 10:29:49'),
(null, 1, 5005, 1, 0, 0, 0, '2022-07-13 10:57:22'),
(null, 4, 5005, 1, 0, 0, 0, '2022-07-13 11:23:43'),
(null, 3, 5004, 1, 0, 0, 0, '2020-06-14 13:03:50'),
(null, 6, 5004, 1, 0, 0, 0, '2022-07-13 10:29:49'),
(null, 2, 5004, 1, 0, 0, 0, '2022-07-13 10:20:19'),
(null, 1, 5004, 1, 0, 0, 0, '2022-07-13 10:57:22'),
(null, 4, 5004, 1, 0, 0, 0, '2022-07-13 11:23:43'),
(null, 2, 5003, 1, 1, 0, 0, '2022-07-13 10:21:13'),
(null, 6, 5003, 1, 0, 0, 1, '2022-07-13 10:33:55'),
(null, 1, 5003, 1, 1, 0, 1, '2022-07-13 11:00:28'),
(null, 4, 5003, 1, 1, 0, 0, '2022-07-13 11:38:07'),
(null, 3, 5003, 1, 1, 0, 1, '2020-06-14 13:03:50'),
(null, 2, 5002, 1, 1, 0, 0, '2022-07-13 10:25:20'),
(null, 6, 5002, 1, 1, 0, 1, '2022-07-13 10:52:36'),
(null, 1, 5002, 1, 1, 0, 1, '2022-07-13 11:00:28'),
(null, 4, 5002, 1, 1, 0, 0, '2022-07-13 11:38:07'),
(null, 2, 5001, 1, 0, 1, 0, '2022-07-13 10:28:26'),
(null, 1, 5001, 1, 0, 1, 0, '2022-07-13 11:00:36'),
(null, 6, 5001, 1, 0, 1, 0, '2022-07-13 10:52:36'),
(null, 4, 5001, 1, 0, 0, 0, '2022-07-13 11:49:49');

INSERT INTO `sidebar_menus` (`id`, `product_name`, `permission_group_id`, `icon`, `menu`, `activate_menu`, `lang_key`, `system_level`, `level`, `sidebar_display`, `access_permissions`, `is_active`, `created_at`) VALUES
(30, 'sszlc', 500, 'fa fa-video-camera ftlayer', 'Zoom Live Classes', 'zoom_live_classes', 'zoom_live_classes', 0, 6, 1, '(\'setting\', \'can_view\') || (\'live_classes\', \'can_view\') || (\'live_meeting\', \'can_view\') || (\'live_classes_report\', \'can_view\') || (\'live_meeting_report\', \'can_view\')', 1, '2023-01-10 12:49:51');

INSERT INTO `sidebar_sub_menus` (`id`, `sidebar_menu_id`, `menu`, `key`, `lang_key`, `url`, `level`, `access_permissions`, `permission_group_id`, `activate_controller`, `activate_methods`, `addon_permission`, `is_active`, `created_at`) VALUES
(175, 30, 'live_class', NULL, 'live_class', 'admin/conference/timetable', 2, '(\'live_classes\', \'can_view\')', NULL, 'conference', 'timetable', 'sszlc', 1, '2022-07-22 05:55:48'),
(176, 30, 'live_meeting', NULL, 'live_meeting', 'admin/conference/meeting', 1, '(\'live_meeting\', \'can_view\')', NULL, 'conference', 'meeting', 'sszlc', 1, '2022-07-22 05:55:48'),
(177, 30, 'live_class_report', NULL, 'live_class_report', 'admin/conference/class_report', 3, '(\'live_classes_report\', \'can_view\')', NULL, 'conference', 'class_report', 'sszlc', 1, '2022-07-11 11:38:41'),
(178, 30, 'live_meeting_report', NULL, 'live_meeting_report', 'admin/conference/meeting_report', 4, '(\'live_meeting_report\', \'can_view\')', NULL, 'conference', 'meeting_report', 'sszlc', 1, '2022-07-11 11:38:41'),
(179, 30, 'setting', NULL, 'setting', 'admin/conference', 5, '(\'setting\', \'can_view\')', NULL, 'conference', 'index', '', 1, '2022-07-11 11:38:41');

ALTER TABLE zoom_settings
  ADD `parent_live_class` int(11) NOT NULL DEFAULT '0' after use_zoom_app_user;  

ALTER TABLE `conferences`
  ADD KEY `idx_class_id` (`class_id`);
  
