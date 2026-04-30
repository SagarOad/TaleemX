<style type="text/css">
.draggable {
    border: 2px dashed #8d8d8d;
    padding: 0px 5px;
    cursor: move;
    background-color: #15b57e33;
    top: 0;
    max-width: 500px;
    color: #333333;
}

/* Dark theme support for draggable text color */
body.dark .draggable,
.dark .draggable {
    color: #ffffff !important;
}

.submit-button {
    padding: 12px 15px;
    margin: 10px;
    background-color: #2d32d5;
    border-radius: 5px;
    color: #fff;
    text-decoration: none;
    border: none;
    cursor: pointer;
}

.back-button {
    padding: 12px 15px;
    background-color: #848484;
    border-radius: 5px;
    color: #fff;
    text-decoration: none;
    border: none;
    cursor: pointer;
}

.hidden-position {
    background-color: #ffd3d3 !important;
}
</style>

<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-mortar-board"></i><?php echo $this->lang->line('add_tag'); ?></h1>
    </section>
    <section class="content">
       <?php  if ($this->rbac->hasPrivilege('course_certificate_template', 'can_view')) { ?>
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header ptbnull">
                        <h3 class="box-title titlefix"><?php echo $this->lang->line('certificate_template_list'); ?>
                        </h3>
                        <div class="box-tools pull-right">
                            <div class="pull-right button-gap-mb-1">
                                <a class="btn btn-primary btn-sm " target="" href="<?php echo base_url("onlinecourse/coursecertificate/addcertificate/0");?>"><i class="fa fa-plus"></i><?php echo $this->lang->line('add'); ?></a>
                            </div>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive mailbox-messages overflow-visible">
                            <div class="download_label"><?php echo $this->lang->line('certificate_template_list'); ?>
                            </div>
                            <table class="table table-striped table-bordered table-hover example" data-export-title="<?php echo $this->lang->line('certificate_template_list'); ?>">
                                <thead>
                                    <tr>
                                        <th><?php echo $this->lang->line('certificate_name'); ?></th>
                                        <th><?php echo $this->lang->line('certificate_text'); ?></th>
                                        <th class="text-right noExport"><?php echo $this->lang->line('action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                foreach ($certificateList as $value) {  ?>
                                    <tr>
                                        <td class="mailbox-name">
                                            <?php echo $value['certificate_name']; ?>
                                        </td>
                                        <td class="mailbox-name">
                                            <?php echo $value['certificate_text']; ?>
                                        </td>
                                        <td class="mailbox-date pull-right">
                                        <?php if ($this->rbac->hasPrivilege('course_certificate_template', 'can_edit')) {  ?>
                                            <a href="<?php echo base_url(); ?>onlinecourse/coursecertificate/addcertificate/<?php echo $value['id']; ?>"
                                                class="btn btn-primary btn-xs" target="" data-toggle="tooltip"
                                                title="<?php echo $this->lang->line('edit'); ?>" >
												<i class="fa fa-pencil"></i>
											</a>
                                        <?php } ?>
                                        <?php if ($this->rbac->hasPrivilege('course_certificate_template', 'can_delete')) { ?>
                                            <a href="<?php echo base_url(); ?>onlinecourse/coursecertificate/delete_record/<?php echo $value['id']; ?>"
                                                class="btn btn-primary btn-xs" 
                                                onclick="return confirm('Delete Confirm?');" data-toggle="tooltip"
                                                title="<?php echo $this->lang->line('delete'); ?>"
                                                onclick="return confirm('<?php echo $this->lang->line('are_you_sure_want_to_delete'); ?>');">
                                                <i class="fa fa-remove"></i>
                                            </a>
                                        <?php } ?>
                                        </td>
                                    </tr>
                                   <?php  }  ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
    </section>
</div>

