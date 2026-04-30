-- Smart School Behaviour Records DB
-- Version 4.0
-- https://smart-school.in
-- https://qdocs.net
-- Tables added: 4

-- --------------------------------------------------------

CREATE TABLE `behaviour_settings` (
  `id` int(11) NOT NULL,
  `comment_option` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP										
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `behaviour_settings` (`id`, `comment_option`, `created_at`) VALUES
(1, '[\"student\",\"parent\"]', '2022-12-31 11:49:38');

INSERT INTO `notification_setting` (`id`, `type`, `is_mail`, `is_sms`, `is_notification`, `display_notification`, `display_sms`, `is_student_recipient`, `is_guardian_recipient`, `is_staff_recipient`, `display_student_recipient`, `display_guardian_recipient`, `display_staff_recipient`, `subject`, `template_id`, `template`, `variables`, `created_at`) VALUES
(null, 'behaviour_incident_assigned', '1', '1', 1, 1, 1, 1, 1, 0, 1, 1, NULL, 'Behaviour Incident Assigned', '', 'A new {{incident_title}}  behaviour incident with {{incident_point}} point is assigned on you. {{student_name}} {{class}} {{section}} {{admission_no}} {{mobileno}} {{email}} {{guardian_name}} {{guardian_phone}} {{guardian_email}}', '{{incident_title}} {{incident_point}} {{student_name}} {{class}} {{section}} {{admission_no}} {{mobileno}} {{email}} {{guardian_name}} {{guardian_phone}} {{guardian_email}}', '2023-01-02 06:35:44');

INSERT INTO `permission_group` (`id`, `name`, `short_code`, `is_active`, `system`, `created_at`) VALUES 
(800, 'Behaviour Records', 'behaviour_records', 1, 0, '2022-05-03 10:20:33');

INSERT INTO `permission_student` (`id`, `name`, `short_code`, `system`, `student`, `parent`, `group_id`, `created_at`) VALUES 
(800, 'Behaviour Records', 'behaviour_records', 0, 1, 1, 800, '2022-05-03 10:20:33');

INSERT INTO `permission_category` (`id`, `perm_group_id`, `name`, `short_code`, `enable_view`, `enable_add`, `enable_edit`, `enable_delete`, `created_at`) VALUES 
(8001, 800, 'Behaviour Records Assign Incident', 'behaviour_records_assign_incident', 1, 1, 0, 1, '2022-05-03 08:56:15'),
(8002, 800, 'Behaviour Records Incident', 'behaviour_records_incident', 1, 1, 1, 1, '2022-05-03 07:25:10'),
(8003, 800, 'Student Incident Report', 'student_incident_report', 1, 0, 0, 0, '2022-05-03 07:24:48'),
(8004, 800, 'Student Behaviour Rank Report', 'student_behaviour_rank_report', 1, 0, 0, 0, '2022-05-03 07:24:45'),
(8005, 800, 'Class Wise Rank Report', 'class_wise_rank_report', 1, 0, 0, 0, '2022-05-03 07:24:37'),
(8006, 800, 'Class Section Wise Rank Report', 'class_section_wise_rank_report', 1, 0, 0, 0, '2022-05-03 07:24:33'),
(8007, 800, 'House Wise Rank Report', 'house_wise_rank_report', 1, 0, 0, 0, '2022-05-03 07:24:30'),
(8008, 800, 'Incident Wise Report', 'incident_wise_report', 1, 0, 0, 0, '2022-05-03 07:24:26'),
(8009, 800, 'Behaviour Records Setting', 'behaviour_records_setting', 1, 0, 1, 0, '2022-05-03 07:24:05');

INSERT INTO `roles_permissions` (`id`, `role_id`, `perm_cat_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `created_at`) VALUES 
(null, 1, 8001, 1, 1, 0, 1, '2022-05-05 07:00:06'),
(null, 2, 8003, 1, 0, 0, 0, '2022-05-05 06:50:12'),													   
(null, 2, 8004, 1, 0, 0, 0, '2022-05-05 06:50:42'),
(null, 2, 8005, 1, 0, 0, 0, '2022-05-05 06:50:59'),
(null, 2, 8006, 1, 0, 0, 0, '2022-05-05 06:51:17'),												   
(null, 1, 8002, 1, 1, 1, 1, '2022-05-05 07:01:15'),
(null, 1, 8003, 1, 0, 0, 0, '2022-05-05 07:01:49'),
(null, 1, 8004, 1, 0, 0, 0, '2022-05-05 07:05:12'),										   
(null, 1, 8005, 1, 0, 0, 0, '2022-05-05 07:08:53'),
(null, 1, 8006, 1, 0, 0, 0, '2022-05-05 07:10:42'),
(null, 2, 8008, 1, 0, 0, 0, '2022-05-05 07:11:34'),
(null, 1, 8007, 1, 0, 0, 0, '2022-05-05 07:12:37'),											   
(null, 1, 8008, 1, 0, 0, 0, '2022-05-05 07:15:07'),
(null, 1, 8009, 1, 0, 1, 0, '2022-05-05 08:19:18'),
(null, 2, 8007, 1, 0, 0, 0, '2022-05-05 07:26:02'),
(null, 2, 8002, 1, 1, 1, 1, '2022-05-05 09:47:39'),
(null, 2, 8009, 1, 0, 1, 0, '2022-05-05 08:25:01'),
(null, 2, 8001, 1, 1, 0, 1, '2022-05-05 09:47:39'),
(null, 4, 8001, 1, 1, 0, 1, '2022-05-05 08:37:50'),
(null, 4, 8002, 1, 1, 1, 1, '2022-05-05 08:37:50'),
(null, 4, 8003, 1, 0, 0, 0, '2022-05-05 08:32:25'),
(null, 4, 8004, 1, 0, 0, 0, '2022-05-05 08:32:25'),
(null, 4, 8005, 1, 0, 0, 0, '2022-05-05 08:32:25'),
(null, 4, 8006, 1, 0, 0, 0, '2022-05-05 08:32:25'),
(null, 4, 8007, 1, 0, 0, 0, '2022-05-05 08:32:25'),
(null, 4, 8008, 1, 0, 0, 0, '2022-05-05 08:32:25'),
(null, 4, 8009, 1, 0, 0, 0, '2022-05-05 08:32:25'),
(null, 3, 8001, 1, 1, 0, 1, '2022-05-05 08:36:56'),
(null, 3, 8002, 1, 1, 1, 1, '2022-05-05 08:42:21'),
(null, 3, 8003, 1, 0, 0, 0, '2022-05-05 08:44:16'),
(null, 3, 8004, 1, 0, 0, 0, '2022-05-05 08:49:05'),
(null, 3, 8005, 1, 0, 0, 0, '2022-05-05 08:54:09'),
(null, 3, 8006, 1, 0, 0, 0, '2022-05-05 08:57:04'),
(null, 3, 8007, 1, 0, 0, 0, '2022-05-05 09:00:02'),
(null, 3, 8008, 1, 0, 0, 0, '2022-05-05 09:02:30'),
(null, 3, 8009, 1, 0, 1, 0, '2022-05-05 09:09:34');

CREATE TABLE `student_incidents` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `assign_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `student_incident_comments` (
  `id` int(11) NOT NULL,									
  `student_incident_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,																	   
  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP									 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `student_behaviour` (
  `id` int(11) NOT NULL,
  `point` int(11) NOT NULL,
  `description` text NOT NULL,
  `title` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `student_behaviour`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `behaviour_settings`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `student_incidents`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `student_incident_comments`
  ADD PRIMARY KEY (`id`);
  
ALTER TABLE `behaviour_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
  
ALTER TABLE `student_incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `student_behaviour`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
  
ALTER TABLE `student_incident_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
  
ALTER TABLE `student_incidents`
  ADD CONSTRAINT `student_incidents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE, 
  ADD CONSTRAINT `student_incidents_ibfk_2` FOREIGN KEY (`incident_id`) REFERENCES `student_behaviour` (`id`) ON DELETE CASCADE;  
  
ALTER TABLE `student_incident_comments`
  ADD CONSTRAINT `student_incident_comments_ibfk_1` FOREIGN KEY (`student_incident_id`) REFERENCES `student_incidents` (`id`) ON DELETE CASCADE;
  
INSERT INTO `sidebar_menus` (`id`, `product_name`, `permission_group_id`, `icon`, `menu`, `activate_menu`, `lang_key`, `system_level`, `level`, `sidebar_display`, `access_permissions`, `is_active`, `created_at`) VALUES
(31, 'ssbr', 800, 'fa fa-map-signs ftlayer', 'Behaviour Records', 'behaviour_records', 'behaviour_records', 0, 4, 1, '(\'student_incident_report\', \'can_view\') || (\'student_behaviour_rank_report\', \'can_view\') || (\'class_wise_rank_report\', \'can_view\') || (\'class_section_wise_rank_report\', \'can_view\') || (\'house_wise_rank_report\', \'can_view\') || (\'incident_wise_report\', \'can_view\') || (\'behaviour_records_assign_incident\', \'can_view\') || (\'behaviour_records_incident\', \'can_view\') || (\'behaviour_records_setting\', \'can_view\')', 1, '2023-01-10 12:55:29');

INSERT INTO `sidebar_sub_menus` (`id`, `sidebar_menu_id`, `menu`, `key`, `lang_key`, `url`, `level`, `access_permissions`, `permission_group_id`, `activate_controller`, `activate_methods`, `addon_permission`, `is_active`, `created_at`) VALUES
(186, 31, 'assign_incident', NULL, 'assign_incident', 'behaviour/studentincidents', 1, '(\'behaviour_records_assign_incident\', \'can_view\')', NULL, 'studentincidents', 'index', 'ssbr', 1, '2022-07-27 04:55:37'),
(187, 31, 'incidents', NULL, 'incidents', 'behaviour/incidents', 1, '(\'behaviour_records_incident\', \'can_view\')', NULL, 'incidents', 'index', 'ssbr', 1, '2022-09-06 13:02:04'),
(188, 31, 'reports', NULL, 'reports', 'behaviour/report', 1, '(\'student_incident_report\', \'can_view\') || (\'student_behaviour_rank_report\', \'can_view\') || (\'class_wise_rank_report\', \'can_view\') || (\'class_section_wise_rank_report\', \'can_view\') || (\'house_wise_rank_report\', \'can_view\') || (\'incident_wise_report\', \'can_view\')', NULL, 'report', 'index,studentincidentreport,studentbehaviorsrankreport,classwiserankreport,classsectionwiserank,housewiserank,incidentwisereport', 'ssbr', 1, '2022-09-06 13:25:28'),
(189, 31, 'setting', NULL, 'setting', 'behaviour/setting', 1, '(\'behaviour_records_setting\', \'can_view\')', NULL, 'setting', 'index', '', 1, '2022-09-06 13:02:20');

ALTER TABLE `student_behaviour`
  ADD KEY `idx_title` (`title`),
  ADD KEY `idx_description` (`description`(200));
  
UPDATE `sidebar_sub_menus` SET `url` = 'behaviour/report/behaviourreport', `activate_methods` = 'behaviourreport,studentincidentreport,studentbehaviorsrankreport,classwiserankreport,classsectionwiserank,housewiserank,incidentwisereport' WHERE `sidebar_sub_menus`.`id` = 188;

UPDATE `sidebar_sub_menus` SET `url` = 'behaviour/behavioursetting', `activate_controller` = 'behavioursetting' WHERE `sidebar_sub_menus`.`id` = 189;