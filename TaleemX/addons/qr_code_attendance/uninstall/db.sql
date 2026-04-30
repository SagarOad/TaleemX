-- Smart School QR Code Attendance Uninstall DB
-- Version 3.0
-- https://smart-school.in
-- https://qdocs.net
-- Tables drop: 1

DROP TABLE IF EXISTS `QR_code_settings`;

-- --------------------------------------------------------

DELETE from `permission_group` where id='1200';
DELETE from `permission_category` where id='12001';
DELETE from `permission_category` where id='12002';
DELETE from `sidebar_menus` where id='35';
DELETE from `sidebar_sub_menus` where id='213';
DELETE from `sidebar_sub_menus` where id='214';

update `addons` set current_version =NULL, installation_by=NULL WHERE product_id=50336584;
