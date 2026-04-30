<div class="pagebg">
	<div class="row row-eq"> 
		<div class="col-lg-8 col-md-8 col-sm-8 paddlr">      
        <!-- general form elements -->
			<form id="upload" method="post" class="ptt10" style="min-height: 500px;">
				<div class="scroll-area">  
					<div class="form-group">
						<label><?php echo $this->lang->line('assignment'); ?>:</label>
					</div>
					<p><?php  echo $result->assignment_title; ?></p>
					<hr>
					<div class="form-group">
						<label><?php echo $this->lang->line('description'); ?>:</label>
					</div>
					<p><?php  echo $result->description; ?></p>
					<hr>
					<input type="hidden" id="assignmentid" name="assignmentid" value="<?php echo $assignment_id; ?>">
					<input type="hidden" id="studentid" name="studentid" value="<?php echo $studentid_or_guestid; ?>">                                    
					<input type="hidden" id="submit_edit_id" name="submit_edit_id" value="<?php  if (!empty($assignment_data[0]['id'])){ echo $assignment_data[0]['id']; }else{ echo "0"; } ?>">                                    
					<div class="row">
						<div class="col-sm-12">
							<div class="row"> 
								<div class="col-sm-12">
									<div class="form-group">                            
										<label for="pwd"><?php echo $this->lang->line('message'); ?></label><small class="req"> *</small>
										<textarea id="assigment_message" name="message" class="form-control"><?php  if (!empty($assignment_data[0]['message'])){ echo $assignment_data[0]['message']; } ?></textarea>
									</div>
								</div>                       
								<?php 
								if(empty($assignment_data[0]['evaluated_date'])) {
								 ?>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="pwd"><?php echo $this->lang->line('attach_document'); ?></label>
                                        <input type="file"  id="file" name="file" class="form-control filestyle">
                                    </div>
                                </div>
								<?php } ?>               
							</div>  
						</div>
					</div>
					
					<?php 
					if(isset($result->submit_date)){ 
						if(strtotime($result->submit_date) >= strtotime(date('Y-m-d'))  && empty($assignment_data[0]['evaluated_date'])){ ?>
							<div class="row">
								<div class="col-sm-12">
									<button type="submit" class="btn btn-info" id="submit" data-loading-text="<i class='fa fa-spinner fa-spin '></i> <?php echo $this->lang->line('please_wait'); ?>"><?php echo $this->lang->line('save'); ?></button>
								</div>
							</div>		
						<?php }
					}else if(empty($assignment_data[0]['evaluated_date'])){ ?>
						<div class="row">
							<div class="col-sm-12">
								<button type="submit" class="btn btn-info" id="submit" data-loading-text="<i class='fa fa-spinner fa-spin '></i> <?php echo $this->lang->line('please_wait'); ?>"><?php echo $this->lang->line('save'); ?></button>
							</div>
						</div>
					<?php } ?>

				</div>  
				<?php 
				if (!empty($assignment_data[0]['docs'])) { ?>
				<div class="row">
					<div class="col-md-12 ptt10">
						<h4 class="box-title bmedium mb0 font16"><?php echo $this->lang->line('uploaded_document'); ?></h4>  
						<ul class="list-group content-share-list">                        
							<li class="list-group-item overflow-hidden mb5">
								<img src="<?php echo base_url('backend/images/upload-file.png'); ?>">
								<?php echo $this->media_storage->fileview($assignment_data[0]['docs']) ?>						
								<a href="<?php echo base_url(); ?>./user/studentcourse/download_submit_assignment/<?php echo $assignment_data[0]['id']; ?>"  download data-toggle="tooltip" data-placement="right" data-original-title="<?php echo $this->lang->line('download'); ?>"> <i class="fa fa-download"></i></a>
							</li>
						</ul>
					</div>
				</div>
				<?php }  ?> 
				<?php 
				if (!empty($assignment_data[0]['evaluated_note'])) { ?>
				<div class="row">
					<div class="col-md-12 ptt10">
						<h4 class="box-title bmedium mb0 font16"><?php echo $this->lang->line('teacher_remark'); ?></h4>  
						<?php echo $assignment_data[0]['evaluated_note']; ?>
					</div>
				</div>
				<?php }  ?>   
			</form> 
		</div>
		<div class="col-lg-4 col-md-4 col-sm-4 col-eq">
			<div class="scroll-area">
				<div class="taskside">                
					<h4><?php echo $this->lang->line('summary'); ?></h4>
					<div class="box-tools pull-right">
					</div><!-- /.box-tools -->
					<h5 class="pt0 task-info-created"></h5>
					<hr class="taskseparator" />
					<div class="task-info task-single-inline-wrap task-info-start-date">
						<h5><i class="fa task-info-icon fa-fw fa-lg fa-calendar-plus-o pull-left fa-margin"></i>
							<span><?php echo $this->lang->line('assignment_date'); ?></span>:<?php echo $this->customlib->dateformat($result->assignment_date); ?>
						</h5>
					</div>
					<div class="task-info task-single-inline-wrap task-info-start-date">
						<h5><i class="fa task-info-icon fa-fw fa-lg fa-calendar-plus-o pull-left fa-margin"></i>
							<span><?php echo $this->lang->line('submission_date'); ?></span>:<?php echo $this->customlib->dateformat($result->submit_date); ?>
						</h5>
					</div>
					<div class="task-info task-single-inline-wrap task-info-start-date">
						<h5><i class="fa task-info-icon fa-fw fa-lg fa-calendar-plus-o pull-left fa-margin"></i>
							<span>
								<?php echo $this->lang->line('evaluation_date'); ?></span>:
							<?php
							$evl_date = ""; 
							if ( !empty($assignment_data[0]['evaluated_date'])) {
							    echo $this->customlib->dateformat($assignment_data[0]['evaluated_date']);
							}
							?>
						</h5>
					</div>
                    <div class="task-info task-single-inline-wrap ptt10">
                    <label><span><?php echo $this->lang->line('created_by'); ?></span>: <?php echo $created_by; ?>  </label>
                    <label><span><?php echo $this->lang->line('evaluated_by'); ?></span>: <?php if(!empty($assignment_data[0]['evaluated_date']) && isset($evaluated_by)){ echo $evaluated_by;}; ?> </label>
                    <label><?php echo $this->lang->line("status") ?>:

                    	<?php
                    	    //if submit data exist than show submitted else pending
                    		if(!empty($assignment_data[0]['evaluated_date'])){
 								$status_lable = $this->lang->line("evaluated");
                           		$class = "class= 'label label-success'";
                    		}else if(!empty($assignment_data)){
   								$class = "class= 'label label-warning'";
                                $status_lable = $this->lang->line('submitted');
                    		}else{
 								$class = "class= 'label label-danger'";
                                $status_lable = $this->lang->line("pending");
                    		}
                    		echo "<font $class >" . $status_lable . "</font>"; 
                    	?>
                    </label>    
                    <?php                   
                    if (!empty($result->document)) { ?>
                        <label><?php echo $this->lang->line('documents'); ?></label>
                        <ul class="list-group content-share-list">                        
                			<li class="overflow-hidden mb5">
              					<img src="<?php echo base_url('backend/images/upload-file.png'); ?>">
              					<?php echo $this->media_storage->fileview($result->document) ?>            					
            					<a href="<?php echo base_url() . "user/studentcourse/download_assignment/" . $result->id ?>" data-toggle="tooltip" data-original-title=""><i class="fa fa-download"></i></a> 
            				</li>
                        </ul>
                    <?php } ?>                
                </div>
            </div>
        </div>    
    </div>
</div>
</div>

<?php
function searchForId($id, $array) {
    foreach ($array as $key => $val) {
        if ($val['studentid_or_guestid'] === $id) {
            return "<label class='label label-success'>" . $val["status"] . "</label>";
        }
    }
    return "<label class='label label-danger'>".$this->lang->line('incomplete')."</label>";
}
?>

<script type="text/javascript">
    $('.filestyle').dropify();
</script>

<script>
$("#upload").on('submit', (function (e) {
        e.preventDefault();
        var $this = $(this).find("button[type=submit]:focus");
        var assignment_id=<?php echo $assignment_id; ?>;
        $.ajax({
            url : '<?php echo base_url(); ?>user/studentcourse/save_assignment',
            type: "POST",
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
                $this.button('loading');
            },
            success: function (res){ 
                if (res.status == "fail") {
                    var message = "";
                    $.each(res.error, function (index, value) {
                        message += value;
                    });
                    errorMsg(message);
                } else {
                    successMsg(res.message);
                    $("#assignment_"+assignment_id).find('.assignment_btn_id').click();
                }
            },
            error: function (xhr) { // if error occured
                alert("<?php echo $this->lang->line('error_occurred_please_try_again'); ?>");
                $this.button('reset');
            },
            complete: function () {
                $this.button('reset');
            }
        });
    }));
</script>

