-- Smart School Thermal Print DB
-- Version 2.0
-- https://smart-school.in
-- https://qdocs.net
-- Tables added: 1

-- --------------------------------------------------------

CREATE TABLE `thermal_print_settings` (
  `id` int NOT NULL,
  `is_print` int NOT NULL,
  `school_name` text,
  `address` text,
  `footer_text` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

INSERT INTO `thermal_print_settings` (`id`, `is_print`, `school_name`, `address`, `footer_text`, `created_at`) VALUES
(1, 0, 'Mount/ Carmel /School', '25 Kings Street, CA <br> 89562423934 <br> mountcarmelmailtest@gmail.com','This receipt is computer generated hence no signature is required.', '2025-01-10 17:02:48');

ALTER TABLE `thermal_print_settings`
  ADD PRIMARY KEY (`id`);
  
ALTER TABLE `thermal_print_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
  
INSERT INTO `permission_group` (`id`, `name`, `short_code`, `is_active`, `system`, `created_at`) VALUES
(1400, 'Thermal Print', 'thermal_print', 1, 0, '2025-01-10 05:36:34');

INSERT INTO `permission_category` (`id`, `perm_group_id`, `name`, `short_code`, `enable_view`, `enable_add`, `enable_edit`, `enable_delete`, `created_at`) VALUES
(14001, 1400, 'Thermal Print', 'thermal_print', 1, 0, 0, 0, '2024-12-18 08:12:35');

INSERT INTO `roles_permissions` (`id`, `role_id`, `perm_cat_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `created_at`) VALUES 
(null, 1, 14001, 1, 0, 0, 0, '2025-01-17 06:26:55');

INSERT INTO `sidebar_menus` (`id`, `product_name`, `permission_group_id`, `icon`, `menu`, `activate_menu`, `lang_key`, `system_level`, `level`, `sidebar_display`, `access_permissions`, `is_active`, `created_at`) VALUES
(39, 'sstpa', 1400, 'fa fa-usd', 'Thermal Print', 'thermal_print', 'thermal_print', 300, 1, 0, '(\'thermal_print\', \'can_view\')\r\n', 0, '2025-01-29 06:12:04');

INSERT INTO `sidebar_sub_menus` (`id`, `sidebar_menu_id`, `menu`, `key`, `lang_key`, `url`, `level`, `access_permissions`, `permission_group_id`, `activate_controller`, `activate_methods`, `addon_permission`, `is_active`, `created_at`) VALUES
(221, 27, 'thermal_print', NULL, 'thermal_print', 'admin/thermalprint/index', 7, '(\'thermal_print\', \'can_view\')', NULL, 'thermalprint', 'index', 'sstpa', 1, '2025-01-10 12:08:46');