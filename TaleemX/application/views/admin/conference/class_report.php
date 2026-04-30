<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-money"></i> <?php echo $this->lang->line('fees_collection'); ?> </h1>
    </section>
    <section class="content">
        <div class="row">  
            <form id='feesforward' action="<?php echo site_url('admin/conference/class_report') ?>"  method="post" accept-charset="utf-8">
                <div class="col-md-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-search"></i> <?php echo $this->lang->line('select_criteria'); ?></h3>
                            <div class="box-tools pull-right">
                            </div>
                        </div>

                        <div class="box-body">
                            <?php echo $this->customlib->getCSRF(); ?>
                            <div class="row">
                                <div class="col-md-12">                                   
                                    <?php if ($this->session->flashdata('msg')) { ?>
                                        <?php echo $this->session->flashdata('msg'); $this->session->unset_userdata('msg'); ?>
                                    <?php } ?>
                                </div>
                                <div class="col-md-6">                                   
                                    <div class="form-group">
                                        <label for="exampleInputEmail1"><?php echo $this->lang->line('class'); ?></label><small class="req"> *</small>
                                        <select  id="class_id" name="class_id" class="form-control"  >
                                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                                            <?php
                                            foreach ($classlist as $class) {
                                                ?>
                                                <option value="<?php echo $class['id'] ?>"<?php if (set_value('class_id') == $class['id']) echo "selected=selected" ?>><?php echo $class['class'] ?></option>
                                                <?php
                                                $count++;
                                            }
                                            ?>
                                        </select>
                                        <span class="text-danger"><?php echo form_error('class_id'); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1"><?php echo $this->lang->line('section'); ?></label><small class="req"> *</small>
                                        <select  id="section_id" name="section_id" class="form-control" >
                                            <option value=""   ><?php echo $this->lang->line('select'); ?></option>
                                        </select>
                                        <span class="text-danger"><?php echo form_error('section_id'); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="">
                                <button type="submit" name="action" value ="search" class="btn btn-primary pull-right"><?php echo $this->lang->line('search'); ?></button>
                            </div>
                        </div>  

                        <?php
                        if (isset($liveclassList)) {
                            ?>
                            <div class="box-header ptbnull"></div>   
                            <div class="">
                                <div class="box-header with-border">
                                    <h3 class="box-title titlefix"><?php echo $this->lang->line('live_class') . ' ' . $this->lang->line('report'); ?></h3>

                                </div>
                                <div class="box-body">
                                    <div class="download_label"><?php echo $this->lang->line("live_class") . " " . $this->lang->line('report') ?></div>
                                    <?php
                                    if (!empty($liveclassList)) {
                                        ?>
                                       <div class="table-responsive">   
                                        <table class="table table-hover table-striped table-bordered example" data-export-title="<?php echo $this->lang->line("live_class") . " " . $this->lang->line('report') ?>">
                                            <thead>
                                                <tr>
                                                    <th><?php echo $this->lang->line('class') . ' ' . $this->lang->line('title'); ?></th>
                                <th width="20%"><?php echo $this->lang->line('description'); ?></th>
                                <th><?php echo $this->lang->line('date'); ?></th>
                                <th><?php echo $this->lang->line('api_used'); ?></th>
                                <th><?php echo $this->lang->line('created_by'); ?> </th>
                                <th><?php echo $this->lang->line('created_for'); ?></th>
                                <th><?php echo $this->lang->line('total') . ' ' . $this->lang->line('join'); ?></th>
                                <th class="text-right"><?php echo $this->lang->line('action'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if (empty($liveclassList)) {
                                                    ?>

                                                    <?php
                                                } else {
                                                    foreach ($liveclassList as $liveclass_key => $liveclass_value) {

                                                        $return_response = json_decode($liveclass_value->return_response);
                                                        ?>
                                                        <tr>
                                                            <td class="mailbox-name">
                                                                <a href="#" data-toggle="popover" class="detail_popover"><?php echo $liveclass_value->title; ?></a>                                                                
                                                            </td>
                                                            <td class="mailbox-name"><?php echo $liveclass_value->description; ?></td>

                                                            <td class="mailbox-name">
                                                                <?php echo $this->customlib->dateyyyymmddToDateTimeformat($liveclass_value->date); ?></td>
                                                            <td class="mailbox-name">
                                                                <?php echo $this->lang->line($liveclass_value->api_type); ?>
                                                            </td>
                                                            <td class="mailbox-name">
                                                                <?php
                                                                if ($liveclass_value->created_id == $logged_staff_id) {
                                                                    echo $this->lang->line('self');
                                                                } else {
                                                                    
                                                                    if($logged_staff_id == 7){ 
                                                                        echo $liveclass_value->create_by_name . " " . $liveclass_value->create_by_surname . " (" . $liveclass_value->create_by_role_name . " : " . $liveclass_value->create_bystaffid . ")";
                                                                    }else{
                                                                
                                                                        if($superadmin_visible == 'disabled' && $liveclass_value->role_id == 7){
                                                                            echo '';               
                                                                        }else{
                                                                            echo $liveclass_value->create_by_name . " " . $liveclass_value->create_by_surname . " (" . $liveclass_value->create_by_role_name . " : " . $liveclass_value->create_bystaffid . ")";
                                                                        }               
                                                                
                                                                    }                                                                    
                                                                }
                                                                ?>

                                                            </td>
                                                            <td class="mailbox-name">

                                                                <?php
                                                                echo $liveclass_value->for_create_name . " " . $liveclass_value->for_create_surname . " (" . $liveclass_value->create_for_role_name . " : " . $liveclass_value->for_creatstaffid . ")";
                                                                ?>

                                                            </td>
                                                            <td>
                                                                <?php echo $liveclass_value->total_viewers; ?>
                                                            </td>
                                                            <td class="mailbox-date pull-right">
                                                                <button data-placement="top" data-toggle="modal" type="button" class="btn btn-primary btn-xs viewer-list" id="load" data-recordid="<?php echo $liveclass_value->id; ?>" data-class_id="<?php echo $class_id; ?>" data-section_id="<?php echo $section_id; ?>"  title="<?php echo $this->lang->line('join_list'); ?>" data-loading-text="<i class='fa fa-spinner fa-spin'></i>"><i class="fa fa-list"></i></button>
                                                            </td>
                                                        </tr>
                                                        <?php
                                                    }
                                                }
                                                ?>
                                            </tbody>
                                        </table><!-- /.table -->
                                       </div> 
                                        <?php
                                    } else {
                                        ?>
                                        <div class="alert alert-info"><?php echo $this->lang->line('no_record_found'); ?>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>

                            </div>
                        </div> 
                        <?php
                    }
                    ?>

                </div>
            </form>
        </div>
    </section>
</div>
<script type="text/javascript">

    (function ($) {
        "use strict";

        $(document).ready(function () {
            var date_format = '<?php echo $result = strtr($this->customlib->getSchoolDateFormat(), ['d' => 'dd', 'm' => 'mm', 'Y' => 'yyyy',]) ?>';
            $('.date').datepicker({
                format: date_format,
                autoclose: true,
                locale:  moment.locale('en', {
        week: { dow: start_week }
    }),

            });

            var class_id = $('#class_id').val();
            var section_id = '<?php echo set_value('section_id', 0) ?>';
            var hostel_id = $('#hostel_id').val();
            var hostel_room_id = '<?php echo set_value('hostel_room_id', 0) ?>';

            getSectionByClass(class_id, section_id);

            $.extend($.fn.dataTable.defaults, {
                searching: true,
                ordering: true,
                paging: false,
                retrieve: true,
                destroy: true,
                info: false
            });
        });
        $(document).on('change', '#class_id', function (e) {
            $('#section_id').html("");
            var class_id = $(this).val();
            getSectionByClass(class_id, 0);
        });

    })(jQuery);




</script>

<div id="viewerModal" class="modal fade modalmark" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo $this->lang->line('join_list'); ?> </h4>
            </div>

            <div class="modal-body">

            </div>

        </div>

    </div>
</div>


<script type="text/javascript">
    (function ($) {
        "use strict";
        $(document).on('click', '.viewer-list', function () {
            var $this = $(this);
            var recordid = $this.data('recordid');
            var class_id= $this.data('class_id');
            var section_id=$this.data('section_id');
            $.ajax({
                type: 'POST',
                url: baseurl + "admin/conference/getViewerList",
                data: {'recordid': recordid,'type':'student','class_id':class_id,'section_id':section_id},
                dataType: 'JSON',
                beforeSend: function () {
                    $this.button('loading');
                },
                success: function (data) {

                    $('#viewerModal .modal-body').html(data.page);
                    $('#viewerModal').modal('show');
                    $this.button('reset');
                },
                error: function (xhr) {
                    alert("Error occured.please try again");
                    $this.button('reset');
                },
                complete: function () {
                    $this.button('reset');
                }
            });

        });
    })(jQuery);

    function getSectionByClass(class_id, section_id) {

        if (class_id != "") {
            $('#section_id').html("");
            var base_url = '<?php echo base_url() ?>';
            var div_data = '<option value=""><?php echo $this->lang->line('select'); ?></option>';
            $.ajax({
                type: "GET",
                url: base_url + "sections/getByClass",
                data: {'class_id': class_id},
                dataType: "json",
                beforeSend: function () {
                    $('#section_id').addClass('dropdownloading');
                },
                success: function (data) {
                    $.each(data, function (i, obj)
                    {
                        var sel = "";
                        if (section_id == obj.section_id) {
                            sel = "selected";
                        }
                        div_data += "<option value=" + obj.section_id + " " + sel + ">" + obj.section + "</option>";
                    });
                    $('#section_id').append(div_data);
                },
                complete: function () {
                    $('#section_id').removeClass('dropdownloading');
                }
            });
        }
    }
</script>