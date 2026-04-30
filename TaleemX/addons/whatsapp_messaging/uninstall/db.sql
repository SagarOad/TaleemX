-- Smart School Whatsapp Messaging Uninstall DB
-- Version 1.0
-- https://smart-school.in
-- https://qdocs.net


DROP TABLE IF EXISTS `whatsapp_config`; 

-- --------------------------------------------------------

DELETE from `permission_group` where id=1500;

DELETE from `permission_category` where id=15001; 


DELETE from `sidebar_sub_menus` where id=226;

update `addons` set current_version =NULL, installation_by=NULL WHERE product_id='999999999';


