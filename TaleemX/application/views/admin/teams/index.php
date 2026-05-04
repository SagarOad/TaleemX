<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-gears"></i> <small class="pull-right">
                <a type="button" class="btn btn-primary btn-sm"><?php echo $this->lang->line('setting'); ?></a>
            </small>
        </h1>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-envelope"></i> <?php echo $this->lang->line('setting'); ?></h3>
                    </div>
                    <form id="form1" action="<?php echo site_url('admin/teams'); ?>" name="teamssettingform" class="form-horizontal form-label-left" method="post" accept-charset="utf-8">
                        <div class="box-body">
                            <?php if ($this->session->flashdata('msg')) { ?>
                                <?php echo $this->session->flashdata('msg'); $this->session->unset_userdata('msg'); ?>
                            <?php } ?>
                            <?php echo $this->customlib->getCSRF(); ?>
                            <?php
                            $parent_live_class = empty($setting->parent_live_class) ? 0 : $setting->parent_live_class;
                            ?>
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12" for="parent_live_class">
                                    <?php echo $this->lang->line('parent_live_class'); ?><small class="req"> *</small>
                                </label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <label class="radio-inline">
                                        <input type="radio" name="parent_live_class" value="0" <?php echo ($parent_live_class == 0) ? "checked" : ""; ?>><?php echo $this->lang->line('disabled'); ?>
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="parent_live_class" value="1" <?php echo ($parent_live_class == 1) ? "checked" : ""; ?>><?php echo $this->lang->line('enabled'); ?>
                                    </label>
                                    <span class="text-danger"><?php echo form_error('parent_live_class'); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <div class="col-md-6 col-sm-6 col-xs-6 col-md-offset-3">
                                <?php if ($this->rbac->hasPrivilege('teams_setting', 'can_edit')) { ?>
                                    <button type="submit" class="btn btn-info pull-left"><?php echo $this->lang->line('save'); ?></button>
                                <?php } ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
