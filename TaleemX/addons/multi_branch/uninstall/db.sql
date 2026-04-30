-- Smart School Multi Branch Uninstall DB
-- Version 3.0
-- https://smart-school.in
-- https://qdocs.net
-- Tables drop: 1

DROP TABLE IF EXISTS `multi_branch`; 

-- --------------------------------------------------------

DELETE from `permission_group` where id=1000;
DELETE from `permission_category` where id=10001;
DELETE from `permission_category` where id=10002;
DELETE from `permission_category` where id=10003;
DELETE from `permission_category` where id=10004;
DELETE from `permission_category` where id=10005;
DELETE from `permission_category` where id=10006;
DELETE from `permission_category` where id=10007;
DELETE from `permission_category` where id=10008;
 
DELETE from `sidebar_menus` where id=33;
DELETE from `sidebar_sub_menus` where id=198;
DELETE from `sidebar_sub_menus` where id=199;
DELETE from `sidebar_sub_menus` where id=200;

update `addons` set current_version =NULL, installation_by=NULL WHERE product_id=44277916;
