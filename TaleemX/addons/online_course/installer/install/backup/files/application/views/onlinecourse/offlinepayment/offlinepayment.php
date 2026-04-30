<?php $this->load->view('layout/course_css.php'); ?>
<?php
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
?>

<div class="content-wrapper">
	<!-- Main content -->
	<section class="content">
		<div class="row">
		<!-- left column -->
			<div class="col-md-12">
				<div class="box box-primary">
					<div class="box-header with-border">
						<h3 class="box-title"><i class="fa fa-search"></i> <?php echo $this->lang->line('offline_payment'); ?></h3>
					</div>
					<form id="form1" action="<?php echo base_url(); ?>onlinecourse/offlinepayment/search"  method="post">
						<div class="box-body">
						<?php echo $this->customlib->getCSRF(); ?>
							<div class="row">
								<div class="col-md-4">
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
								<div class="col-md-4">
									<div class="form-group">
										<label><?php echo $this->lang->line('section'); ?></label><small class="req"> *</small>
										<select  id="section_id" name="section_id" class="form-control" >
											<option value=""><?php echo $this->lang->line('select'); ?></option>
										</select>
                                         <span class="text-danger" id="error_section_id"></span>
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<label><?php echo $this->lang->line('student'); ?></label><small class="req"> *</small>
										<select id="student_id" name="student_id" class="form-control" >
											<option value=""><?php echo $this->lang->line('select'); ?></option>
										</select>
                                         <span class="text-danger" id="error_student_id"></span>
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
                    <div class="download_label"><?php echo $this->lang->line('offline_payment'); ?> </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover all-list" cellspacing="0" data-export-title="<?php echo $this->lang->line('offline_payment'); ?>">
    						<input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
    							<thead>
    								<tr>
    									<th><?php echo $this->lang->line('course'); ?></th>
                                        <th><?php echo $this->lang->line('section'); ?></th>
                                        <th><?php echo $this->lang->line('lesson'); ?></th>
                                        <?php 
                                        if($this->customlib->get_online_course_curriculam_status("online_course_quiz")==""){ ?>
                                            <th><?php echo $this->lang->line('quiz'); ?></th>
                                        <?php } ?>
                                        <?php if($this->customlib->get_online_course_curriculam_status("online_course_exam")==""){ ?>
                                              <th><?php echo $this->lang->line('exam'); ?></th>
                                        <?php } ?>
                                        <?php if($this->customlib->get_online_course_curriculam_status("online_course_assignment")==""){ ?>
                                            <th><?php echo $this->lang->line('assignment'); ?></th>
                                        <?php } ?>
                                        <th><?php echo $this->lang->line('course_provider'); ?></th>
                                        <th ><?php echo $this->lang->line('price').' ('.$currency_symbol.')'; ?></th>
                                        <th><?php echo $this->lang->line('current_price').' ('.$currency_symbol.')'; ?></th>
                                        <th class="text-right noExport"><?php echo $this->lang->line('action'); ?></th>
    								</tr>
    							</thead>
    					            <tbody>
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

<div id="payment_modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php echo $this->lang->line('online_course_fee'); ?></h4>
                </div>
                <div class="modal-body pt0 pb0">
                    <div class="row">
                        <div class="col-md-12">
                          <form id="paymentform" method="post" class="ptt10">
                              <div class="row">
                                <div class="col-sm-12">
                                    <div id="paymentdata"></div>
                                </div>
                              </div>
                          </form>
                        </div>
                      </div>
                </div>
                <div class="modal-footer">
                    <button id="payment_btn" class="btn btn-primary" data-loading-text="<i class='fa fa-spinner fa-spin '></i>Processing"><i class="fa fa-money"></i> <?php echo $this->lang->line('pay'); ?></button>
                </div>
            </div>        
    </div>
</div>

 <script>
$(document).ready(function() {
     emptyDatatable('all-list','data');
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

        $.ajax({
            url: '<?php echo base_url(); ?>onlinecourse/offlinepayment/checkvalidation',
            type: "POST",
            data: formData,
            dataType: 'json',
            contentType: false,
            cache: false,
            processData: false,
            success: function (response) {
                if(response.status == "fail"){
                    $.each(response.error, function(key, value) {
                    $('#error_' + key).html(value);
                    });
                }else{
                 
                $('#error_class_id').html('');
                $('#error_section_id').html('');
                 $('#error_student_id').html('');
                   initDatatable('all-list','onlinecourse/offlinepayment/courselist',response.params,[],100);
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
    var class_section_id = '<?php echo set_value('section_id') ?>';

    $(document).ready(function(){
        getSectionByClass(class_id,section_id);
        studentlist(class_section_id);
    });

    $(document).on('change', '#class_id', function (e) {
        $('#section_id').html("");
        $('#student_id').empty();
        $('#student_id').append('<option value=""><?php echo $this->lang->line('select'); ?></option>');
        var class_id = $(this).val();
        getSectionByClass(class_id);
    });

    $('#section_id').change(function(){
        var classid = $('#class_id').val();
        var class_section_id = $('#section_id').val();
        studentlist(class_section_id);
    })

    $(document).on('click', '.paid_btn', function (e) {
        $('#paymentdata').html('');
        var courseid = $(this).attr('data-id');
        var studentid = $(this).attr('user-data-id');
        var class_section_id = $(this).attr('class-section-id');
        var class_id = $(this).attr('class_id');
       
        $.ajax({
            url: '<?php echo base_url(); ?>onlinecourse/offlinepayment/paid',
            type: 'post',
            data: {courseid: courseid,studentid:studentid,class_section_id:class_section_id,class_id:class_id},
            success: function(data){
               $('#paymentdata').html(data);
            }
        });
    });

    $('#payment_btn').click(function(){
        var formData = new FormData($('#paymentform')[0]);
        $.ajax({
            url: '<?php echo base_url(); ?>onlinecourse/offlinepayment/success',
            type: 'post',
            data: formData,
            contentType: false,
            processData: false,
            success: function(data){
                var result = JSON.parse(data);
                console.log(result);
                if (result.status == "fail") {
                    var message = "";
                    $.each(result.error, function (index, value) {errorMsg(value);                    
                    });                
                } else {
                    $('#payment_modal').modal('hide');
                    successMsg(result.message);
                    var class_section_id = result.class_section_id;
                    var student_id = result.student_id;
                    var class_id = result.class_id;
                  initDatatable('all-list', 'onlinecourse/offlinepayment/courselist',result.params,[],100);
                }  
            }
        });
    });

    $(document).on('click', '.print_btn', function () {
        var courseid = $(this).attr('data-id');
        var studentid = $(this).attr('user-data-id');
        $.ajax({
            url: '<?php echo site_url("onlinecourse/offlinepayment/print") ?>',
            type: 'post',
            data: {courseid:courseid,studentid:studentid},
            success: function (response) {
                popup(response);
            }
        });
    });

    $(document).on('click', '.revert_btn', function () {
        var courseid = $(this).attr('data-id');
        var studentid = $(this).attr('user-data-id');
        var class_section_id = $(this).attr('class-section-id');
        var class_id = $(this).attr('class_id');
        
        if(confirm('<?php echo $this->lang->line('are_you_sure') ?>')){
        $.ajax({
            url: '<?php echo site_url("onlinecourse/offlinepayment/revert") ?>',
            type: 'post',
            data: {courseid:courseid,studentid:studentid,class_id:class_id,class_section_id:class_section_id},
            success: function (response) {
                  var result = JSON.parse(response);
                initDatatable('all-list', 'onlinecourse/offlinepayment/courselist',result.params,[],100);
            }
        });
        }
        
        
    });

})(jQuery);

function studentlist(class_section_id){
    $.ajax({
        url: '<?php echo base_url(); ?>onlinecourse/offlinepayment/studentlist',
        type: 'post',
        data: {class_section_id:class_section_id},
        dataType : 'json',
        success: function(data){
            $('#student_id').empty();
            $('#student_id').append('<option value=""><?php echo $this->lang->line('select'); ?></option>');
            $.each(data, function (i, obj)
            {
                var select = "";
                if (student_id == obj.id) {
                    select = "selected";
                }
                
                if(obj.lastname != null){
                    var lastname = obj.lastname; 
                } else {
                    var lastname = '';
                }
                
            $('#student_id').append("<option value=" + obj.id +" "+ select + ">" + obj.firstname +" "+ lastname + " ("+ obj.admission_no + ")</option>");
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
<script>
function popup(data)
{
    var base_url = '<?php echo base_url() ?>';
    var frame1 = $('<iframe />');
    frame1[0].name = "frame1";
    frame1.css({"position": "absolute", "top": "-1000000px"});
    $("body").append(frame1);
    var frameDoc = frame1[0].contentWindow ? frame1[0].contentWindow : frame1[0].contentDocument.document ? frame1[0].contentDocument.document : frame1[0].contentDocument;
    frameDoc.document.open();
    //Create a new HTML document.
    frameDoc.document.write('<html>');
    frameDoc.document.write('<head>');
    frameDoc.document.write('<title></title>');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/bootstrap/css/bootstrap.min.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/dist/css/font-awesome.min.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/dist/css/ionicons.min.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/dist/css/AdminLTE.min.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/dist/css/skins/_all-skins.min.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/iCheck/flat/blue.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/morris/morris.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/jvectormap/jquery-jvectormap-1.2.2.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/datepicker/datepicker3.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/daterangepicker/daterangepicker-bs3.css">');
    frameDoc.document.write('</head>');
    frameDoc.document.write('<body>');
    frameDoc.document.write(data);
    frameDoc.document.write('</body>');
    frameDoc.document.write('</html>');
    frameDoc.document.close();
    setTimeout(function () {
        window.frames["frame1"].focus();
        window.frames["frame1"].print();
        frame1.remove();
    }, 500);
    return true;
}
</script>