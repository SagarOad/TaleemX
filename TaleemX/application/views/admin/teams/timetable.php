<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-mortar-board"></i> <?php echo $this->lang->line('live_class'); ?></h1>
    </section>
    <section class="content">
        <div class="row">
            <?php if ($this->rbac->hasPrivilege('teams_live_classes', 'can_add')) { ?>
            <div class="col-md-4">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-plus"></i> <span id="teams_form_title"><?php echo $this->lang->line('add_live_class'); ?></span></h3>
                    </div>
                    <form id="teams-class-form" action="<?php echo site_url('admin/teams/add'); ?>" method="POST">
                        <div class="box-body">
                            <?php echo $this->customlib->getCSRF(); ?>
                            <input type="hidden" name="teams_id" id="teams_id" value="">
                            <div class="form-group">
                                <label><?php echo $this->lang->line('class_title'); ?><small class="req"> *</small></label>
                                <input type="text" class="form-control" name="title">
                            </div>
                            <div class="form-group">
                                <label><?php echo $this->lang->line('class_date_time'); ?><small class="req"> *</small></label>
                                <div class="input-group" id="teams_class_date">
                                    <input type="text" class="form-control" name="date" readonly="readonly">
                                    <span class="input-group-addon"><span class="fa fa-calendar"></span></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><?php echo $this->lang->line('class_duration_minutes'); ?><small class="req"> *</small></label>
                                <input type="number" class="form-control" name="duration">
                            </div>
                            <?php if ($role->id == 2) { ?>
                                <input type="hidden" name="staff_id" value="<?php echo $logged_staff_id; ?>">
                            <?php } else { ?>
                                <div class="form-group">
                                    <label><?php echo $this->lang->line('staff'); ?><small class="req"> *</small></label>
                                    <select name="staff_id" class="form-control">
                                        <option value=""><?php echo $this->lang->line('select'); ?></option>
                                        <?php foreach ($stafflist as $staff) { ?>
                                            <option value="<?php echo $staff['id']; ?>"><?php echo $staff['name'] . ' ' . $staff['surname'] . ' (' . $staff['employee_id'] . ')'; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            <?php } ?>
                            <div class="form-group">
                                <label><?php echo $this->lang->line('class'); ?><small class="req"> *</small></label>
                                <select id="class_id" name="class_id" class="form-control">
                                    <option value=""><?php echo $this->lang->line('select'); ?></option>
                                    <?php foreach ($classlist as $class) { ?>
                                        <option value="<?php echo $class['id']; ?>"><?php echo $class['class']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo $this->lang->line('section'); ?><small class="req"> *</small></label>
                                <select id="section_id" name="section_id[]" class="form-control">
                                    <option value=""><?php echo $this->lang->line('select'); ?></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?php echo $this->lang->line('teams_url'); ?><small class="req"> *</small></label>
                                <input type="text" class="form-control" name="url" placeholder="https://teams.microsoft.com/...">
                            </div>
                            <div class="form-group">
                                <label><?php echo $this->lang->line('description'); ?></label>
                                <textarea class="form-control" name="description"></textarea>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-info"><?php echo $this->lang->line('save'); ?></button>
                            <button type="button" class="btn btn-default" id="teams-form-reset"><?php echo $this->lang->line('cancel'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
            <?php } ?>
            <div class="<?php echo $this->rbac->hasPrivilege('teams_live_classes', 'can_add') ? 'col-md-8' : 'col-md-12'; ?>">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> <?php echo $this->lang->line('scheduled_live_class'); ?></h3>
                    </div>
                    <div class="box-body">
                        <?php if ($this->session->flashdata('msg')) { ?>
                            <?php echo $this->session->flashdata('msg'); $this->session->unset_userdata('msg'); ?>
                        <?php } ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-bordered example">
                                <thead>
                                    <tr>
                                        <th><?php echo $this->lang->line('class_title'); ?></th>
                                        <th><?php echo $this->lang->line('date_time'); ?></th>
                                        <th><?php echo $this->lang->line('class_duration_minutes'); ?></th>
                                        <th><?php echo $this->lang->line('created_by'); ?></th>
                                        <th><?php echo $this->lang->line('class'); ?></th>
                                        <th><?php echo $this->lang->line('status'); ?></th>
                                        <th class="text-right noExport"><?php echo $this->lang->line('action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teams as $teams_value) { ?>
                                        <?php
                                        $teams_classes_for_edit = (array) $teams_value->classes;
                                        $teams_first_class      = !empty($teams_classes_for_edit) ? reset($teams_classes_for_edit) : null;
                                        $teams_edit_class_id   = !empty($teams_first_class) ? (int) $teams_first_class->class_id : 0;
                                        $teams_edit_section_id = !empty($teams_first_class) ? (int) $teams_first_class->cls_section_id : 0;
                                        ?>
                                        <tr>
                                            <td><?php echo $teams_value->title; ?><br><small><?php echo $teams_value->description; ?></small></td>
                                            <td data-sort="<?php echo strtotime($teams_value->date); ?>"><?php echo $this->customlib->dateyyyymmddToDateTimeformat($teams_value->date); ?></td>
                                            <td><?php echo $teams_value->duration; ?></td>
                                            <td>
                                                <?php
                                                if ($teams_value->created_id == $logged_staff_id) {
                                                    echo $this->lang->line('self');
                                                } elseif ($superadmin_visible == 'disabled' && $teams_value->role_id == 7) {
                                                    echo '';
                                                } else {
                                                    echo $teams_value->create_by_name . " " . $teams_value->create_by_surname . " (" . $teams_value->create_by_employee_id . ")";
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <ul class="liststyle1">
                                                    <?php foreach ($teams_value->classes as $teams_class_value) { ?>
                                                        <li><i class="fa fa-check-square-o"></i> <?php echo $teams_class_value->class . " (" . $teams_class_value->section . ")"; ?></li>
                                                    <?php } ?>
                                                </ul>
                                            </td>
                                            <td width="110">
                                                <form class="chgstatus_form" method="POST" action="<?php echo site_url('admin/teams/chgstatus'); ?>">
                                                    <?php echo $this->customlib->getCSRF(); ?>
                                                    <input type="hidden" name="teams_id" value="<?php echo $teams_value->id; ?>">
                                                    <select class="form-control chgstatus_dropdown" name="chg_status">
                                                        <option value="0" <?php echo ($teams_value->status == 0) ? "selected='selected'" : ""; ?>><?php echo $this->lang->line('awaited'); ?></option>
                                                        <option value="1" <?php echo ($teams_value->status == 1) ? "selected='selected'" : ""; ?>><?php echo $this->lang->line('cancelled'); ?></option>
                                                        <option value="2" <?php echo ($teams_value->status == 2) ? "selected='selected'" : ""; ?>><?php echo $this->lang->line('finished'); ?></option>
                                                    </select>
                                                </form>
                                            </td>
                                            <td class="text-right">
                                                <?php if ($teams_value->status == 0) { ?>
                                                    <a href="<?php echo base_url(); ?>admin/teams/start/<?php echo $teams_value->id; ?>" class="btn label-success btn-xs" target="_blank"><span class="label"><i class="fa fa-sign-in"></i> <?php echo $this->lang->line('start'); ?></span></a>
                                                <?php } ?>
                                                <?php if ($teams_value->created_id == $logged_staff_id && $teams_value->status == 0 && $this->rbac->hasPrivilege('teams_live_classes', 'can_add')) { ?>
                                                    <a href="#" class="btn btn-default btn-xs teams-edit-class"
                                                        data-id="<?php echo (int) $teams_value->id; ?>"
                                                        data-title="<?php echo html_escape($teams_value->title); ?>"
                                                        data-date="<?php echo html_escape($teams_value->date); ?>"
                                                        data-duration="<?php echo html_escape($teams_value->duration); ?>"
                                                        data-staff-id="<?php echo (int) $teams_value->staff_id; ?>"
                                                        data-class-id="<?php echo $teams_edit_class_id; ?>"
                                                        data-section-id="<?php echo $teams_edit_section_id; ?>"
                                                        data-url="<?php echo html_escape($teams_value->url); ?>"
                                                        data-description="<?php echo html_escape($teams_value->description); ?>"
                                                        data-toggle="tooltip" title="<?php echo $this->lang->line('edit'); ?>"><i class="fa fa-pencil"></i></a>
                                                <?php } ?>
                                                <?php if ($teams_value->created_id == $logged_staff_id && $this->rbac->hasPrivilege('teams_live_classes', 'can_delete')) { ?>
                                                    <a href="<?php echo base_url(); ?>admin/teams/delete/<?php echo $teams_value->id; ?>" class="btn btn-primary btn-xs" data-toggle="tooltip" title="<?php echo $this->lang->line('delete'); ?>" onclick="return confirm('<?php echo $this->lang->line('delete_confirm'); ?>');"><i class="fa fa-remove"></i></a>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript">
    (function ($) {
        "use strict";
        var base_url = '<?php echo base_url(); ?>';
        var teamsAddUrl = '<?php echo site_url('admin/teams/add'); ?>';
        var teamsUpdateUrl = '<?php echo site_url('admin/teams/update_live_class'); ?>';
        var datetime_format = '<?php echo strtr($this->customlib->getSchoolDateFormat(), ['d' => 'DD', 'm' => 'MM', 'M' => 'MMM', 'Y' => 'YYYY']); ?>';

        $('#teams_class_date').datetimepicker({
            format: datetime_format + " HH:mm",
            showTodayButton: true,
            minDate: moment().startOf('day'),
            ignoreReadonly: true
        });

        $('#class_id').on('change', function () {
            getSectionByClass($(this).val());
        });

        $('#teams-form-reset').on('click', function () {
            resetTeamsClassForm();
        });

        $(document).on('click', '.teams-edit-class', function (e) {
            e.preventDefault();
            var $btn = $(this);
            $('#teams_form_title').text('<?php echo $this->lang->line('edit') . ' ' . $this->lang->line('live_class'); ?>');
            $('#teams-class-form').attr('action', teamsUpdateUrl);
            $('#teams_id').val($btn.data('id'));
            $('#teams-class-form [name="title"]').val($btn.data('title'));
            $('#teams-class-form [name="duration"]').val($btn.data('duration'));
            $('#teams-class-form [name="url"]').val($btn.data('url'));
            $('#teams-class-form [name="description"]').val($btn.data('description'));
            $('#teams-class-form [name="staff_id"]').val(String($btn.data('staff-id')));
            $('#teams_class_date').data('DateTimePicker').date(moment($btn.data('date')));
            $('#class_id').val(String($btn.data('class-id')));
            getSectionByClass($btn.data('class-id'), $btn.data('section-id'));
            $('html, body').animate({scrollTop: $('#teams-class-form').offset().top - 80}, 300);
        });

        $(document).on('change', '.chgstatus_dropdown', function () {
            var $form = $(this).closest('form');
            $.ajax({
                type: "POST",
                url: $form.attr('action'),
                data: $form.serialize(),
                dataType: "JSON",
                success: function (data) {
                    if (data.status == 0) {
                        errorMsg(data.error);
                    } else {
                        successMsg(data.message);
                    }
                }
            });
        });

        function resetTeamsClassForm() {
            $('#teams_form_title').text('<?php echo $this->lang->line('add_live_class'); ?>');
            $('#teams-class-form').attr('action', teamsAddUrl);
            $('#teams_id').val('');
            $('#teams-class-form')[0].reset();
            $('#section_id').html('<option value=""><?php echo $this->lang->line('select'); ?></option>');
            $('#teams_class_date').data('DateTimePicker').clear();
        }

        function getSectionByClass(class_id, selected_section_id) {
            $('#section_id').html("");
            if (class_id == "") {
                return;
            }
            $.ajax({
                type: "GET",
                url: base_url + "sections/getByClass",
                data: {'class_id': class_id},
                dataType: "json",
                success: function (data) {
                    $('#section_id').append('<option value=""><?php echo $this->lang->line('select'); ?></option>');
                    $.each(data, function (i, obj) {
                        var selected = (selected_section_id && parseInt(selected_section_id, 10) === parseInt(obj.id, 10)) ? " selected" : "";
                        $('#section_id').append("<option value=" + obj.id + selected + ">" + obj.section + "</option>");
                    });
                }
            });
        }
    })(jQuery);
</script>
