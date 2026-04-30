-- Smart School QR Code Attendance DB
-- Version 3.0
-- https://smart-school.in
-- https://qdocs.net
-- Tables added: 1

-- --------------------------------------------------------


CREATE TABLE `QR_code_settings` (
  `id` int(11) NOT NULL,
  `camera_type` varchar(15) DEFAULT NULL,
  `auto_attendance` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO `QR_code_settings` (`id`, `camera_type`, `auto_attendance`, `created_at`) VALUES
(1, 'environment', 0, '2023-12-16 05:16:34');


INSERT INTO `permission_group` (`id`, `name`, `short_code`, `is_active`, `system`, `created_at`) VALUES 
(1200, 'QR Code Attendence', 'qr_code_attendence', 1, 0, '2023-12-13 09:21:57');


INSERT INTO `permission_category` (`id`, `perm_group_id`, `name`, `short_code`, `enable_view`, `enable_add`, `enable_edit`, `enable_delete`, `created_at`) VALUES
(12001, 1200, 'Attendance', 'qr_code_attendance', 1, 0, 0, 0, '2023-12-11 12:34:11'),
(12002, 1200, 'Setting', 'qr_code_setting', 1, 0, 0, 0, '2023-12-11 12:34:25');


INSERT INTO `sidebar_menus` (`id`, `product_name`, `permission_group_id`, `icon`, `menu`, `activate_menu`, `lang_key`, `system_level`, `level`, `sidebar_display`, `access_permissions`, `is_active`, `created_at`) VALUES
(35, 'ssqra', 1200, 'fa fa-qrcode', 'QR Code Attendance', 'qr_code_attendance', 'qr_code_attendance', 230, 13, 1, '(\'qr_code_attendance\', \'can_view\') || (\'qr_code_setting\', \'can_view\')\n\n', 1, '2023-12-11 12:28:29');


INSERT INTO `sidebar_sub_menus` (`id`, `sidebar_menu_id`, `menu`, `key`, `lang_key`, `url`, `level`, `access_permissions`, `permission_group_id`, `activate_controller`, `activate_methods`, `addon_permission`, `is_active`, `created_at`) VALUES
(213, 35, 'attendance', NULL, 'attendance', 'admin/qrattendance/attendance/index', 1, '(\'qr_code_attendance\', \'can_view\')', NULL, 'attendance', 'index', 'ssqra', 1, '2023-12-11 12:38:24'),
(214, 35, 'setting', NULL, 'setting', 'admin/qrattendance/setting/index', 2, '(\'qr_code_setting\', \'can_view\')', NULL, 'setting', 'index', NULL, 1, '2023-12-11 12:38:27');


ALTER TABLE `QR_code_settings`
  ADD PRIMARY KEY (`id`);
  
ALTER TABLE `QR_code_settings` ADD `machine_type` VARCHAR(255) NULL DEFAULT NULL AFTER `auto_attendance`;

UPDATE `QR_code_settings` SET `machine_type` = '[\"camera\"]' WHERE `QR_code_settings`.`id` = 1;

UPDATE `sidebar_sub_menus` SET `activate_methods` = 'index,scannergun' WHERE `sidebar_sub_menus`.`id` = 213;

UPDATE `sidebar_sub_menus` SET `url` = 'admin/qrattendance/qrsetting/index', `activate_controller` = 'qrsetting' WHERE `sidebar_sub_menus`.`id` = 214;