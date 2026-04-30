-- Smart School Gmeet Live Classes Uninstall DB
-- Version 7.0
-- https://smart-school.in
-- https://qdocs.net
-- Tables drop: 5


DROP TABLE IF EXISTS `gmeet_staff`;
DROP TABLE IF EXISTS `gmeet_sections`;
DROP TABLE IF EXISTS `gmeet_history`;
DROP TABLE IF EXISTS `gmeet` ;
DROP TABLE IF EXISTS `gmeet_settings`;

-- --------------------------------------------------------

DELETE from `notification_setting` where type='gmeet_online_meeting_start';
DELETE from `notification_setting` where type='gmeet_online_classes_start';
DELETE from `notification_setting` where type='gmeet_online_meeting';
DELETE from `notification_setting` where type='gmeet_online_classes';

DELETE from `permission_group` where name='Gmeet Live Classes' and  short_code='gmeet_live_classes';

DELETE from `permission_student` where name='Gmeet Live Classes' and  short_code='gmeet_live_classes';

DELETE FROM `sidebar_menus` WHERE lang_key='gmeet_live_classes';


update `addons` set current_version =NULL, installation_by=NULL WHERE product_id=28941973;


