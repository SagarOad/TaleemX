-- Smart School Online Course DB
-- Version 5.0
-- https://smart-school.in
-- https://qdocs.net
-- Tables added: 29

-- --------------------------------------------------------

--
-- Table structure for table `aws_s3_settings`
--

CREATE TABLE `aws_s3_settings` (
  `id` int(11) NOT NULL,
  `api_key` varchar(250) DEFAULT NULL,
  `api_secret` varchar(250) DEFAULT NULL,
  `bucket_name` varchar(250) DEFAULT NULL,
  `region` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `course_category`
--

CREATE TABLE `course_category` (
  `id` int(11) NOT NULL,
  `category_name` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `is_active` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO `captcha` (`id`, `name`, `status`, `created_at`) VALUES
(6, 'guest_login_signup', 0, '2022-12-07 07:11:31');

--
-- Table structure for table `course_lesson_quiz_order`
--

CREATE TABLE `course_lesson_quiz_order` (
  `id` int(11) NOT NULL,
  `type` varchar(10) DEFAULT NULL,
  `course_section_id` int(11) DEFAULT NULL,
  `lesson_quiz_id` int(11) DEFAULT NULL,
  `order` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `course_progress`
--

CREATE TABLE `course_progress` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `guest_id` int(1) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `course_section_id` int(11) DEFAULT NULL,
  `lesson_quiz_id` int(11) DEFAULT NULL,
  `lesson_quiz_type` int(11) DEFAULT NULL COMMENT '1 lesson, 2 quiz'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `course_quiz_answer`
--

CREATE TABLE `course_quiz_answer` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `course_quiz_id` int(11) DEFAULT NULL,
  `course_quiz_question_id` int(11) DEFAULT NULL,
  `answer` varchar(255) DEFAULT NULL,
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `course_quiz_question`
--

CREATE TABLE `course_quiz_question` (
  `id` int(11) NOT NULL,
  `course_quiz_id` int(11) DEFAULT NULL,
  `question` text,
  `option_1` varchar(255) DEFAULT NULL,
  `option_2` varchar(255) DEFAULT NULL,
  `option_3` varchar(255) DEFAULT NULL,
  `option_4` varchar(255) DEFAULT NULL,
  `option_5` varchar(255) DEFAULT NULL,
  `correct_answer` varchar(255) DEFAULT NULL,
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `course_rating`
--

CREATE TABLE `course_rating` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `course_id` int(11) NOT NULL,
  `rating` varchar(200) NOT NULL,
  `review` text NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `guest`
--

CREATE TABLE `guest` (
  `id` int(11) NOT NULL,
  `guest_name` varchar(200) NOT NULL,
  `guest_unique_id` varchar(200) NOT NULL,
  `lang_id` int(11) NOT NULL,
  `currency_id` int(11) NOT NULL,
  `email` varchar(200) NOT NULL,
  `mobileno` varchar(100) NOT NULL,
  `password` varchar(200) NOT NULL,
  `dob` varchar(200) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `note` text NOT NULL,
  `address` varchar(200) NOT NULL,
  `guest_image` varchar(200) NOT NULL,
  `verification_code` varchar(200) NOT NULL,
  `created_at` date NOT NULL,
  `is_active` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `notification_setting` (`id`, `type`, `is_mail`, `is_sms`, `is_notification`, `display_notification`, `display_sms`, `is_student_recipient`, `is_guardian_recipient`, `is_staff_recipient`, `display_student_recipient`, `display_guardian_recipient`, `display_staff_recipient`, `subject`, `template_id`, `template`, `variables`, `created_at`) VALUES
(NULL, 'online_course_publish', '1', '1', 0, 1, 1, 1, NULL, NULL, 1, 1, NULL, 'Online Course Publish', '', 'Dear student, a new online course {{title}} and price {{price}} with discount {{discount}}% for {{class}} {{section}} is {{paid_free}} now available and assign to {{assign_teacher}}.', '{{title}} {{class}} {{section}} {{price}} {{discount}} {{paid_free}} {{assign_teacher}}\r\n ', '2022-11-15 06:09:13'),
(NULL, 'online_course_purchase', '1', '1', 0, 1, 1, 1, NULL, NULL, 1, 1, NULL, 'Online Course Purchase', '', 'Thanks for purchasing course {{title}} amount {{price}} purchase date {{purchase_date}} class {{class}} section {{section}} and assign for {{assign_teacher}}  discount {{discount}}%', '{{title}} {{class}} {{section}} {{price}} {{discount}}  \r\n{{purchase_date}}', '2022-11-19 10:09:29'),
(NULL, 'online_course_purchase_for_guest_user', '1', '1', 0, 0, 1, 1, NULL, NULL, 1, NULL, NULL, 'Online Course Purchase For Guest', '', 'Thanks for purchasing course {{title}} discount {{discount}} amount {{price}} purchase date {{purchase_date}}', '{{title}} {{price}} {{discount}} {{purchase_date}}', '2022-07-15 05:53:52'),
(NULL, 'online_course_guest_user_sign_up', '1', '0', 0, 0, 0, 1, NULL, NULL, 1, NULL, NULL, 'Online Course Guest User Sign Up', '', 'Dear {{guest_user_name}} you have successfully sign up with Email: {{email}} Url {{url}}', '{{guest_user_name}} {{email}} {{url}}', '2022-07-15 05:56:31');


--
-- Table structure for table `online_courses`
--

CREATE TABLE `online_courses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(200) NOT NULL,
  `url` varchar(200) NOT NULL,
  `description` text,
  `teacher_id` int(11) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `outcomes` text,
  `course_thumbnail` varchar(100) DEFAULT NULL,
  `course_provider` varchar(100) DEFAULT NULL,
  `course_url` varchar(255) DEFAULT NULL,
  `video_id` text,
  `price` float(10,2) NOT NULL DEFAULT '0.00',
  `discount` float(10,2) NOT NULL DEFAULT '0.00',
  `free_course` tinyint(1) DEFAULT NULL COMMENT '0=paid,1=free',
  `view_count` int(11) DEFAULT NULL,
  `front_side_visibility` varchar(10) NOT NULL DEFAULT 'yes',
  `status` tinyint(1) DEFAULT '0',
  `created_by` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_date` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `online_course_class_sections`
--

CREATE TABLE `online_course_class_sections` (
  `id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `class_section_id` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `online_course_lesson`
--

CREATE TABLE `online_course_lesson` (
  `id` int(11) NOT NULL,
  `course_section_id` int(11) DEFAULT NULL,
  `lesson_title` varchar(255) DEFAULT NULL,
  `lesson_type` varchar(20) DEFAULT NULL,
  `thumbnail` varchar(100) DEFAULT NULL,
  `summary` text,
  `attachment` varchar(200) DEFAULT NULL,
  `video_provider` varchar(20) DEFAULT NULL,
  `video_url` text,
  `video_id` varchar(50) DEFAULT NULL,
  `duration` time DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `online_course_payment`
--

CREATE TABLE `online_course_payment` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `online_courses_id` int(11) DEFAULT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `actual_price` float(10,2) NOT NULL DEFAULT '0.00',
  `paid_amount` float(10,2) NOT NULL DEFAULT '0.00',
  `payment_mode` varchar(50) DEFAULT NULL,
  `payment_type` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `note` text,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `online_course_processing_payment`
--

CREATE TABLE `online_course_processing_payment` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `online_courses_id` int(11) DEFAULT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `actual_price` float(10,2) NOT NULL DEFAULT '0.00',
  `paid_amount` float(10,2) NOT NULL DEFAULT '0.00',
  `payment_mode` varchar(50) DEFAULT NULL,
  `payment_type` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `note` text,
  `date` datetime DEFAULT NULL,
  `gateway_ins_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `online_course_quiz`
--

CREATE TABLE `online_course_quiz` (
  `id` int(11) NOT NULL,
  `course_section_id` int(11) DEFAULT NULL,
  `quiz_title` varchar(255) DEFAULT NULL,
  `quiz_instruction` text,
  `created_by` int(10) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `online_course_section`
--

CREATE TABLE `online_course_section` (
  `id` int(11) NOT NULL,
  `online_course_id` int(11) DEFAULT NULL,
  `section_title` varchar(255) DEFAULT NULL,
  `order` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `online_course_settings`
--

CREATE TABLE `online_course_settings` (
  `id` int(11) NOT NULL,
  `guest_prefix` varchar(50) NOT NULL,
  `guest_id_start_from` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `permission_group` (`id`, `name`, `short_code`, `is_active`, `system`, `created_at`) VALUES
(700, 'Online Course', 'online_course', 1, 0, '2021-05-15 11:35:53');

INSERT INTO `permission_student` (`id`, `name`, `short_code`, `system`, `student`, `parent`, `group_id`, `created_at`) VALUES
(700, 'Online Course', 'online_course', 0, 1, 1, 700, '2021-05-15 11:35:53');

INSERT INTO `permission_category` (`id`, `perm_group_id`, `name`, `short_code`, `enable_view`, `enable_add`, `enable_edit`, `enable_delete`, `created_at`) VALUES
(7001, 700, 'Online Course', 'online_course', 1, 1, 1, 1, '2019-11-23 18:25:18'),
(7002, 700, 'Course Publish', 'course_publish', 1, 0, 0, 0, '2019-11-23 18:25:18'),
(7003, 700, 'Course Section', 'online_course_section', 1, 1, 1, 1, '2021-05-17 05:26:33'),
(7004, 700, 'Course Lesson', 'online_course_lesson', 1, 1, 1, 1, '2021-05-17 05:26:24'),
(7005, 700, 'Course Quiz', 'online_course_quiz', 1, 1, 1, 1, '2021-05-17 05:26:20'),
(7006, 700, 'Offline Payment', 'online_course_offline_payment', 1, 1, 0, 0, '2021-05-17 05:26:17'),
(7007, 700, 'Student Course Purchase Report', 'student_course_purchase_report', 1, 0, 0, 0, '2021-05-17 05:25:56'),
(7008, 700, 'Course Sell Count Report', 'course_sell_count_report', 1, 0, 0, 0, '2021-05-17 05:25:52'),
(7009, 700, 'Course Trending Report', 'course_trending_report', 1, 0, 0, 0, '2021-05-17 05:25:49'),
(7010, 700, 'Course Complete Report', 'course_complete_report', 1, 0, 0, 0, '2021-05-17 05:25:42'),
(7011, 700, 'Setting', 'online_course_setting', 1, 0, 0, 0, '2021-05-17 05:25:37'),
(7012, 700, 'Course Rating Report', 'course_rating_report', 1, 0, 0, 1, '2022-06-14 11:24:57'),
(7013, 700, 'Guest Report', 'guest_report', 1, 0, 1, 1, '2022-06-14 11:33:09'),
(7014, 700, 'Course Category', 'course_category', 1, 1, 1, 1, '2019-11-23 18:25:18');

INSERT INTO `roles_permissions` (`id`, `role_id`, `perm_cat_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `created_at`) VALUES
(null, 1, 7001, 1, 1, 1, 1, '2021-05-11 07:21:33'),
(null, 1, 7002, 1, 0, 0, 0, '2021-05-17 05:28:47'),
(null, 1, 7003, 1, 1, 1, 1, '2021-05-11 08:29:37'),
(null, 1, 7005, 1, 1, 1, 1, '2021-05-17 05:28:47'),
(null, 1, 7004, 1, 1, 1, 1, '2021-05-11 10:00:50'),
(null, 1, 7006, 1, 1, 0, 0, '2021-05-17 05:28:47'),
(null, 1, 7007, 1, 0, 0, 0, '2021-05-11 10:00:50'),
(null, 1, 7008, 1, 0, 0, 0, '2021-05-11 10:00:50'),
(null, 1, 7009, 1, 0, 0, 0, '2021-05-11 10:00:50'),
(null, 1, 7010, 1, 0, 0, 0, '2021-05-11 10:00:50'),
(null, 2, 7001, 1, 1, 1, 1, '2021-05-15 11:07:28'),
(null, 2, 7002, 1, 0, 0, 0, '2021-05-17 10:51:44'),
(null, 2, 7003, 1, 1, 1, 1, '2021-05-15 10:28:38'),
(null, 2, 7004, 1, 1, 1, 1, '2021-05-15 10:28:38'),
(null, 3, 7006, 1, 1, 0, 0, '2021-05-17 10:52:19'),
(null, 3, 7007, 1, 0, 0, 0, '2021-05-17 04:32:06'),
(null, 1, 7011, 1, 0, 0, 0, '2021-05-17 05:28:47'),
(null, 2, 7005, 1, 1, 1, 1, '2021-05-17 10:51:44'),
(null, 2, 7010, 1, 0, 0, 0, '2021-05-17 10:51:44'),
(null, 1, 7012, 1, 0, 0, 1, '2022-06-20 04:20:01'),
(null, 1, 7013, 1, 0, 1, 1, '2022-06-18 09:52:16'),
(null, 1, 7014, 1, 1, 1, 1, '2021-05-15 10:28:38'),
(null, 6, 7014, 1, 1, 1, 1, '2022-06-18 09:09:15'),
(null, 6, 7013, 1, 0, 0, 0, '2022-06-18 09:13:07'),
(null, 4, 7012, 1, 0, 0, 0, '2022-07-15 04:22:20'),
(null, 4, 7013, 1, 0, 1, 1, '2022-07-15 04:26:29'),
(null, 4, 7014, 1, 1, 1, 1, '2022-07-15 04:24:48'),
(null, 2, 7012, 1, 0, 0, 1, '2022-07-15 05:01:12'),
(null, 2, 7013, 1, 0, 0, 1, '2022-07-15 05:01:12'),
(null, 2, 7014, 1, 0, 0, 1, '2022-07-15 05:01:12'),
(null, 3, 7012, 1, 0, 0, 0, '2022-07-15 05:31:18'),
(null, 3, 7013, 1, 0, 0, 0, '2022-07-15 05:31:18'),
(null, 3, 7014, 1, 0, 0, 0, '2022-07-15 05:31:18');

INSERT INTO `sidebar_menus` (`id`, `product_name`, `permission_group_id`, `icon`, `menu`, `activate_menu`, `lang_key`, `system_level`, `level`, `sidebar_display`, `access_permissions`, `is_active`, `created_at`) VALUES
(28, 'ssoclc', 700, 'fa fa-file-video-o ftlayer', 'Online Course', 'online_course', 'online_course', 0, 4, 1, '(\'online_course\', \'can_view\') || (\'online_course_offline_payment\', \'can_view\') || (\'student_course_purchase_report\', \'can_view\') || (\'course_sell_count_report\', \'can_view\') || (\'online_course_setting\', \'can_view\')|| (\'course_category\', \'can_view\') || (\'guest_report\', \'can_view\')|| (\'course_rating_report\', \'can_view\')', 0, '2023-01-10 12:49:51');


INSERT INTO `sidebar_sub_menus` (`id`, `sidebar_menu_id`, `menu`, `key`, `lang_key`, `url`, `level`, `access_permissions`, `permission_group_id`, `activate_controller`, `activate_methods`, `addon_permission`, `is_active`, `created_at`) VALUES
(167, 28, 'online_course', NULL, 'online_course', 'onlinecourse/course/index', 1, '(\'online_course\', \'can_view\')', NULL, 'course', 'index', 'ssoclc', 1, '2022-05-18 07:50:11'),
(168, 28, 'offline_payment', NULL, 'offline_payment', 'onlinecourse/offlinepayment/payment', 2, '(\'online_course_offline_payment\', \'can_view\')', NULL, 'offlinepayment', 'payment', 'ssoclc', 1, '2022-12-04 10:26:40'),
(169, 28, 'course_category', NULL, 'course_category', 'onlinecourse/coursecategory/categoryadd', 3, '(\'course_category\', \'can_view\') || (\'course_category\', \'can_add\') || (\'course_category\', \'can_edit\') || (\'course_category\', \'can_delete\')', NULL, 'coursecategory', 'categoryadd,categoryedit', 'ssoclc', 1, '2023-01-02 09:23:54'),
(180, 28, 'online_course_report', NULL, 'online_course_report', 'onlinecourse/coursereport/report', 4, '(\'student_course_purchase_report\', \'can_view\') || (\'course_sell_count_report\', \'can_view\') || (\'course_trending_report\', \'can_view\') || (\'course_complete_report\', \'can_view\')  || (\'course_rating_report\', \'can_view\')  || (\'guest_report\', \'can_view\')', NULL, 'coursereport', 'report,coursepurchase,coursesellreport,trendingreport,completereport,courseratingreport,guestlist,quizperformance', 'ssoclc', 1, '2022-12-09 05:00:31'),
(197, 28, 'setting', NULL, 'setting', 'onlinecourse/course/setting', 5, '(\'online_course_setting\', \'can_view\')', NULL, 'course', 'setting', '', 1, '2022-07-22 01:13:30');


--
-- Table structure for table `student_quiz_status`
--

CREATE TABLE `student_quiz_status` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `guest_id` int(1) DEFAULT NULL,
  `course_quiz_id` int(11) DEFAULT NULL,
  `total_question` int(11) DEFAULT NULL,
  `correct_answer` int(11) DEFAULT NULL,
  `wrong_answer` int(11) DEFAULT NULL,
  `not_answer` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL COMMENT '1-completed,0-incomplete	',
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Indexes for table `aws_s3_settings`
--
ALTER TABLE `aws_s3_settings`
  ADD PRIMARY KEY (`id`);
  

--
-- Indexes for table `course_category`
--
ALTER TABLE `course_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `course_lesson_quiz_order`
--
ALTER TABLE `course_lesson_quiz_order`
  ADD PRIMARY KEY (`id`),
  ADD KEY `section_id` (`course_section_id`);

--
-- Indexes for table `course_progress`
--
ALTER TABLE `course_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_section_id` (`course_section_id`);

--
-- Indexes for table `course_quiz_answer`
--
ALTER TABLE `course_quiz_answer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `quiz_id` (`course_quiz_id`),
  ADD KEY `question_id` (`course_quiz_question_id`);

--
-- Indexes for table `course_quiz_question`
--
ALTER TABLE `course_quiz_question`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_quiz_id` (`course_quiz_id`);

--
-- Indexes for table `course_rating`
--
ALTER TABLE `course_rating`
  ADD PRIMARY KEY (`id`);
  

--
-- Indexes for table `guest`
--
ALTER TABLE `guest`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);
  

--
-- Indexes for table `online_courses`
--
ALTER TABLE `online_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `title` (`title`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `online_course_class_sections`
--
ALTER TABLE `online_course_class_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `class_section_id` (`class_section_id`);

--
-- Indexes for table `online_course_lesson`
--
ALTER TABLE `online_course_lesson`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_section_id` (`course_section_id`) USING BTREE;

--
-- Indexes for table `online_course_payment`
--
ALTER TABLE `online_course_payment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `online_course_processing_payment`
--
ALTER TABLE `online_course_processing_payment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `online_courses_id` (`online_courses_id`);

--
-- Indexes for table `online_course_quiz`
--
ALTER TABLE `online_course_quiz`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `course_section_id` (`course_section_id`) USING BTREE;

--
-- Indexes for table `online_course_section`
--
ALTER TABLE `online_course_section`
  ADD PRIMARY KEY (`id`),
  ADD KEY `online_course_id` (`online_course_id`);

--
-- Indexes for table `online_course_settings`
--
ALTER TABLE `online_course_settings`
  ADD PRIMARY KEY (`id`);
  

--
-- Indexes for table `student_quiz_status`
--
ALTER TABLE `student_quiz_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_quiz_id` (`course_quiz_id`) USING BTREE;
  

--
-- AUTO_INCREMENT for table `aws_s3_settings`
--
ALTER TABLE `aws_s3_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
  

--
-- AUTO_INCREMENT for table `course_category`
--
ALTER TABLE `course_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_lesson_quiz_order`
--
ALTER TABLE `course_lesson_quiz_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_progress`
--
ALTER TABLE `course_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_quiz_answer`
--
ALTER TABLE `course_quiz_answer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_quiz_question`
--
ALTER TABLE `course_quiz_question`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_rating`
--
ALTER TABLE `course_rating`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
  

--
-- AUTO_INCREMENT for table `guest`
--
ALTER TABLE `guest`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
  

--
-- AUTO_INCREMENT for table `online_courses`
--
ALTER TABLE `online_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `online_course_class_sections`
--
ALTER TABLE `online_course_class_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `online_course_lesson`
--
ALTER TABLE `online_course_lesson`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `online_course_payment`
--
ALTER TABLE `online_course_payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `online_course_processing_payment`
--
ALTER TABLE `online_course_processing_payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `online_course_quiz`
--
ALTER TABLE `online_course_quiz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `online_course_section`
--
ALTER TABLE `online_course_section`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `online_course_settings`
--
ALTER TABLE `online_course_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
  

--
-- AUTO_INCREMENT for table `student_quiz_status`
--
ALTER TABLE `student_quiz_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
  

--
-- Constraints for table `course_lesson_quiz_order`
--
ALTER TABLE `course_lesson_quiz_order`
  ADD CONSTRAINT `course_lesson_quiz_order_ibfk_1` FOREIGN KEY (`course_section_id`) REFERENCES `online_course_section` (`id`);

--
-- Constraints for table `course_progress`
--
ALTER TABLE `course_progress`
  ADD CONSTRAINT `course_progress_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `online_courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_progress_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_progress_ibfk_3` FOREIGN KEY (`course_section_id`) REFERENCES `online_course_section` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_quiz_answer`
--
ALTER TABLE `course_quiz_answer`
  ADD CONSTRAINT `course_quiz_answer_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_quiz_answer_ibfk_2` FOREIGN KEY (`course_quiz_id`) REFERENCES `online_course_quiz` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_quiz_answer_ibfk_3` FOREIGN KEY (`course_quiz_question_id`) REFERENCES `course_quiz_question` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_quiz_question`
--
ALTER TABLE `course_quiz_question`
  ADD CONSTRAINT `course_quiz_question_ibfk_1` FOREIGN KEY (`course_quiz_id`) REFERENCES `online_course_quiz` (`id`);
  

--
-- Constraints for table `online_courses`
--
ALTER TABLE `online_courses`
  ADD CONSTRAINT `online_courses_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `online_course_class_sections`
--
ALTER TABLE `online_course_class_sections`
  ADD CONSTRAINT `online_course_class_sections_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `online_courses` (`id`),
  ADD CONSTRAINT `online_course_class_sections_ibfk_2` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`);

--
-- Constraints for table `online_course_lesson`
--
ALTER TABLE `online_course_lesson`
  ADD CONSTRAINT `online_course_lesson_ibfk_1` FOREIGN KEY (`course_section_id`) REFERENCES `online_course_section` (`id`);


--
-- Constraints for table `online_course_quiz`
--
ALTER TABLE `online_course_quiz`
  ADD CONSTRAINT `online_course_quiz_ibfk_1` FOREIGN KEY (`course_section_id`) REFERENCES `online_course_section` (`id`),
  ADD CONSTRAINT `online_course_quiz_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `online_course_section`
--
ALTER TABLE `online_course_section`
  ADD CONSTRAINT `online_course_section_ibfk_1` FOREIGN KEY (`online_course_id`) REFERENCES `online_courses` (`id`);
  

--
-- Constraints for table `student_quiz_status`
--
ALTER TABLE `student_quiz_status`
  ADD CONSTRAINT `student_quiz_status_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_quiz_status_ibfk_2` FOREIGN KEY (`course_quiz_id`) REFERENCES `online_course_quiz` (`id`) ON DELETE CASCADE;
  
ALTER TABLE online_course_settings
  ADD `guest_login` int(11) DEFAULT '0' after guest_id_start_from;
  
  
ALTER TABLE `course_rating`
  ADD KEY `idx_course_id` (`course_id`);
  

ALTER TABLE `online_courses`
  ADD KEY `idx_category_id` (`category_id`);


ALTER TABLE `online_course_payment`
  ADD KEY `idx_online_courses_id` (`online_courses_id`);
  
  
ALTER TABLE `guest`
  ADD KEY `idx_lang_id` (`lang_id`),
  ADD KEY `idx_currency_id` (`currency_id`),
  ADD KEY `idx_email` (`email`);
 

CREATE TABLE `online_course_assignment` (
  `id` int(11) NOT NULL,
  `course_section_id` int(11) NOT NULL,
  `assignment_title` varchar(255) DEFAULT NULL,
  `assignment_date` date DEFAULT NULL,
  `submit_date` date DEFAULT NULL,
  `evaluation_date` date DEFAULT NULL,
  `marks` float(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `document` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `evaluated_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `online_course_assignment_evaluation` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `marks` float(10,2) DEFAULT NULL,
  `note` varchar(255) NOT NULL,
  `date` date NOT NULL COMMENT 'evaluation date',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `online_course_assignment_submit` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `docs` varchar(225) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `online_course_exam` (
  `id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `course_section_id` int(11) DEFAULT NULL,
  `exam` text DEFAULT NULL,
  `attempt` int(11) NOT NULL,
  `exam_from` datetime DEFAULT NULL,
  `exam_to` datetime DEFAULT NULL,
  `is_quiz` int(11) NOT NULL DEFAULT 0,
  `auto_publish_date` datetime DEFAULT NULL,
  `time_from` time DEFAULT NULL,
  `time_to` time DEFAULT NULL,
  `duration` time NOT NULL,
  `passing_percentage` float NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `publish_result` int(11) NOT NULL DEFAULT 0,
  `answer_word_count` int(11) NOT NULL DEFAULT -1,
  `is_active` varchar(1) DEFAULT '0',
  `is_marks_display` int(11) NOT NULL DEFAULT 0,
  `is_neg_marking` int(11) NOT NULL DEFAULT 0,
  `is_random_question` int(11) NOT NULL DEFAULT 0,
  `is_rank_generated` int(11) NOT NULL DEFAULT 0,
  `publish_exam_notification` int(11) NOT NULL,
  `publish_result_notification` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `online_course_exam_attempts` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `online_course_exam_marks` (
  `id` int(11) NOT NULL,
  `question_id` int(11) DEFAULT NULL,
  `online_course_exam_id` int(11) DEFAULT NULL,
  `marks` float(10,2) NOT NULL DEFAULT 0.00,
  `neg_marks` float(10,2) NOT NULL DEFAULT 0.00,
  `is_active` varchar(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `online_course_exam_question` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `question_type` varchar(100) NOT NULL,
  `level` varchar(10) NOT NULL,
  `question` text NOT NULL,
  `opt_a` text NOT NULL,
  `opt_b` text NOT NULL,
  `opt_c` text NOT NULL,
  `opt_d` text NOT NULL,
  `opt_e` text NOT NULL,
  `correct` text NOT NULL,
  `descriptive_word_limit` int(11) NOT NULL,
  `question_tag` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `online_course_exam_result` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `select_option` longtext DEFAULT NULL,
  `marks` float(10,2) NOT NULL,
  `remark` text DEFAULT NULL,
  `attachment_name` text DEFAULT NULL,
  `attachment_upload_name` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `online_course_lesson_attachment` (
  `id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `attachment` text NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `online_course_tag` (
  `id` int(11) NOT NULL,
  `tag_name` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;  

ALTER TABLE `online_course_assignment`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `online_course_assignment_evaluation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `online_course_assignment_evaluation_ibfk_1` (`assignment_id`);

ALTER TABLE `online_course_assignment_submit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `online_course_assignment_submit_ibfk_1` (`assignment_id`);

ALTER TABLE `online_course_exam`
  ADD PRIMARY KEY (`id`),
  ADD KEY `online_course_exam_ibfk_1` (`course_id`);

ALTER TABLE `online_course_exam_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `online_course_exam_attempts_ibfk_1` (`exam_id`);

ALTER TABLE `online_course_exam_marks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `online_course_exam_marks_ibfk_1` (`online_course_exam_id`);
  
ALTER TABLE `online_course_exam_question`
  ADD PRIMARY KEY (`id`),
  ADD KEY `online_course_exam_question_ibfk_1` (`question_tag`);

ALTER TABLE `online_course_exam_result`
  ADD PRIMARY KEY (`id`),
  ADD KEY `online_course_exam_result_ibfk_1` (`question_id`);  

ALTER TABLE `online_course_lesson_attachment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `online_course_lesson_attachment_ibfk_1` (`lesson_id`);

ALTER TABLE `online_course_tag`
  ADD PRIMARY KEY (`id`);  

ALTER TABLE `online_course_assignment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `online_course_assignment_evaluation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `online_course_assignment_submit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `online_course_exam`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `online_course_exam_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `online_course_exam_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `online_course_exam_question`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `online_course_exam_result`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;  

ALTER TABLE `online_course_lesson_attachment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `online_course_tag`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `online_course_assignment_evaluation`
  ADD CONSTRAINT `online_course_assignment_evaluation_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `online_course_assignment` (`id`) ON DELETE CASCADE;

ALTER TABLE `online_course_assignment_submit`
  ADD CONSTRAINT `online_course_assignment_submit_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `online_course_assignment` (`id`) ON DELETE CASCADE;

ALTER TABLE `online_course_exam`
  ADD CONSTRAINT `online_course_exam_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `online_courses` (`id`) ON DELETE CASCADE;

ALTER TABLE `online_course_exam_attempts`
  ADD CONSTRAINT `online_course_exam_attempts_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `online_course_exam` (`id`) ON DELETE CASCADE;

ALTER TABLE `online_course_exam_marks`
  ADD CONSTRAINT `online_course_exam_marks_ibfk_1` FOREIGN KEY (`online_course_exam_id`) REFERENCES `online_course_exam` (`id`) ON DELETE CASCADE;

ALTER TABLE `online_course_exam_question`
  ADD CONSTRAINT `online_course_exam_question_ibfk_1` FOREIGN KEY (`question_tag`) REFERENCES `online_course_tag` (`id`) ON DELETE CASCADE;

ALTER TABLE `online_course_lesson_attachment`
  ADD CONSTRAINT `online_course_lesson_attachment_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `online_course_lesson` (`id`) ON DELETE CASCADE;
 
ALTER TABLE `course_progress` CHANGE `lesson_quiz_type` `lesson_quiz_type` INT(11) NULL DEFAULT NULL COMMENT '1 lesson, 2 quiz, 3 assignment,4 exam';

ALTER TABLE `online_course_settings` ADD `course_curriculum_settings` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `guest_login`;  

INSERT INTO `online_course_settings` (`id`, `guest_prefix`, `guest_id_start_from`, `guest_login`, `course_curriculum_settings`) VALUES
(1, 'Guest', 1, 0, '[\"online_course_quiz\",\"online_course_exam\",\"online_course_assignment\"]');
  
INSERT INTO `permission_category` (`id`, `perm_group_id`, `name`, `short_code`, `enable_view`, `enable_add`, `enable_edit`, `enable_delete`, `created_at`) VALUES
(7024, 700, 'Course Exam Attempt Report', 'course_exam_attempt_report', 1, 0, 0, 0, '2025-01-06 11:16:24'),
(7023, 700, 'Course Exam Report', 'course_exam_report', 1, 0, 0, 0, '2025-01-06 11:16:27'),
(7022, 700, 'Course Exam Result Report', 'course_exam_result_report', 1, 0, 0, 0, '2025-01-06 11:16:33'),
(7021, 700, 'Course Assignment Report', 'course_assignment_report', 1, 0, 0, 0, '2025-01-06 11:16:36'),
(7020, 700, 'Course Evalute Assignment', 'online_course_evalute_assignment', 1, 1, 0, 0, '2024-12-25 10:16:20'),
(7019, 700, 'Course Add Questions in Exam ', 'online_course_add_questions_in_exam', 1, 0, 1, 1, '2024-12-25 11:48:07'),
(7018, 700, 'Course Assignment', 'online_course_assignment', 1, 1, 1, 1, '2021-05-17 05:26:33'),
(7017, 700, 'Course Exam', 'online_course_exam', 1, 1, 1, 1, '2021-05-17 05:26:24'),
(7016, 700, 'Question Tag', 'online_course_question_tag', 1, 1, 1, 1, '2024-12-24 06:47:59'),
(7015, 700, 'Question Bank', 'online_course_question_bank', 1, 1, 1, 1, '2024-12-24 06:47:52');

INSERT INTO `roles_permissions` (`id`, `role_id`, `perm_cat_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `created_at`) VALUES
(null, 1, 7015, 1, 1, 1, 1, '2021-05-11 07:21:33'),
(null, 1, 7016, 1, 1, 1, 1, '2021-05-11 07:21:33'),
(null, 1, 7017, 1, 1, 1, 1, '2021-05-11 07:21:33'),
(null, 1, 7018, 1, 1, 1, 1, '2021-05-11 07:21:33'),
(null, 1, 7019, 1, 0, 0, 0, '2021-05-11 07:21:33'),
(null, 1, 7020, 1, 0, 0, 0, '2021-05-11 07:21:33'),
(null, 1, 7021, 1, 0, 0, 0, '2021-05-11 07:21:33'),
(null, 1, 7022, 1, 0, 0, 0, '2021-05-11 07:21:33'),
(null, 1, 7023, 1, 0, 0, 0, '2021-05-11 07:21:33'),
(null, 1, 7024, 1, 0, 0, 0, '2021-05-11 07:21:33');

UPDATE `sidebar_menus` SET `access_permissions` = '(\'online_course\', \'can_view\') || (\'online_course_offline_payment\', \'can_view\') || (\'student_course_purchase_report\', \'can_view\') || (\'course_sell_count_report\', \'can_view\') || (\'online_course_setting\', \'can_view\')|| (\'course_category\', \'can_view\') || (\'guest_report\', \'can_view\') || (\'course_rating_report\', \'can_view\') || (\'course_assignment_report\', \'can_view\') || (\'course_exam_result_report\', \'can_view\') || (\'course_exam_report\', \'can_view\') || (\'course_exam_attempt_report\', \'can_view\') || (\'online_course_question_bank\', \'can_view\')' WHERE `sidebar_menus`.`id` = 28;

INSERT INTO `sidebar_sub_menus` (`id`, `sidebar_menu_id`, `menu`, `key`, `lang_key`, `url`, `level`, `access_permissions`, `permission_group_id`, `activate_controller`, `activate_methods`, `addon_permission`, `is_active`, `created_at`) VALUES
(222, 28, 'online_course_question_bank', NULL, 'online_course_question_bank', 'onlinecourse/courseexamquestion/index', 3, '(\'online_course_question_bank\', \'can_view\') || (\'online_course_question_bank\', \'can_add\') || (\'online_course_question_bank\', \'can_edit\') || (\'online_course_question_bank\', \'can_delete\')', NULL, 'courseexamquestion', 'index', NULL, 1, '2024-12-24 07:02:50');

ALTER TABLE `online_course_lesson_attachment` ADD `attachment_name` TEXT NOT NULL AFTER `attachment`;

ALTER TABLE online_course_processing_payment ADD processing_charge_type VARCHAR(255) NULL AFTER date, ADD processing_charge_amount FLOAT(10,2) NULL DEFAULT '0.00' AFTER processing_charge_type;

ALTER TABLE online_course_payment ADD processing_charge_type VARCHAR(255) NULL AFTER date, ADD processing_charge_amount FLOAT(10,2) NULL DEFAULT '0.00' AFTER processing_charge_type;

ALTER TABLE `course_progress` ADD `completion_date` DATE NULL DEFAULT NULL AFTER `lesson_quiz_type`;

ALTER TABLE `online_courses` ADD `certificate_template_id` INT NOT NULL AFTER `front_side_visibility`;

CREATE TABLE `online_course_certificate_template` (
  `id` int NOT NULL,
  `certificate_name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `certificate_text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `certificate_template` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `background_image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `status` int NOT NULL DEFAULT '0',
  `create_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `online_course_start_date` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `student_id` int NOT NULL,
  `guest_id` int NOT NULL,
  `start_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

ALTER TABLE `online_course_certificate_template`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `online_course_start_date`
  ADD PRIMARY KEY (`id`);
  
ALTER TABLE `online_course_certificate_template`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
  
  
ALTER TABLE `online_course_start_date`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
  
INSERT INTO `permission_category` (`id`, `perm_group_id`, `name`, `short_code`, `enable_view`, `enable_add`, `enable_edit`, `enable_delete`, `created_at`, `updated_at`) VALUES
(7025, 700, 'Course Certificate Template', 'course_certificate_template', 1, 1, 1, 1, '2025-01-06 10:16:24', '2025-10-04 06:41:15');

UPDATE `sidebar_sub_menus` SET `access_permissions` = '(\'student_course_purchase_report\', \'can_view\') || (\'course_sell_count_report\', \'can_view\') || (\'course_trending_report\', \'can_view\') || (\'course_complete_report\', \'can_view\') || (\'course_rating_report\', \'can_view\') || (\'guest_report\', \'can_view\') || (\'course_assignment_report\', \'can_view\') || (\'course_exam_result_report\', \'can_view\') || (\'course_exam_report\', \'can_view\') || (\'course_exam_attempt_report\', \'can_view\')', `activate_methods` = 'report,coursepurchase,coursesellreport,trendingreport,completereport,courseratingreport,guestlist,quizperformance,course_exam_attempt_report,course_exam_report,result_report,course_assignment_report' WHERE `sidebar_sub_menus`.`id` = 180;

INSERT INTO `sidebar_sub_menus` (`id`, `sidebar_menu_id`, `menu`, `key`, `lang_key`, `url`, `level`, `access_permissions`, `permission_group_id`, `activate_controller`, `activate_methods`, `addon_permission`, `is_active`, `created_at`, `updated_at`) VALUES
(227, 28, 'certificate_template', NULL, 'certificate_template', 'onlinecourse/coursecertificate/createcertificate', 5, '(\'course_certificate_template\', \'can_view\')', NULL, 'coursecertificate', 'createcertificate', 'ssoclc', 1, '2022-05-18 05:50:11', '2025-11-06 12:21:34');


UPDATE `sidebar_menus` SET `access_permissions` = '(\'online_course\', \'can_view\') || (\'online_course_offline_payment\', \'can_view\') || (\'student_course_purchase_report\', \'can_view\') || (\'course_sell_count_report\', \'can_view\') || (\'online_course_setting\', \'can_view\')|| (\'course_category\', \'can_view\') || (\'guest_report\', \'can_view\') || (\'course_rating_report\', \'can_view\') || (\'course_assignment_report\', \'can_view\') || (\'course_exam_result_report\', \'can_view\') || (\'course_exam_report\', \'can_view\') || (\'course_exam_attempt_report\', \'can_view\') || (\'online_course_question_bank\', \'can_view\') || (\'course_certificate_template\',\'can_view\')' WHERE `sidebar_menus`.`id` = 28;

UPDATE `sidebar_sub_menus` SET `level` = '2' WHERE `sidebar_sub_menus`.`id` = 222;

UPDATE `sidebar_sub_menus` SET `level` = '3' WHERE `sidebar_sub_menus`.`id` = 168;

UPDATE `sidebar_sub_menus` SET `level` = '4' WHERE `sidebar_sub_menus`.`id` = 169;

UPDATE `sidebar_sub_menus` SET `level` = '6' WHERE `sidebar_sub_menus`.`id` = 180;

UPDATE `sidebar_sub_menus` SET `level` = '7' WHERE `sidebar_sub_menus`.`id` = 197;

UPDATE `sidebar_sub_menus` SET `access_permissions` = '(\'online_course_question_bank\', \'can_view\') || (\'online_course_question_tag\', \'can_view\')' WHERE `sidebar_sub_menus`.`id` = 222;

ALTER TABLE `online_course_certificate_template` ADD `fontsizeselectblock` INT NULL DEFAULT NULL AFTER `background_image`, ADD `fontsizeselectall` INT NULL DEFAULT NULL AFTER `fontsizeselectblock`;

UPDATE `sidebar_sub_menus` SET `activate_methods` = 'templatelist,addcertificate' WHERE `sidebar_sub_menus`.`id` = 227;

UPDATE `notification_setting` SET `display_whatsapp` = '0' WHERE `notification_setting`.`type` = 'online_course_guest_user_sign_up';

UPDATE `notification_setting` SET `display_whatsapp` = '0' WHERE `notification_setting`.`type` = 'online_course_purchase_for_guest_user';

UPDATE `sidebar_sub_menus` SET `url` = 'onlinecourse/coursecertificate/templatelist' WHERE `sidebar_sub_menus`.`id` = 227;

INSERT INTO `online_course_certificate_template` (`id`, `certificate_name`, `certificate_text`, `certificate_template`, `background_image`, `fontsizeselectblock`, `fontsizeselectall`, `status`, `create_at`) VALUES
(1, 'Sample Transfer Certificate 1', 'This is to certify that Mr./Ms. [student_name] has successfully completed the [course_name] under [assign_teacher].\nThe course ran from [start_date] to [completion_date] for Class [class_name], Section [section_name].\nIssued on [current_date].', '\r\n                                                <img width=\"100%\" id=\"preview\" src=\"default_template.jpg\">\r\n                                                <div class=\"draggable course_name\" id=\"temp_course_name\" style=\"position: absolute; font-size: 20px; top: 433.844px; left: 136.844px;\">\r\n                                                    {course_name}</div>\r\n                                                <div class=\"draggable completion_date\" id=\"temp_completion_date\" style=\"position: absolute; font-size: 20px; top: 462.844px; left: 491.844px;\">\r\n                                                    {completion_date}</div>\r\n\r\n                                                <div class=\"draggable start_date\" id=\"temp_start_date\" style=\"position: absolute; font-size: 20px; top: 427.797px; left: 533.844px;\">\r\n                                                    {start_date}</div>\r\n\r\n                                                <div class=\"draggable current_date\" id=\"temp_current_date\" style=\"position: absolute; font-size: 20px; top: 108.797px; left: 597.844px;\">\r\n                                                    {current_date}</div>\r\n\r\n                                                <div class=\"draggable assign_teacher\" id=\"temp_assign_teacher\" style=\"position: absolute; font-size: 20px; top: 468.797px; left: 135.797px;\">\r\n                                                    {assign_teacher}</div>\r\n\r\n                                                <div class=\"draggable class_name hidden-position\" id=\"temp_class_name\" style=\"position: absolute; font-size: 20px; top: 303.797px; left: 803.797px;\">\r\n                                                    {class_name}</div>\r\n\r\n                                                <div class=\"draggable section_name hidden-position\" id=\"temp_section_name\" style=\"position: absolute; font-size: 20px; top: 342.875px; left: 801.844px;\">\r\n                                                    {section_name}</div>\r\n\r\n                                                <div class=\"draggable student_name hidden-position\" id=\"temp_student_name\" style=\"position: absolute; font-size: 20px; top: 267.922px; left: 795.891px;\">\r\n                                                    {student_name}</div>\r\n\r\n                                                <div class=\"draggable certificate_text\" id=\"temp_certificate_text\" style=\"position: absolute; width: 500px; text-align: center; font-size: 20px; top: 163.938px; font-family: &quot;Pinyon Script&quot;; left: 122.891px;\">This is to certify that Mr./Ms. [student_name] has successfully completed the [course_name] under [assign_teacher].\r\nThe course ran from [start_date] to [completion_date] for Class [class_name], Section [section_name].\r\nIssued on [current_date].</div>\r\n\r\n                                                <div class=\"draggable qrCode\" id=\"qrCode\" style=\"position: absolute; height: 65px; text-align: center; font-size: 20px; top: 88.875px; left: 36.9219px;\">\r\n                                                    <p style=\"text-align: center; padding: 4px 0px;\">{qr_code}</p>\r\n                                                </div>\r\n                                            ', 'default_template.jpg', 20, 20, 0, '2025-11-24 09:00:42'),
(2, 'Sample Transfer Certificate 2', 'This is to certify that Mr./Ms. [student_name] has successfully completed the [course_name] under [assign_teacher].\r\nThe course ran from [start_date] to [completion_date] for Class [class_name], Section [section_name].\r\nIssued on [current_date].', '\r\n                                                <img width=\"100%\" id=\"preview\" src=\"default_template01.jpg\">\r\n                                                <div class=\"draggable course_name hidden-position\" id=\"temp_course_name\" style=\"position: absolute; font-size: 20px; top: 411.844px; left: 797.844px;\">\r\n                                                    {course_name}</div>\r\n                                                <div class=\"draggable completion_date\" id=\"temp_completion_date\" style=\"position: absolute; font-size: 20px; top: 462.844px; left: 539.844px;\">\r\n                                                    {completion_date}</div>\r\n\r\n                                                <div class=\"draggable start_date\" id=\"temp_start_date\" style=\"position: absolute; font-size: 20px; top: 427.797px; left: 586.844px;\">\r\n                                                    {start_date}</div>\r\n\r\n                                                <div class=\"draggable current_date\" id=\"temp_current_date\" style=\"position: absolute; font-size: 20px; top: 495.797px; left: 568.844px;\">\r\n                                                    {current_date}</div>\r\n\r\n                                                <div class=\"draggable assign_teacher hidden-position\" id=\"temp_assign_teacher\" style=\"position: absolute; font-size: 20px; top: 370.797px; left: 792.797px;\">\r\n                                                    {assign_teacher}</div>\r\n\r\n                                                <div class=\"draggable class_name hidden-position\" id=\"temp_class_name\" style=\"position: absolute; font-size: 20px; top: 299.797px; left: 799.797px;\">\r\n                                                    {class_name}</div>\r\n\r\n                                                <div class=\"draggable section_name hidden-position\" id=\"temp_section_name\" style=\"position: absolute; font-size: 20px; top: 336.875px; left: 793.844px;\">\r\n                                                    {section_name}</div>\r\n\r\n                                                <div class=\"draggable student_name hidden-position\" id=\"temp_student_name\" style=\"position: absolute; font-size: 20px; top: 263.922px; left: 794.891px;\">\r\n                                                    {student_name}</div>\r\n\r\n                                                <div class=\"draggable certificate_text\" id=\"temp_certificate_text\" style=\"position: absolute; width: 500px; text-align: center; font-size: 20px; top: 139.938px; font-family: &quot;Pinyon Script&quot;; left: 187.891px;\">This is to certify that Mr./Ms. [student_name] has successfully completed the [course_name] under [assign_teacher].\r\nThe course ran from [start_date] to [completion_date] for Class [class_name], Section [section_name].\r\nIssued on [current_date].</div>\r\n\r\n                                                <div class=\"draggable qrCode\" id=\"qrCode\" style=\"position: absolute; height: 65px; text-align: center; font-size: 20px; top: 431.875px; left: 189.922px;\">\r\n                                                    <p style=\"text-align: center; padding: 4px 0px;\">{qr_code}</p>\r\n                                                </div>\r\n                                            ', 'default_template01.jpg', 20, 20, 0, '2025-11-24 09:00:42'),
(3, 'Sample Transfer Certificate 3', 'This is to certify that Mr./Ms. [student_name] has successfully completed the [course_name] under [assign_teacher].\r\nThe course ran from [start_date] to [completion_date] for Class [class_name], Section [section_name].\r\nIssued on [current_date].', '\r\n                                                <img width=\"100%\" id=\"preview\" src=\"default_template02.jpg\">\r\n                                                <div class=\"draggable course_name hidden-position\" id=\"temp_course_name\" style=\"position: absolute; font-size: 16px; top: 648.844px; left: 129.844px;\">\r\n                                                    {course_name}</div>\r\n                                                <div class=\"draggable completion_date hidden-position\" id=\"temp_completion_date\" style=\"position: absolute; font-size: 16px; top: 684.844px; left: 485.844px;\">\r\n                                                    {completion_date}</div>\r\n\r\n                                                <div class=\"draggable start_date hidden-position\" id=\"temp_start_date\" style=\"position: absolute; font-size: 16px; top: 650.797px; left: 533.844px;\">\r\n                                                    {start_date}</div>\r\n\r\n                                                <div class=\"draggable current_date\" id=\"temp_current_date\" style=\"position: absolute; font-size: 16px; top: 303.797px; left: 598.844px;\">\r\n                                                    {current_date}</div>\r\n\r\n                                                <div class=\"draggable assign_teacher hidden-position\" id=\"temp_assign_teacher\" style=\"position: absolute; font-size: 16px; top: 681.797px; left: 130.797px;\">\r\n                                                    {assign_teacher}</div>\r\n\r\n                                                <div class=\"draggable class_name hidden-position\" id=\"temp_class_name\" style=\"position: absolute; font-size: 16px; top: 683.797px; left: 318.797px;\">\r\n                                                    {class_name}</div>\r\n\r\n                                                <div class=\"draggable section_name hidden-position\" id=\"temp_section_name\" style=\"position: absolute; font-size: 16px; top: 715.875px; left: 315.844px;\">\r\n                                                    {section_name}</div>\r\n\r\n                                                <div class=\"draggable student_name hidden-position\" id=\"temp_student_name\" style=\"position: absolute; font-size: 16px; top: 647.922px; left: 308.891px;\">\r\n                                                    {student_name}</div>\r\n\r\n                                                <div class=\"draggable certificate_text\" id=\"temp_certificate_text\" style=\"position: absolute; width: 500px; text-align: center; font-size: 28px; top: 358.938px; font-family: &quot;Pinyon Script&quot;; left: 130.891px;\">This is to certify that Mr./Ms. [student_name] has successfully completed the [course_name] under [assign_teacher].\r\nThe course ran from [start_date] to [completion_date] for Class [class_name], Section [section_name].\r\nIssued on [current_date].</div>\r\n\r\n                                                <div class=\"draggable qrCode\" id=\"qrCode\" style=\"position: absolute; height: 65px; text-align: center; font-size: 20px; top: 273.875px; left: 53.9219px;\">\r\n                                                    <p style=\"text-align: center; padding: 4px 0px;\">{qr_code}</p>\r\n                                                </div>\r\n                                            ', 'default_template02.jpg', 0, 0, 0, '2025-11-24 09:00:42'),
(4, 'Sample Transfer Certificate 4', 'This is to certify that Mr./Ms. [student_name] has successfully completed the [course_name] under [assign_teacher].\r\nThe course ran from [start_date] to [completion_date] for Class [class_name], Section [section_name].\r\nIssued on [current_date].', '\r\n                                                <img width=\"100%\" id=\"preview\" src=\"default_template03.jpg\">\r\n                                                <div class=\"draggable course_name hidden-position\" id=\"temp_course_name\" style=\"position: absolute; font-size: 16px; top: 621.844px; left: 130.844px;\">\r\n                                                    {course_name}</div>\r\n                                                <div class=\"draggable completion_date hidden-position\" id=\"temp_completion_date\" style=\"position: absolute; font-size: 16px; top: 614.844px; left: 487.844px;\">\r\n                                                    {completion_date}</div>\r\n\r\n                                                <div class=\"draggable start_date hidden-position\" id=\"temp_start_date\" style=\"position: absolute; font-size: 16px; top: 579.797px; left: 533.844px;\">\r\n                                                    {start_date}</div>\r\n\r\n                                                <div class=\"draggable current_date\" id=\"temp_current_date\" style=\"position: absolute; font-size: 16px; top: 235.797px; left: 592.844px;\">\r\n                                                    {current_date}</div>\r\n\r\n                                                <div class=\"draggable assign_teacher hidden-position\" id=\"temp_assign_teacher\" style=\"position: absolute; font-size: 16px; top: 663.797px; left: 130.797px;\">\r\n                                                    {assign_teacher}</div>\r\n\r\n                                                <div class=\"draggable class_name hidden-position\" id=\"temp_class_name\" style=\"position: absolute; font-size: 16px; top: 578.797px; left: 265.797px;\">\r\n                                                    {class_name}</div>\r\n\r\n                                                <div class=\"draggable section_name hidden-position\" id=\"temp_section_name\" style=\"position: absolute; font-size: 16px; top: 578.875px; left: 379.844px;\">\r\n                                                    {section_name}</div>\r\n\r\n                                                <div class=\"draggable student_name hidden-position\" id=\"temp_student_name\" style=\"position: absolute; font-size: 16px; top: 579.922px; left: 130.891px;\">\r\n                                                    {student_name}</div>\r\n\r\n                                                <div class=\"draggable certificate_text\" id=\"temp_certificate_text\" style=\"position: absolute; width: 500px; text-align: center; font-size: 28px; top: 279.938px; font-family: &quot;Pinyon Script&quot;; left: 130.891px;\">This is to certify that Mr./Ms. [student_name] has successfully completed the [course_name] under [assign_teacher].\r\nThe course ran from [start_date] to [completion_date] for Class [class_name], Section [section_name].\r\nIssued on [current_date].</div>\r\n\r\n                                                <div class=\"draggable qrCode\" id=\"qrCode\" style=\"position: absolute; height: 65px; text-align: center; font-size: 20px; top: 200.875px; left: 339.922px;\">\r\n                                                    <p style=\"text-align: center; padding: 4px 0px;\">{qr_code}</p>\r\n                                                </div>\r\n                                            ', 'default_template03.jpg', 0, 0, 0, '2025-11-24 09:00:42');