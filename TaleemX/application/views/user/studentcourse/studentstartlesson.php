<?php $this->load->view('layout/course_css.php'); ?>
<style>
/* Lesson play screen: shrink the video and course content list by ~30% so
   that action buttons (Summarize / Explain / etc.) fit neatly below the
   video. Scoped to .lesson-play-scope so other pages keep the original
   dimensions from course_addon.css. */
.lesson-play-scope .course-video-height iframe,
.lesson-play-scope .course-video-height video,
.lesson-play-scope #player-overlay video,
.lesson-play-scope .embed-container iframe,
.lesson-play-scope .embed-container video {
    /* Fill the full available area above the action buttons bar.
       The header is ~50px tall and we leave ~10px gap below the video. */
    height: calc(100vh - 165px) !important;
    max-height: calc(100vh - 165px) !important;
    width: 100%;
}
.lesson-play-scope .scroll-area-fullheight-video,
.lesson-play-scope #player-overlay,
.lesson-play-scope .embed-container {
    height: calc(100vh - 165px);
    overflow: hidden;
    padding-bottom: 0 !important;
}
.lesson-play-scope {
    --lesson-main-height: calc(100vh - 165px);
}
.lesson-play-scope .course-video-height iframe,
.lesson-play-scope .course-video-height video,
.lesson-play-scope #player-overlay video,
.lesson-play-scope .embed-container iframe,
.lesson-play-scope .embed-container video,
.lesson-play-scope .scroll-area-fullheight-video,
.lesson-play-scope #player-overlay,
.lesson-play-scope .embed-container,
.lesson-play-scope #sidebar-wrapper,
.lesson-play-scope .sidebar-nav {
    height: var(--lesson-main-height) !important;
    max-height: var(--lesson-main-height) !important;
}
.lesson-play-scope .sidebar-nav {
    overflow-y: auto !important;
    overflow-x: hidden !important;
}
.wrapper-modal.lesson-play-scope,
.wrapper-modal.lesson-play-scope .row,
.wrapper-modal.lesson-play-scope .col-lg-12,
.wrapper-modal.lesson-play-scope #video_id {
    background: #ffffff !important;
}
/* Parent modal wrapper uses black by default in course_addon.css. Override it
   for the lesson player so any leftover area below content is white. */
#coursemodal .video-contentfull,
#coursemodal #course_model_body {
    background: #ffffff !important;
}
.lesson-actions-bar {
    margin-top: 14px;
    padding: 12px 15px;
    background: #f6f8fa;
    border: 1px solid #e1e4e8;
    border-radius: 6px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}
.lesson-actions-bar .lesson-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
}
.lesson-actions-bar .lesson-action-btn .fa {
    font-size: 14px;
}
.lesson-actions-hint {
    color: #6a737d;
    font-size: 12px;
    margin-left: auto;
}

/* --------- Lesson AI modal --------- */
#lesson_ai_modal .modal-dialog { margin-top: 60px; }
#lesson_ai_modal .modal-content {
    border: 1px solid #e1e4e8;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.18);
}
#lesson_ai_modal .modal-header {
    border-bottom: 1px solid #eef0f2;
    padding: 14px 20px;
}
#lesson_ai_modal .modal-title {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #ffffff;
    font-size: 16px;
}
#lesson_ai_modal .modal-title .fa { color: #ffffff; }
#lesson_ai_modal .modal-header .close,
#lesson_ai_modal .modal-header .close span { color: #ffffff; opacity: 0.85; text-shadow: none; }
#lesson_ai_modal .modal-header .close:hover { opacity: 1; }
#lesson_ai_modal .modal-body { padding: 18px 20px; }
#lesson_ai_modal .modal-footer { padding: 10px 20px; border-top: 1px solid #eef0f2; }

.lesson-ai-modal__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    padding-bottom: 14px;
    border-bottom: 1px dashed #e1e4e8;
    margin-bottom: 14px;
}
.lesson-ai-modal__actions .lesson-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
}
.lesson-ai-modal__result { min-height: 80px; }
.lesson-ai-modal__result:empty::before {
    content: "Pick Summarize or Explain to generate AI content for this video.";
    display: block;
    color: #6a737d;
    font-style: italic;
    padding: 20px 4px;
    text-align: center;
}

/* AI result card shown inside the modal. */
.lesson-ai-result {
    padding: 14px 16px;
    background: #ffffff;
    border: 1px solid #e1e4e8;
    border-radius: 6px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.lesson-ai-result__head {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #24292e;
    margin-bottom: 8px;
    font-size: 14px;
}
.lesson-ai-result__head .fa { color: #0366d6; }
.lesson-ai-result__body {
    white-space: pre-wrap;
    color: #24292e;
    line-height: 1.55;
    font-size: 14px;
}
.lesson-ai-result__body.is-error { color: #b31d28; }
.lesson-ai-result__body.is-muted { color: #6a737d; font-style: italic; }
.lesson-ai-spinner {
    display: inline-block;
    width: 14px; height: 14px;
    border: 2px solid #c8d1d9;
    border-top-color: #0366d6;
    border-radius: 50%;
    animation: lessonAiSpin 0.8s linear infinite;
    vertical-align: middle;
    margin-right: 6px;
}
@keyframes lessonAiSpin { to { transform: rotate(360deg); } }

.lesson-ai-followup {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dashed #e1e4e8;
}
.lesson-ai-followup__label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #586069;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.lesson-ai-followup__row {
    display: flex;
    gap: 8px;
}
.lesson-ai-followup__row input {
    flex: 1 1 auto;
    height: 38px;
    padding: 0 12px;
    border: 1px solid #d1d5da;
    border-radius: 6px;
    font-size: 14px;
    background: #fff;
    color: #24292e;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
}
.lesson-ai-followup__row input:focus {
    border-color: #0366d6;
    box-shadow: 0 0 0 3px rgba(3,102,214,0.15);
}
.lesson-ai-followup__row button {
    flex: 0 0 auto;
    height: 38px;
    padding: 0 16px;
    background: #0366d6;
    border: 1px solid #0366d6;
    color: #fff;
    font-weight: 500;
    border-radius: 6px;
    cursor: pointer;
    transition: background .15s;
}
.lesson-ai-followup__row button:hover:not([disabled]) { background: #0256b3; }
.lesson-ai-followup__row button[disabled] { opacity: 0.6; cursor: not-allowed; }

.lesson-ai-qa { margin-top: 14px; }
.lesson-ai-qa__item {
    padding: 10px 0;
    border-top: 1px solid #eef0f2;
}
.lesson-ai-qa__item:first-child { border-top: 0; }
.lesson-ai-qa__q {
    font-weight: 600;
    color: #24292e;
    font-size: 14px;
    margin-bottom: 4px;
}
.lesson-ai-qa__q::before { content: "Q: "; color: #0366d6; }
.lesson-ai-qa__a {
    color: #24292e;
    white-space: pre-wrap;
    font-size: 14px;
    line-height: 1.55;
}
.lesson-ai-qa__a::before { content: "A: "; color: #2ea44f; font-weight: 600; }
</style>
<div class="wrapheader">
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-8">
			<div class="wraplogo">
				<img src="<?php echo base_url('uploads/school_content/admin_logo/1776861047-150388710369e8bf7714b9e!TaleemX%20Logo.png');?>" alt="<?php echo $this->customlib->getAppName() ?>" />
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

<div class="wrapper-modal lesson-play-scope">
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
                <div id="lesson_actions_bar" class="lesson-actions-bar" style="display:none;">
                    <button type="button" id="lesson_summarize_btn" class="btn btn-primary lesson-action-btn">
                        <i class="fa fa-align-left"></i>
                        <span><?php echo $this->lang->line('summarize') ? $this->lang->line('summarize') : 'Summarize'; ?></span>
                    </button>
                    <button type="button" id="lesson_explain_btn" class="btn btn-success lesson-action-btn">
                        <i class="fa fa-lightbulb-o"></i>
                        <span><?php echo $this->lang->line('explain') ? $this->lang->line('explain') : 'Explain'; ?></span>
                    </button>
                    <span class="lesson-actions-hint">AI tools for this lesson</span>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     Lesson AI modal
     The Summarize / Explain buttons open this modal and the AI
     response is rendered inside its body. The same Summarize /
     Explain action buttons are duplicated here so the student can
     switch modes without closing the modal, and a follow-up input
     appears after the first Explain answer.
     ============================================================ -->
<div id="lesson_ai_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="lesson_ai_modal_title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="lesson_ai_modal_title">
                    <i class="fa fa-magic"></i>
                    <span>Lesson AI</span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="lesson-ai-modal__actions">
                    <button type="button" id="lesson_ai_modal_summarize_btn" class="btn btn-primary lesson-action-btn">
                        <i class="fa fa-align-left"></i>
                        <span><?php echo $this->lang->line('summarize') ? $this->lang->line('summarize') : 'Summarize'; ?></span>
                    </button>
                    <button type="button" id="lesson_ai_modal_explain_btn" class="btn btn-success lesson-action-btn">
                        <i class="fa fa-lightbulb-o"></i>
                        <span><?php echo $this->lang->line('explain') ? $this->lang->line('explain') : 'Explain'; ?></span>
                    </button>
                    <span class="lesson-actions-hint">AI tools for this lesson</span>
                </div>
                <div id="lesson_action_result" class="lesson-ai-modal__result"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
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
// Tracks the currently playing lesson so Summarize / Explain clicks can
// reference it. Real behaviour of these buttons will be implemented later.
window.currentLessonContext = { lessonId: null, sectionId: null, courseId: <?php echo (int) $coursesList['id']; ?> };

function showLessonActionsBar(show) {
    var bar = document.getElementById('lesson_actions_bar');
    if (bar) { bar.style.display = show ? '' : 'none'; }
    if (window.LessonAI && typeof window.LessonAI.reset === 'function') {
        window.LessonAI.reset();
    }
}

(function ($) {
  "use strict";

  $('.quiz_button').click(function(){
  	$('#video_id').html('');
    showLessonActionsBar(false);
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
        window.currentLessonContext.lessonId  = lessonID;
        window.currentLessonContext.sectionId = sectionID;
        showLessonActionsBar(true);
      }
    });
  });

  $('.quiz_btn_id').click(function(){
  	$('#video_id').html('');
    showLessonActionsBar(false);
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
	        window.currentLessonContext.lessonId  = lessonID;
	        window.currentLessonContext.sectionId = sectionID;
	        showLessonActionsBar(true);
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
  });

})(jQuery);

// =========================================================================
// Lesson AI — Summarize / Explain + follow-up questions.
//
// Flow:
//   1) Student clicks Summarize or Explain.
//   2) We POST to /user/studentcourse/caption_ai with the current lesson id
//      and the desired action. The server fetches the stored transcript for
//      the lesson (video_transcripts.full_transcript) and forwards it to the
//      external Caption AI service.
//   3) The answer is rendered below the buttons. For Explain we additionally
//      expose a follow-up input so the student can ask more questions about
//      the same video transcript.
// =========================================================================
(function ($) {
    "use strict";

    var ENDPOINT = '<?php echo base_url('user/studentcourse/caption_ai'); ?>';

    var state = {
        mode: null,           // 'summarize' | 'explain'
        lessonId: null,
        qa: []                // [{q:string,a:string}]
    };

    function escapeHTML(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function $panel() { return document.getElementById('lesson_action_result'); }
    function $modal() { return $('#lesson_ai_modal'); }

    function openModal() {
        var $m = $modal();
        if ($m && $m.length) { $m.modal('show'); }
    }

    function hideModal() {
        var $m = $modal();
        if ($m && $m.length) { $m.modal('hide'); }
    }

    function clearPanel() {
        var el = $panel(); if (!el) return;
        el.innerHTML = '';
    }

    function renderLoading(title) {
        var el = $panel(); if (!el) return;
        el.innerHTML =
            '<div class="lesson-ai-result">' +
                '<div class="lesson-ai-result__head">' +
                    '<i class="fa fa-magic"></i><span>' + escapeHTML(title) + '</span>' +
                '</div>' +
                '<div class="lesson-ai-result__body is-muted">' +
                    '<span class="lesson-ai-spinner"></span>Thinking...' +
                '</div>' +
            '</div>';
    }

    function renderError(title, msg) {
        var el = $panel(); if (!el) return;
        el.innerHTML =
            '<div class="lesson-ai-result">' +
                '<div class="lesson-ai-result__head">' +
                    '<i class="fa fa-exclamation-triangle"></i><span>' + escapeHTML(title) + '</span>' +
                '</div>' +
                '<div class="lesson-ai-result__body is-error">' + escapeHTML(msg) + '</div>' +
            '</div>';
    }

    function renderSummary(answer) {
        var el = $panel(); if (!el) return;
        el.innerHTML =
            '<div class="lesson-ai-result">' +
                '<div class="lesson-ai-result__head">' +
                    '<i class="fa fa-align-left"></i><span>Summary</span>' +
                '</div>' +
                '<div class="lesson-ai-result__body">' + escapeHTML(answer) + '</div>' +
            '</div>';
    }

    function renderExplain() {
        var el = $panel(); if (!el) return;

        var qaHtml = '';
        if (state.qa.length) {
            qaHtml = '<div class="lesson-ai-qa">';
            state.qa.forEach(function (pair) {
                qaHtml +=
                    '<div class="lesson-ai-qa__item">' +
                        (pair.q
                            ? '<div class="lesson-ai-qa__q">' + escapeHTML(pair.q) + '</div>'
                            : '') +
                        '<div class="lesson-ai-qa__a">' + escapeHTML(pair.a) + '</div>' +
                    '</div>';
            });
            qaHtml += '</div>';
        }

        el.innerHTML =
            '<div class="lesson-ai-result">' +
                '<div class="lesson-ai-result__head">' +
                    '<i class="fa fa-lightbulb-o"></i><span>Explain</span>' +
                '</div>' +
                qaHtml +
                '<div class="lesson-ai-followup">' +
                    '<label class="lesson-ai-followup__label" for="lesson_ai_followup_input">Ask a follow-up about this video</label>' +
                    '<div class="lesson-ai-followup__row">' +
                        '<input type="text" id="lesson_ai_followup_input" placeholder="e.g. Can you give me an example?" maxlength="500" />' +
                        '<button type="button" id="lesson_ai_followup_btn">Ask</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        var input = document.getElementById('lesson_ai_followup_input');
        if (input) { input.focus(); }
    }

    function renderFollowupLoading() {
        renderExplain();
        var panel = $panel();
        if (!panel) return;
        var row = panel.querySelector('.lesson-ai-followup__row');
        if (row) {
            var btn = row.querySelector('button');
            var inp = row.querySelector('input');
            if (btn) { btn.setAttribute('disabled', 'disabled'); btn.innerHTML = '<span class="lesson-ai-spinner"></span>'; }
            if (inp) { inp.setAttribute('disabled', 'disabled'); }
        }
    }

    function callApi(payload) {
        return fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        }).then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, status: res.status, data: data };
            }).catch(function () {
                return { ok: false, status: res.status, data: { error: 'Invalid server response.' } };
            });
        });
    }

    function runSummarize(lessonId) {
        state.mode = 'summarize';
        state.lessonId = lessonId;
        state.qa = [];
        openModal();
        renderLoading('Summarizing this video...');
        callApi({ action: 'summarize', lesson_id: lessonId })
            .then(function (resp) {
                if (resp.ok && resp.data && typeof resp.data.answer === 'string' && resp.data.answer !== '') {
                    renderSummary(resp.data.answer);
                } else {
                    var msg = (resp.data && resp.data.error) ? resp.data.error : 'Unable to generate a summary right now.';
                    renderError('Summary failed', msg);
                }
            })
            .catch(function (err) {
                renderError('Summary failed', (err && err.message) ? err.message : 'Network error.');
            });
    }

    function runExplain(lessonId) {
        state.mode = 'explain';
        state.lessonId = lessonId;
        state.qa = [];
        openModal();
        renderLoading('Explaining this video...');
        callApi({ action: 'explain', lesson_id: lessonId })
            .then(function (resp) {
                if (resp.ok && resp.data && typeof resp.data.answer === 'string' && resp.data.answer !== '') {
                    state.qa.push({ q: '', a: resp.data.answer });
                    renderExplain();
                } else {
                    var msg = (resp.data && resp.data.error) ? resp.data.error : 'Unable to generate an explanation right now.';
                    renderError('Explain failed', msg);
                }
            })
            .catch(function (err) {
                renderError('Explain failed', (err && err.message) ? err.message : 'Network error.');
            });
    }

    function askFollowup(question) {
        if (!state.lessonId || !question) return;
        renderFollowupLoading();
        callApi({ action: 'explain', lesson_id: state.lessonId, question: question })
            .then(function (resp) {
                if (resp.ok && resp.data && typeof resp.data.answer === 'string' && resp.data.answer !== '') {
                    state.qa.push({ q: question, a: resp.data.answer });
                    renderExplain();
                } else {
                    var msg = (resp.data && resp.data.error) ? resp.data.error : 'Unable to answer that right now.';
                    state.qa.push({ q: question, a: '\u26A0\uFE0F ' + msg });
                    renderExplain();
                }
            })
            .catch(function (err) {
                state.qa.push({ q: question, a: '\u26A0\uFE0F ' + ((err && err.message) ? err.message : 'Network error.') });
                renderExplain();
            });
    }

    // Expose a reset hook so that switching lessons/quizzes clears the panel
    // and closes the modal (stale answers should never leak across lessons).
    window.LessonAI = {
        reset: function () {
            state = { mode: null, lessonId: null, qa: [] };
            clearPanel();
            hideModal();
        }
    };

    // Handle clicks on both the outer (below-video) and the inner (inside-modal)
    // action buttons. The outer ones will also open the modal via runSummarize
    // / runExplain; the inner ones simply re-run the action inside the open
    // modal so the student can switch between Summarize and Explain freely.
    $(document).on('click', '#lesson_summarize_btn, #lesson_ai_modal_summarize_btn', function () {
        var ctx = window.currentLessonContext || {};
        if (!ctx.lessonId) {
            openModal();
            renderError('No lesson selected', 'Open a lesson video first, then click Summarize.');
            return;
        }
        runSummarize(parseInt(ctx.lessonId, 10));
    });

    $(document).on('click', '#lesson_explain_btn, #lesson_ai_modal_explain_btn', function () {
        var ctx = window.currentLessonContext || {};
        if (!ctx.lessonId) {
            openModal();
            renderError('No lesson selected', 'Open a lesson video first, then click Explain.');
            return;
        }
        runExplain(parseInt(ctx.lessonId, 10));
    });

    // When the modal is fully closed, wipe the result so re-opening starts
    // from a clean slate. This keeps state predictable if the student closes
    // the modal mid-answer.
    $(document).on('hidden.bs.modal', '#lesson_ai_modal', function () {
        state = { mode: null, lessonId: state.lessonId, qa: [] };
        clearPanel();
    });

    // Follow-up question (wired via event delegation because the input is
    // re-rendered on every successful answer).
    $(document).on('click', '#lesson_ai_followup_btn', function () {
        var inp = document.getElementById('lesson_ai_followup_input');
        var q   = (inp && inp.value ? inp.value : '').trim();
        if (q === '') { if (inp) inp.focus(); return; }
        askFollowup(q);
    });

    $(document).on('keydown', '#lesson_ai_followup_input', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            $('#lesson_ai_followup_btn').trigger('click');
        }
    });
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
		showLessonActionsBar(false);
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
		showLessonActionsBar(false);
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
