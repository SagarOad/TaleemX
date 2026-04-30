-- Smart School Online Course Uninstall DB
-- Version 5.0
-- https://smart-school.in
-- https://qdocs.net
-- Tables drop: 29

DROP TABLE IF EXISTS `online_course_assignment_evaluation`;
DROP TABLE IF EXISTS `online_course_assignment_submit`;
DROP TABLE IF EXISTS `online_course_assignment`;
DROP TABLE IF EXISTS `online_course_exam_attempts`;
DROP TABLE IF EXISTS `online_course_exam_marks`;
DROP TABLE IF EXISTS `online_course_exam_question`;
DROP TABLE IF EXISTS `online_course_exam_result`;
DROP TABLE IF EXISTS `online_course_exam`;
DROP TABLE IF EXISTS `online_course_lesson_attachment`;
DROP TABLE IF EXISTS `course_lesson_quiz_order`;
DROP TABLE IF EXISTS `course_progress`;
DROP TABLE IF EXISTS `course_quiz_answer`;
DROP TABLE IF EXISTS `course_rating`;
DROP TABLE IF EXISTS `online_course_class_sections`;
DROP TABLE IF EXISTS `online_course_lesson`;
DROP TABLE IF EXISTS `online_course_payment`;
DROP TABLE IF EXISTS `online_course_processing_payment`;
DROP TABLE IF EXISTS `student_quiz_status`;
DROP TABLE IF EXISTS `course_quiz_question`;
DROP TABLE IF EXISTS `online_course_quiz`;
DROP TABLE IF EXISTS `online_course_section`;
DROP TABLE IF EXISTS `online_courses`;
DROP TABLE IF EXISTS `course_category`;
DROP TABLE IF EXISTS `online_course_settings`;
DROP TABLE IF EXISTS `aws_s3_settings`;
DROP TABLE IF EXISTS `online_course_tag`;
DROP TABLE IF EXISTS `guest`;
DROP TABLE IF EXISTS `online_course_certificate_template`; 
DROP TABLE IF EXISTS `online_course_start_date`;

-- -------------------------------------------------------- 

DELETE FROM `captcha` WHERE ID='6';

DELETE FROM `notification_setting` WHERE TYPE='online_course_publish';
DELETE FROM `notification_setting` WHERE TYPE='online_course_purchase';
DELETE FROM `notification_setting` WHERE TYPE='online_course_purchase_for_guest_user';
DELETE FROM `notification_setting` WHERE TYPE='online_course_guest_user_sign_up';

DELETE FROM `permission_group` WHERE ID='700';
DELETE FROM `permission_student` WHERE ID='700';

DELETE FROM `permission_category` WHERE ID='7001';
DELETE FROM `permission_category` WHERE ID='7002';
DELETE FROM `permission_category` WHERE ID='7003';
DELETE FROM `permission_category` WHERE ID='7004';
DELETE FROM `permission_category` WHERE ID='7005';
DELETE FROM `permission_category` WHERE ID='7006';
DELETE FROM `permission_category` WHERE ID='7007';
DELETE FROM `permission_category` WHERE ID='7008';
DELETE FROM `permission_category` WHERE ID='7009';
DELETE FROM `permission_category` WHERE ID='7010';
DELETE FROM `permission_category` WHERE ID='7011';
DELETE FROM `permission_category` WHERE ID='7012';
DELETE FROM `permission_category` WHERE ID='7013';
DELETE FROM `permission_category` WHERE ID='7014';
DELETE FROM `permission_category` WHERE ID='7015';
DELETE FROM `permission_category` WHERE ID='7016';
DELETE FROM `permission_category` WHERE ID='7017';
DELETE FROM `permission_category` WHERE ID='7018';
DELETE FROM `permission_category` WHERE ID='7019';
DELETE FROM `permission_category` WHERE ID='7020';
DELETE FROM `permission_category` WHERE ID='7021';
DELETE FROM `permission_category` WHERE ID='7022';
DELETE FROM `permission_category` WHERE ID='7023';
DELETE FROM `permission_category` WHERE ID='7024'; 
DELETE FROM `permission_category` WHERE ID='7025'; 

DELETE FROM `sidebar_menus` WHERE ID=28; 

DELETE FROM `sidebar_sub_menus` WHERE ID=167; 
DELETE FROM `sidebar_sub_menus` WHERE ID=168; 
DELETE FROM `sidebar_sub_menus` WHERE ID=169; 
DELETE FROM `sidebar_sub_menus` WHERE ID=197; 
DELETE FROM `sidebar_sub_menus` WHERE ID=180; 
DELETE FROM `sidebar_sub_menus` WHERE ID=221; 
DELETE FROM `sidebar_sub_menus` WHERE ID=227; 

update `addons` set current_version =NULL, installation_by=NULL WHERE product_id=33101540;
