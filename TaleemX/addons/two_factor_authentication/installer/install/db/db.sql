-- Smart School Two Factor Authentication DB
-- Version 4.0
-- https://smart-school.in
-- https://qdocs.net
-- Tables added: 2

-- --------------------------------------------------------

CREATE TABLE `google_authenticator` (
  `id` int(11) NOT NULL,
  `use_authenticator` int(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `permission_group` (`id`, `name`, `short_code`, `is_active`, `system`, `created_at`) VALUES
(1100, 'Two Factor Authenticator', 'google_authenticator', 1, 0, '2022-11-17 10:53:36');

INSERT INTO `permission_category` (`id`, `perm_group_id`, `name`, `short_code`, `enable_view`, `enable_add`, `enable_edit`, `enable_delete`, `created_at`) VALUES
(11001, 1100, 'Setting', 'google_authenticate_setting', 1, 0, 0, 0, '2018-07-06 10:41:49'),
(11002, 1100, 'Setup 2FA', 'google_authenticate_setup_two_fa', 1, 0, 0, 0, '2018-07-06 10:41:49');

INSERT INTO `roles_permissions` (`id`, `role_id`, `perm_cat_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `created_at`) VALUES 
(null, 1, 11001, 1, 0, 0, 0, '2022-05-05 07:00:06'),
(null, 1, 11002, 1, 0, 0, 0, '2022-05-05 06:50:12');

CREATE TABLE `user_google_authenticate_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `secret_code` varchar(100) DEFAULT NULL,
  `is_active` int(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `sidebar_menus` (`id`, `product_name`, `permission_group_id`, `icon`, `menu`, `activate_menu`, `lang_key`, `system_level`, `level`, `sidebar_display`, `access_permissions`, `is_active`, `created_at`) VALUES
(32, 'sstfa', 1100, 'fa fa-lock ftlayer', 'Two Factor Authenticator', 'two_factor_authentication', 'two_factor_authentication', 0, 4, 1, '(\'google_authenticate_setting\', \'can_view\') || (\'google_authenticate_setup_two_fa\', \'can_view\')', 0, '2023-01-10 12:55:03');

INSERT INTO `sidebar_sub_menus` (`id`, `sidebar_menu_id`, `menu`, `key`, `lang_key`, `url`, `level`, `access_permissions`, `permission_group_id`, `activate_controller`, `activate_methods`, `addon_permission`, `is_active`, `created_at`) VALUES
(194, 32, 'setup_two_fa', NULL, 'setup_two_fa', 'admin/gauthenticate/admin/setup', 1, '(\'google_authenticate_setup_two_fa\', \'can_view\')', NULL, 'admin', 'setup', 'sstfa', 1, '2022-11-21 09:00:15'),
(195, 32, 'setting', NULL, 'setting', 'admin/gauthenticate/admin', 1, '(\'google_authenticate_setting\', \'can_view\')', NULL, 'admin', 'index', '', 1, '2022-11-21 09:01:34');

ALTER TABLE `google_authenticator`
  ADD PRIMARY KEY (`id`); 

ALTER TABLE `user_google_authenticate_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `staff_id` (`staff_id`);  

ALTER TABLE `google_authenticator`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;  

ALTER TABLE `user_google_authenticate_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
  
ALTER TABLE `user_google_authenticate_codes`
  ADD CONSTRAINT `user_google_authenticate_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_google_authenticate_codes_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;