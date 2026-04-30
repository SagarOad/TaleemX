<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>backend\dist\css\zoom_addon.css">
<div class="content-wrapper">

    <section class="content-header">
        <h1>
            <i class="fa fa-mortar-board"></i> <?php echo $this->lang->line('live_class') ?>
        </h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> <?php echo $this->lang->line('live_class') ?>
                        </h3>
                        <div class="box-tools pull-right"></div>
                    </div>

                    <div class="box-body">
                        <?php

                        if (!empty($timetable)) {
                        ?>
                        <div class="table-responsive">
                            <table class="table table-stripped">
                                <thead>
                                    <tr>
                                        <?php
                                            foreach ($timetable as $tm_key => $tm_value) {
                                            ?>
                                        <th class="text text-center"><?php echo $tm_key; ?></th>
                                        <?php
                                            }
                                            ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <?php
                                            foreach ($timetable as $tm_key => $tm_value) {
                                            ?>
                                        <td class="text text-center v-align-top" width="14%">

                                            <?php
                                                    if (!$timetable[$tm_key]) {
                                                    ?>
                                            <div class="attachment-block clearfix">
                                                <b
                                                    class="text text-center"><?php echo $this->lang->line('not_scheduled'); ?></b><br>
                                            </div>
                                            <?php
                                                    } else {

                                                        foreach ($timetable[$tm_key] as $tm_k => $tm_kue) {

                                                        ?>
                                            <div class="attachment-block clearfix <?php
                                                                                                    if ($this->rbac->hasPrivilege('live_classes', 'can_add')) {
                                                                                                        echo "online-timetable";
                                                                                                    }
                                                                                                    ?>"
                                                data-subject="<?php echo $tm_kue->subject_name . " (" . $tm_kue->subject_code . ")"; ?>"
                                                data-class="<?php echo $tm_kue->class . "(" . $tm_kue->section . ")"; ?>"
                                                data-class-section-id="<?php echo $tm_kue->class_section_id; ?>"
                                                data-class-id="<?php echo $tm_kue->class_id; ?>"
                                                data-section-id="<?php echo $tm_kue->section_id; ?>"
                                                data-time-from="<?php echo $tm_kue->time_from; ?>">
                                                <b class="text-green"><?php echo $this->lang->line('subject') ?>:
                                                    <?php echo $tm_kue->subject_name . " (" . $tm_kue->subject_code . ")"; ?>

                                                </b>
                                                <br>

                                                <strong class="text-green"><?php echo $this->lang->line('class') ?>:
                                                    <?php echo $tm_kue->class . "(" . $tm_kue->section . ")"; ?></strong><br>
                                                <strong class="text-green"><?php echo $tm_kue->time_from ?></strong>
                                                <b class="text text-center">-</b>
                                                <strong class="text-green"><?php echo $tm_kue->time_to; ?></strong><br>

                                                <strong class="text-green"><?php echo $this->lang->line('room_no'); ?>:
                                                    <?php echo $tm_kue->room_no; ?></strong><br>

                                            </div>

                                            <?php
                                                        }
                                                    }
                                                    ?>

                                        </td>
                                        <?php
                                            }
                                            ?>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                        <?php
                        } else {
                        ?>
                        <div class="alert alert-info">
                            <?php echo $this->lang->line('no_record_found'); ?>
                        </div>
                        <?php
                        }
                        ?>
                    </div>
                    <div class="box-header ptbnull"></div>
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> <?php echo $this->lang->line('live_class') ?>
                        </h3>
                        <div class="box-tools pull-right">
                            <?php if ($this->rbac->hasPrivilege('live_classes', 'can_add')) {
                            ?>


                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                                data-target="#modal-classteacher-timetable"><i class="fa fa-plus"></i>
                                <?php echo $this->lang->line('add_live_class'); ?></button>
                            <?php }
                            ?>
                            <?php

                            if ($conference_setting->use_teacher_api) {
                            ?>

                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                                data-target="#modal-credential"><i class="fa fa-plus"></i>
                                <?php echo $this->lang->line('add') . " " . $this->lang->line('credential'); ?></button>
                            <?php
                            }

                            ?>
                        </div>
                    </div>
                    <div class="box-body">
                        <?php if ($this->session->flashdata('msg')) { ?>
                        <?php echo $this->session->flashdata('msg');
                            $this->session->unset_userdata('msg'); ?>
                        <?php } ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-bordered mintable-w900 example"
                                data-export-title="<?php echo $this->lang->line('live_class');?>">
                                <thead>
                                    <tr>
                                        <th><?php echo $this->lang->line('class') . " " . $this->lang->line('title'); ?>
                                        </th>
                                        <th><?php echo $this->lang->line('description'); ?></th>
                                        <th><?php echo $this->lang->line('date_time'); ?></th>
                                        <th><?php echo $this->lang->line('api_used'); ?></th>
                                        <th><?php echo $this->lang->line('created_by') ?></th>
                                        <th><?php echo $this->lang->line('class'); ?></th>
                                        <th width="10%"><?php echo $this->lang->line('status'); ?></th>
                                        <th class="text-right"><?php echo $this->lang->line('action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (empty($conferences)) {
                                    ?>
                                    <?php
                                    } else {
                                        foreach ($conferences as $conference_key => $conference_value) {
                                            $return_response = json_decode($conference_value->return_response);

                                        ?>
                                    <tr>
                                        <td class="mailbox-name">
                                            <a href="#" data-toggle="popover"
                                                class="detail_popover"><?php echo $conference_value->title; ?></a>
                                        </td>
                                        <td class="mailbox-name"><?php echo $conference_value->description; ?></td>

                                        <td class="mailbox-name"
                                            data-sort="<?php echo strtotime($conference_value->date); ?>">
                                            <?php echo $this->customlib->dateyyyymmddToDateTimeformat($conference_value->date); ?>
                                        </td>
                                        <td class="mailbox-name">
                                            <?php echo $this->lang->line($conference_value->api_type); ?>
                                        </td>
                                        <td class="mailbox-name">
                                            <?php
                                                    if ($conference_value->created_id == $logged_staff_id) {
                                                        echo "Self";
                                                    } else {
                                                        if ($superadmin_visible == 'disabled' && $conference_value->role_id == 7) {
                                                            echo '';
                                                        } else {
                                                            echo $conference_value->create_by_name . " " . $conference_value->create_by_surname . " (" . $conference_value->create_by_employee_id . ")";
                                                        }
                                                    }
                                                    ?>
                                        </td>
                                        <td class="mailbox-name">
                                            <ul class="liststyle1">
                                                <?php

                                                        foreach ($conference_value->classes as $confernce_class_key => $confernce_class_value) {
                                                        ?>
                                                <li> <i class="fa fa-check-square-o"></i>
                                                    <?php echo $confernce_class_value->class . " (" . $confernce_class_value->section . ")" ?>
                                                </li>
                                                <?php
                                                        }
                                                        ?>
                                            </ul>

                                        </td>
                                        <td class="mailbox-name" width="110">
                                            <form class="chgstatus_form" method="POST"
                                                action="<?php echo site_url('admin/conference/chgstatus') ?>">
                                                <input type="hidden" name="conference_id"
                                                    value="<?php echo $conference_value->id; ?>">
                                                <select class="form-control chgstatus_dropdown" name="chg_status">
                                                    <option value="0"
                                                        <?php if ($conference_value->status == 0) echo "selected='selected'" ?>>
                                                        <?php echo $this->lang->line('awaited'); ?></option>
                                                    <option value="1"
                                                        <?php if ($conference_value->status == 1) echo "selected='selected'" ?>>
                                                        <?php echo $this->lang->line('cancelled') ?></option>
                                                    <option value="2"
                                                        <?php if ($conference_value->status == 2) echo "selected='selected'" ?>>
                                                        <?php echo $this->lang->line('finished'); ?></option>
                                                </select>
                                            </form>
                                        </td>
                                        <td class="mailbox-date relative" width="90">
                                            <?php
                                                    if ($conference_value->status == 0) {
                                                    ?>
                                            <a data-placement="top" href="#"
                                                class="btn btn-primary btn-xs" data-toggle="modal"
                                                data-target="#modal-chkstatus"
                                                data-id="<?php echo $conference_value->id; ?>" title="<?php echo $this->lang->line('start') ?>">
                                                 <i class="fa fa-video-camera"></i> 

                                            </a>

                                            <?php                                             }
                                                    ?>
                                            <?php
                                                    if ($conference_value->created_id == $logged_staff_id) {
                                                        if ($this->rbac->hasPrivilege('live_classes', 'can_delete')) {
                                                    ?>
                                            <a data-placement="top"
                                                href="<?php echo base_url(); ?>admin/conference/delete/<?php echo $conference_value->id . "/" . $return_response->id; ?>"
                                                class="btn btn-primary btn-xs" data-toggle="tooltip"
                                                title="<?php echo $this->lang->line('delete'); ?>"
                                                onclick="return confirm('<?php echo $this->lang->line('delete_confirm') ?>');">
                                                <i class="fa fa-remove"></i>
                                            </a>
                                            <?php
                                                        }
                                                    }
                                                    ?>

                                        </td>
                                    </tr>
                                    <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table><!-- /.table -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>


<div class="modal fade" id="modal-credential" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <form id="form-addcredential" action="<?php echo site_url('admin/conference/addcredential'); ?>" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"> <?php echo $this->lang->line('zoom_credential'); ?></h4>
                </div>

                <div class="modal-body minheight149 pb10">

                    <div class="modal_loader_div" style="display: none;"></div>

                    <div class="modal-body-inner">

                    </div>

                </div>
            </div>
        </form>
    </div>
</div>


<div class="modal fade" id="modal-chkstatus" data-backdrop="static">
    <div class="modal-dialog">
        <form id="form-chkstatus" action="<?php echo site_url('admin/conference/chkstatus'); ?>" method="POST">
            <div class="modal-content zoom_details">
            </div>
        </form>
    </div>
</div>



<div class="modal fade" id="modal-online-timetable" data-backdrop="static">
    <div class="modal-dialog">
        <form id="form-addconference" action="<?php echo site_url('admin/conference/add'); ?>" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?php echo $this->lang->line('add_live_class'); ?></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="class_id" id="class_id" value="0">
                    <input type="hidden" name="section_id[]" id="section_id" value="0">
                    <input type="hidden" class="form-control" id="password" name="password">
                    <div class="row">
                        <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <label for="title"> <?php echo $this->lang->line('class_title'); ?><small class="req">
                                    *</small></label>
                            <input type="text" class="form-control" id="title" name="title">
                            <span class="text text-danger" id="title_error"></span>
                        </div>
                        <div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6">
                            <label for="date"> <?php echo $this->lang->line('class_date_time') ?><small class="req">
                                    *</small></label>
                            <div class='input-group' id='meeting_date'>
                                <input type='text' class="form-control" name="date" readonly="readonly" />
                                <span class="input-group-addon">
                                    <span class="glyphicon glyphicon-calendar"></span>
                                </span>
                            </div>
                            <span class="text text-danger" id="title_error"></span>
                        </div>
                        <div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6">
                            <label for="duration"> <?php echo $this->lang->line('class_duration_minutes') ?><small
                                    class="req"> *</small></label>
                            <input type="number" class="form-control" id="duration" name="duration">
                            <span class="text text-danger" id="title_error"></span>
                        </div>
                          <div class="clearfix"></div>
                        <div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6">
                            <label for="class"> <?php echo $this->lang->line('host_video'); ?><small class="req">
                                    *</small></label>
                            <div class="material-switch">
                                <input id="add_host_video" name="host_video" type="checkbox" class="host_video"  value="1" />
                                <label for="add_host_video" class="label-info-success"></label>
                            </div>
                            <span class="text text-danger" id="class_error"></span>
                        </div>
                        <div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6">
                            <label for="class"><?php echo $this->lang->line('client_video'); ?><small class="req">
                                    *</small></label>
                            <div class="material-switch">
                                <input id="add_client_video" name="client_video" type="checkbox" class="client_video"  value="1" />
                                <label for="add_client_video" class="label-info-success"></label>
                            </div>
                            <span class="text text-danger" id="class_error"></span>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <label for="description"><?php echo $this->lang->line('description') ?></label>
                            <textarea class="form-control" name="description" id="description"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="load"
                        data-loading-text="<i class='fa fa-spinner fa-spin '></i> <?php echo $this->lang->line('saving') ?>"><?php echo $this->lang->line('save') ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-classteacher-timetable" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
    aria-hidden="true" style="z-index: 1050;">
    <div class="modal-dialog">
        <form id="form-addconference" action="<?php echo site_url('admin/conference/addByClassTeacher'); ?>"
            method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <?php echo $this->lang->line('add') . " " . $this->lang->line('live_class') ?></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" class="form-control" id="password" name="password">
                    <div class="row">
                        <div class="col-md-12 col-lg-12">
                            <div class="form-group">
                                <label for="title"> <?php echo $this->lang->line('class_title') ?><small class="req">
                                        *</small></label>
                                <input type="text" class="form-control" id="title" name="title">
                                <span class="text text-danger" id="title_error"></span>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="date"> <?php echo $this->lang->line('class_date_time') ?><small class="req">
                                        *</small></label>
                                <div class='input-group' id='meeting_classteacher_date'>
                                    <input type='text' class="form-control" name="date" readonly="readonly" />
                                    <span class="input-group-addon">
                                        <span class="glyphicon glyphicon-calendar"></span>
                                    </span>
                                </div>
                                <span class="text text-danger" id="title_error"></span>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="duration"> <?php echo $this->lang->line('class_duration_minutes') ?><small
                                        class="req"> *</small></label>
                                <input type="number" class="form-control" id="duration" name="duration">
                                <span class="text text-danger" id="title_error"></span>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-12 col-lg-12">
                            <div class="form-group">
                                <label for="class"> <?php echo $this->lang->line('staff') ?><small class="req">
                                        *</small></label>
                                <select id="staff_id" name="staff_id" class="form-control">
                                    <option value=""><?php echo $this->lang->line('select'); ?></option>
                                    <?php
                                    foreach ($stafflist as $staff) {
                                    ?>
                                    <option value="<?php echo $staff['id']; ?>"><?php
                                                                                    echo ($staff["surname"] == "") ? $staff["name"] . " (" . $staff["employee_id"] . ")" : $staff["name"] . " " . $staff["surname"] . " (" . $staff["employee_id"] . ")";
                                                                                    ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                                <span class="text text-danger" id="class_error"></span>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-12 col-lg-12">
                            <div class="form-group">
                                <label for="class"> <?php echo $this->lang->line('class') ?><small class="req">
                                        *</small></label>
                                <select id="class_id" name="class_id" class="form-control select2">
                                    <option value=""><?php echo $this->lang->line('select'); ?></option>
                                    <?php
                                    foreach ($classlist as $class) {
                                    ?>
                                    <option value="<?php echo $class['id'] ?>"><?php echo $class['class'] ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                                <span class="text text-danger" id="class_error"></span>
                            </div>
                        </div>
                        <div class="form-group col-sm-12 col-md-12 col-lg-12">
                            <label for="section"><?php echo $this->lang->line('section'); ?><small class="req">
                                    *</small></label>
                            <select id="section_id" name="section_id[]" class="form-control section-list fullselectbox"
                                multiple="multiple">
                                <option value=""><?php echo $this->lang->line('select'); ?></option>
                            </select>
                            <span class="text text-danger" id="section_error"></span>
                        </div>

                        <div class="clearfix"></div>
                        <div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6">
                            <label for="class"> <?php echo $this->lang->line('host_video'); ?><small class="req">
                                    *</small></label>
                            <div class="material-switch">
                                <input id="host_video" name="host_video" type="checkbox" class="host_video"  value="1" />
                                <label for="host_video" class="label-info-success"></label>
                            </div>
                            <span class="text text-danger" id="class_error"></span>
                        </div>

                        <div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6">
                            <label for="class"> <?php echo $this->lang->line('client_video') ?><small class="req">
                                    *</small></label>
                            <div class="material-switch">
                                <input id="client_video" name="client_video" type="checkbox" class="client_video"  value="1" />
                                <label for="client_video" class="label-info-success"></label>
                            </div>
                            <span class="text text-danger" id="class_error"></span>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <label for="description"><?php echo $this->lang->line('description') ?></label>
                            <textarea class="form-control" name="description" id="description"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="load"
                        data-loading-text="<i class='fa fa-spinner fa-spin '></i> <?php echo $this->lang->line('saving') ?>"><?php echo $this->lang->line('save') ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $('#modal-online-timetable,#modal-classteacher-timetable').modal({
        backdrop: 'static',
        keyboard: false,
        show: false
    });
});

(function($) {
    "use strict";
    var datetime_format =
        '<?php echo $result = strtr($this->customlib->getSchoolDateFormat(), ['d' => 'DD', 'm' => 'MM', 'M' => "MMM", 'Y' => 'YYYY']) ?>';
    $('#meeting_date,#meeting_classteacher_date').datetimepicker({
        format: datetime_format + " HH:mm",
        showTodayButton: true,
        locale: moment.locale('en', {
            week: {
                dow: start_week
            }
        }),
        ignoreReadonly: true
    });
    $(document).ready(function() {

        $('.section-list').select2();
        $(document).on('click', '.online-timetable', function(event) {
            var password = makeid(5);

            var class_name = $(this).data('class');
            var subject_name = $(this).data('subject');
            var classSectionId = $(this).data('classSectionId');
            var class_id = $(this).data('classId');
            var section_id = $(this).data('sectionId');
            var timeFrom = $(this).data('timeFrom');
            var format_hour = Converttimeformat(timeFrom);
            var d = new Date();
            d.setHours(format_hour.hours, format_hour.minutes, format_hour.second);
            $('#meeting_date').data("DateTimePicker").date(d);

            $('#class_id').val("").val(class_id);
            $('#section_id').val("").val(classSectionId);
            $('#class').val("").val(class_name);
            $('#title').val("");
            $('#password').val("").val(password);
            $('#modal-online-timetable').modal('show');
        });
        $('.detail_popover').popover({
            placement: 'right',
            trigger: 'hover',
            container: 'body',
            html: true,
            content: function() {
                return $(this).closest('td').find('.fee_detail_popover').html();
            }
        });
    });
    $("form#form-addconference").submit(function(event) {
        event.preventDefault();
        var $form = $(this),
        url = $form.attr('action');
        var $button = $form.find("button[type=submit]:focus");

        //added
        var host_video = $(this).find(".host_video").prop("checked");
        var isHostVideoEnabled = host_video ? "1" : "0";
        var client_video = $(this).find(".client_video").prop("checked");
        var isClientVideoEnabled = client_video ? "1" : "0";
        var formData = $form.serialize();
        formData += '&host_video='+isHostVideoEnabled;
        formData += '&client_video='+isClientVideoEnabled;
        //added

        $.ajax({
            type: "POST",
            url: url,
            data: formData,
            dataType: "JSON",
            beforeSend: function() {
                $button.button('loading');
            },
            success: function(data) {
                if (data.status == 0) {
                    var message = "";
                    $.each(data.error, function(index, value) {
                        message += value;
                    });
                    errorMsg(message);
                } else {
                    $('#modal-online-timetable').modal('hide');
                    successMsg(data.message);
                    window.location.reload(true);
                }
                $button.button('reset');
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $button.button('reset');
            },
            complete: function(data) {
                $button.button('reset');
            }
        });
    })
    $('#modal-online-timetable').on('hidden.bs.modal', function() {
        $(this).find("input,textarea,select").not("input[type=radio]")
            .val('')
            .end();
        $(this).find("input[type=checkbox], input[type=radio]")
            .prop('checked', false);
        $('input:radio[name="host_video"][value="1"]').prop('checked', true);
        $('input:radio[name="client_video"][value="1"]').prop('checked', true);
    });

    $('#modal-online-timetable').on('shown.bs.modal', function(e) {
        var password = makeid(5);
        $('#password').val("").val(password);
    });


    $('#modal-credential').on('shown.bs.modal', function(e) {
        var $modalDiv = $(e.delegateTarget);
        $.ajax({
            type: "POST",
            url: base_url + 'admin/conference/staffCredential',
            data: {},
            dataType: "JSON",
            beforeSend: function() {

                $('#modal-credential .modal-body .modal-body-inner').html("");
                $('#modal-credential .modal-body .modal_loader_div').css("display", "block");
            },
            success: function(data) {
                $('#modal-credential .modal-body .modal-body-inner').html(data.page);
                $('#modal-credential .modal-body .modal_loader_div').fadeOut(400);
            },
            error: function(jqXHR, textStatus, errorThrown) {

            },
            complete: function(data) {

            }
        });
    })



    $("form#form-addcredential").submit(function(event) {
        event.preventDefault();
        var $form = $(this),
            url = $form.attr('action');
        var $button = $form.find("button[type=submit]:focus");
        var formData = $form.serializeArray();
        formData.push({
            name: 'button',
            value: $button.val()
        });
        $.ajax({
            type: "POST",
            url: url,
            data: formData,
            dataType: "JSON",
            beforeSend: function() {
                $button.button('loading');
            },
            success: function(data) {
                if (data.status == 0) {
                    var message = "";
                    $.each(data.error, function(index, value) {
                        message += value;
                    });
                    errorMsg(message);
                } else {

                    $('#modal-credential').modal('hide');
                    successMsg(data.message);
                }
                $button.button('reset');
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $button.button('reset');
            },
            complete: function(data) {
                $button.button('reset');
            }
        });
    })
    $('#modal-chkstatus').on('shown.bs.modal', function(e) {
        var $modalDiv = $(e.delegateTarget);
        // var id=$(this).data();
        var id = $(e.relatedTarget).data('id');


        $.ajax({
            type: "POST",
            url: base_url + 'admin/conference/getlivestatus',
            data: {
                'id': id
            },
            dataType: "JSON",
            beforeSend: function() {
                $('.zoom_details').html("");
                $modalDiv.addClass('modal_loading');
            },
            success: function(data) {


                $('.zoom_details').html(data.page);
                $modalDiv.removeClass('modal_loading');
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $modalDiv.removeClass('modal_loading');
            },
            complete: function(data) {
                $modalDiv.removeClass('modal_loading');
            }
        });
    })



    $('#modal-classteacher-timetable').on('shown.bs.modal', function(e) {
        $("#class_id", this).prop("selectedIndex", 0);
        $("#section_id", this).find('option:not(:first)').remove();
        var password = makeid(5);
        $('#password', this).val("").val(password);

    });

    $(document).on('change', '#form-addconference #class_id', function(e) {
        $('#section_id').html("");
        var class_id = $(this).val();
        getSectionByClass(class_id, 0);
    });
    $(document).on('change', '.chgstatus_dropdown', function() {
        $(this).parent('form.chgstatus_form').submit();
    });
    $("form.chgstatus_form").submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        $.ajax({
            type: "POST",
            url: url,
            data: form.serialize(),
            dataType: "JSON",
            success: function(data) {
                if (data.status == 0) {
                    var message = "";
                    $.each(data.error, function(index, value) {

                        message += value;
                    });
                    errorMsg(message);
                } else {
                    successMsg(data.message);
                    window.location.reload(true);
                }
            }
        });
    });
})(jQuery);

function makeid(length) {
    var result = '';
    var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var charactersLength = characters.length;
    for (var i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
    }
    return result;
}

function Converttimeformat(time) {
    var hrs = Number(time.match(/^(\d+)/)[1]);
    var mnts = Number(time.match(/:(\d+)/)[1]);
    var format = time.match(/\s(.*)$/)[1];
    if (format == "PM" && hrs < 12)
        hrs = hrs + 12;
    if (format == "AM" && hrs == 12)
        hrs = hrs - 12;
    var hours = hrs.toString();
    var minutes = mnts.toString();
    if (hrs < 10)
        hours = "0" + hours;
    if (mnts < 10)
        minutes = "0" + minutes;
    return {
        hours: hours,
        minutes: minutes,
        second: 0
    };
}

function getSectionByClass(class_id, section_id) {

    if (class_id != "") {
        $('#form-addconference #section_id').html("");
        var base_url = '<?php echo base_url() ?>';
        var div_data = '';
        $.ajax({
            type: "GET",
            url: base_url + "sections/getByClass",
            data: {
                'class_id': class_id
            },
            dataType: "json",
            beforeSend: function() {
                $('#form-addconference #section_id').addClass('dropdownloading');
            },
            success: function(data) {
                $.each(data, function(i, obj) {
                    var sel = "";
                    if (section_id == obj.section_id) {
                        sel = "selected";
                    }
                    div_data += "<option value=" + obj.id + " " + sel + ">" + obj.section +
                        "</option>";
                });
                $('#form-addconference #section_id').append(div_data);
            },
            complete: function() {
                $('#form-addconference #section_id').removeClass('dropdownloading');
            }
        });
    }
}
</script>