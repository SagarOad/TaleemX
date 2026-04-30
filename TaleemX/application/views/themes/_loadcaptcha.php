<?php if ($is_captcha) {?>
    <div class="form-group has-feedback row">
        <div class='col-lg-5 col-md-5 col-sm-5'>
            <span id="captcha_image"><?php echo $captcha_image; ?>
            <span class="fa fa-refresh catpcha" title='Refresh Catpcha' onclick="loadCaptcha('<?php echo $login_type; ?>')"></span></span>
        </div>
        <div class='col-lg-7 col-md-7 col-sm-7'>
            <input type="text" style="height:50px" name="captcha" placeholder="<?php echo $this->lang->line('captcha'); ?>" autocomplete="off" class=" form-control" id="captcha">
            <span class="text-danger"><?php echo form_error('captcha'); ?></span>
        </div>
    </div>
<?php } ?>