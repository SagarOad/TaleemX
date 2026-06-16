<?php $this->load->view('layout/course_css.php'); ?>
<style>
/* Lesson play screen layout.
   The video preview sits on the LEFT (~60%) with its stored captions/transcript
   running below it; a wider RIGHT panel (~40%) holds the tabbed "AI Assistant"
   and "Course Content" views. Scoped to .lesson-play-scope so other pages keep
   the original dimensions from course_addon.css. */
.lesson-play-scope {
    /* Width of the right side panel (AI Assistant / Content). */
    --ai-side-w: clamp(380px, 38vw, 640px);
    /* Video preview height — intentionally compact so captions + AI fit. */
    --lesson-video-h: min(56vh, calc(100vh - 230px));
}

/* The video media itself (smaller preview; fullscreen still available). */
.lesson-play-scope .course-video-height iframe,
.lesson-play-scope .course-video-height video,
.lesson-play-scope #player-overlay video,
.lesson-play-scope .embed-container iframe,
.lesson-play-scope .embed-container video {
    height: var(--lesson-video-h) !important;
    max-height: var(--lesson-video-h) !important;
    width: 100%;
}
.lesson-play-scope #player-overlay,
.lesson-play-scope .embed-container {
    height: var(--lesson-video-h);
    overflow: hidden;
    padding-bottom: 0 !important;
}
/* Un-clip the left column so the transcript shows (and scrolls) below the video. */
.lesson-play-scope .scroll-area-fullheight-video {
    height: auto !important;
    max-height: calc(100vh - 96px);
    overflow-y: auto;
    overflow-x: hidden;
    padding-bottom: 0 !important;
}

/* Widen the right side panel to ~40% (video gets the remaining ~60%). */
.lesson-play-scope.wrapper-modal { padding-right: var(--ai-side-w); }
.lesson-play-scope.wrapper-modal.toggled { padding-right: 0; }
.lesson-play-scope #sidebar-wrapper {
    width: var(--ai-side-w);
    right: var(--ai-side-w);
    margin-right: calc(-1 * var(--ai-side-w));
    height: calc(100vh - 60px) !important;
    max-height: calc(100vh - 60px) !important;
    overflow: hidden;
}
.lesson-play-scope .sidebar-nav {
    width: var(--ai-side-w);
    max-width: var(--ai-side-w);
    height: calc(100vh - 60px) !important;
    max-height: calc(100vh - 60px) !important;
    overflow: hidden !important;
    display: flex;
    flex-direction: column;
}
@media (max-width: 991px) {
    .lesson-play-scope { --ai-side-w: 280px; --lesson-video-h: 34vh; }
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
.lesson-ai-modal__result { min-height: 80px; background: #fff; color: #24292e; }
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
.lesson-ai-qa__q .fa { color: #0366d6; margin-right: 4px; }

/* --------- Structured AI response --------- */
.lesson-ai-result__subtitle {
    font-size: 15px;
    font-weight: 600;
    color: #1b2733;
    margin: 2px 0 8px;
}
.lesson-ai-result__body code {
    background: #f3f4f6;
    border-radius: 4px;
    padding: 1px 5px;
    font-size: 12.5px;
}
.lesson-ai-block { margin-top: 14px; }
.lesson-ai-block__title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #57606a;
    margin-bottom: 6px;
}
.lesson-ai-block__title .fa { margin-right: 5px; }
.lesson-ai-block ul {
    margin: 0;
    padding-left: 18px;
}
.lesson-ai-block ul li {
    font-size: 14px;
    line-height: 1.5;
    color: #24292e;
    margin-bottom: 4px;
}
.lesson-ai-keys ul li::marker { color: #0366d6; }
.lesson-ai-takeaways ul li::marker { color: #2ea44f; }

/* Reference chips (jump to video) */
.lesson-ai-ref-list { display: flex; flex-wrap: wrap; gap: 8px; }
.lesson-ai-ref {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    max-width: 100%;
    border: 1px solid #d0d7de;
    background: #f6f8fa;
    border-radius: 999px;
    padding: 5px 12px;
    font-size: 12.5px;
    color: #24292e;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
}
.lesson-ai-ref:hover { background: #ddf4ff; border-color: #54aeff; }
.lesson-ai-ref .fa { color: #0366d6; }
.lesson-ai-ref__ts { font-family: monospace; font-weight: 700; color: #0366d6; }
.lesson-ai-ref__q {
    color: #57606a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 220px;
}

/* Suggested follow-up chips */
.lesson-ai-suggest-list { display: flex; flex-wrap: wrap; gap: 8px; }
.lesson-ai-suggest {
    border: 1px dashed #c6cdd5;
    background: #fff;
    border-radius: 8px;
    padding: 7px 12px;
    font-size: 13px;
    color: #0a5dc2;
    cursor: pointer;
    text-align: left;
    transition: background 0.15s, border-color 0.15s;
}
.lesson-ai-suggest:hover { background: #f0f7ff; border-color: #54aeff; }

/* Attachment links */
.lesson-ai-att-list { display: flex; flex-wrap: wrap; gap: 8px; }
.lesson-ai-att {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid #d0d7de;
    border-radius: 8px;
    padding: 6px 11px;
    font-size: 13px;
    color: #24292e;
    text-decoration: none;
    background: #fff;
}
.lesson-ai-att:hover { background: #f6f8fa; text-decoration: none; color: #0366d6; }
.lesson-ai-att .fa { color: #57606a; }

/* Difficulty / depth selector */
.lesson-ai-level {
    height: 32px;
    border: 1px solid #d0d7de;
    border-radius: 6px;
    background: #fff;
    color: #24292e;
    font-size: 13px;
    padding: 0 8px;
}
.lesson-ai-level:focus { outline: none; border-color: #54aeff; }

/* --------- Tabbed right panel (AI Assistant / Course Content) --------- */
.ai-side-tabs {
    display:flex; flex:0 0 auto; border-bottom:1px solid #e1e4e8; background:#fff;
}
.ai-side-tab {
    flex:1 1 0; border:0; background:transparent; padding:12px 8px; cursor:pointer;
    font-weight:600; font-size:13px; color:#57606a; border-bottom:2px solid transparent;
    display:inline-flex; align-items:center; justify-content:center; gap:6px;
    transition: color .15s, border-color .15s, background .15s;
}
.ai-side-tab .fa { font-size:13px; }
.ai-side-tab:hover { color:#0366d6; }
.ai-side-tab.active { color:#0366d6; border-bottom-color:#0366d6; background:#f6f9fc; }

.ai-side-pane { flex:1 1 auto; min-height:0; overflow-y:auto; overflow-x:hidden; }
.ai-side-pane--hidden { display:none !important; }
#ai_side_assistant {
    overflow:hidden; display:flex; flex-direction:column;
    background:#fff; color:#24292e;
}

/* AI assistant pane internals */
.ai-assistant-actions {
    flex:0 0 auto; display:flex; flex-wrap:wrap; gap:8px; align-items:center;
    padding:12px; border-bottom:1px solid #eef0f2; background:#fafbfc;
}
.ai-assistant-actions .lesson-action-btn {
    display:inline-flex; align-items:center; gap:6px; font-weight:500; font-size:13px;
}
.ai-assistant-actions .lesson-actions-hint { width:100%; margin:0; color:#6a737d; }
#lesson_action_result,
.lesson-play-scope #ai_side_assistant .lesson-ai-modal__result {
    flex:1 1 auto; min-height:0; overflow-y:auto; padding:14px;
    background:#fff; color:#24292e;
}
#lesson_action_result:empty::before {
    content: "Pick Summarize or Explain, ask a question, or tap the wand on any caption.";
    display:block; color:#6a737d; font-style:italic; padding:24px 6px; text-align:center; font-size:13px;
}
/* Override dark sidebar text inheritance inside the AI pane */
.lesson-play-scope #ai_side_assistant .lesson-ai-qa__q,
.lesson-play-scope #ai_side_assistant .lesson-ai-result__head,
.lesson-play-scope #ai_side_assistant .lesson-ai-result__body,
.lesson-play-scope #ai_side_assistant .lesson-ai-result__subtitle,
.lesson-play-scope #ai_side_assistant .lesson-ai-block__title,
.lesson-play-scope #ai_side_assistant .lesson-ai-block ul li,
.lesson-play-scope #ai_side_assistant .lesson-ai-followup__label {
    color:#24292e;
}
.lesson-play-scope #ai_side_assistant .lesson-ai-followup__row input {
    background:#fff; color:#24292e;
}
.lesson-play-scope #sidebar-wrapper .videoaccordion { padding:0; }
.lesson-play-scope #sidebar-wrapper .course-content { padding:12px 14px 4px; margin:0; }
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
			<div class="ai-side-tabs">
				<button type="button" class="ai-side-tab active" data-tab="assistant"><i class="fa fa-magic"></i> AI Assistant</button>
				<button type="button" class="ai-side-tab" data-tab="content"><i class="fa fa-list-ul"></i> <?php echo $this->lang->line('course_content'); ?></button>
			</div>
			<!-- AI Assistant pane (default) -->
			<div class="ai-side-pane" id="ai_side_assistant">
				<div class="ai-assistant-actions">
					<button type="button" id="lesson_summarize_btn" class="btn btn-primary lesson-action-btn">
						<i class="fa fa-align-left"></i>
						<span><?php echo $this->lang->line('summarize') ? $this->lang->line('summarize') : 'Summarize'; ?></span>
					</button>
					<button type="button" id="lesson_explain_btn" class="btn btn-success lesson-action-btn">
						<i class="fa fa-lightbulb-o"></i>
						<span><?php echo $this->lang->line('explain') ? $this->lang->line('explain') : 'Explain'; ?></span>
					</button>
					<select id="lesson_ai_level" class="lesson-ai-level" title="Response depth">
						<option value="standard">Standard</option>
						<option value="simple">Simple</option>
						<option value="advanced">Advanced</option>
						<option value="exam">Exam revision</option>
					</select>
					<span class="lesson-actions-hint">Summaries, explanations &amp; answers for this lesson</span>
				</div>
				<div id="lesson_action_result" class="lesson-ai-modal__result"></div>
			</div>
			<!-- Course content pane -->
			<div class="ai-side-pane ai-side-pane--hidden" id="ai_side_content">
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
			</div><!-- /#ai_side_content -->
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
// Tracks the currently playing lesson so Summarize / Explain clicks can
// reference it. Real behaviour of these buttons will be implemented later.
window.currentLessonContext = { lessonId: null, sectionId: null, courseId: <?php echo (int) $coursesList['id']; ?> };

function showLessonActionsBar(show) {
    if (!window.LessonAI) { return; }
    if (show && typeof window.LessonAI.onLesson === 'function') {
        var ctx = window.currentLessonContext || {};
        window.LessonAI.onLesson(ctx.lessonId);
    } else if (typeof window.LessonAI.reset === 'function') {
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
        level: 'standard',    // 'simple' | 'standard' | 'advanced' | 'exam'
        summary: null,        // last structured summarize result
        turns: []             // [{ q:string, result:{answer,title,key_points,...} }]
    };

    // Persist the AI conversation per lesson in localStorage (same approach as
    // the Ask AI page) so responses + follow-up chat survive closing the modal
    // and reopening it for the same lesson.
    var STORE_KEY = 'lessonai.threads.v1';
    function loadStore() {
        try { return JSON.parse(localStorage.getItem(STORE_KEY)) || {}; }
        catch (e) { return {}; }
    }
    function saveState() {
        if (!state.lessonId) { return; }
        var store = loadStore();
        store[state.lessonId] = {
            mode: state.mode,
            level: state.level,
            summary: state.summary,
            turns: state.turns,
            ts: Date.now()
        };
        try { localStorage.setItem(STORE_KEY, JSON.stringify(store)); } catch (e) {}
    }
    function restoreState(lessonId) {
        var s = loadStore()[lessonId];
        state.lessonId = lessonId;
        if (s) {
            state.mode    = s.mode || null;
            state.summary = s.summary || null;
            state.turns   = Array.isArray(s.turns) ? s.turns : [];
            if (s.level) { state.level = s.level; }
        } else {
            state.mode = null; state.summary = null; state.turns = [];
        }
    }
    // Re-render whatever conversation is currently held in state.
    function renderRestored() {
        if (state.mode === 'summarize' && state.summary) {
            renderSummary(state.summary);
        } else if (state.mode === 'explain' && state.turns.length) {
            renderExplain();
        } else {
            clearPanel();
        }
    }

    function escapeHTML(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // Lightweight, safe markdown: escape first, then re-enable a tiny subset
    // (bold, italics, inline code, line breaks). Never injects raw HTML.
    function mdLite(s) {
        var out = escapeHTML(s);
        out = out.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        out = out.replace(/(^|[^*])\*([^*\n]+)\*/g, '$1<em>$2</em>');
        out = out.replace(/`([^`]+)`/g, '<code>$1</code>');
        out = out.replace(/\n{2,}/g, '<br/><br/>').replace(/\n/g, '<br/>');
        return out;
    }

    // Seek the lesson video player to a given time (seconds). Supports HTML5
    // <video> and YouTube/Vimeo iframes (postMessage). Mirrors the transcript
    // panel's seek behaviour so AI references jump the actual video.
    window.LessonAISeek = function (seconds) {
        var t = parseFloat(seconds) || 0;
        var el = document.getElementById('videoPlayer');
        if (!el) { return; }
        var tag = (el.tagName || '').toLowerCase();
        if (tag === 'video') {
            try {
                el.currentTime = t;
                var p = el.play();
                if (p && typeof p.catch === 'function') { p.catch(function () {}); }
            } catch (e) {}
            return;
        }
        if (tag === 'iframe' && el.contentWindow) {
            var src = el.src || '';
            try {
                if (src.indexOf('youtube') !== -1) {
                    el.contentWindow.postMessage(JSON.stringify({
                        event: 'command', func: 'seekTo', args: [t, true]
                    }), '*');
                } else if (src.indexOf('vimeo') !== -1) {
                    el.contentWindow.postMessage(JSON.stringify({
                        method: 'setCurrentTime', value: t
                    }), '*');
                }
            } catch (e) {}
        }
    };

    function $panel() { return document.getElementById('lesson_action_result'); }

    // Switch the right side panel to a given tab ('assistant' | 'content').
    function switchTab(tab) {
        $('.ai-side-tab').removeClass('active');
        $('.ai-side-tab[data-tab="' + tab + '"]').addClass('active');
        $('#ai_side_assistant').toggleClass('ai-side-pane--hidden', tab !== 'assistant');
        $('#ai_side_content').toggleClass('ai-side-pane--hidden', tab !== 'content');
    }

    // The assistant now lives inline in the side panel; "opening" it simply
    // surfaces the AI Assistant tab. Kept named openModal so existing callers
    // (Summarize / Explain / follow-up) work unchanged.
    function openModal() { switchTab('assistant'); }
    function hideModal() {}

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

    // ---- Structured response builders ------------------------------------
    function listBlock(label, items, cls) {
        if (!items || !items.length) { return ''; }
        var lis = items.map(function (it) {
            return '<li>' + mdLite(it) + '</li>';
        }).join('');
        return '<div class="lesson-ai-block ' + cls + '">' +
                    '<div class="lesson-ai-block__title">' + escapeHTML(label) + '</div>' +
                    '<ul>' + lis + '</ul>' +
                '</div>';
    }

    function referencesBlock(refs) {
        if (!refs || !refs.length) { return ''; }
        var chips = refs.map(function (r) {
            var label = r.label || '';
            var quote = r.quote ? (' ' + r.quote) : '';
            return '<button type="button" class="lesson-ai-ref" data-seek="' + (parseFloat(r.timestamp) || 0) + '">' +
                        '<i class="fa fa-play-circle"></i>' +
                        '<span class="lesson-ai-ref__ts">' + escapeHTML(label) + '</span>' +
                        '<span class="lesson-ai-ref__q">' + escapeHTML(quote) + '</span>' +
                    '</button>';
        }).join('');
        return '<div class="lesson-ai-block lesson-ai-refs">' +
                    '<div class="lesson-ai-block__title"><i class="fa fa-clock-o"></i> Jump to in video</div>' +
                    '<div class="lesson-ai-ref-list">' + chips + '</div>' +
                '</div>';
    }

    function attachmentsBlock(atts) {
        if (!atts || !atts.length) { return ''; }
        var links = atts.map(function (a) {
            return '<a class="lesson-ai-att" href="' + escapeHTML(a.url) + '" target="_blank" rel="noopener">' +
                        '<i class="fa fa-paperclip"></i>' + escapeHTML(a.name) +
                    '</a>';
        }).join('');
        return '<div class="lesson-ai-block lesson-ai-atts">' +
                    '<div class="lesson-ai-block__title"><i class="fa fa-folder-open-o"></i> Lesson materials</div>' +
                    '<div class="lesson-ai-att-list">' + links + '</div>' +
                '</div>';
    }

    function suggestedBlock(qs) {
        if (!qs || !qs.length) { return ''; }
        var chips = qs.map(function (q) {
            return '<button type="button" class="lesson-ai-suggest" data-q="' + escapeHTML(q) + '">' +
                        escapeHTML(q) + '</button>';
        }).join('');
        return '<div class="lesson-ai-block lesson-ai-suggests">' +
                    '<div class="lesson-ai-block__title"><i class="fa fa-comments-o"></i> Ask next</div>' +
                    '<div class="lesson-ai-suggest-list">' + chips + '</div>' +
                '</div>';
    }

    // Render a single structured answer card (used by both summary + explain).
    function answerCard(result, headIcon, headLabel) {
        result = result || {};
        var title = result.title
            ? '<div class="lesson-ai-result__subtitle">' + escapeHTML(result.title) + '</div>'
            : '';
        return '<div class="lesson-ai-result">' +
                '<div class="lesson-ai-result__head">' +
                    '<i class="fa ' + headIcon + '"></i><span>' + escapeHTML(headLabel) + '</span>' +
                '</div>' +
                title +
                '<div class="lesson-ai-result__body">' + mdLite(result.answer || '') + '</div>' +
                listBlock('Key points', result.key_points, 'lesson-ai-keys') +
                listBlock('Takeaways', result.takeaways, 'lesson-ai-takeaways') +
                referencesBlock(result.references) +
                attachmentsBlock(result.attachments) +
            '</div>';
    }

    function renderSummary(result) {
        var el = $panel(); if (!el) return;
        el.innerHTML = answerCard(result, 'fa-align-left', 'Summary') +
                       suggestedBlock(result && result.suggested_questions);
    }

    function renderExplain() {
        var el = $panel(); if (!el) return;

        var turnsHtml = '';
        state.turns.forEach(function (turn) {
            var q = turn.q
                ? '<div class="lesson-ai-qa__q"><i class="fa fa-user-o"></i> ' + escapeHTML(turn.q) + '</div>'
                : '';
            turnsHtml += '<div class="lesson-ai-qa__item">' + q +
                            answerCard(turn.result, 'fa-lightbulb-o', turn.q ? 'Answer' : 'Explanation') +
                         '</div>';
        });

        var lastResult = state.turns.length ? state.turns[state.turns.length - 1].result : null;
        var suggests = suggestedBlock(lastResult && lastResult.suggested_questions);

        el.innerHTML =
            '<div class="lesson-ai-qa">' + turnsHtml + '</div>' +
            suggests +
            '<div class="lesson-ai-followup">' +
                '<label class="lesson-ai-followup__label" for="lesson_ai_followup_input">Ask a follow-up about this video</label>' +
                '<div class="lesson-ai-followup__row">' +
                    '<input type="text" id="lesson_ai_followup_input" placeholder="e.g. Can you give me an example?" maxlength="500" />' +
                    '<button type="button" id="lesson_ai_followup_btn">Ask</button>' +
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

    // Build conversation history (for follow-up grounding) from prior turns.
    function buildHistory() {
        var history = [];
        state.turns.forEach(function (turn) {
            if (turn.q) { history.push({ role: 'user', content: turn.q }); }
            if (turn.result && turn.result.answer) {
                history.push({ role: 'assistant', content: turn.result.answer });
            }
        });
        return history;
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

    // Normalise a server response into a structured result object so the UI
    // works whether the AI returned rich fields or just a plain answer.
    function toResult(data) {
        data = data || {};
        return {
            answer: typeof data.answer === 'string' ? data.answer : '',
            title: data.title || '',
            key_points: data.key_points || [],
            takeaways: data.takeaways || [],
            references: data.references || [],
            suggested_questions: data.suggested_questions || [],
            attachments: data.attachments || []
        };
    }

    function runSummarize(lessonId) {
        state.mode = 'summarize';
        state.lessonId = lessonId;
        state.turns = [];
        state.summary = null;
        openModal();
        renderLoading('Summarizing this video...');
        callApi({ action: 'summarize', lesson_id: lessonId, level: state.level })
            .then(function (resp) {
                if (resp.ok && resp.data && typeof resp.data.answer === 'string' && resp.data.answer !== '') {
                    state.summary = toResult(resp.data);
                    renderSummary(state.summary);
                    saveState();
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
        state.turns = [];
        openModal();
        renderLoading('Explaining this video...');
        callApi({ action: 'explain', lesson_id: lessonId, level: state.level })
            .then(function (resp) {
                if (resp.ok && resp.data && typeof resp.data.answer === 'string' && resp.data.answer !== '') {
                    state.turns.push({ q: '', result: toResult(resp.data) });
                    renderExplain();
                    saveState();
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
        if (state.mode !== 'explain') { state.mode = 'explain'; }
        var history = buildHistory();
        renderFollowupLoading();
        callApi({
            action: 'explain',
            lesson_id: state.lessonId,
            question: question,
            level: state.level,
            history: history
        })
            .then(function (resp) {
                if (resp.ok && resp.data && typeof resp.data.answer === 'string' && resp.data.answer !== '') {
                    state.turns.push({ q: question, result: toResult(resp.data) });
                    renderExplain();
                    saveState();
                } else {
                    var msg = (resp.data && resp.data.error) ? resp.data.error : 'Unable to answer that right now.';
                    state.turns.push({ q: question, result: { answer: '\u26A0\uFE0F ' + msg } });
                    renderExplain();
                    saveState();
                }
            })
            .catch(function (err) {
                state.turns.push({ q: question, result: { answer: '\u26A0\uFE0F ' + ((err && err.message) ? err.message : 'Network error.') } });
                renderExplain();
                saveState();
            });
    }

    // Hooks used by the page when a lesson loads / unloads.
    //  - onLesson: restore this lesson's saved conversation into the side panel
    //    and surface the AI Assistant tab (default view).
    //  - reset: clear the in-memory conversation (the saved copy in
    //    localStorage is kept, keyed per lesson, like Ask AI).
    window.LessonAI = {
        onLesson: function (lessonId) {
            var lid = parseInt(lessonId, 10);
            if (!lid) { window.LessonAI.reset(); return; }
            restoreState(lid);
            switchTab('assistant');
            renderRestored();
        },
        reset: function () {
            state = { mode: null, lessonId: null, level: 'standard', summary: null, turns: [] };
            clearPanel();
        }
    };

    // Tab switching (AI Assistant / Course Content).
    $(document).on('click', '.ai-side-tab', function () {
        switchTab(this.getAttribute('data-tab') || 'assistant');
    });

    $(document).on('click', '#lesson_summarize_btn', function () {
        var ctx = window.currentLessonContext || {};
        switchTab('assistant');
        if (!ctx.lessonId) {
            renderError('No lesson selected', 'Open a lesson video first, then click Summarize.');
            return;
        }
        runSummarize(parseInt(ctx.lessonId, 10));
    });

    $(document).on('click', '#lesson_explain_btn', function () {
        var ctx = window.currentLessonContext || {};
        switchTab('assistant');
        if (!ctx.lessonId) {
            renderError('No lesson selected', 'Open a lesson video first, then click Explain.');
            return;
        }
        runExplain(parseInt(ctx.lessonId, 10));
    });

    // Ask the AI to explain a specific caption line (the wand button on a
    // transcript row). Surfaces the AI tab and asks it as a follow-up.
    $(document).on('click', '.vtpanel-ai-ask', function (e) {
        e.stopPropagation();
        var text = (this.getAttribute('data-text') || '').trim();
        if (!text) { return; }
        var ctx = window.currentLessonContext || {};
        if (!ctx.lessonId) { return; }
        var lid = parseInt(ctx.lessonId, 10);
        if (state.lessonId !== lid) { restoreState(lid); }
        switchTab('assistant');
        askFollowup('Explain this part of the lesson: "' + text + '"');
    });

    // Click an AI reference chip → seek the lesson video to that moment.
    $(document).on('click', '.lesson-ai-ref', function () {
        var t = parseFloat(this.getAttribute('data-seek')) || 0;
        if (typeof window.LessonAISeek === 'function') { window.LessonAISeek(t); }
    });

    // Click a suggested question → ask it as a follow-up.
    $(document).on('click', '.lesson-ai-suggest', function () {
        var q = this.getAttribute('data-q') || '';
        if (q.trim() === '') { return; }
        askFollowup(q.trim());
    });

    // Difficulty / depth selector.
    $(document).on('change', '#lesson_ai_level', function () {
        state.level = this.value || 'standard';
        saveState();
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
