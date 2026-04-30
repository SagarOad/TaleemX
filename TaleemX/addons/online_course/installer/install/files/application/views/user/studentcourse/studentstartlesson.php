<?php $this->load->view('layout/course_css.php'); ?>
<div class="wrapheader">
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-8">
			<div class="wraplogo">
				<img src="<?php echo base_url(); ?>uploads/school_content/admin_logo/<?php echo $this->setting_model->getAdminlogo();?>" alt="<?php echo $this->customlib->getAppName() ?>" />
				<span id="course_title_id"><?php if (!empty($coursesList['title'])) {echo ucfirst($coursesList['title']);}?> </span>
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-4">
			<ul class="wraplist">
				<li>				
					<?php if(!empty($quizprogress)){ ?>
					<a type="button" data-toggle="modal" course-data-id="<?php echo $coursesList['id']; ?>" class=" quiz_button quiz_button-align btn btn-info"><?php echo $this->lang->line('course_performance'); ?></a>
					<?php } ?>
				</li>
				<li>
					<a href="#menu-toggle" class="sidebar-closebtn btn-info" id="menu-toggle"><i class="fa fa-angle-right"></i></a>
				</li> 
				<li><a type="button" onclick="closevideo()" class="btn btn-info" data-dismiss="modal">&times;</a></li>	
			</ul>	
		</div>	
	</div>		
</div>

<div class="wrapper-modal">
    <div id="sidebar-wrapper">
        <div class="sidebar-nav">
			<?php if($coursesList['free_course'] == '1' || $paidstatus == '1' || (!empty($lessonprogress)) || (!empty($quizprogress))){ 
			?>
			<div class="videoaccordion videoaccordion-bottom-sm">				
				<div class="box-group" id="accordion">
					<div class="panel">
					<h4 class="course-content fontmedium"><?php echo $this->lang->line('course_content'); ?></h4>

					<?php if (!empty($sectionList)) {
						$lessoncount=0; $quizcount=0; $sectioncount = 1; $assignmentcount=0; $examcount=0;$next_step_status=0;	$previous_complete=0;	
						$count=0;
						foreach ($sectionList as $sectionList_key => $sectionList_value) { ?>
					<?php $sectionID = $sectionList_value->id;?>
						<div class="box-header">
							<h4 class="box-title">
							<a data-toggle="collapse" data-parent="#accordion" href="#course_<?php echo $sectionID; ?>">
							<h5 class="h5section fontmedium"><?php echo $this->lang->line('section'); ?> <?php echo $sectioncount; ?>: <?php echo $sectionList_value->section_title; ?></h5></a>
							</h4>
						</div>
						<div id="course_<?php echo $sectionID; ?>" class="panel-collapse collapse">
							<div class="box-body pt0 pb0">
							<?php  
							if (!empty($lessonquizdetail[$sectionID])){
								foreach ($lessonquizdetail[$sectionID] as $lessonquizdetail_value){ $count++;
								$lessoncount 	= $lessoncount+1;
							$order_id 	=	 $lessonquizdetail_value['id'];		
							
							if($lessonquizdetail_value['type'] == 'lesson'){
									$lesson_id 	  =	 $lessonquizdetail_value['lesson_id'];							
									$checked = "";
									$class="";
									$disabled="";		
												
									if($lessonprogress[$lesson_id]){
										$checked = "checked";
									}
								  								
								if($lessonquizdetail[$sectionID][0]['type'] == 'lesson'){ ?>
								      <input type="hidden" id="type"  value="lesson">
								<?php }else{ ?>
	                    <input type="hidden" id="type"  value="quiz">
								<?php } ?>
								<input type="hidden" id="lessonID"  value="<?php echo $lessonquizdetail_value['lesson_id']; ?>">
								<input type="hidden" id="sectionID"  value="<?php echo $sectionID; ?>">
								<?php if($lessonquizdetail_value['type'] !=''){ ?>
								<ul class="navsidelist">
									<li  class="video_list" id="lesson_<?php echo $lessonquizdetail_value["lesson_id"]; ?>">		 
										<div class="firstcontent">		 								
											<input type="checkbox" <?php echo $disabled;?> class="checkbox" id="<?php echo $lessonquizdetail_value["lesson_id"]; ?>" onchange="markAsComplete(this.id,1,'<?php echo $sectionList_value->id; ?>',<?php echo $count;?>)" <?php echo $checked; ?>/>
											<div class="<?php //echo $class;?> lesson_video_ID displayinline pl5 valign-top" section-data-id="<?php echo $sectionID; ?>" data-id="<?php echo $lessonquizdetail_value['lesson_id']; ?>"><?php echo "<b>".$this->lang->line($lessonquizdetail_value['type'])." ".$lessoncount.": "."</b>". $lessonquizdetail_value['lesson_title']; ?> </div>
										</div>
											<div class="video_time"><?php if($lessonquizdetail_value['lesson_type'] == 'video'){ echo $lessonquizdetail_value['duration'];} ?></div>
									</li>
								</ul>
							<?php } ?>

							<?php }else if($lessonquizdetail_value['type'] == 'quiz'  && $this->customlib->get_online_course_curriculam_status("online_course_quiz")==""){ 
									$quizcount = $quizcount+1;
									$quiz_id = $lessonquizdetail_value["quiz_id"]	;							
									$checkedquiz = "";
									$class="";
									$disabled="";
									if($quizprogress[$quiz_id]){
										$checkedquiz = "checked";
									}	

									?>
							<?php if($lessonquizdetail_value['type'] !=''){ ?>
								<ul class="navsidelist "> 				
									<li class="video_list" id="quiz_<?php echo $lessonquizdetail_value['quiz_id']; ?>"> 
										<div class="firstcontent">  
											<input type="hidden" id="quiz_id" value="<?php echo $lessonquizdetail_value['quiz_id']; ?>">
											<input type="checkbox" <?php echo $disabled;?> class="checkbox" id="<?php echo $lessonquizdetail_value["quiz_id"]; ?>" onchange="markAsComplete(this.id,2,'<?php echo $sectionList_value->id; ?>',<?php echo $count;?>)" <?php echo $checkedquiz; ?>/>
											<div class="<?php //echo $class;?> quiz_btn_id displayinline pl5 valign-top" course-data-id="<?php echo $coursesList['id']; ?>" data-id="<?php echo $lessonquizdetail_value['quiz_id']; ?>"><?php echo "<b>".$this->lang->line($lessonquizdetail_value['type'])." ".$quizcount.": "."</b>". $lessonquizdetail_value['quiz_title']; ?> </div>
										</div>
									</li>
								</ul>
							<?php } ?>


							<?php }else if($lessonquizdetail_value['type'] == 'assignment'  && $this->customlib->get_online_course_curriculam_status("online_course_assignment")==""){ 
									$assignmentcount = $assignmentcount+1;
									$course_assignment_id = $lessonquizdetail_value["course_assignment_id"]	;							
									$checkedassignment = "";
									$class="";
									$current_date = date('Y-m-d H:i:s');	
									//$disabled="";
									//if($lessonquizdetail_value['submit_date']){				//					
									//	if(strtotime($current_date) <= strtotime($lessonquizdetail_value['submit_date'])){
											$disabled="";
									//	}else{
									//		$disabled="disabled";
									//	}
									//}
									if($assignment_progress[$course_assignment_id]){
										$checkedassignment = "checked";
									}	

									?>
							<?php if($lessonquizdetail_value['type'] !=''){ ?>
								<ul class="navsidelist "> 	 			
									<li class="video_list" id="assignment_<?php echo $lessonquizdetail_value['course_assignment_id']; ?>"> 
										<div class="firstcontent" > 
											<input type="hidden" id="course_assignment_id" value="<?php echo $lessonquizdetail_value['course_assignment_id']; ?>">
											<input type="checkbox" <?php echo $disabled; ?>  class="checkbox" id="<?php echo $lessonquizdetail_value["course_assignment_id"]; ?>" onchange="markAsComplete(this.id,3,'<?php echo $sectionList_value->id; ?>',<?php echo $count;?>)" <?php echo $checkedassignment; ?>/>
											<div class="<?php //echo $class;?> assignment_btn_id displayinline pl5 valign-top "  id="div_id_<?php echo $lessonquizdetail_value['course_assignment_id']; ?>" data-status="0" course-data-id="<?php echo $coursesList['id']; ?>" data-id="<?php echo $lessonquizdetail_value['course_assignment_id']; ?>"><?php echo "<b>".$this->lang->line($lessonquizdetail_value['type'])." ".$assignmentcount.": "."</b>". $lessonquizdetail_value['assignment_title']; ?> </div>
										</div>
									</li>
								</ul>
							<?php }   }else if($lessonquizdetail_value['type'] == 'exam'  && $lessonquizdetail_value['is_active']==1  && $this->customlib->get_online_course_curriculam_status("online_course_exam")==""){ 
									$examcount = $examcount+1;
									$course_exam_id = $lessonquizdetail_value["course_exam_id"]	;							
									$checkedexam = "";
									$class="";
									$current_date = date('Y-m-d H:i:s');								 
									$disabled="";
									//if($lessonquizdetail_value['exam_to']){									
									//	if(strtotime($current_date) <= strtotime($lessonquizdetail_value['exam_to'])){
										//	$disabled="";
										//}else{
										//	$disabled="disabled";
										//}
									//}
									if($exam_progress[$course_exam_id]){
										$checkedexam = "checked";
									}
									
									?>
							<?php if($lessonquizdetail_value['type'] !=''){ ?>
								<ul class="navsidelist "> 				
									<li class="video_list"   id="exam_<?php echo $lessonquizdetail_value['course_exam_id']; ?>"> 
										<div class="firstcontent"> 
											<input type="hidden" id="course_exam_id" value="<?php echo $lessonquizdetail_value['course_exam_id']; ?>">
											<input type="checkbox" <?php echo $disabled;?>  class="checkbox" id="<?php echo $lessonquizdetail_value["course_exam_id"]; ?>" onchange="markAsComplete(this.id,4,'<?php echo $sectionList_value->id; ?>',<?php echo $count;?>)" <?php echo $checkedexam; ?>/>
											<div class="<?php //echo $class;?> exam_btn_id  displayinline pl5 valign-top"  data-status="0" course-data-id="<?php echo $coursesList['id']; ?>" data-id="<?php echo $lessonquizdetail_value['course_exam_id']; ?>"><?php echo "<b>".$this->lang->line($lessonquizdetail_value['type'])." ".$examcount.": "."</b>". $lessonquizdetail_value['course_exam_name']; ?></div>
										</div>
									</li>
								</ul>
							<?php }   } ?>
						<?php } } ?>
							</div>
						</div>
						<?php
						$sectioncount++;
						}} else {?>
						<div class="alert alert-danger">
						<?php echo $this->lang->line('no_record_found') ?>
						</div>
						<?php }?>
					</div>
				</div>
			</div>
			<?php } ?>
		</div><!--./nav-->
	</div><!--/#sidebar-wrapper-->
    <div class="">
        <div class="row">
            <div class="col-lg-12">
                <?php if($coursesList['free_course'] == '1' || $paidstatus == '1' || (!empty($lessonprogress)) || (!empty($quizprogress))){
                ?>
                <div id="video_id"></div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<script>
function closevideo()
{ 
    window.location.reload();
}
</script>
<script>
	function markAsComplete(lesson_quiz_id,lesson_quiz_type,section_id,count){	
		$.ajax({
			type : 'POST',
			url : "<?= base_url('user/studentcourse/markascomplete'); ?>",
			data : {lesson_quiz_id : lesson_quiz_id,lesson_quiz_type : lesson_quiz_type,section_id : section_id},
			success : function(data){
			},
			complete : function(data){
		 }
		});
    }

</script>
<script>
(function ($) {
  "use strict";

  $('.quiz_button').click(function(){
  	$('#video_id').html('');
    var courseid = $(this).attr('course-data-id');
    $.ajax({
      url : '<?php echo base_url(); ?>user/studentcourse/quizperformance',
      data: {courseid:courseid},
      type:'post',
      success : function(response){
        $('#video_id').html(response);
      }
    });
  });

  $('.lesson_video_ID').click(function(){
  	$('#video_id').html('');
    var sectionID = $(this).attr('section-data-id');
    var lessonID = $(this).attr('data-id');
	$('.video_list').removeClass('active');
	$('#lesson_'+lessonID).addClass('active');	
    $.ajax({
      url : '<?php echo base_url(); ?>user/studentcourse/getlessonvideo',
      data: {lessonID:lessonID,sectionID:sectionID},
      type:'post',
      success : function(response){
        $('#video_id').html(response);
      }
    });
  });

  $('.quiz_btn_id').click(function(){
  	$('#video_id').html('');
    var courseid = $(this).attr('course-data-id');
    var quizID = $(this).attr('data-id');
	$('.video_list').removeClass('active');
	$('#quiz_'+quizID).addClass('active');
    $.ajax({
      url : '<?php echo base_url(); ?>user/studentcourse/quizinstruction',
      data: {quizID:quizID,courseid:courseid},
      type:'post',
      success : function(response){
        $('#video_id').html(response);
      }
    });
  });

  $(document).ready(function(){
  	$('#video_id').html('');
    var lessonID = $('#lessonID').val();
    var sectionID = $('#sectionID').val();
    var type = $('#type').val();
	
    if(type == 'lesson'){
		$('#lesson_'+lessonID).addClass('active');
	    $.ajax({
	      url : '<?php echo base_url(); ?>user/studentcourse/getlessonvideo',
	      data: {lessonID:lessonID,sectionID:sectionID},
	      type:'post',
	      success : function(response){
	        $('#video_id').html(response);
	      }
	    });
    }else{
    	var courseid = "<?php echo $coursesList['id']; ?>";
    	var quizID = $('#quiz_id').val();
		$('#quiz_'+quizID).addClass('active');
	    $.ajax({
	      url : '<?php echo base_url(); ?>user/studentcourse/quizinstruction',
	      data: {quizID:quizID,courseid:courseid},
	      type:'post',
	      success : function(response){
	        $('#video_id').html(response);
	      }
	    });
    }
  })
})(jQuery);
</script>
<script>
(function ($) {
	"use strict";
	$(".sidebar-closebtn").on('click', function () {
		$(".fa-angle-right").toggleClass("rotate");
	});

	$("#menu-toggle").click(function (e) {
		e.preventDefault();
		$(".wrapper-modal").toggleClass("toggled");
	});
})(jQuery);
</script>

<script>
//online course assignemnt work start
	$('.assignment_btn_id').click(function(){
		$('#video_id').html('');
		var courseid = $(this).attr('course-data-id');
		var id = $(this).attr('data-id');//ASSINGMENT ID
		var status = $(this).attr('data-status');
		$('.video_list').removeClass('active');
		$('#assignment_'+id).addClass('active');
		$.ajax({
			url : '<?php echo base_url(); ?>user/studentcourse/submit_assigment/'+id+'/'+status,
			data: {id:id,courseid:courseid},
			type:'post',
			success : function(response){
				$('#video_id').html(response);
			}
		});
	});

</script>

<script>
//online course Exam work start
	$('.exam_btn_id').click(function(){
		$('#video_id').html('');
		var courseid = $(this).attr('course-data-id');
		var exam_id = $(this).attr('data-id'); 		 

		$('.video_list').removeClass('active');
		$('#exam_'+exam_id).addClass('active');
		
		$.ajax({       
			url : '<?php echo base_url(); ?>user/studentcourse/exam_details',
			data: {exam_id:exam_id,courseid:courseid},
			type:'post',
			success : function(response){
				$('#video_id').html(response);
			}
		});
	});
	
</script>
