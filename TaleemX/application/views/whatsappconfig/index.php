<div class="content-wrapper">
      
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="nav-tabs-custom theme-shadow">
                    <div class="box-header with-border">
                       <h3 class="box-title titlefix"></i> <?php echo  $this->lang->line('whatsapp_messaging_setting'); ?></h3>
                    </div>
                    <ul class="nav nav-tabs">
					
						<li class="active"><a href="#tab_2" data-toggle="tab"><?php echo $this->lang->line('meta_whatsapp_official'); ?></a></li> 
                        <li ><a href="#tab_1" data-toggle="tab"><?php echo $this->lang->line('twilio'); ?></a></li>
						
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane" id="tab_1">
                            <form role="form" id="twilio" action="<?php echo site_url('whatsappconfig/twilio') ?>" class="form-horizontal" method="post">
                                <div class="box-body">
                                    <div class="row">
                                        <div class="minheight170">
                                            <div class="col-md-7">
                                                <?php
                                                $twilio_result = check_in_array('twilio', $list);
                                                ?>
                                                <div class="form-group">
                                                    <label class="col-sm-5 control-label"><?php echo $this->lang->line('twilio_account_sid'); ?><small class="req"> *</small></label>
                                                    <div class="col-sm-7">
                                                        <input autofocus="" type="text" class="form-control" name="twilio_account_sid" value="<?php echo $twilio_result->username; ?>">
                                                        <span class=" text text-danger twilio_account_sid_error"></span>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-5 control-label"><?php echo $this->lang->line('authentication_token'); ?><small class="req"> *</small></label>
                                                    <div class="col-sm-7">
                                                        <input type="password" class="form-control" name="twilio_auth_token"  value="<?php echo $twilio_result->password; ?>">
                                                        <span class=" text text-danger twilio_auth_token_error"></span>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-5 control-label"><?php echo $this->lang->line('registered_phone_number'); ?><small class="req"> *</small></label>
                                                    <div class="col-sm-7">
                                                        <input type="text" class="form-control" name="twilio_sender_phone_number"  value="<?php echo $twilio_result->contact; ?>">
                                                        <span class=" text text-danger twilio_sender_phone_number_error"></span>
                                                    </div>
                                                </div>											
                                                <div class="form-group">
                                                    <label class="col-sm-5 control-label"><?php echo $this->lang->line('status'); ?><small class="req"> *</small></label>
                                                    <div class="col-sm-7">
                                                        <select class="form-control" name="twilio_status">
                                                            <?php
                                                            foreach ($statuslist as $s_key => $s_value) {
                                                                ?>
                                                                <option 
                                                                    value="<?php echo $s_key; ?>"
                                                                    <?php
                                                                    if ($twilio_result->is_active == $s_key) {
                                                                        echo "selected=selected";
                                                                    }
                                                                    ?>
                                                                    ><?php echo $s_value; ?></option>
                                                                    <?php
                                                                }
                                                                ?>
                                                        </select>
                                                        <span class=" text text-danger twilio_status_error"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-5 text text-center disblock">
                                                <a href="https://console.twilio.com/" target="_blank"><img src="<?php echo base_url() ?>backend/images/twilio.png<?php echo img_time(); ?>"><p>https://console.twilio.com/</p></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- /.box-body -->

                                <div class="box-footer">
                                        <div class="col-md-offset-3">
                                            <?php if ($this->rbac->hasPrivilege('whatsapp_messaging', 'can_view')) {
                                                ?>
                                                <button type="submit" class="btn btn-primary btnleftinfo"><?php echo $this->lang->line('save'); ?></button>&nbsp;&nbsp;<span class="twilio_loader"></span>
                                            <?php } ?>
                                        </div>       
                                </div>
                            </form>
                        </div>
					 				
						<div class="tab-pane active" id="tab_2">
                            <form role="form" id="metawhatsapp" action="<?php echo site_url('whatsappconfig/metawhatsapp') ?>" class="form-horizontal" method="post">
                                <div class="box-body">
                                    <div class="row">
                                        <div class="minheight170">
                                            <div class="col-md-7">
                                                <?php
                                                $meta_result = check_in_array('meta', $list);
                                                ?>
                                                <div class="form-group">
                                                    <label class="col-sm-5 control-label"><?php echo  $this->lang->line('access_token'); ?><small class="req"> *</small></label>
                                                    <div class="col-sm-7">
                                                        <input autofocus="" type="text" class="form-control" name="meta_access_token" value="<?php echo $meta_result->authkey; ?>">
                                                        <span class=" text text-danger access_token_error"></span>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="col-sm-5 control-label"><?php echo $this->lang->line('registered_phone_number'); ?><small class="req"> *</small></label>
                                                    <div class="col-sm-7">
                                                        <input type="text" class="form-control" name="meta_sender_phone_number"  value="<?php echo $meta_result->contact; ?>">
                                                        <span class=" text text-danger meta_sender_phone_number_error"></span>
                                                    </div>
                                                </div>	

												<div class="form-group">
                                                    <label class="col-sm-5 control-label"><?php echo $this->lang->line('language'); ?><small class="req"> *</small></label>
                                                    <div class="col-sm-7">
                                                        <input type="text" class="form-control" name="meta_language"  value="<?php if(!empty($meta_result->language)){ echo $meta_result->language; } ?>">
                                                        <span class=" text text-danger meta_language_error"></span>
                                                    </div>
                                                </div>
												
                                                <div class="form-group">
                                                    <label class="col-sm-5 control-label"><?php echo $this->lang->line('status'); ?><small class="req"> *</small></label>
                                                    <div class="col-sm-7">
                                                        <select class="form-control" name="meta_status">
                                                            <?php
                                                            foreach ($statuslist as $s_key => $s_value) {
                                                                ?>
                                                                <option 
                                                                    value="<?php echo $s_key; ?>"
                                                                    <?php
                                                                    if ($meta_result->is_active == $s_key) {
                                                                        echo "selected=selected";
                                                                    }
                                                                    ?>
                                                                    ><?php echo $s_value; ?></option>
                                                                    <?php
                                                                }
                                                                ?>
                                                        </select>
                                                        <span class=" text text-danger meta_status_error"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-5 text text-center disblock">
                                                <a href="https://business.facebook.com/" target="_blank"><img src="<?php echo base_url() ?>backend/images/meta.jpg<?php echo img_time(); ?>"><p>https://business.facebook.com/</p></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- /.box-body -->

                                <div class="box-footer">
                                        <div class="col-md-offset-3">
                                            <?php if ($this->rbac->hasPrivilege('whatsapp_messaging', 'can_view')) {
                                                ?>
                                                <button type="submit" class="btn btn-primary btnleftinfo"><?php echo $this->lang->line('save'); ?></button>&nbsp;&nbsp;<span class="meta_loader"></span>
                                            <?php } ?>
                                        </div>       
                                </div>
                            </form>
                        </div>                       
						
                    </div>
                    <!-- /.tab-content -->
                </div>
            </div>
        </div>  
    </section>
</div>



</div>
</div>
<?php

function check_in_array($find, $array) {

    foreach ($array as $element) {
        if ($find == $element->type) {
            return $element;
        }
    }
    $object = new stdClass();
    $object->id = "";
    $object->type = "";
    $object->api_id = "";
    $object->username = "";
    $object->url = "";
    $object->name = "";
    $object->contact = "";
    $object->password = "";
    $object->authkey = "";
    $object->senderid = "";
    $object->is_active = "";
    return $object;
}
?>


<script type="text/javascript">
(function ($) {
    "use strict";

    var img_path = "<?php echo base_url('backend/images/loading.gif'); ?>";

    /* ------------------------- Twilio Form Submit -------------------------- */
    $(document).on('submit', '#twilio', function (e) {
        e.preventDefault();

        $("[class$='_error']").html('');
        $(".twilio_loader").html('<img src="' + img_path + '">');

        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: $(this).attr('action'),
            data: $(this).serialize(),
            success: function (data) {
                if (data.st === 1) {
                    $.each(data.msg, function (key, value) {
                        $('.' + key + '_error').html(value);
                    });
                } else {
                    successMsg(data.msg);
                }
                $(".twilio_loader").html('');
            },
            error: function () {
                $(".twilio_loader").html('');
            }
        });
    });

    /* -------------------------  Meta WhatsApp Form Submit  -------------------------- */
    $(document).on('submit', '#metawhatsapp', function (e) {
        e.preventDefault();

        $("[class$='_error']").html('');
        $(".meta_loader").html('<img src="' + img_path + '">');

        $.ajax({
            type: "POST",
            dataType: "JSON",
            url: $(this).attr('action'),
            data: $(this).serialize(),
            success: function (data) {
                if (data.st === 1) {
                    $.each(data.msg, function (key, value) {
                        $('.' + key + '_error').html(value);
                    });
                } else {
                    successMsg(data.msg);
                }
                $(".meta_loader").html('');
            },
            error: function () {
                $(".meta_loader").html('');
            }
        });
    });

})(jQuery);
</script>
