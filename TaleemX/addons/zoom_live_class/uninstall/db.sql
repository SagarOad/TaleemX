-- Smart School Smart School Zoom Live Classes Uninstall DB
-- Version 8.0
-- https://smart-school.in
-- https://qdocs.net
-- Tables drop: 5

DROP TABLE IF EXISTS `conferences_history`;
DROP TABLE IF EXISTS `conference_sections`;
DROP TABLE IF EXISTS `conference_staff`;
DROP TABLE IF EXISTS `conferences`;
DROP TABLE IF EXISTS `zoom_settings`; 

-- --------------------------------------------------------

DELETE from `notification_setting` where type='online_classes';
DELETE from `notification_setting` where type='online_meeting';
DELETE from `notification_setting` where type='zoom_online_classes_start';
DELETE from `notification_setting` where type='zoom_online_meeting_start';

ALTER TABLE `sch_settings`
  DROP `zoom_api_key`,
  DROP `zoom_api_secret`;
  
ALTER TABLE `staff`
  DROP `zoom_api_key`,
  DROP `zoom_api_secret`;

DELETE from `permission_group` where id=500;

DELETE from `permission_category` where id=5001;
DELETE from `permission_category` where id=5002;
DELETE from `permission_category` where id=5003;
DELETE from `permission_category` where id=5004;
DELETE from `permission_category` where id=5005;

DELETE from `permission_student` where id=500;
 
DELETE from `sidebar_menus` where id=30;

DELETE from `sidebar_sub_menus` where id=175;
DELETE from `sidebar_sub_menus` where id=176;
DELETE from `sidebar_sub_menus` where id=177;
DELETE from `sidebar_sub_menus` where id=178;
DELETE from `sidebar_sub_menus` where id=179;

update `addons` set current_version =NULL, installation_by=NULL WHERE product_id=27492043;
