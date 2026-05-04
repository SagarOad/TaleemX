<aside class="main-sidebar" id="alert2">
    <?php if ($this->rbac->hasPrivilege('student', 'can_view')) {?>
        <form class="navbar-form navbar-left search-form2" role="search"  action="<?php echo site_url('admin/admin/search'); ?>" method="POST">
            <?php echo $this->customlib->getCSRF(); ?>
            <div class="input-group ">
                <input type="text"  name="search_text" class="form-control search-form" placeholder="<?php echo $this->lang->line('search_by_student_name'); ?>">
                <span class="input-group-btn">
                    <button type="submit" name="search" id="search-btn" style="padding: 3px 12px !important;border-radius: 0px 30px 30px 0px;" class="btn btn-flat search-btn-sm"><i class="fa fa-search"></i></button>
                </span>
            </div>
        </form>
    <?php }?>
    <section class="sidebar" id="sibe-box">
        <?php $this->load->view('layout/top_sidemenu');?>

        <ul class="sidebar-menu verttop">
            
<!-- //==================sidebar dynamic======================= -->

<?php
$side_list = side_menu_list(1);
$sidebar_section_map = [
    'front_office' => 'Front Desk',
    'student_information' => 'Front Desk',
    'front_cms' => 'Front Desk',

    'fees_collection' => 'Accounts',
    'income' => 'Accounts',
    'expense' => 'Accounts',

    'online_course' => 'LMS',
    'lesson_plan' => 'LMS',
    'homework' => 'LMS',
    'examinations' => 'LMS',
    'online_examinations' => 'LMS',
    'gmeet_live_classes' => 'LMS',
    'zoom_live_classes' => 'LMS',
    'teams_live_classes' => 'LMS',
    'ssmb' => 'LMS',
    'multi_branch' => 'LMS',

    'academics' => 'Academics',
    'behaviour_records' => 'Academics',

    'human_resource' => 'HR',
    'attendance' => 'HR',
    'qr_code_attendance' => 'HR',
    'annual_calendar' => 'HR',

    'communicate' => 'General',
    'download_center' => 'General',
    'library' => 'General',
    'alumni' => 'General',
    'transport' => 'General',
    'student_cv' => 'General',
    'hostel' => 'General',
    'certificate' => 'General',
    'inventory' => 'General',
    'reports' => 'General',
    'system_settings' => 'General',
];
$sidebar_section_order = ['Front Desk', 'Accounts', 'LMS', 'Academics', 'HR', 'General'];
$sidebar_item_order_map = [
    'Front Desk' => ['front_office', 'student_information', 'front_cms'],
    'Accounts' => ['fees_collection', 'income', 'expense', 'expenses'],
    'LMS' => ['online_course', 'lesson_plan', 'homework', 'examinations', 'online_examinations', 'gmeet_live_classes', 'zoom_live_classes', 'teams_live_classes', 'ssmb', 'multi_branch'],
    'Academics' => ['academics', 'behaviour_records'],
    'HR' => ['human_resource', 'attendance', 'qr_code_attendance', 'annual_calendar'],
    'General' => ['communicate', 'download_center', 'library', 'inventory', 'transport', 'student_cv', 'hostel', 'certificate', 'alumni', 'reports', 'system_settings'],
];
$sidebar_grouped_items  = [];
foreach ($sidebar_section_order as $section_name) {
    $sidebar_grouped_items[$section_name] = [];
}

if (!empty($side_list)) {
    foreach ($side_list as $side_list_key => $side_list_value) {

        $module_permission = access_permission_sidebar_remove_pipe($side_list_value->access_permissions);
        $module_access     = false;
        if (!empty($module_permission)) {
            foreach ($module_permission as $m_permission_key => $m_permission_value) {
                $cat_permission = access_permission_remove_comma($m_permission_value);

                if ($this->rbac->hasPrivilege($cat_permission[0], $cat_permission[1])) {
                    $module_access = true;
                    break;
                }
            }
        }
        if ($module_access) {
            if ($this->module_lib->hasModule($side_list_value->short_code) && $this->module_lib->hasActive($side_list_value->short_code)) {
                $section_key = strtolower((string) $side_list_value->short_code);
                if (!isset($sidebar_section_map[$section_key])) {
                    $section_key = strtolower((string) $side_list_value->lang_key);
                }
                $sidebar_section_label = isset($sidebar_section_map[$section_key]) ? $sidebar_section_map[$section_key] : 'General';
                $sidebar_grouped_items[$sidebar_section_label][] = $side_list_value;
            }
        }
    }

    foreach ($sidebar_section_order as $sidebar_section_label) {
        if (empty($sidebar_grouped_items[$sidebar_section_label])) {
            continue;
        }
        if (isset($sidebar_item_order_map[$sidebar_section_label])) {
            $sidebar_sort_order = array_flip($sidebar_item_order_map[$sidebar_section_label]);
            usort($sidebar_grouped_items[$sidebar_section_label], function ($a, $b) use ($sidebar_sort_order) {
                $a_key = strtolower((string) $a->short_code);
                $b_key = strtolower((string) $b->short_code);
                if (!isset($sidebar_sort_order[$a_key])) {
                    $a_key = strtolower((string) $a->lang_key);
                }
                if (!isset($sidebar_sort_order[$b_key])) {
                    $b_key = strtolower((string) $b->lang_key);
                }
                $a_pos = isset($sidebar_sort_order[$a_key]) ? $sidebar_sort_order[$a_key] : PHP_INT_MAX;
                $b_pos = isset($sidebar_sort_order[$b_key]) ? $sidebar_sort_order[$b_key] : PHP_INT_MAX;
                if ($a_pos === $b_pos) {
                    return strcmp((string) $a->lang_key, (string) $b->lang_key);
                }
                return $a_pos - $b_pos;
            });
        }
        if ($sidebar_section_label !== '__NO_HEADING__') {
        ?>
        <li class="ss-sidebar-section-title"><span><?php echo html_escape($sidebar_section_label); ?></span></li>
        <?php
        }
        $is_first_group_item = true;
        foreach ($sidebar_grouped_items[$sidebar_section_label] as $side_list_value) {
            $is_no_heading_group = ($sidebar_section_label === '__NO_HEADING__');
            ?>
            <li class="treeview <?php echo activate_main_menu($side_list_value->activate_menu); ?> <?php echo $is_no_heading_group ? 'ss-sidebar-no-indent' : 'ss-sidebar-indented'; ?> <?php echo ($is_no_heading_group && $is_first_group_item) ? 'ss-sidebar-no-heading-start' : ''; ?>">
                <a href="#">
                    <i class="<?php echo $side_list_value->icon; ?>"></i> <span><?php echo $this->lang->line($side_list_value->lang_key); ?></span> <i class="icon-chevron-left pull-right"></i>
                </a>
                <?php if (!empty($side_list_value->submenus)) { ?>
                    <ul class="treeview-menu">
                        <?php
foreach ($side_list_value->submenus as $submenu_key => $submenu_value) {
                $sidebar_permission = access_permission_sidebar_remove_pipe($submenu_value->access_permissions);
                $sidebar_access     = false;

                if (!empty($sidebar_permission)) {
                    foreach ($sidebar_permission as $sidebar_permission_key => $sidebar_permission_value) {
                        $sidebar_cat_permission = access_permission_remove_comma($sidebar_permission_value);

                        if ($submenu_value->addon_permission != "") {
                            if ($this->rbac->hasPrivilege($sidebar_cat_permission[0], $sidebar_cat_permission[1])
                                && $this->auth->addonchk($submenu_value->addon_permission, false)) {
                                $sidebar_access = true;
                                break;
                            }
                        } else {
                            if ($this->rbac->hasPrivilege($sidebar_cat_permission[0], $sidebar_cat_permission[1])) {
                                $sidebar_access = true;
                                break;
                            }
                        }
                    }
                }

                if ($sidebar_access) {
                    if (!empty($submenu_value->permission_group_id)) {
                        if (!$this->module_lib->hasActive($submenu_value->short_code)) {
                            continue;
                        }
                    }
                    ?>
                            <li class="<?php echo activate_submenu($submenu_value->activate_controller, explode(',', $submenu_value->activate_methods)); ?>"><a href="<?php echo site_url($submenu_value->url); ?>"><i class="icon-circle-small"></i><?php echo $this->lang->line($submenu_value->lang_key); ?></a></li>
                            <?php
}
            }
            ?>
                    </ul>
                <?php } ?>
            </li>
            <?php
            $is_first_group_item = false;
        }
    }
}
?>
        <!-- //==================sidebar dynamic======================= -->

        </ul>
    </section>
</aside>