<link rel="stylesheet" href="<?php echo base_url(); ?>backend/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">
<script src="<?php echo base_url(); ?>backend/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>

<div class="content-wrapper">     
    <section class="content">
        <div class="row">     
            
            <!-- left column -->
            <div class="col-md-12">
                <!-- general form elements -->

                <div class="box box-primary">
                    <div class="box-header ptbnull">
                        <h3 class="box-title titlefix"><i class="fa fa-gear"></i><?php echo $this->lang->line('thermal_print'); ?></h3>
                        <div class="box-tools pull-right">
                        </div><!-- /.box-tools -->
                    </div><!-- /.box-header -->
                    <div class="">
                        <form role="form" id="fees_form" method="post" enctype="multipart/form-data"> 
                            <input type="hidden" name="sch_id" value="<?php echo $result['id']; ?>">
                            <div class="box-body">                       
                                <div class="row">
                                    <div class="row">
										<div class="col-md-12">
											<div class="col-md-12">
												<div class="form-group row">
													<label class="col-sm-4"><?php echo $this->lang->line('thermal_print'); ?><small class="req"> *</small></label>
													<div class="col-sm-8" id="radioBtnDiv">
                           									<div class="material-switch">
                           									    <input id="is_print" name="is_print" type="checkbox" class="is_print"  value="1"  <?php echo set_checkbox('is_print', '1', ($result['is_print'] == 1)); ?> />
                           									    <label for="is_print" class="label-info-success"></label>
                           									</div>
													</div>
												</div>
											</div>                                    
											<div class="col-md-12">
												<div class="form-group row">
													<label class="col-sm-4"><?php echo $this->lang->line('school_name'); ?><small class="req"> *</small></label>
													<div class="col-sm-8">													
														<input type ="text" id="school_name" name="school_name" class="form-control" value="<?php echo $result['school_name'] ; ?>" autocomplete="off"/>
														<span class="text-danger"><?php echo form_error('school_name'); ?></span>                                           
													</div>
												</div>
											</div>                                    
											<div class="col-md-12">
												<div class="form-group row">
													<label class="col-sm-4"><?php echo $this->lang->line('address'); ?></label>
													<div class="col-sm-8">														
														<textarea id="address" name="address" class="form-control" rows="7"><?php echo $result['address']; ?></textarea>
														<span class="text-danger"><?php echo form_error('address'); ?></span>
													</div>
												</div>
											</div>                                   
											<div class="col-md-12">
												<div class="form-group row">
													<label class="col-sm-4"><?php echo $this->lang->line('footer_text'); ?></label>
													<div class="col-sm-8">												
														<textarea id="footer_text" name="footer_text" class="form-control" rows="7"><?php echo $result['footer_text']; ?></textarea>
														<span class="text-danger"><?php echo form_error('footer_text'); ?></span>
													</div>
												</div>
											</div>
										
										</div>
                                    </div>
								</div><!--./row--> 
                            </div><!-- /.box-body -->
                            <div class="box-footer">
                                <?php
                                if ($this->rbac->hasPrivilege('general_setting', 'can_edit')) {
                                    ?>
                                    <button type="button" class="btn btn-primary submit_schsetting pull-right edit_fees" data-loading-text="<i class='fa fa-circle-o-notch fa-spin'></i> <?php echo $this->lang->line('processing'); ?>"> <?php echo $this->lang->line('save'); ?></button>
                                    <?php
                                }
                                ?>
                            </div>
                        </form>
                    </div><!-- /.box-body -->
                </div>
            </div><!--/.col (left) -->
            <!-- right column -->
        </div>
    </section><!-- /.content -->
</div><!-- /.content-wrapper -->
<!-- new END -->
</div><!-- /.content-wrapper -->

<script type="text/javascript">
    var base_url = '<?php echo base_url(); ?>';
 
    $(".edit_fees").on('click', function (e) {
        var $this = $(this);
        $this.button('loading');
  		//added
        var is_print = $("#is_print").prop("checked");
        var isPrintEnable = is_print ? "1" : "0";
        var formData = $("#fees_form").serialize();
        formData += '&is_print='+isPrintEnable;
        //added
        $.ajax({
            url: '<?php echo site_url("admin/thermalprint/save") ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',

            success: function (data) {

                if (data.status == "fail") {
                    var message = "";
                    $.each(data.error, function (index, value) {

                        message += value;
                    });
                    errorMsg(message);
                } else {
                    successMsg(data.message); 
                }
                $this.button('reset');
            }
        });
    });
</script>