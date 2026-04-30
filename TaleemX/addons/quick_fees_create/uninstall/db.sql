-- Smart School Quick Fees Create Uninstall DB
-- Version 2.0
-- https://smart-school.in
-- https://qdocs.net
-- Tables drop: 0

-- --------------------------------------------------------

DELETE from `permission_group` where id=1300;

DELETE from `permission_category` where id=13001; 

DELETE from `sidebar_menus` where id=38;

DELETE from `sidebar_sub_menus` where id=220;

update `addons` set current_version =NULL, installation_by=NULL WHERE product_id='57220011';


