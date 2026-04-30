-- Smart School Whatsapp Messaging DB
-- Version 1.0
-- https://smart-school.in
-- https://qdocs.net
-- Tables added: 1

-- --------------------------------------------------------


CREATE TABLE `whatsapp_config` (
  `id` int NOT NULL,
  `type` varchar(50) NOT NULL,
  `language` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `api_id` varchar(100) NOT NULL,
  `authkey` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `senderid` varchar(100) NOT NULL,
  `contact` text,
  `username` varchar(150) DEFAULT NULL,
  `password` varchar(150) DEFAULT NULL,
  `is_active` varchar(255) DEFAULT 'disabled',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `whatsapp_config`
--

INSERT INTO `whatsapp_config` (`id`, `type`, `language`, `name`, `api_id`, `authkey`, `senderid`, `contact`, `username`, `password`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'twilio', NULL, '', '', '', '', '', '', '', 'disabled', '2025-05-09 15:04:54', '2026-01-09 05:32:49');



ALTER TABLE `whatsapp_config`
  ADD PRIMARY KEY (`id`);
  
ALTER TABLE `whatsapp_config`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;


INSERT INTO `permission_group` (`id`, `name`, `short_code`, `is_active`, `system`, `created_at`, `updated_at`) VALUES
(1500, 'Whatsapp Messaging', 'whatsapp_messaging', 1, 0, '2025-01-10 04:36:34', '2025-10-04 06:41:15');


INSERT INTO `permission_category` (`id`, `perm_group_id`, `name`, `short_code`, `enable_view`, `enable_add`, `enable_edit`, `enable_delete`, `created_at`, `updated_at`) VALUES
(15001, 1500, 'Whatsapp Messaging', 'whatsapp_messaging', 1, 0, 0, 0, '2024-12-18 07:12:35', '2025-10-04 06:41:15');

INSERT INTO `roles_permissions` (`id`, `role_id`, `perm_cat_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `created_at`, `updated_at`) VALUES
(null, 1, 15001, 1, NULL, NULL, NULL, '2025-09-04 09:07:00', '2025-10-04 06:41:16');


INSERT INTO `sidebar_menus` (`id`, `product_name`, `permission_group_id`, `icon`, `menu`, `activate_menu`, `lang_key`, `system_level`, `level`, `sidebar_display`, `access_permissions`, `is_active`, `created_at`) VALUES
(40, 'sswm', 1500, 'fa fa-usd', 'Whatsapp Messaging', 'whatsapp_messaging', 'whatsapp_messaging', 300, 1, 0, '(\'whatsapp_messaging\', \'can_view\')\r\n', 0, '2025-01-29 06:12:04');

INSERT INTO `sidebar_sub_menus` (`id`, `sidebar_menu_id`, `menu`, `key`, `lang_key`, `url`, `level`, `access_permissions`, `permission_group_id`, `activate_controller`, `activate_methods`, `addon_permission`, `is_active`, `created_at`, `updated_at`) VALUES
(226, 27, 'whatsapp_messaging', NULL, 'whatsapp_messaging', 'whatsappconfig/index', 4, '(\'whatsapp_messaging\', \'can_view\')', NULL, 'whatsappconfig', 'index', '', 1, '2025-01-10 11:08:46', '2025-10-04 06:41:16');