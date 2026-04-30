-- Smart School Quick Fees Create DB
-- Version 2.0
-- https://smart-school.in
-- https://qdocs.net
-- Tables added: 0

-- --------------------------------------------------------  
  
INSERT INTO `permission_group` (`id`, `name`, `short_code`, `is_active`, `system`, `created_at`) VALUES
(1300, 'Quick Fees Create', 'quick_fees', 1, 0, '2024-12-18 08:12:58');

INSERT INTO `permission_category` (`id`, `perm_group_id`, `name`, `short_code`, `enable_view`, `enable_add`, `enable_edit`, `enable_delete`, `created_at`) VALUES
(13001, 1300, 'Quick Fees Create', 'quick_fees', 1, 1, 0, 1, '2024-12-18 08:12:35');

INSERT INTO `roles_permissions` (`id`, `role_id`, `perm_cat_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `created_at`) VALUES 
(null, 1, 13001, 1, 0, 0, 0, '2025-01-17 06:26:55');

INSERT INTO `sidebar_menus` (`id`, `product_name`, `permission_group_id`, `icon`, `menu`, `activate_menu`, `lang_key`, `system_level`, `level`, `sidebar_display`, `access_permissions`, `is_active`, `created_at`) VALUES
(38, 'ssqfc', 1300, 'fa fa-usd', 'Quick Fees Create', 'quick_fees', 'quick_fees', 300, 1, 0, '(\'quick_fees\', \'can_view\')\n', 0, '2025-01-28 16:53:51');

INSERT INTO `sidebar_sub_menus` (`id`, `sidebar_menu_id`, `menu`, `key`, `lang_key`, `url`, `level`, `access_permissions`, `permission_group_id`, `activate_controller`, `activate_methods`, `addon_permission`, `is_active`, `created_at`) VALUES
(220, 3, 'quick_fees', NULL, 'quick_fees', 'admin/customfeesmaster/index', 5, '(\'quick_fees\', \'can_view\')', 1300, 'customfeesmaster', 'index', 'ssqfc', 1, '2025-01-29 05:40:13');

UPDATE `sidebar_menus`
SET `access_permissions` = CONCAT(`access_permissions`, ' || (\'quick_fees\', \'can_view\')')
WHERE `id` = 3 AND `access_permissions` NOT LIKE '%quick_fees%';