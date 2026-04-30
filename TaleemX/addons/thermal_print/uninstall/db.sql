-- Smart School Thermal Print Uninstall DB
-- Version 2.0
-- https://smart-school.in
-- https://qdocs.net
-- Tables drop: 1


DROP TABLE IF EXISTS `thermal_print_settings`; 

-- --------------------------------------------------------

DELETE from `permission_group` where id=1400;

DELETE from `permission_category` where id=14001; 

DELETE from `sidebar_menus` where id=39;

DELETE from `sidebar_sub_menus` where id=221;

update `addons` set current_version =NULL, installation_by=NULL WHERE product_id='57219905';


