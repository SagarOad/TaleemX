<?php  
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
?>
<form id="edit_course_form_ID" method="post" accept-charset="utf-8" enctype="multipart/form-data">
    <div class="">
    <?php echo $this->customlib->getCSRF(); ?>
    <div class="row scroll-area-16">
        <div class="col-md-8">
               <input type="hidden" name="edit_courseID" value="<?php if (!empty($courseID)) {echo $courseID;}?>">
               <input type="hidden" name="class_sections_id" value="<?php if (!empty($class_sections_id)) {echo $class_sections_id;}?>">
                        <div class="form-group">
                            <label><?php echo $this->lang->line('title'); ?></label><small class="req"> *</small>
                            <input autofocus="" id="title" name="title" type="text" class="form-control" value="<?php if (!empty($coursesList['title'])) {echo $coursesList['title'];}?>"><span class="text-danger"><?php echo form_error('title'); ?></span>
                        </div>
                    <label><?php echo $this->lang->line('outcomes'); ?></label>
                    <table id="edit_outcometableID">
                        <?php
$outcomes = json_decode($coursesList['outcomes']);
$i        = 0;
if (!empty($outcomes)) {
    foreach ($outcomes as $key => $outcomevalue) {
        ?>
                        <tr id="edit_outcomerow<?php echo $i; ?>">
                            <td width="98%">
                                <div class="form-group">
                                <input type="text" name="outcomes[]" value="<?php echo $outcomevalue ?>" class="form-control">
                                <span class="text-danger"><?php echo form_error('outcomes'); ?></span></div>
                            </td>
                            <?php if ($i != 0) {?>
                            <td valign="top" width="30">
                                <button type="button" onclick="edit_delete_outcomerow(<?php echo $i; ?>)"  class="addclose"><i class="fa fa-remove"></i></button></td>
                            <?php } else {?>
                            <td valign="top" width="30"><button type="button" onclick="edit_add_outcomerow()" class="plusgreenbtn addplus"><i class="fa fa-plus"></i></button></td>
                            <?php }?>
                        </tr>
                    <?php $i++;}} else {?>
                        <tr id="edit_outcomerow0">
                            <td width="98%"> <div class="form-group">
                            <input type="text" name="outcomes[]" class="form-control">
                            <span class="text-danger"><?php echo form_error('outcomes'); ?></span>
                        </div></td>
                        <td valign="top" width="30"><button type="button" onclick="edit_add_outcomerow()" class="plusgreenbtn addplus"><i class="fa fa-plus"></i></button></td>
                        </tr>
                        <?php }?>
                    </table>      
                        <div class="form-group">
                        <label><?php echo $this->lang->line('description'); ?></label><small class="req"> *</small>
                        <textarea rows="10" id="description" name="description" placeholder="" type="text" class="form-control"><?php if (!empty($coursesList['description'])) {echo $coursesList['description'];}?></textarea><span class="text-danger"><?php echo form_error('description'); ?></span>
                        </div>                    
        </div>
        <div class="col-md-4">
            <div class="row">
				<div class="col-md-12">
                        <div class="form-group">
                            <label><?php echo $this->lang->line('inline_preview_image'); ?> (700px X 400px) </label>
                            <input autofocus="" id="course_thumbnailID" name="edit_course_thumbnail" type="file" class="filestyle form-control">
                            <input type="hidden" name="old_background" value="<?php if (!empty($coursesList['course_thumbnail'])) {echo $coursesList['course_thumbnail'];}?>">
                            <span class="text-danger"><?php echo form_error('edit_course_thumbnail'); ?></span>
                        </div>
                    </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label><?php echo $this->lang->line('class'); ?></label><small class="req"> *</small>
                        <select autofocus="" id="edit_class_id" name="class" class="form-control class-list">
                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                            <?php
if (!empty($classlist)) {
    foreach ($classlist as $classlist_value) {
        ?>
              <option value="<?php echo $classlist_value['id']; ?>" <?php if ($classlist_value['id'] == $classid['class_id']) {echo "selected";}?> ><?php echo $classlist_value['class']; ?></option>
                            <?php
}
}
?>
                        </select>
                        <span class="text-danger"><?php echo form_error('class'); ?></span>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                    <label> <?php echo $this->lang->line('section') ?><small class="req"> *</small></label>
                    <select id="edit_section_id" name="section[]" class="form-control selectpicker section-list" multiple data-live-search="true">
                    </select>
                    </div>
                </div>
            </div>
            <?php
$result = $this->customlib->getUserData();
$roleid = $result["role_id"];
if ($roleid != "2") { 
    ?>
                <div class="form-group">
                    <label><?php echo $this->lang->line('assign_teacher'); ?><small class="req"> *</small></label>
                    <select  id="teacher_id" name="teacher" class="form-control" >
                    <option value=""><?php echo $this->lang->line('select'); ?></option>
                    <?php
if (!empty($allTeacherList)) {
        $selected = '';
        foreach ($allTeacherList as $allTeacherList_value) { 
            ?>
                            <option value="<?php echo $allTeacherList_value['id']; ?>" <?php  if ($allTeacherList_value['id'] == $coursesList['teacher_id']) {
							echo $selected = 'selected';
            } ?>><?php echo $allTeacherList_value['name'] . ' ' . $allTeacherList_value['surname']; ?> (<?php echo $allTeacherList_value['employee_id'];?>)</option>
                        <?php
}
    }
    ?>
                    </select>
                       <span class="text-danger"><?php echo form_error('teacher'); ?></span>
                </div>
            <?php }?>
			<?php if ($roleid == 7) { ?>
			<div class="form-group">
                    <label><?php echo $this->lang->line('created_by'); ?><small class="req"> *</small></label>
                    <select  id="created_by" name="created_by" class="form-control" >
                    <?php
						if (!empty($created_by)) {
						$selected = '';
							foreach ($created_by as $created_by_value) {           
						?>
                            <option value="<?php echo $created_by_value['id']; ?>" <?php  if ($created_by_value['id'] == $coursesList['created_by']) {
							echo $selected = 'selected';
							} ?>><?php echo $created_by_value['name'] . ' ' . $created_by_value['surname'].' ('.$created_by_value['employee_id'].') '; ?> </option>
                        <?php
							}
					}
					?>
                    </select>
                    <span class="text-danger"><?php echo form_error('created_by_value'); ?></span>
            </div>
			<?php } ?>			
            <div class="row">
                <div class="col-md-12">
                    <label><?php echo $this->lang->line('course_preview_url'); ?></label>
                </div>   
                <div class="col-md-4"> 
                 <div class="form-group">
                    <select  id="course_provider_edit" onclick="checkCourseProviderEdit()" name="course_provider" class="form-control">
                    <?php if (!empty($course_provider)) {
    foreach ($course_provider as $key => $course_provider_value) {?>
                        <option value="<?php echo $key ?>" <?php if ($key == $coursesList["course_provider"]) {echo "selected";}?> > <?php echo $course_provider_value; ?></option>
                    <?php }}?>
                   </select>
                  </div>
                </div> 
                <div class="col-md-8">
                    <div class="form-group" id="course_url_div_edit">
                        <input autofocus="" id="course_url" name="course_url"  placeholder="" type="text" class="form-control" value="<?php if (!empty($coursesList['course_url'])) {echo $coursesList['course_url'];}?>">
                    </div>
                </div>
                 <div class="col-md-8 hide" id="s3_file_div_edit">
                  <div class="form-group">
                    <input autofocus="" id="s3_file" name="s3_file"  placeholder="" type="file" class="form-control filestyle" />
                  </div>
              </div>                
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo $this->lang->line('price'); ?> (<?php echo $currency_symbol; ?>)</label><small class="req"> *</small>
                        <input autofocus="" id="course_price" name="course_price"  placeholder="" type="text" class="form-control" value="<?php if (!empty($coursesList['price'] && $coursesList['price'] != '0.00')) {echo convertBaseAmountCurrencyFormat($coursesList['price']);}?>" /><span class="text-danger"><?php echo form_error('course_price'); ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo $this->lang->line('discount'); ?>(%)</label>
                        <input autofocus="" id="course_discount" name="course_discount"  placeholder="" type="text" class="form-control" value="<?php if (!empty($coursesList['discount'])) {echo $coursesList['discount'];}?>" /><span class="text-danger"><?php echo form_error('course_discount'); ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo $this->lang->line('free_course'); ?></label>
                        <div class="checkbox mt4">
                        <label>
                            <input type="checkbox" value="1" name="free_course" autocomplete="off" class="form-check-input" <?php if (!empty($coursesList['free_course'])) {
    if ($coursesList['free_course'] == "1") {
        echo 'checked';
    }}?>></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo $this->lang->line('course_category'); ?><small class="req"> *</small></label>
                        <select name="category_id" id="category_id" class="form-control">
                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                            <?php foreach($category_result as $key => $category_result_value){ 
                                $selected = '';
                                if($coursesList['category_id'] == $category_result_value['id']){
                                    $selected = 'selected';
                                }
                            ?>
                            <option value="<?php echo $category_result_value['id']; ?>" <?php echo $selected; ?>><?php echo $category_result_value['category_name']; ?></option>
                            <?php } ?>

                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo $this->lang->line('front_site_visibility'); ?></label>
                        <select name="front_side_visibility" class="form-control">
                            <?php foreach($front_side_visibility as $key => $front_side_visibility_value){ 
                                $selected = '';
                                    if($coursesList['front_side_visibility'] == $key){
                                        $selected = 'selected';
                                    }
                                ?>
                                <option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $front_side_visibility_value; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo $this->lang->line('certificate'); ?><small class="req"> </small></label>
                        <select name="certificate_template_id" class="form-control">
                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                            <?php foreach($certificateList as $certificate_key => $certificate_value){ 
                                $selected = '';
                                if($coursesList['certificate_template_id'] == $certificate_value['id']){
                                    $selected = 'selected';
                                }
                                ?>
                                   <option value="<?php echo $certificate_value['id']; ?>" <?php echo $selected; ?>><?php echo $certificate_value['certificate_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>	
    </div> 
</div>
    <div class="row">
        <div class="box-footer row">  
            <div class="pull-right">
                <a id="edit_course_btn" onclick="editcourse(<?php echo $courseID; ?>)" class="btn btn-info"><span id="loader_button"></span> <?php echo $this->lang->line('save'); ?></a>   
            </div>                
        </div>
    </div>
</form>

<script>
    (function ($) {
    "use strict";
            $('.filestyle').dropify();
    })(jQuery);
</script>

<script type="text/javascript">
(function ($) {
  "use strict"; 
    $('.section-list').select2();
    var classid = $('#edit_class_id').val();
    var base_url = '<?php echo base_url() ?>';
    var courseid = "<?php echo $courseID; ?>";
    $.ajax({
      url: base_url + "onlinecourse/course/getsection",
      type: "post",
      data: {classid: classid,courseid:courseid},
      success: function (data) {
        $('#edit_section_id').html(data);
        $('#edit_section_id').select2();
      }
    });

    $("#edit_class_id").change(function() {   
        $("#edit_section_id").select2().select2("val", '');
        var class_id = $('#edit_class_id').val();
        var base_url = '<?php echo base_url() ?>';
        $.ajax({
            url: base_url + "sections/getByClass",
            type: "GET",
            data: {'class_id': class_id},
            dataType: "json",
            success: function (data) {
            $('#edit_section_id').empty();
                $.each(data, function (i, obj)
                {
                $('#edit_section_id').append("<option value=" + obj.id + ">" + obj.section + "</option>");
                });
            }
        });
    });
})(jQuery);

function edit_add_outcomerow()
{
    var table = document.getElementById("edit_outcometableID");
    var table_len = (table.rows.length);
    var id = parseInt(table_len + 1);
    var div = "<td><div class='form-group'><input type='text' name='outcomes[]' class='form-control'></div></td>";
    var row = table.insertRow(table_len).outerHTML = "<tr id='edit_outcomerow" + id + "'>" + div + "<td valign='top'><button type='button' onclick='edit_delete_outcomerow(" + id + ")' class='addclose'><i class='fa fa-remove'></i></button></td></tr>";
}

function edit_delete_outcomerow(id)
{
    var table = document.getElementById("edit_outcometableID");
    var rowCount = table.rows.length;
    $("#edit_outcomerow" + id).remove();
}
</script>
<script>
(function ($) {
  "use strict";
    $(document).ready(function () {
        CKEDITOR.replace('editor2',
        {
            allowedContent: true
        });
    });
})(jQuery);
</script>
<script>
    checkCourseProviderEdit();
    function checkCourseProviderEdit(){
        course_provider = $("#course_provider_edit").val();
        if(course_provider == "s3_bucket"){
            $("#course_url_div_edit").addClass("hide");
            $("#s3_file_div_edit").removeClass("hide");
        }
        else{
            $("#course_url_div_edit").removeClass("hide");
            $("#s3_file_div_edit").addClass("hide");
        }
    }
</script>
