<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-mortar-board"></i> <?php echo ('add_category'); ?>     </h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <?php
           if ($this->rbac->hasPrivilege('course_category', 'can_add')) {
                ?>  
                <div class="col-md-4">
                    <!-- Horizontal Form -->
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><?php echo $this->lang->line('add_category'); ?></h3>
                        </div><!-- /.box-header -->
                        <form id="form1" action="<?php echo site_url('onlinecourse/coursecategory/categoryadd'); ?>" method="post" accept-charset="utf-8">
                            <div class="box-body">
                                <?php if ($this->session->flashdata('msg')) { ?>
                                    <?php echo $this->session->flashdata('msg') ?>
                                <?php } ?>
                                <?php
                                if (isset($error_message)) {
                                    echo "<div class='alert alert-danger'>" . $error_message . "</div>";
                                }
                                ?>
                                <?php echo $this->customlib->getCSRF(); ?>
                                <div class="form-group">
                                    <label for="exampleInputEmail1"><?php echo  $this->lang->line('category_name'); ?></label><small class="req"> *</small>
                                    <input autofocus="" id="category_name" name="category_name" placeholder="" type="text" class="form-control"  value="<?php echo set_value('category_name'); ?>" />
                                    <span class="text-danger"><?php echo form_error('category_name'); ?></span>
                                </div> 

                            </div><!-- /.box-body -->

                            <div class="box-footer">
                                <button type="submit" class="btn btn-info pull-right"><?php echo $this->lang->line('save'); ?></button>
                            </div>
                        </form>
                    </div>
                </div><!--/.col (right) -->
                <!-- left column -->
            <?php } ?>
            <div class="col-md-<?php
            if ($this->rbac->hasPrivilege('course_category', 'can_add') || $this->rbac->hasPrivilege('course_category', 'can_edit')) {
                echo "8";
            } else {
                echo "12";
            }
            ?>">
                <!-- general form elements -->
                <div class="box box-primary">
                    <div class="box-header ptbnull">
                        <h3 class="box-title titlefix"><?php echo $this->lang->line('category_list'); ?></h3>
                        <div class="box-tools pull-right">
                        </div><!-- /.box-tools -->
                    </div><!-- /.box-header -->
                    <div class="box-body">
                        <div class="table-responsive mailbox-messages">
                            <div class="download_label"><?php echo $this->lang->line('category_list'); ?></div>
                            <table class="table table-striped table-bordered table-hover example" data-export-title="<?php echo $this->lang->line('category_list'); ?>">
                                <thead>
                                    <tr>
                                        <th><?php echo  $this->lang->line('category_name'); ?></th>
                                        <th class="text-right"><?php echo $this->lang->line('action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($category_result as $category_value) {
                                        ?>
                                        <tr>
                                            <td class="mailbox-name">
                                                <?php echo $category_value['category_name']; ?>
                                            </td> 
                                            <td class="mailbox-date pull-right">
                                                <?php
                                                if ($this->rbac->hasPrivilege('course_category', 'can_edit')) {
                                                    ?>  
                                                    <a href="<?php echo base_url(); ?>onlinecourse/coursecategory/categoryedit/<?php echo $category_value['id']; ?>" class="btn btn-primary btn-xs"  data-toggle="tooltip" title="<?php echo $this->lang->line('edit'); ?>">
                                                        <i class="fa fa-pencil"></i>
                                                    </a>
                                                    <?php
                                                }
                                                if ($this->rbac->hasPrivilege('course_category', 'can_delete')) {
                                                    ?>  
                                                    <a href="<?php echo base_url(); ?>onlinecourse/coursecategory/categorydelete/<?php echo $category_value['id']; ?>"class="btn btn-primary btn-xs"  data-toggle="tooltip" title="<?php echo $this->lang->line('delete'); ?>" onclick="return confirm('<?php echo $this->lang->line('are_you_sure_want_to_delete'); ?>');">
                                                        <i class="fa fa-remove"></i>
                                                    </a>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table><!-- /.table -->
                        </div><!-- /.mail-box-messages -->
                    </div><!-- /.box-body -->
                </div>
            </div><!--/.col (left) -->
            <!-- right column -->
        </div> 
    </section><!-- /.content -->
</div><!-- /.content-wrapper -->

