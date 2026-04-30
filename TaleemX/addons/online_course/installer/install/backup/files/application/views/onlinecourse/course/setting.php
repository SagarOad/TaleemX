<?php $this->load->view('layout/course_css.php'); ?>
<div class="content-wrapper">   
    <section class="content-header">
        <h1> </h1>
    </section>
    <!-- Main content -->
    <section class="content">
        <?php 
            if (!$this->rbac->hasPrivilege('online_course_setting', 'can_view')) {
                access_denied();
            } 
        ?>
        <!-- setting tab -->
        <div class="row ">
          <form role="form" id="save_online_course_setting" method="post" enctype="multipart/form-data">
            <div class="col-md-12 ">
               <div class="box box-primary">
                   <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i><?php echo $this->lang->line("setting");?></h3>
                    </div>
                            <div class="box-body">                       
                                <div class="row around10 ">
                                    <div class="col-md-3">
                                        <div class="form-group row">
                                           <label class=""><?php echo $this->lang->line("online_course_curriculam");?> </label>
                                            <?php if($course_setting->course_curriculum_settings!=null){ $course_curriculum_settings = json_decode($course_setting->course_curriculum_settings); } ?>       
                                        </div>
                                    </div>  
                                        <div class="col-md-9">
                                             <div class="form-group row">
                                                <div class="row">
                                                    <div class="col-md-1">
                                                    <label class="checkbox-inline">
                                                    <input type="checkbox" name="course_curriculum_settings[]" value="online_course_quiz" <?php
                                                    if(!empty($course_setting->course_curriculum_settings) && in_array("online_course_quiz",$course_curriculum_settings)){
                                                        echo "checked";
                                                    }
                                                    ?> ><?php echo $this->lang->line("quiz");?>
                                                    </label>
                                                    </div>
                                                    <div class="col-md-1"><label class="checkbox-inline">
                                                    <input type="checkbox" name="course_curriculum_settings[]" value="online_course_exam" <?php
                                                    if(!empty($course_setting->course_curriculum_settings) && in_array("online_course_exam",$course_curriculum_settings)){
                                                        echo "checked";
                                                    }
                                                    ?> ><?php echo $this->lang->line("exam");?>
                                                    </label>
                                                    </div>
                                                    <div class="col-md-1"><label class="checkbox-inline">
                                                    <input type="checkbox" name="course_curriculum_settings[]" value="online_course_assignment" <?php
                                                    if(!empty($course_setting->course_curriculum_settings) && in_array("online_course_assignment",$course_curriculum_settings)){
                                                        echo "checked";
                                                    }
                                                    ?> ><?php echo $this->lang->line("assignment");?>
                                                    </label>
                                                    </div>
                                                </div>
                                            </div>                                      
                                        </div>  
                                </div>
                        <div class="box-footer">
                             <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">    
                             <button type="button" class="btn btn-info pull-left btnleftinfo-2 saveform " data-loading-text="<i class='fa fa-circle-o-notch fa-spin'></i> <?php echo $this->lang->line('processing'); ?>"> <?php echo $this->lang->line('save'); ?></button> 
                            </div>   
                        </div> 
                        </div>         
                    </div>
                </div>
            </form>
        </div>
        <!-- setting tab -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> <?php echo $this->lang->line('s3_bucket_setting'); ?></h3>
                    </div>
					<div class="around10">
                            <?php if ($this->session->flashdata('msg_aws')) { ?>
                                <?php echo $this->session->flashdata('msg_aws') ?>
                            <?php } ?>
                    </div>
                    <div class="box-body">
                        <?php          
                            if (!$this->auth->addonchk('ssoclc',false)) {
                        ?>
                            <div class="alert alert-danger">You are using unregistered version of Smart School Online Course addon, please <a href="#" class="displayinline align-text-top" data-addon-version="<?php echo $version;?>" data-addon="ssoclc" data-toggle="modal" data-target="#addonModal">click here</a> to register addon</div>
                        <?php
                            }
                        ?>
                        <form action="<?=site_url();?>onlinecourse/course/setting" method="post" class="form-horizontal form-label-left">
                            <div class="row around10 pt0">
                            <div class="around10 pt0">
                                <div class="form-group">
                                    <label class=" col-md-3 col-sm-3 col-xs-12" for="api_key"> <?php echo $this->lang->line('access_key_id'); ?><small class="req"> * </small></label>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <input type="text" class="form-control col-md-7 col-xs-12" name="api_key" id="api_key" value="<?= set_value('api_key', isset($aws_setting->api_key)?$aws_setting->api_key:"");?>" autocomplete="off">
                                        <span class="text-danger"><?php echo form_error('api_key'); ?></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class=" col-md-3 col-sm-3 col-xs-12" for="api_secret"> <?php echo $this->lang->line('secret_access_key'); ?><small class="req"> * </small></label>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <input type="password" class="form-control col-md-7 col-xs-12" name="api_secret" id="api_secret" value="<?= set_value('api_secret', isset($aws_setting->api_secret)?$aws_setting->api_secret:"");?>" autocomplete="off">
                                        <span class="text-danger"><?php echo form_error('api_secret'); ?></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class=" col-md-3 col-sm-3 col-xs-12" for="bucket_name"> <?php echo $this->lang->line('bucket_name'); ?><small class="req"> * </small></label>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <input type="text" class="form-control col-md-7 col-xs-12" name="bucket_name" id="bucket_name" value="<?= set_value('bucket_name', isset($aws_setting->bucket_name)?$aws_setting->bucket_name:"");?>" autocomplete="off">
                                        <span class="text-danger"><?php echo form_error('bucket_name'); ?></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class=" col-md-3 col-sm-3 col-xs-12" for="bucket_name"> <?php echo $this->lang->line('region'); ?><small class="req"> * </small></label>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <input type="text" class="form-control col-md-7 col-xs-12" name="region" id="region" value="<?= set_value('region', isset($aws_setting->region)?$aws_setting->region:"");?>" autocomplete="off">
                                        <span class="text-danger"><?php echo form_error('region'); ?></span>
                                    </div>
                                </div>                            
                            </div>     
                            </div>     
                            <div class="box-footer">
                                <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">                              
                                    <button type="submit" name="setting_btn" value="aws" class="btn btn-info pull-left btnleftinfo-2"><?php echo $this->lang->line('save'); ?></button>                                                         
                                </div>                                 
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>    
        
        <div class="row ">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i><?php echo $this->lang->line('guest_user'); ?> </h3>
                    </div>
					<div class="around10">
                            <?php if ($this->session->flashdata('msg_course')) { ?>
                                <?php echo $this->session->flashdata('msg_course') ?>
                            <?php } ?>
                    </div>
                    <div class="box-body">
                        <form id="save_saveguestform_setting"  method="post" class="form-horizontal form-label-left">
                            <div class="row around10 pt0">
                            <div class=" around10 pt0">
                                <div class="form-group">
                                    <label class=" col-md-3 col-sm-3 col-xs-12" for="api_key"><?php echo $this->lang->line('guest').' '.$this->lang->line('login'); ?><small class="req"> * </small></label>
                                    <?php 
                                    $guest_login = 0;
                                    if (empty($course_setting->guest_login)) { 
                                        $guest_login = 0;
                                    }else{ 
                                        $guest_login = $course_setting->guest_login; 
                                    } 
                                    ?>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div class="material-switch">
                                        <input id="guest_login" name="guest_login" type="checkbox" class="guest_login"  value="1"  
                                        <?php echo set_checkbox('guest_login', '1', ($guest_login == 1)); ?> />
                                        <label for="guest_login" class="label-info-success"></label>
                                    </div>
                                    </div>
                                </div>             
                                <div class="form-group">
                                    <label class=" col-md-3 col-sm-3 col-xs-12" for="api_key"><?php echo $this->lang->line('guest_user_prefix'); ?><small class="req"> * </small></label>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <input type="text" class="form-control col-md-7 col-xs-12" name="guest_prefix" id="guest_prefix" value="<?= set_value('guest_prefix', isset($course_setting->guest_prefix)?$course_setting->guest_prefix:"");?>" autocomplete="off">
                                        <span class="text-danger"><?php echo form_error('guest_prefix'); ?></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class=" col-md-3 col-sm-3 col-xs-12" for="api_key"><?php echo $this->lang->line('guest_user_id_start_from'); ?><small class="req"> * </small></label>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <input type="text" class="form-control col-md-7 col-xs-12" name="guest_id_start_from" id="guest_id_start_from" value="<?= set_value('guest_id_start_from', isset($course_setting->guest_id_start_from)?$course_setting->guest_id_start_from:"");?>" autocomplete="off">
                                        <span class="text-danger"><?php echo form_error('guest_id_start_from'); ?></span>
                                    </div>
                                </div>                             
                            </div>                  
                            </div>                  
                            <div class="box-footer">
                                <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">                              
                                    <button type="submit" id="setting_btn" name="setting_btn" value="course" class="btn btn-info pull-left btnleftinfo-2 saveguestform"><?php echo $this->lang->line('save'); ?></button>                                                         
                                </div>                                
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript">
    var base_url = '<?php echo base_url(); ?>';
    $(".saveform").on('click', function (e) {
        var $this = $(this);
        $this.button('loading');
        $.ajax({
            url: '<?php echo site_url("onlinecourse/course/savesetting") ?>',
            type: 'POST',
            data: $('#save_online_course_setting').serialize(),
            dataType: 'json',
            success: function (data) {
                if (data.status == "fail") {
                    errorMsg(data.message);
                } else {
                    successMsg(data.message);
                }
                $this.button('reset');
            }
        });
    });
</script>


<script type="text/javascript">
    var base_url = '<?php echo base_url(); ?>';
    $(".saveguestform").on('click', function (e) {
        var $this = $(this);
        $this.button('loading');
        //added
        var guest_login = $("#guest_login").prop("checked");
        var isGuestLoginEnabled = guest_login ? "1" : "0";
        var formData = $('#save_saveguestform_setting').serialize();
        formData += '&guest_login='+isGuestLoginEnabled;
        formData += '&setting_btn='+$("#setting_btn").val();
        //added
        $.ajax({
            url: '<?php echo site_url("onlinecourse/course/setting") ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (data) {
                $this.button('reset');
            }
        });
    });
</script>