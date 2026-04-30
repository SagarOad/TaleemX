<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-gears"></i> <small class="pull-right">
                <a type="button" class="btn btn-primary btn-sm"><?php echo $this->lang->line('setting') ?></a>
            </small>
        </h1>
    </section>
    <section class="content">
        <?php 
            if (!$this->rbac->hasPrivilege('qr_code_setting', 'can_view')) {
                access_denied();
            } 
        ?>
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-envelope"></i> <?php echo $this->lang->line('setting') ?></h3>
                    </div>
                    <form id="saveqrsettingform" name="employeeform" class="form-horizontal form-label-left" method="post" accept-charset="utf-8">
                        <div class="box-body">                            
                            <?php if ($this->session->flashdata('msg')) { ?>
                                <?php echo $this->session->flashdata('msg');
                                $this->session->unset_userdata('msg'); ?>
                            <?php } ?>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="exampleInputEmail1"><?php echo $this->lang->line("auto_attendance"); ?><small class="req"> * </small>
                                </label>
                                <div class="col-md-9 col-sm-9 col-xs-12 pt5"> 
                                    <div class="material-switch">
                                        <input id="auto_attendance" name="auto_attendance" type="checkbox" class="auto_attendance"  value="1"  <?php echo set_checkbox('auto_attendance', '1', ($setting->auto_attendance == 1)); ?>/>
                                        <label for="auto_attendance" class="label-info-success"></label>
                                    </div>
                                    <span class="text-danger"><?php echo form_error('auto_attendance'); ?></span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="exampleInputEmail1"><?php echo $this->lang->line("scanner_device_type"); ?><small class="req"> *</small>
                                </label>
                                <div class="col-md-9 col-sm-9 col-xs-12"> &nbsp
									<?php 
									if (!empty($machine_type->machine_type)){
										$machine_type    = json_decode($machine_type->machine_type); 
									}else{
										$machine_type    = [];
									}		?>
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="machine_type[]" value="gun_machine" <?php
                                                        if (!empty($machine_type) && in_array("gun_machine", $machine_type)){
                                                            echo "checked";
                                                        }
                                                        ?> ><?php echo $this->lang->line("sensor_based_device_like_a_scanning_gun"); ?>
                                    </label>
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="machine_type[]" value="camera" <?php
                                                        if (!empty($machine_type) && in_array("camera", $machine_type)){
                                                            echo "checked";
                                                        }
                                                        ?>><?php echo $this->lang->line("camera_based_device_like_a_mobile_phone_or_webcam"); ?>
                                    </label>

                                    <span class="text-danger"><?php echo form_error('machine_type[]'); ?></span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="exampleInputEmail1"><?php echo $this->lang->line("select_camera") ?><small class="req"> *</small>
                                </label>
                                <div class="col-md-9 col-sm-9 col-xs-12"> &nbsp
                                    <label class="radio-inline">
                                        <input type="radio" name="camera_type" value="environment" <?php
                                                                                        if ($setting->camera_type =="environment") {
                                                                                            echo "checked";
                                                                                        }
                                                                                        ?>><?php echo $this->lang->line("primary_camera") ?>
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="camera_type" value="user" <?php
                                                                                        if ($setting->camera_type  =="user") {
                                                                                            echo "checked";
                                                                                        }
                                                                                        ?>><?php echo $this->lang->line("secondary_camera") ?>
                                    </label>

                                    <span class="text-danger"><?php echo form_error('camera_type'); ?></span>
                                </div>
                            </div>
                            
                        </div>
                        <div class="box-footer">
                            <div class="col-md-6 col-sm-6 col-xs-6 col-md-offset-3">                                 
                                    <button type="submit" class="btn btn-info pull-left saveqrsetting"><?php echo $this->lang->line('save'); ?></button>                                
                            </div>                             
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>


<script type="text/javascript">
    var base_url = '<?php echo base_url(); ?>';
 
    $(".saveqrsetting").on('click', function (e) {
        var $this = $(this);
        $this.button('loading');  
        
        //added 
        var auto_attendance = $("#auto_attendance").prop("checked");
        var isAutoAttendance = auto_attendance ? "1" : "0";
        var formData = $("#saveqrsettingform").serialize();
        formData += '&auto_attendance='+isAutoAttendance;
        //added

        $.ajax({
            url: '<?php echo site_url("admin/qrattendance/qrsetting") ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (data) {
                if (data.status == "fail") {
                    var message = "";
                    $.each(data.error, function (index, value) {

                        message += value;
                    });
                    errorMsg(message);
                } else {
                    successMsg(data.message); 
                }
                $this.button('reset');
            }
        });
    });
</script>
