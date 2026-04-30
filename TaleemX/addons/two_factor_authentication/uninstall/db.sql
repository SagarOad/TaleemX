-- Smart School Two Factor Authentication Uninstall DB
-- Version 4.0
-- https://smart-school.in
-- https://qdocs.net
-- Tables drop: 2


DROP TABLE IF EXISTS `google_authenticator`; 
DROP TABLE IF EXISTS `user_google_authenticate_codes`; 

-- --------------------------------------------------------

DELETE from `permission_group` where id=1100;
DELETE from `permission_category` where id=11001;
DELETE from `permission_category` where id=11002; 
DELETE from `sidebar_menus` where id=32;
DELETE from `sidebar_sub_menus` where id=194;
DELETE from `sidebar_sub_menus` where id=195;

update `addons` set current_version =NULL, installation_by=NULL WHERE product_id=44278049;


