<div class="content-wrapper">       
    <section class="content">
        <div class="row">
            <div class="col-md-12">             
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-envelope"></i> <?php echo $this->lang->line('setting'); ?></h3>
                    </div>   
                    <form id="form1" name="employeeform" class="form-horizontal form-label-left" method="post" accept-charset="utf-8">
                        <div class="box-body">
                            <?php if ($this->session->flashdata('msg')) { ?>
                                <?php echo $this->session->flashdata('msg') ?>
                            <?php } ?>   
                            <?php echo $this->customlib->getCSRF(); ?>                           
                         
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="exampleInputEmail1">
                               <?php echo $this->lang->line('two_factor_authentication'); ?><small class="req"> *</small>
                                </label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div class="material-switch pt5">
                                        <input id="use_authenticator" name="use_authenticator" type="checkbox" class="use_authenticator"  value="1"  <?php echo set_checkbox('use_authenticator', '1', ($setting->use_authenticator == 1)); ?> />
                                        <label for="use_authenticator" class="label-info-success"></label>
                                    </div>
                                    <span class="text-danger"><?php echo form_error('use_authenticator'); ?></span>
                                </div>
                            </div>                          
                        </div>
                        <div class="box-footer">
                            <div class="col-md-6 col-sm-6 col-xs-6 col-md-offset-3">
                                <?php
                                if ($this->rbac->hasPrivilege('google_authenticate_setting', 'can_view')) {
                                    ?>
                                    <button type="submit" class="btn btn-info pull-left savegoogleauthenticate"><?php echo $this->lang->line('save'); ?></button>
                                    <?php
                                }
                                ?>                           
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
    $(".savegoogleauthenticate").on('click', function (e) {
        var $this = $(this);
        $this.button('loading');  
        //added
        var use_authenticator = $("#use_authenticator").prop("checked");
        var isUseAuthenticator = use_authenticator ? "1" : "0";
        var formData = $("#fees_form").serialize();
        formData += '&use_authenticator='+isUseAuthenticator;
        //added
        $.ajax({
            url: '<?php echo current_url(); ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (data) {
                $this.button('reset');
            }
        });
    });
</script>
