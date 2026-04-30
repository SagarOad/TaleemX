<div class="content-wrapper">       
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <!-- Custom Tabs (Pulled to the right) -->
                <div class="nav-tabs-custom theme-shadow">                
                    <div class="box-header with-border pb0">
                        <h3 class="box-title header_tab_style"><i class="fa fa-search"></i><?php echo $this->lang->line('change_password'); ?> </h3>           
                    </div>                    
                                
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab_1-1">
                            <form action="<?php echo site_url('user/studentcourse/updateguestpass') ?>"  id="passwordform" name="passwordform" method="post" data-parsley-validate="" class="form-horizontal form-label-left" novalidate="">
                                <?php if ($this->session->flashdata('msg')) { ?>
                                    <?php echo $this->session->flashdata('msg') ?>
                                <?php } ?>  
                                <?php echo $this->customlib->getCSRF(); ?>
                                <div class="form-group <?php
                                if (form_error('current_pass')) {
                                    echo 'has-error';
                                }
                                ?>">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name"><?php echo $this->lang->line('current_password'); ?>
                                    </label>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <input  name="current_pass" required="required" class="form-control col-md-7 col-xs-12" type="password"  value="<?php echo set_value('current_password'); ?>">
                                        <span class="text-danger"><?php echo form_error('current_password'); ?></span> 
                                    </div>
                                </div>                                
                                <div class="form-group <?php
                                if (form_error('new_pass')) {
                                    echo 'has-error';
                                }
                                ?>">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name"><?php echo $this->lang->line('new_password'); ?>
                                    </label>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <input   required="required" class="form-control col-md-7 col-xs-12" name="new_pass" placeholder="" type="password"  value="<?php echo set_value('new_password'); ?>">
                                        <span class="text-danger"><?php echo form_error('new_password'); ?></span>
                                    </div>
                                </div>
                                <div class="form-group <?php
                                if (form_error('confirm_pass')) {
                                    echo 'has-error';
                                }
                                ?>">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12" for="first-name"><?php echo $this->lang->line('confirm_password'); ?>
                                    </label>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <input id="confirm_pass" name="confirm_pass" placeholder="" type="password"  value="<?php echo set_value('confirm_password'); ?>" class="form-control col-md-7 col-xs-12" >
                                        <span class="text-danger"><?php echo form_error('confirm_password'); ?></span>
                                    </div>
                                </div>
                                <div class="box-footer">
                                    <div class="form-group">
                                        <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                            <button type="submit" class="btn btn-info"><?php echo $this->lang->line('save'); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <!-- /.tab-pane -->
                    </div>
                    <!-- /.tab-content -->
                </div>
                <!-- nav-tabs-custom -->
            </div>
            <!-- /.col -->
        </div>
    </section>
</div>