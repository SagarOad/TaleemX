<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#424242" />
        <title>Login : <?php echo $name; ?></title>
        <link href="<?php echo base_url(); ?>uploads/school_content/admin_small_logo/<?php $this->setting_model->getAdminsmalllogo();?>" rel="shortcut icon" type="image/x-icon">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,100,300,500">
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/usertemplate/assets/bootstrap/css/bootstrap.min.css">
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/usertemplate/assets/font-awesome/css/font-awesome.min.css">
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/usertemplate/assets/css/form-elements.css">
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/usertemplate/assets/css/style.css">
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/usertemplate/assets/css/jquery.mCustomScrollbar.min.css">
      <link rel="stylesheet" href="<?php echo base_url(); ?>backend/dist/css/google_authenticator.css">
    </head>

    <body>
        <!-- Top content -->
        <div class="top-content">

            <div class="inner-bg">

                <div class="container-fluid">
                    <div class="row">
                        <?php
 
$offset       = "";
$bgoffsetbg   = "bgoffsetbg";
$bgoffsetbgno = "";
if (empty($notice)) {
   
}
?>
                        <div class="<?php echo $bgoffsetbg; ?>">

                            <div class="col-lg-4 col-md-4 col-sm-12 nopadding <?php echo $bgoffsetbgno; ?> <?php echo $offset; ?>">
                                <div class="loginbg">
                                    <div class="form-top">
                                        <div class="form-top-left logowidth">
                                            <img src="<?php echo base_url(); ?>uploads/school_content/admin_logo/<?php echo $this->setting_model->getAdminlogo();?>" />
                                        </div>
                                    </div>
                                    <div class="form-bottom">
                                        <h3 class="font-white"><?php echo $this->lang->line('user_login'); ?></h3>
                                         
                                        <form role="form" class="login-form" action="javascript:void(0);">
                                        <span class="error_message"></span>
                                            <?php echo $this->customlib->getCSRF(); ?>
                                            <div class="form-group has-feedback">
                                                <label class="sr-only" for="form-username">
                                                    <?php echo $this->lang->line('username'); ?></label>
                                                <input type="text" name="username" value="<?php echo set_value("username"); ?>" placeholder="<?php echo $this->lang->line('username'); ?>" class="form-username form-control" id="email">
                                                <span class="error_username text-danger"></span>
                                            </div>
                                            <div class="form-group has-feedback">
                                                <input type="password" name="password" value="<?php echo set_value("password"); ?>" placeholder="<?php echo $this->lang->line('password'); ?>" class="form-password form-control" id="password">
                                                <span class="error_password text-danger"></span>
                                            </div>
                                            <?php if ($is_captcha) {?>
                                            <div class="form-group has-feedback row">
                                                <div class='col-lg-7 col-md-12 col-sm-6'>
                                                    <span id="captcha_image"><?php echo $captcha_image; ?></span>
                                                    <span class="fa fa-refresh catpcha" title='<?php echo $this->lang->line("refresh_captcha") ?>' onclick="refreshCaptcha()"></span>
                                                </div>
                                                <div class='col-lg-5 col-md-12 col-sm-6'>
                                                    <input type="text" name="captcha" placeholder="<?php echo $this->lang->line('captcha'); ?>" autocomplete="off" class=" form-control" id="captcha">
                                                    <span class="error_captcha text-danger"></span> 
                                                </div>
                                            </div>
                                            <?php }?>
                                           
                                                  <button type="submit" class="btn" id="submit1" data-loading-text="<i class='fa fa-circle-o-notch fa-spin'></i> Please wait..."><?php echo $this->lang->line('sign_in'); ?></button>

                                        </form>
                                        <form role="form" class="gauthenticate-form" action="javascript:void(0);">
                    <fieldset>
                       
                        <div class="">
                                  <span class="error_message_code">
                    
                </span>
                           
                      <div class="form-group">
                        <input type="text" name="code" placeholder="<?php echo $this->lang->line('verification_code') ;?>" class="form-email form-control" id="code">
                        <span class="error_code text-danger"></span>
                    </div> 


                    <button type="submit"  id="submit2"  class="btn" data-loading-text="<i class='fa fa-circle-o-notch fa-spin'></i> Please wait..."><?php echo $this->lang->line('verify_login') ;?></button>
                        </div>
                    </fieldset>
                </form>
                                        

                                        
                                
                                        <p><a href="<?php echo site_url('site/ufpassword') ?>" class="forgot"> <i class="fa fa-key"></i> <?php echo $this->lang->line('forgot_password'); ?></a> </p> 
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-8 col-md-8 col-sm-12 nopadding-2">
                                 <div class="d-flex align-items-center text-wrap flex-column justify-content-center bg-position-sm-left bg-position-lg-center" style="background: url('<?php echo base_url(); ?>uploads/school_content/login_image/<?php echo $school['user_login_page_background']; ?>') no-repeat; background-size:cover"> 
                            <div class="<?php if ($notice){ ?> bg-shadow-remove <?php } ?>">        
                                 <?php
if ($notice) {
    ?>

                                 
                                    <h3 class="h3"><?php echo $this->lang->line('whats_new_in'); ?> <?php echo $school['name']; ?></h3>
                                    <div class="loginright mCustomScrollbar">
                                        <div class="messages">

                                            <?php
foreach ($notice as $notice_key => $notice_value) {
        ?>
                                                <h4><?php echo $notice_value['title']; ?></h4>

                                                <?php
$string = ($notice_value['description']);
        $string = strip_tags($string);
        if (strlen($string) > 100) {

            // truncate string
            $stringCut = substr($string, 0, 100);
            $endPoint  = strrpos($stringCut, ' ');

            //if the string doesn't contain any space then it will cut without word basis.
            $string = $endPoint ? substr($stringCut, 0, $endPoint) : substr($stringCut, 0);
            $string .= '... <a class=more href="' . site_url('read/' . $notice_value['slug']) . '" target="_blank">'.$this->lang->line('read_more').'</a>';
        }
        echo '<p>' . $string . '</p>';
        ?>
                                                <div class="logdivider"></div>
                                                <?php
}
    ?>

                                        </div>
                                    </div>
                                     <?php
}
?>  
                                    
                                    
                                </div><!--./col-lg-6-->
                            </div>
                        </div> 
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="<?php echo base_url(); ?>backend/usertemplate/assets/js/jquery-1.11.1.min.js"></script>
        <script src="<?php echo base_url(); ?>backend/usertemplate/assets/bootstrap/js/bootstrap.min.js"></script>
        <script src="<?php echo base_url(); ?>backend/usertemplate/assets/js/jquery.backstretch.min.js"></script>
        <script src="<?php echo base_url(); ?>backend/usertemplate/assets/js/jquery.mCustomScrollbar.min.js"></script>
        <script src="<?php echo base_url(); ?>backend/usertemplate/assets/js/jquery.mousewheel.min.js"></script>
    </body>
</html>

<script type="text/javascript">
(function ($) {
    "use strict";
    
    $(document).ready(function () {
    $('.login-form fieldset:first-child').fadeIn('slow');

    $('.login-form input[type="text"]').on('focus', function () {
        $(this).removeClass('input-error');
    });

    $(document).on('submit','.login-form',function(e){
        e.preventDefault(); // avoid to execute the actual submit of the form.
       $( "span[class^='error_']").html("");

        var parent_fieldset = $(this).parents('fieldset');
      
        var next_step = false;
        var $this=$(this);
        let submit_btn= $this.closest('form').find("button[type=submit]");    

        var username=$("input[name='username']").val();
        var password=$("input[name='password']").val();
        var captcha=$("input[name='captcha']").val();
          $.ajax({
            url: "<?php echo site_url('gauthenticate/verfiy_userlogin')?>",
            type: "POST",
            data: {'username': username ,'password': password,'captcha':captcha},
            dataType: 'json',
            async: true, 
            beforeSend: function () {
                submit_btn.button('loading');
            },
            success: function (response)
            {
              
             if (response.status == 0) {
              
                 $.each(response.error, function (index, value) {
                  $('.error_'+index).html(value);
                 });
          
             } else if (response.status == 1)  {
               $('.error_message').html(response.error.error_message);
             }else if (response.status == 2)  {
                if(!response.authenticator){
                   
                    window.location.href=response.redirect_to;
                     return false;
                }
                   submit_btn.button('reset');                 
                    open_gform($this);  
               
             }
                   submit_btn.button('reset');    

            },
            error: function (xhr) { // if error occured
                alert("Error occured.please try again");
                submit_btn.button('reset');
            },
            complete: function () {
                submit_btn.button('reset');
            }

        });
    });


        let open_gform=(selector)=>{
           selector.fadeOut(400, function () {
           $(this).next().children().fadeIn();
           });                  
        }

    // submit
 $(document).on('submit','.gauthenticate-form',function(e){
    e.preventDefault(); // avoid to execute the actual submit of the form.
     $( "span[class^='error_']").html("");
    var form = $(this);
     var from1=$('.login-form');
    var url = form.attr('action');
     let submit_btn= form.closest('form').find("button[type=submit]");  
            $.ajax({
            url: "<?php echo site_url('gauthenticate/user_submit_login')?>",
            type: "POST",
             data: from1.serialize()+ "&" + form.serialize(),
            dataType: 'json',
            async: true, 
            beforeSend: function () {
                submit_btn.button('loading');
            },
            success: function (response)
            {
              
             if (response.status == 0) {
              
                 $.each(response.error, function (index, value) {
                  $('.error_'+index).html(value);
                 });
          
             } else if (response.status == 1)  {
               $('.error_message_code').html(response.error.error_message);
             }else if (response.status == 2)  {
           
               window.location.href=response.redirect_to;
             }

             submit_btn.button('reset');

            },
            error: function (xhr) { // if error occured
                alert("Error occured.please try again");
                submit_btn.button('reset');
            },
            complete: function () {
                submit_btn.button('reset');
            }
        });
    });   
});
})(jQuery); 
</script>
<script type="text/javascript">
    function refreshCaptcha(){
        $.ajax({
            type: "POST",
            url: "<?php echo base_url('site/refreshCaptcha'); ?>",
            data: {},
            success: function(captcha){
                $("#captcha_image").html(captcha);
            }
        });
    }
</script> 