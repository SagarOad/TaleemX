<?php $this->load->view('layout/course_css.php'); ?>
<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
        <?php $this->load->view('onlinecourse/report/_coursereport'); ?>
        <div class="row">
        <!-- left column -->
            <div class="col-md-12">
                <div class="box removeboxmius">
                    <div class="box-header ptbnull"></div>
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> <?php echo $this->lang->line('course_complete_report'); ?></h3>
                    </div>
                    <form id="form1" action="<?php echo base_url(); ?>onlinecourse/coursereport/validation"  method="post">
                        <div class="box-body">
                        <?php echo $this->customlib->getCSRF(); ?>
                            <div class="row">

                                <div class="col-md-3">
                                    <div class="form-group">
                                       <label><?php echo $this->lang->line('users_type'); ?></label><small class="req"> *</small>
                                        <select class="form-control" id="users_type" name="users_type">
                                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                                        
                                        <?php foreach ($users_type as $key => $users_type_value) {
                                            ?>
                                            <option value="<?php echo $key ?>"><?php echo $users_type_value ?></option>
                                        <?php } ?>
                                        </select>
                                        <span class="text-danger" id="error_users_type"></span>
                                       
                                    </div>
                                </div>
                            
                            <div id="show_class_section" style="display: none;">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><?php echo $this->lang->line('class'); ?></label><small class="req"> *</small>
                                        <select id="class_id" name="class_id" class="form-control" >
                                            <option value=""><?php echo $this->lang->line('select'); ?></option> 
                                            <?php foreach ($classlist as $class) { ?>
                                            <option value="<?php echo $class['id'] ?>" <?php if (set_value('class_id') == $class['id']) echo "selected=selected" ?>><?php echo $class['class'] ?></option>
                                            <?php } ?>
                                        </select>
                                         <span class="text-danger" id="error_class_id"></span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><?php echo $this->lang->line('section'); ?></label><small class="req"> *</small>
                                        <select  id="section_id" name="section_id" class="form-control" >
                                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                                        </select>
                                          <span class="text-danger" id="error_section_id"></span>
                                    </div>
                                </div>
                            </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><?php echo $this->lang->line('course'); ?></label><small class="req"> *</small>
                                        <select id="course_id" name="course_id" class="form-control section-list" >
                                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                                        </select>
                                          <span class="text-danger" id="error_course_id"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-12">
                                        <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right"><i class="fa fa-search"></i> <?php echo $this->lang->line('search'); ?></button>
                                    </div>
                                </div>                          
                            </div>
                        </div> 
                    </form>
            <div class="box-body">
                <div class="row">
                    <div class="download_label"><?php echo $this->lang->line('course_complete_report'); ?> </div>
                        <div class="table-responsive">                        
                            <table class="table table-striped table-bordered table-hover all-list" cellspacing="0" data-export-title="<?php echo $this->lang->line('course_complete_report'); ?>">
                                <thead>
                                    <tr>
                                        <th><?php echo $this->lang->line('student_guest'); ?></th>
                                        <th><?php echo $this->lang->line('course_progress'); ?></th>
                                        <th class="text-right noExport"><?php echo $this->lang->line('action'); ?></th> 
                                    </tr>
                                </thead>
                                    <tbody id="datalist">
                                    </tbody>
                            </table>            
                        </div>
                    </div>
                </div>  
                </div>
            </div>
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
    <div class="clearfix"></div>
</div>

<div class="modal fade" id="quiz_performance_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg course_modal" role="document">
        <div class="modal-content modal-media-content">
            <div class="modal-header modal-media-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="box-title"><?php echo $this->lang->line('overall_performance'); ?></h4>
            </div>
        <div  class="scroll-area">
            <div class="modal-body">
            
                <div id="quiz_performance"></div>
                
            </div>
           </div><!--./scroll-area-->
        </div>
    </div>
  </div>

<script>
$(document).ready(function() {
     emptyDatatable('all-list','data');
        $(".section-list").select2();
});
</script>

<script>
( function ( $ ) {
    'use strict';

    $(document).ready(function () {
       $('#form1').on('submit', (function (e) {
        e.preventDefault();
        var search= 'search_filter';
        var formData = new FormData(this);
        formData.append('search', 'search_filter');
        $('#datalist').html('');
        $.ajax({
            url: '<?php echo base_url(); ?>onlinecourse/coursereport/validation',
            type: "POST",
            data: formData,
            dataType: 'json',
            contentType: false,
            processData: false,
            success: function (data) {
                if (data.status == "fail") {
                    $.each(data.error, function(key, value) {
                        $('#error_' + key).html(value);
                    }); 
                } else {
                    $("#error_class_id").html('');
                    $("#error_section_id").html('');
                    $("#error_course_id").html('');
                    initDatatable('all-list', 'onlinecourse/coursereport/coursecompletelist/',data.params,[],100);
                }
            }
        });
        }
       ));
   });
} ( jQuery ) );
</script>

<script>
(function ($) {
  "use strict";

    var class_id = $('#class_id').val();
    var section_id = '<?php echo set_value('section_id') ?>';
    var class_section_id = '<?php echo set_value('section_id') ?>';

    $(document).ready(function(){
        getSectionByClass(class_id,section_id);
        courselist(class_section_id,'');
    });

    $(document).on('change', '#class_id', function (e) {
        $('#section_id').html("");
        $('#student_id').empty();
        $('#student_id').append('<option value=""><?php echo $this->lang->line('select'); ?></option>');
        var class_id = $(this).val();
        getSectionByClass(class_id);
    });
	
	$('#section_id').change(function(){
		var class_section_id = $(this).val();
	 
		$('#course_id').empty();
		$('#course_id').append('<option value=""><?php echo $this->lang->line('select'); ?></option>');
		$('#course_id').val("").trigger('change');
		 
		courselist(class_section_id,'');
	});


    $('#users_type').change(function(){
        var users_type = $('#users_type').val();
        if(users_type == 'student'){
            $('#show_class_section').show();
        }else{
            courselist('', users_type);
            $('#show_class_section').hide();
        }
        $("#course_id").val("").trigger("change");
    }) 

})(jQuery);

function courselist(class_section_id, users_type){
    $.ajax({
        url: '<?php echo base_url(); ?>onlinecourse/coursereport/courselist',
        type: 'post',
        data: {class_section_id:class_section_id,users_type:users_type},
        dataType : 'json',
        success: function(data){
            $('#course_id').empty();
            $('#course_id').append('<option value=""><?php echo $this->lang->line('select'); ?></option>');
            $.each(data, function (i, obj)
            {
                var select = "";
                if (course_id == obj.id) {
                    select = "selected";
                }
                $('#course_id').append("<option value=" + obj.id +" "+ select + ">" + obj.title + "</option>");
            });
        }
    });
}

function getSectionByClass(class_id,section_id) {
    if (class_id != 0 && section_id !== "") {
        $('#section_id').html("");
        var div_data = '<option value=""><?php echo $this->lang->line('select'); ?></option>';
        $.ajax({
            type: "GET",
            url: baseurl + "sections/getByClass",
            data: {'class_id': class_id},
            dataType: "json",
            beforeSend: function () {
                $('#section_id').addClass('dropdownloading');
            },
            success: function (data) {
                $('#section_id').empty;
                $.each(data, function (i, obj)
                {
                    var select = "";
                    if (section_id == obj.id) {
                        select = "selected";
                    }
                    div_data += "<option value=" + obj.id + " " + select + ">" + obj.section + "</option>";
                });
                $('#section_id').append(div_data);
            },
            complete: function () {
                $('#section_id').removeClass('dropdownloading');
            }
        });
    }
}
</script>

