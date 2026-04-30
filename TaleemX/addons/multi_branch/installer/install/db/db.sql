-- Smart School Multi Branch DB
-- Version 4.0
-- https://smart-school.in
-- https://qdocs.net
-- Tables added: 1

-- --------------------------------------------------------

CREATE TABLE `multi_branch` (
  `id` int(11) NOT NULL,
  `branch_name` varchar(200) DEFAULT NULL,
  `branch_url` varchar(500) NOT NULL,
  `hostname` varchar(200) DEFAULT NULL,
  `username` varchar(200) DEFAULT NULL,
  `password` varchar(200) DEFAULT NULL,
  `database_name` varchar(200) DEFAULT NULL,
  `directory_path` varchar(500) DEFAULT NULL,
  `is_verified` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO `permission_group` (`id`, `name`, `short_code`, `is_active`, `system`, `created_at`) VALUES
(1000, 'Multi Branch', 'multi_branch', 1, 0, '2022-11-17 10:53:36');

INSERT INTO `permission_category` (`id`, `perm_group_id`, `name`, `short_code`, `enable_view`, `enable_add`, `enable_edit`, `enable_delete`, `created_at`) VALUES
(10001, 1000, 'Overview', 'multi_branch_overview', 1, 0, 0, 0, '2022-11-15 05:07:36'),
(10002, 1000, 'Daily Collection Report', 'multi_branch_daily_collection_report', 1, 0, 0, 0, '2022-11-15 04:57:02'),
(10003, 1000, 'Payroll Report', 'multi_branch_payroll', 1, 0, 0, 0, '2022-11-16 11:19:48'),
(10004, 1000, 'Income Report', 'multi_branch_income_report', 1, 0, 0, 0, '2022-11-15 05:07:36'),
(10005, 1000, 'Expense Report', 'multi_branch_expense_report', 1, 0, 0, 0, '2022-11-15 05:02:27'),
(10006, 1000, 'User Log Report', 'multi_branch_user_log_report', 1, 0, 0, 0, '2022-11-15 05:02:27'),
(10007, 1000, 'Setting', 'multi_branch_setting', 1, 0, 0, 0, '2022-11-15 05:07:36');

INSERT INTO `roles_permissions` (`id`, `role_id`, `perm_cat_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `created_at`) VALUES 
(null, 1, 10001, 1, 0, 0, 0, '2022-05-05 07:00:06'),
(null, 1, 10002, 1, 0, 0, 0, '2022-05-05 06:50:12'),
(null, 1, 10003, 1, 0, 0, 0, '2022-05-05 06:50:12'),
(null, 1, 10004, 1, 0, 0, 0, '2022-05-05 06:50:12'),
(null, 1, 10005, 1, 0, 0, 0, '2022-05-05 06:50:12'),
(null, 1, 10006, 1, 0, 0, 0, '2022-05-05 06:50:12'),
(null, 1, 10007, 1, 0, 0, 0, '2022-05-05 06:50:12');

INSERT INTO `sidebar_menus` (`id`, `product_name`, `permission_group_id`, `icon`, `menu`, `activate_menu`, `lang_key`, `system_level`, `level`, `sidebar_display`, `access_permissions`, `is_active`, `created_at`) VALUES
(33, 'ssmb', 1000, 'fa fa-sitemap ftlayer', 'Multi Branch', 'multi_branch', 'multi_branch', 0, 4, 1, '(\'multi_branch_overview\', \'can_view\') || (\'multi_branch_daily_collection_report\', \'can_view\') || (\'multi_branch_payroll\', \'can_view\') || (\'multi_branch_income_report\', \'can_view\') || (\'multi_branch_expense_report\', \'can_view\') || (\'multi_branch_user_log_report\', \'can_view\') || (\'multi_branch_setting\', \'can_view\')', 0, '2023-01-10 12:49:51');

INSERT INTO `sidebar_sub_menus` (`id`, `sidebar_menu_id`, `menu`, `key`, `lang_key`, `url`, `level`, `access_permissions`, `permission_group_id`, `activate_controller`, `activate_methods`, `addon_permission`, `is_active`, `created_at`) VALUES
(198, 33, 'overview', NULL, 'overview', 'admin/multibranch/branch/overview', 1, '(\'multi_branch_overview\', \'can_view\')', NULL, 'branch', 'overview', 'ssmb', 1, '2022-11-15 06:05:27'),
(199, 33, 'report', NULL, 'report', 'admin/multibranch/finance/index', 1, '(\'multi_branch_daily_collection_report\', \'can_view\') || (\'multi_branch_payroll\', \'can_view\') || (\'multi_branch_income_report\', \'can_view\') || (\'multi_branch_expense_report\', \'can_view\') || (\'multi_branch_user_log_report\', \'can_view\')', NULL, 'finance', 'dailycollectionreport,payroll,incomelist,expenselist,incomereport,expensereport,userlogreport,index', 'ssmb', 1, '2022-12-22 05:59:38'),
(200, 33, 'setting', NULL, 'setting', 'admin/multibranch/branch', 1, '(\'multi_branch_setting\', \'can_view\')', NULL, 'branch', 'index', '', 1, '2022-11-15 05:45:32');

ALTER TABLE `multi_branch`
  ADD PRIMARY KEY (`id`);
  
ALTER TABLE `multi_branch`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
  
INSERT INTO `permission_category` (`id`, `perm_group_id`, `name`, `short_code`, `enable_view`, `enable_add`, `enable_edit`, `enable_delete`, `created_at`, `updated_at`) VALUES
(10008, 1000, 'Switch Branch', 'multi_branch_switch_branch', 1, 0, 0, 0, '2022-11-15 04:07:36', '2025-10-04 06:41:15');