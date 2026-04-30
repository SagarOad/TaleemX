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
        <?php
            if ($this->rbac->hasPrivilege('course_certificate_template', 'can_add')) {
                ?>
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title" id="heading_title">
                            <?php echo $this->lang->line('certificate_template_data'); ?></h3>
                    </div>
                    <div class="box-body row">
                        <?php if ($this->session->flashdata('msg')) { ?>
                        <?php echo $this->session->flashdata('msg') ?>
                        <?php } ?>
                        <?php
                if (isset($error_message)) {
                    echo "<div class='alert alert-danger'>" . $error_message . "</div>";
                }
                ?>
                        <?php echo $this->customlib->getCSRF(); ?>
                        <div class="col-lg-3 col-md-12 col-sm-12">
                            <form id="save_data" enctype="multipart/form-data"
                                action="<?php echo site_url('onlinecourse/coursecertificate/save_certificate'); ?>"
                                method="post" accept-charset="utf-8">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <input type="hidden" id="id" name="id" value="">
                                        <div class="form-group">
                                            <label><?php echo $this->lang->line('certificate_name'); ?></label><small
                                                class="req"> *</small>
                                            <input id="certificate_name" name="certificate_name" type="text"
                                                class="form-control"
                                                value="<?php echo set_value('certificate_name'); ?>" />
                                            <span
                                                class="text-danger error_msg"><?php echo form_error('certificate_name'); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label><?php echo$this->lang->line('body_text');  ?><small class="req">
                                                    *</small></label>
                                            <textarea name="certificate_text" id="certificate_text"
                                                data-toggle="maxlength" class="form-control" rows="10"
                                                placeholder=""><?php echo set_value('certificate_text'); ?></textarea>
                                            <span class="pt15"><b> [student_name] [course_name] [completion_date]
                                                    [start_date] [current_date] [assign_teacher] [class_name]
                                                    [section_name]</b></span>

                                            <span
                                                class="text-danger error_msg"><?php echo form_error('certificate_text'); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label><?php echo $this->lang->line('background_image'); ?> [720px X 960px]
                                                <small class="req"> </small></label>
                                            <input type="file" id="background_image" class="filestyle form-control"
                                                name="background_image">
                                            <span class="text-danger error_msg"><?php echo form_error('file'); ?></span>
                                        </div>
                                        <button type="submit" class="btn btn-info pull-right" id="savenext">
                                            <?php echo $this->lang->line('save_data'); ?></button>
                                    </div>
                                </div>
                            </form>

                        </div>						
						
                        <div class="col-lg-9 col-md-12 col-sm-12">		
							
                            <form id="savetemplate"  enctype="multipart/form-data"
                                action="<?php //echo site_url('onlinecourse/coursecertificate/save_template_position'); ?>"
                                method="post" accept-charset="utf-8">
								<div class="row ">
									<div class="col-lg-9 col-md-12 col-sm-12">
										<div class="row ">
											<input type="hidden" id="editid" name="editid">
											<div class="col-md-12 pt15 pb10" id="templatedesign">
                                                <div class="table-responsive">                                        
                                                    <div style="width: 750px; margin:0 auto; position: relative; text-align: center;">
                                                        <div class="certificate-text-position" name="text_positions" id="set_template_data">
                                                            <img width="100%" id="preview"
                                                                src="<?php echo base_url('/uploads/course_content/online_course_certificate/default_template.jpg');?>">
                                                            <div class="draggable course_name" id="temp_course_name"
                                                                style="position: absolute; font-size: 16px; top: 355.844px; left: 495.844px;">
                                                                {course_name}</div>
                                                            <div class="draggable completion_date" id="temp_completion_date"
                                                                style="position: absolute; font-size: 16px; top: 396.844px; left: 118.844px;">
                                                                {completion_date}</div>
            
                                                            <div class="draggable start_date" id="temp_start_date"
                                                                style="position: absolute; font-size: 16px; top: 363.797px; left: 119.844px;">
                                                                {start_date}</div>
            
                                                            <div class="draggable current_date" id="temp_current_date"
                                                                style="position: absolute; font-size: 16px; top: 151.797px; left: 499.844px;">
                                                                {current_date}</div>
            
                                                            <div class="draggable assign_teacher" id="temp_assign_teacher"
                                                                style="position: absolute; font-size: 16px; top: 322.797px; left: 477.797px;">
                                                                {assign_teacher}</div>
            
                                                            <div class="draggable class_name" id="temp_class_name"
                                                                style="position: absolute; font-size: 16px; top: 386.797px; left: 505.797px;">
                                                                {class_name}</div>
            
                                                            <div class="draggable section_name" id="temp_section_name"
                                                                style="position: absolute; font-size: 16px; top: 416.875px; left: 490.844px;">
                                                                {section_name}</div>
            
                                                            <div class="draggable student_name" id="temp_student_name"
                                                                style="position: absolute; font-size: 20px; top: 325.922px; left: 118.891px;">
                                                                {student_name}</div>
            
                                                            <div class="draggable certificate_text" id="temp_certificate_text"
                                                                style="position: absolute; width: 500px; text-align: center; font-size: 28px; top: 194.938px; font-family: &quot;Pinyon Script&quot;; left: 118.891px;">
                                                                {certificate_text}</div>
            
                                                            <div class="draggable qrCode" id="qrCode"
                                                                style="position: absolute; height: 65px; text-align: center; font-size: 20px; top: 123.891px; left: 117.922px;">
                                                                <p style="text-align: center; padding: 4px 0px;">{qr_code}</p>
                                                            </div>
            
                                                        </div>
                                                     </div>    
												</div>
											</div>										
										</div>
									</div>
									<div class="col-lg-3 col-md-12 col-sm-12">									 
										 										
										<label><?php echo $this->lang->line('font_size_selected_block'); ?></label>
										<select id="fontsizeselectblock" name="fontsizeselectblock" class="form-control"  >
											<option value="">Select</option>
											<option value="12">12 px</option>
											<option value="14">14 px</option>
											<option value="16">16 px</option>
											<option value="18">18 px</option>
											<option value="20">20 px</option>
											<option value="24">24 px</option>
											<option value="28">28 px</option>
											<option value="32">32 px</option>
											<option value="40">40 px</option>
										</select>
										
										<br>
										
										<label><?php echo $this->lang->line('font_size_all_block'); ?></label>
										<select id="fontsizeselectall" name="fontsizeselectall" class="form-control"  >
											<option value="">Select</option>
											<option value="12">12 px</option>
											<option value="14">14 px</option>
											<option value="16">16 px</option>
											<option value="18">18 px</option>
											<option value="20">20 px</option>
											<option value="24">24 px</option>
											<option value="28">28 px</option>
											<option value="32">32 px</option>
											<option value="40">40 px</option>
										</select>
										
										<br>											
										 
										<button type="submit" class="btn btn-info pull-right" id="btn_save_design"><?php echo $this->lang->line('save_design'); ?></button>						 
										
									</div>
								</div>					
							
                            </form>
                        </div>                        
                    </div>
                </div>
            </div>
        </div>

        <?php }
            if ($this->rbac->hasPrivilege('course_certificate_template', 'can_view')) {
                ?>
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header ptbnull">
                        <h3 class="box-title titlefix"><?php echo $this->lang->line('certificate_template_list'); ?>
                        </h3>
                        <div class="box-tools pull-right">
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
                    foreach ($certificateList as $value) {
                        ?>
                                    <tr>
                                        <td class="mailbox-name">
                                            <?php echo $value['certificate_name']; ?>
                                        </td>
                                        <td class="mailbox-name">
                                            <?php echo $value['certificate_text']; ?>
                                        </td>
                                        <td class="mailbox-date pull-right">
                                            <?php
            if ($this->rbac->hasPrivilege('course_certificate_template', 'can_edit')) {
                ?>
                                            <a type="button" class="btn btn-primary btn-xs edit_certificate shadow-none"
                                                data-certificate_name="<?php echo $value['certificate_name']; ?>"
                                                data-certificate_text="<?php echo $value['certificate_text']; ?>"
                                                data-certificate_image="<?php echo $value['background_image']; ?>"
                                                data-certificate_id="<?php echo $value['id']; ?>"
                                                data-placement="top" data-toggle="tooltip"
                                                data-original-title="<?php echo $this->lang->line('edit'); ?>"><i
                                                    class="fa fa-pencil"></i></a>
                                            <?php } 
            if ($this->rbac->hasPrivilege('course_certificate_template', 'can_delete')) {
                ?>
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
                                    <?php
                    }
                    ?>
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


<script>
	$(document).ready(function () {
		$('#btn_save_design').prop('disabled', true);
	});


$(document).ready(function() {     
    $('.certificate_text').html(
        "{This is to certify that Mr. / Ms. [student_name] has successfully completed the [course_name] on [completion_date].}"
        );
    $('.hidden-position').show();
    $(".draggable").draggableTouch();   
    $(".draggable").on("dragstart", function(e, pos) {
       
    }).on("dragend", function(e, pos) {
        console.log("dragend:", this, pos.left + "," + pos.top);
        if (pos.left <= 720 && pos.top <= 520) {          
            if ($(this).hasClass('hidden-position')) {
                $(this).removeClass('hidden-position');
            }
        } else {
            if (!$(this).hasClass('hidden-position')) {
                $(this).addClass('hidden-position');
            }
        }
    });
});


$(document).ready(function(e) {
    $("#save_data").on('submit', (function(e) {
        e.preventDefault();
        $(".submit_update").prop("disabled", true);
        var formData = new FormData(this);
        $.ajax({
            url: "<?php echo site_url('onlinecourse/coursecertificate/save_certificate'); ?>",
            type: "POST",
            data: formData,
            dataType: 'json',
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function(res) {},
            success: function(res) {
                if (res.status == "fail") {
                    var message = "";
                    $.each(res.error, function(index, value) {
                        message += value;
                    });
                    errorMsg(message);
                } else {
                    successMsg(res.message);
                    $("#id").val(res.record_id);
                    $("#editid").val(res.record_id);
					$('#btn_save_design').prop('disabled', false);					
                    getcertificatedataa(res.record_id);
                }
            }
        });
    }));

    function getcertificatedataa(certificate_id) {
        $.ajax({
            type: "POST",
            url: base_url + "onlinecourse/coursecertificate/getcertificate",
            data: {
                id: certificate_id
            },
            success: function(data) {
                var obj = JSON.parse(data);
                $.each(obj.get_data, function(key, value) {
                    $("#preview").attr("src",
                        "<?php echo base_url('./uploads/course_content/online_course_certificate/');?>" +
                        value.background_image);
                    $('#temp_certificate_text').html(value.certificate_text);
                    $("#templatedesign").removeClass("hide");
                    $("#templatedesign").addClass("show");
                });
            }
        });
    }

    $("#savetemplate").on('submit', (function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        var positionHtml = $('.certificate-text-position').html();
        formData.append('text_positions', positionHtml); //CURRENTLY COMMENTED WF
        $.ajax({
            url: "<?php echo site_url('onlinecourse/coursecertificate/savetemplateposition'); ?>",
            type: "POST",
            data: formData,
            dataType: 'json',
            contentType: false,
            cache: false,
            processData: false,
            success: function(res) {
                if (res.status == "fail") {
                    var message = "";
                    $.each(res.error, function(index, value) {
                        message += value;
                    });
                    errorMsg(message);
                } else {
                    successMsg(res.message);
                }
            },
            complete: function(res) {
                location.reload();

            }
        });
    }));
});


$(".edit_certificate").click(function() {
    var certificateId = $(this).data('certificate_id');
    $.ajax({
        type: "POST",
        url: base_url + "onlinecourse/coursecertificate/getcertificate",
        data: {
            id: certificateId
        },
        success: function(data) {
            var obj = JSON.parse(data);
            $.each(obj.get_data, function(key, value) {
                if (value.background_image != "") {
                    certificateImagePath = base_url +
                        '/uploads/course_content/online_course_certificate/' + value
                        .background_image;
                } else {
                    certificateImagePath = base_url +
                        '/uploads/course_content/online_course_certificate/default_template.jpeg';
                }
                $("#editid").val(value.id);
                $("#id").val(value.id);
				$('#btn_save_design').prop('disabled', false);
                $("#certificate_name").val(value.certificate_name);
                $("#certificate_text").val(value.certificate_text);				
				
				$("#fontsizeselectall").val(value.fontsizeselectall);
                $("#fontsizeselectblock").val(value.fontsizeselectblock);				
				
                if (value.certificate_template != "") {
                    $("#set_template_data").html(value.certificate_template);
                }
                $(".error_msg").text("");
                $("#heading_title").text("<?php echo $this->lang->line('edit_certificate'); ?>");
            });
        },
        complete: function(res) {
            $('.hidden-position').show();
            $(".draggable").draggableTouch();          
            $(".draggable").on("dragstart", function(e, pos) {
           
            }).on("dragend", function(e, pos) {
                console.log("dragend:", this, pos.left + "," + pos.top);
                if (pos.left <= 720 && pos.top <= 520) {                  
                    if ($(this).hasClass('hidden-position')) {
                        $(this).removeClass('hidden-position');
                    }
                } else {
                    if (!$(this).hasClass('hidden-position')) {
                        $(this).addClass('hidden-position');
                    }
                }
            });

            $("#templatedesign").removeClass("hide");
            $("#templatedesign").addClass("show");
        }
    });
});
</script>
<script>
 
	var selectedElement = null;

	$(document).on('click', '.draggable', function (e) {
		e.stopPropagation();
		selectedElement = $(this);
	
		$('.draggable').css('border', '2px dashed #8d8d8d');
		$(this).css('border', '2px solid red');
		
		var fontSize = parseInt($(this).css('font-size'));
		$('#fontsizeselectblock').val(fontSize);
	});

	$('#fontsizeselectblock').on('change', function () {
		if (selectedElement) {
			var size = $(this).val();
			selectedElement.css('font-size', size + 'px');
		} else {
			alert('Please select a text first');
		}
	});
	 
	$(document).on('touchstart', '.draggable', function () {
		selectedElement = $(this);
	});

</script>
<script>
 
	$('#fontsizeselectall').on('change', function () {
		var size = $(this).val();

		if (size !== '') {
			$('.certificate-text-position .draggable').css('font-size', size + 'px');
		}
	});

</script>

