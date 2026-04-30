<link rel="stylesheet" href="<?php echo base_url(); ?>backend/dist/css/course_addon.css">
<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-mortar-board"></i><?php echo $this->lang->line('certificate_template_data'); ?></h1>
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
                                <?php echo $this->lang->line('certificate_template_data'); ?>
                            </h3>
                            
                        </div>
                        <div class="box-body row">
                            <?php if ($this->session->flashdata('msg')) { ?>
                                <?php echo $this->session->flashdata('msg') ?>
                            <?php } ?>
                            <?php
                            if (isset($error_message)) {
                                echo "<div class='alert alert-danger'>" . $error_message . "</div>";
                            } ?>
                            <?php echo $this->customlib->getCSRF(); ?>

                            <div class="col-lg-12 col-md-12 col-sm-12">
                                <form id="save_data" enctype="multipart/form-data"
                                    action="<?php echo site_url('onlinecourse/coursecertificate/save_certificate'); ?>"
                                    method="post" accept-charset="utf-8">
                                    <input type="hidden" id="id" name="id" value="<?php echo $id; ?>">

                                    <div class="panel-group1 mb10" id="accordion_form">
                                        <div class="panel panel-default1">
                                            <div class="panel-heading pt5 pb5">
                                                <h6 class="panel-title panel-title1">
                                                    <a class="display-inline box-plus-panel" data-toggle="collapse"
                                                        data-parent="#accordion_form" href="#collapseForm">
                                                        <div class="font14 ps-20"><?php echo $this->lang->line('certificate_template_data'); ?></div>
                                                    </a>
                                                    <div class="pull-right">
                                                    <a data-toggle="collapse" href="#content2" aria-expanded="false" class="position-absolute right-10">
                                                        <i class="fa fa-question-circle cursor-pointer text-sky-blue pt4"></i>
                                                    </a>

                                                    <div id="content2" class="collapse collapse-detail">
                                                        <h4><?php echo $this->lang->line('instructions') ; ?></h4>
                                                        <ul>
															<li><?php echo $this->lang->line('click_the_save_data_button_to_save_certificate_information'); ?></li>
															<li><?php echo $this->lang->line('after_saving_data_design_the_certificate_using_drag_drop'); ?></li>
															<li><?php echo $this->lang->line('click_the_save_design_button_to_apply_and_update_the_certificate_layout'); ?></li>
                                                            <li><?php echo $this->lang->line('design_changes_will_not_reflect_unless_save_design_is_clicked_after_saving_data'); ?></li>														
														</ul>
                                                    </div>
                                                </div>

                                                </h6>                                           

                                            </div>
                                            <div id="collapseForm" class="panel-collapse collapse in">
                                                <div class="panel-body">
                                                    <div class="row">
                                                        <div class="col-lg-6 col-md-6 col-sm-12">
                                                            <div class="form-group pb0">
                                                                <label><?php echo $this->lang->line('certificate_name'); ?></label><small
                                                                    class="req"> *</small>
                                                                <input id="certificate_name" name="certificate_name"
                                                                    type="text" class="form-control"
                                                                    value="<?php echo set_value('certificate_name'); ?>" />
                                                                <span
                                                                    class="text-danger error_msg"><?php echo form_error('certificate_name'); ?></span>
                                                            </div>
                                                        
                                                            <div class="form-group pb0">
                                                                <label><?php echo $this->lang->line('background_image'); ?>
                                                                    [720px X 960px]<small
                                                                        class="req"> *</small></label>
                                                                <input type="file" id="background_image"
                                                                    class="filestyle form-control" name="background_image">
                                                                <span
                                                                    class="text-danger error_msg"><?php echo form_error('file'); ?></span>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6 col-md-6 col-sm-12">
                                                            <div class="form-group">
                                                                <label><?php echo $this->lang->line('body_text'); ?><small
                                                                        class="req"> *</small></label>
                                                                <textarea name="certificate_text" id="certificate_text"
                                                                    data-toggle="maxlength" class="form-control" rows="4"
                                                                    placeholder=""><?php echo set_value('certificate_text'); ?></textarea>
                                                                <div class="font12 mt5">
                                                                    <b> [student_name] [course_name] [completion_date]
                                                                        [start_date] [current_date] [assign_teacher]
                                                                        [class_name] [section_name]</b>
                                                                </div>
                                                                <span
                                                                    class="text-danger error_msg"><?php echo form_error('certificate_text'); ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <button type="submit" class="btn btn-info pull-right"
                                                                id="savenext"><?php echo $this->lang->line('save_data'); ?></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="col-lg-12 col-md-12 col-sm-12">
                                <div class="panel-group1 mb0" id="accordion_design">
                                    <div class="panel panel-default1">
                                        <div class="panel-heading pt5 pb5">
                                            <h6 class="panel-title panel-title1">
                                                <a class="display-inline box-plus-panel collapsed design-collapse-toggle"
                                                    data-toggle="collapse" data-parent="#accordion_design"
                                                    href="#collapseDesign" style="pointer-events: none; opacity: 0.5;">
                                                    <div class="font14 ps-20"><?php echo $this->lang->line('certificate_design') ; ?></div>
												</a>												
												 
												<div class="pull-right">
                                                    <a data-toggle="collapse" href="#content1" aria-expanded="false" class="position-absolute right-10">
                                                        <i class="fa fa-question-circle cursor-pointer text-sky-blue  pt4"></i>
                                                    </a>

                                                    <div id="content1" class="collapse collapse-detail">
                                                        <h4><?php echo $this->lang->line('instructions') ; ?></h4>
                                                        <ul>
															<li><?php echo $this->lang->line('use_drag_and_drop_to_reposition_text_fields'); ?></li>
															<li><?php echo $this->lang->line('drag_unused_fields_outside_the_certificate_layout_to_hide_them'); ?></li>
															<li><?php echo $this->lang->line('click_the_save_button_to_apply_your_changes'); ?></li>														
														</ul>
                                                    </div>
                                                </div>    
                                            </h6>
                                        </div>
                                        <div id="collapseDesign" class="panel-collapse collapse">
                                            <div class="panel-body">
                                                <form id="savetemplate" enctype="multipart/form-data" action=""
                                                    method="post" accept-charset="utf-8">
                                                    <input type="hidden" id="editid" name="editid"
                                                        value="<?php echo $id; ?>">
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-4 col-sm-12">
                                                            <div class="form-group">
                                                                <label><?php echo $this->lang->line('font_size_selected_block'); ?></label>
                                                                <select id="fontsizeselectblock" name="fontsizeselectblock"
                                                                    class="form-control">
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
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3 col-md-4 col-sm-12">
                                                            <div class="form-group">
                                                                <label><?php echo $this->lang->line('font_size_all_block'); ?></label>
                                                                <select id="fontsizeselectall" name="fontsizeselectall"
                                                                    class="form-control">
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
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3 col-md-4 col-sm-12">
                                                            <div class="form-group">
                                                                <label class="displayblock opacity d-sm-none">&nbsp;</label>
                                                                <button type="submit" class="btn btn-info" id="btn_save_design"><?php echo $this->lang->line('save_design'); ?></button>
                                                            </div>    
                                                        </div>
                                                    </div>
                                                    <div class="pt15 pb10 element">
                                                        <div class="table-responsive">
                                                             
                                                            <div>    
                                                                <div style="width: 770px; position: relative; text-align: center;">
                                                                    <div class="certificate-text-position" name="text_positions"
                                                                        id="set_template_data">
                                                                        
                                                                        <?php if ($id == 0) { ?>
                                                                            <img width="100%" id="preview"
                                                                                src="<?php echo base_url('/uploads/course_content/online_course_certificate/default_template.jpg'); ?>">
                                                                            <div class="draggable course_name" id="temp_course_name"
                                                                                style="position: absolute; font-size: 16px; top: 355.844px; left: 495.844px;">
                                                                                {course_name}</div>
                                                                            <div class="draggable completion_date"
                                                                                id="temp_completion_date"
                                                                                style="position: absolute; font-size: 16px; top: 396.844px; left: 118.844px;">
                                                                                {completion_date}</div>
                                                                            <div class="draggable start_date" id="temp_start_date"
                                                                                style="position: absolute; font-size: 16px; top: 363.797px; left: 119.844px;">
                                                                                {start_date}</div>
                                                                            <div class="draggable current_date"
                                                                                id="temp_current_date"
                                                                                style="position: absolute; font-size: 16px; top: 151.797px; left: 499.844px;">
                                                                                {current_date}</div>
                                                                            <div class="draggable assign_teacher"
                                                                                id="temp_assign_teacher"
                                                                                style="position: absolute; font-size: 16px; top: 322.797px; left: 477.797px;">
                                                                                {assign_teacher}</div>
                                                                            <div class="draggable class_name" id="temp_class_name"
                                                                                style="position: absolute; font-size: 16px; top: 386.797px; left: 505.797px;">
                                                                                {class_name}</div>
                                                                            <div class="draggable section_name"
                                                                                id="temp_section_name"
                                                                                style="position: absolute; font-size: 16px; top: 416.875px; left: 490.844px;">
                                                                                {section_name}</div>
                                                                            <div class="draggable student_name"
                                                                                id="temp_student_name"
                                                                                style="position: absolute; font-size: 20px; top: 325.922px; left: 118.891px;">
                                                                                {student_name}</div>
                                                                            <div class="draggable certificate_text"
                                                                                id="temp_certificate_text"
                                                                                style="position: absolute; width: 500px; text-align: center; font-size: 28px; top: 194.938px; font-family: &quot;Pinyon Script&quot;; left: 118.891px;">
                                                                                {certificate_text}</div>
                                                                            <div class="draggable qrCode" id="qrCode"
                                                                                style="position: absolute; height: 65px; text-align: center; font-size: 20px; top: 123.891px; left: 117.922px;">
                                                                                <p style="text-align: center; padding: 4px 0px;">
                                                                                    {qr_code}</p>
                                                                            </div>
                                                                        <?php } else { ?>
                                                                            <div><?php echo $this->lang->line('loading'); ?></div>
                                                                        <?php } ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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

    $(document).ready(function () {
        $('.certificate_text').html(
            "{This is to certify that Mr. / Ms. [student_name] has successfully completed the [course_name] on [completion_date].}"
        );
        $('.hidden-position').show();
        $(".draggable").draggableTouch();
        $(".draggable").on("dragstart", function (e, pos) {

        }).on("dragend", function (e, pos) {
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

    $(document).ready(function (e) {
        $("#save_data").on('submit', (function (e) {
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
                beforeSend: function (res) { },
                success: function (res) {
                    if (res.status == "fail") {
                        var message = "";
                        $.each(res.error, function (index, value) {
                            message += value;
                        });
                        errorMsg(message);
                    } else {
                        successMsg(res.message);
                        $("#id").val(res.record_id);
                        $("#editid").val(res.record_id);
                        $('#btn_save_design').prop('disabled', false);

                        // Unlock and open the design panel
                        $('.design-collapse-toggle').css({ 'pointer-events': 'auto', 'opacity': '1' }).removeClass('collapsed');
                        $('#collapseDesign').collapse('show');

                        // Collapse the form panel
                        $('#collapseForm').collapse('hide');

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
                success: function (data) {
                    var obj = JSON.parse(data);
                    $.each(obj.get_data, function (key, value) {
                        $("#preview").attr("src",
                            "<?php echo base_url('./uploads/course_content/online_course_certificate/'); ?>" +
                            value.background_image);
                        $('#temp_certificate_text').html(value.certificate_text);
                        $("#templatedesign").removeClass("hide");
                        $("#templatedesign").addClass("show");
                    });
                }
            });
        }

        $("#savetemplate").on('submit', (function (e) {
            $('.draggable').removeClass('draggable_border_red');//added

            e.preventDefault();
            var formData = new FormData(this);
			
            var positionHtml = $('.certificate-text-position').html();

 
    positionHtml = positionHtml.replace(/<img[^>]+src="([^"]+)"/g, function (match, src) {
        var filename = src.split('/').pop(); // last part
        return match.replace(src, filename);
    });

    formData.append('text_positions', positionHtml);
			
			
            $.ajax({
                url: "<?php echo site_url('onlinecourse/coursecertificate/savetemplateposition'); ?>",
                type: "POST",
                data: formData,
                dataType: 'json',
                contentType: false,
                cache: false,
                processData: false,
                success: function (res) {
                    if (res.status == "fail") {
                        var message = "";
                        $.each(res.error, function (index, value) {
                            message += value;
                        });
                        errorMsg(message);
                    } else {
                        successMsg(res.message);
                    }
                },
                complete: function (res) {
                    window.location.href = '<?php echo base_url("onlinecourse/coursecertificate/templatelist"); ?>';
                }
            });
        }));
    });

    $(document).ready(function () {
        var certificateId = $("#editid").val();
        if (certificateId != 0) {
            $.ajax({
                type: "POST",
                url: base_url + "onlinecourse/coursecertificate/getcertificate",
                data: {
                    id: certificateId
                },
                success: function (data) {
                    var obj = JSON.parse(data);
                    $.each(obj.get_data, function (key, value) {
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

							var html = value.certificate_template;
							
							html = html.replace(
								/src="([^"]+)"/g,
								'src="<?php echo base_url("uploads/course_content/online_course_certificate/"); ?>$1"'
							);
	
							$("#set_template_data").html(html);
						}
						
                        $(".error_msg").text("");
                        $("#heading_title").text("<?php echo $this->lang->line('edit_certificate'); ?>");

                        // If editing, make sure the design panel is accessible
                        $('.design-collapse-toggle').css({ 'pointer-events': 'auto', 'opacity': '1' });
                    });
                },
                complete: function (res) {
                    $('.hidden-position').show();
                    $(".draggable").draggableTouch();
                    $(".draggable").on("dragstart", function (e, pos) {

                    }).on("dragend", function (e, pos) {
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
        }

    });
</script>
<script>
    var selectedElement = null;

    $(document).on('click', '.draggable', function (e) {
        e.stopPropagation();
        selectedElement = $(this);
        $('.draggable').removeClass('draggable_border_red');
        $(this).addClass('draggable_border_red');

        var fontSize = parseInt($(this).css('font-size'));
        $('#fontsizeselectblock').val(fontSize);
    });

    $('#fontsizeselectblock').on('change', function () {
        if (selectedElement) {
            var size = $(this).val();
            selectedElement.css('font-size', size + 'px');
        } else {
            errorMsg("<?php echo $this->lang->line('please_select_a_text_first'); ?>");
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