-- Smart School Behaviour Records Uninstall DB
-- Version 4.0
-- https://smart-school.in
-- https://qdocs.net

DROP TABLE IF EXISTS `behaviour_settings`;
DROP TABLE IF EXISTS `student_incident_comments`;
DROP TABLE IF EXISTS `student_incidents`;
DROP TABLE IF EXISTS `student_behaviour`;

-- --------------------------------------------------------

DELETE FROM `notification_setting` WHERE TYPE='behaviour_incident_assigned';

DELETE FROM `permission_group` WHERE ID='800';
DELETE FROM `permission_student` WHERE ID='800';
DELETE FROM `permission_category` WHERE ID='8001';
DELETE FROM `permission_category` WHERE ID='8002';
DELETE FROM `permission_category` WHERE ID='8003';
DELETE FROM `permission_category` WHERE ID='8004';
DELETE FROM `permission_category` WHERE ID='8005';
DELETE FROM `permission_category` WHERE ID='8006';
DELETE FROM `permission_category` WHERE ID='8007';
DELETE FROM `permission_category` WHERE ID='8008';
DELETE FROM `permission_category` WHERE ID='8009';

DELETE FROM `sidebar_menus` WHERE ID=31;

DELETE FROM `sidebar_sub_menus` WHERE ID=186;
DELETE FROM `sidebar_sub_menus` WHERE ID=187;
DELETE FROM `sidebar_sub_menus` WHERE ID=188;
DELETE FROM `sidebar_sub_menus` WHERE ID=189;

update `addons` set current_version =NULL, installation_by=NULL WHERE product_id=44247532;
