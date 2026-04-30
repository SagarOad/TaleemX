<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>backend/dist/css/google_authenticator.css">
<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-gears"></i> <small class="pull-right">
                <a type="button" class="btn btn-primary btn-sm"><?php echo $this->lang->line('google_authenticator_setting'); ?></a>
            </small>
        </h1>
    </section>   
    <section class="content">
        <div class="row">
            <div class="col-md-12">             
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-envelope"></i> <?php echo $this->lang->line('setup_the_2fa_method'); ?></h3>
                    </div>                   
                    <div class="box-body">
                            
<div class="row">
    <?php 

if(!$user){
    ?>

    <div class="col-md-8">
        <ol class="ol_gauth">         
           <li><?php echo $this->lang->line('download_google_authenticator_app_in_your_mobile_device') ?></li>
           <li><?php echo $this->lang->line('click_the_plus_sign') ?></li>
           <li><?php echo $this->lang->line('select_scan_a_qrcode') ?></li>
           <li><?php echo $this->lang->line('scan_qr_code_to_the_right') ?></li>
        </ol>
        <?php echo $this->lang->line('otherwise_click_enter_a_setup_key_and_type_in_the_key_manually_below') ?> : <br/>
        <b><?php echo $qr_detail->secret; ?></b>

    </div>
     <div class="col-md-4">

   <form action="<?php echo site_url('user/gauthenticate/setup/verify_setup'); ?>" method="POST" class="verfiy_form">
    <input type="hidden" name="secret_code" value="<?php echo $qr_detail->secret; ?>">
      <img  class="img-responsive" src="<?php echo $qr_detail->url; ?>" /><br/>
      <div class="form-group">
<input type="text" class="form-control" id="app_code" name="app_code" placeholder="<?php echo $this->lang->line('enter_six_digit_code_from_your_authenticator_app') ?>">
</div>
     
        <button type="submit" name="search" value="verify-btn" class="btn btn-primary btn-sm"  data-loading-text="<i class='fa fa-circle-o-notch fa-spin'></i> Please wait..."> <?php echo $this->lang->line('verify_activate') ?></button>

</form>
    </div>
    <?php
}else{
    ?>
                                          <div class="alert alert-danger"><?php echo $this->lang->line('you_have_enabled_the_two_fa_method_if_you_want_to_remove_two_fa_account_please'); ?>  <a href="#" class="displayinline align-text-top" data-addon-version="<?php echo $version;?>" data-addon="sstfa" data-toggle="modal" data-target="#confirm-delete-account"><?php echo $this->lang->line('click_here'); ?></a>
</div>
    <?php
}
     ?>
   
</div>
                                            </div>
                     
                  
                </div>
            </div>           
        </div>
    </section>
</div>

    <div class="delmodal modal fade" id="confirm-delete-account"  data-backdrop="static" data-keyboard="false" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h4 class="modal-title" id="myModalLabel"><?php echo $this->lang->line('confirm_delete') ?></h4>
                </div>
                <div class="modal-body">
                    <p><?php echo $this->lang->line('you_are_about_to_delete_your_two_fa_account_this_procedure_is_irreversible') ?></p>
                    <p><?php echo $this->lang->line('do_you_want_to_proceed') ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger btn-ok"><?php echo $this->lang->line('delete') ?></button>
                </div>
            </div>
        </div>
    </div>

<script type="text/javascript">
(function ($) {
    "use strict";
    
    $(document).ready(function(){

    $("form.verfiy_form").on('submit', (function (e) {
        e.preventDefault(); 
        var form = $(this);
        var $this = form.find("button[type=submit]:focus");
        var url = form.attr('action');
        $.ajax({
            url: url,
            type: "POST",
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
                $this.button('loading');
            },
            success: function (response)
            {
              
             if (response.status == 0) {
                 var message = "";
                 $.each(response.error, function (index, value) {
                     message += value;
                 });
                 errorMsg(message);
             } else {
                 successMsg(response.message);
                 window.location.reload();
             }

             $this.button('reset');

            },
            error: function (xhr) { // if error occured
                alert("Error occured.please try again");
                $this.button('reset');
            },
            complete: function () {
                $this.button('reset');
            }

        });
    }
        ));

    });

    $('#confirm-delete-account').on('click', '.btn-ok', function(e) {
            var $modalDiv = $(e.delegateTarget);        

        $.ajax({
            url: baseurl+'user/gauthenticate/setup/delete_setup',
            type: "POST",
            dataType: 'json',
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
               $modalDiv.addClass('modalloading');
            },
            success: function (response)
            {
              
             if (response.status == 0) {
                 var message = "";
                 $.each(response.error, function (index, value) {
                     message += value;
                 });
                 errorMsg(message);
             } else {
                 successMsg(response.message);
                 window.location.reload();
             }

              $modalDiv.removeClass('modalloading');

            },
            error: function (xhr) { // if error occured
                alert("Error occured.please try again");
                 $modalDiv.removeClass('modalloading');
            },
            complete: function () {
                 $modalDiv.removeClass('modalloading');
            }

        });
          
    });
})(jQuery);      
</script>