<?php $this->load->view('layout/course_css.php'); ?>
<section class="course-wrap">
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12">
			<div class="box-header ptbnull">
				<h3 class="box-title"><?php echo $this->lang->line('course_detail'); ?></h3>
				<?php if (!empty($coursesList)) {?>
				<?php if($this->rbac->hasPrivilege('online_course', 'can_delete')) {?> 
					<a href="#" class="btn btn-xs pull-right delete_course_id" data-id="<?php echo $courseID; ?>" data-placement="top" data-toggle="modal" data-original-title="<?php echo $this->lang->line('delete_course'); ?>"><i class="fa fa-remove"></i></a>
				<?php } ?>
				<?php if($this->rbac->hasPrivilege('online_course', 'can_edit')) {?> 
					<a href="#" data-placement="top" class="btn btn-xs pull-right edit_course_id" data-toggle="modal" data-id="<?php echo $courseID; ?>" data-target="#edit_course_modal" data-backdrop="static" data-keyboard="false" data-original-title="<?php echo $this->lang->line('edit_course'); ?>"><i class="fa fa-pencil"></i></a>
				<?php } ?>
				<?php } ?>
				
				<input type="hidden" value="<?php echo $courseID; ?>" id="courseid" name="courseid" />
			</div>
			<?php if (!empty($coursesList)) {
			?>
			<div class="scroll-area">
				<div class="box-body">
					<div class="form-group">
					<?php if (!empty($coursesList['course_thumbnail'])) {?>
						<img src="<?php echo base_url(); ?>uploads/course/course_thumbnail/<?php echo rawurlencode($coursesList['course_thumbnail']); ?>" class="img-responsive center-block">
					<?php }?>
					</div>
					<hr>
					<div class="form-group">
						<label><b><?php echo $this->lang->line('title'); ?></b></label>
						<br>
						<?php if (!empty($coursesList['title'])) {echo $coursesList['title'];}?>
					</div>
					<hr>
					<div class="form-group">
						<label><b><?php echo $this->lang->line('description'); ?></b></label>
						<br>
						<?php if (!empty($coursesList['description'])) {echo $coursesList['description'];}?>
					</div>
					<hr>
					<div class="form-group">
						<label><b><?php echo $this->lang->line('class'); ?></b></label>
						<br>
						<?php if (!empty($coursesList['class'])) {echo $coursesList['class'];}?>
					</div>
					<hr>
					<div class="form-group">
						<label><b><?php echo $this->lang->line('section'); ?></b></label>
						<br>
						<?php if (!empty($multipalsection)) {
							foreach ($multipalsection as $key => $multipalsection_value) {
								$key = 1 + $key;
								if ($key == count($multipalsection)) {
									echo $multipalsection_value['section'];
								} else {
									echo $multipalsection_value['section'] . ",";
								}
							}
						}?>
					</div>
					<hr>
					<div class="form-group">
						<label><b><?php echo $this->lang->line('assign_teacher'); ?></b></label>
						<br>
						<?php if (!empty($coursesList['staff_name'])) {echo $coursesList['staff_name'] . ' ' . $coursesList['staff_surname']. ' (' . $coursesList['assign_employee_id'].')';}?>
					</div>
					<hr>
					<div class="form-group">
						<label><b><?php echo $this->lang->line('created_by'); ?></b></label>
						<br>
						<?php 
						if($role->id == 7){
							if (!empty($coursesList['name'])) {echo $coursesList['name'] . ' ' . $coursesList['surname']. ' (' . $coursesList['employee_id'].')';}
						}else{
							if($superadmin_visible == 'disabled' && $coursesList['role_id'] == 7){
    							echo '';               
							}else{
							    if (!empty($coursesList['name'])) {echo $coursesList['name'] . ' ' . $coursesList['surname']. ' (' . $coursesList['employee_id'].')';}
							}
						}					
						?>
					</div>
					<?php
						if (!empty($coursesList["outcomes"])) {
							$json_array = json_decode($coursesList["outcomes"]);
							if (!empty($json_array[0])) {
								if (is_array($json_array)) {
					?>
					<hr>
					<div class="form-group">
						<label><b><?php echo $this->lang->line('outcomes'); ?></b></label>
						<ul class="getboxlist">
						<?php
							foreach ($json_array as $key => $jsonvalue) {
								echo "<li>" . $jsonvalue . "</li>";
							}
						?>
						</ul>
					</div>
					<?php }
					}
					}?>
				</div>
			<?php }?>
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12">
			<div class="box-header ptbnull">
				<h3 class="box-title titlefix"><?php echo $this->lang->line('section'); ?> & <?php echo $this->lang->line('lesson'); ?></h3>
				<div class="box-tools pull-right">
				
				<?php if($this->rbac->hasPrivilege('online_course_section', 'can_add')) {?> 
					<button type="button" class="btn btn-sm btn-primary add_section_id mb5" data-toggle="modal" data-backdrop="static" data-keyboard="false" data-id="<?php echo $courseID; ?>" data-target="#add_section_modal"><i class="fa fa-plus"></i> <?php echo $this->lang->line('add_section'); ?> </button>
				<?php } ?>
				<?php if($this->rbac->hasPrivilege('online_course_section', 'can_edit')) {?>
					<button type="button" class="btn btn-sm btn-primary order_section_id mb5" data-toggle="modal" data-backdrop="static" data-keyboard="false" data-id="<?php echo $courseID; ?>" data-target="#order_section_modal"><i class="fa fa-first-order"></i> <?php echo $this->lang->line('order_section'); ?> </button>
				<?php } ?>
				<?php if($this->rbac->hasPrivilege('course_publish', 'can_view')) {?>
					<?php if($coursesList['status'] == 0){ ?>
					<button type="button" onclick="publish_unpublish('<?php echo $courseID; ?>','1','<?php echo $coursesList['title'] ?>')" class="btn btn-sm btn-publishgreen mb5"><i class="fa fa-thumbs-o-up"></i> <?php echo $this->lang->line('publish'); ?></button>
					<?php }elseif($coursesList['status'] == 1){ ?>
					<button type="button" onclick="publish_unpublish('<?php echo $courseID; ?>','0','<?php echo $coursesList['title'] ?>')" class="btn btn-sm btn-primary btn-danger mb5"><i class="fa fa-thumbs-o-down"></i> <?php echo $this->lang->line('unpublish'); ?> </button>
					<?php } ?>
				<?php } ?>
				</div>
			</div>
			<?php if($this->rbac->hasPrivilege('online_course_section', 'can_view')) {?> 
			<div class="scroll-area">
				<div class="box-body">
					<div id="modal">
						<img id="loader" src="<?php echo base_url() ?>/backend/images/loading_blue.gif" />
					</div>
					<?php
						if (!empty($sectionList)) {
					?>
					<div id="accordion" class="panel-group">
					<?php $lessoncount=0; $quizcount=0; $sectioncount=0;$assignment=0;$exam_count=0;
						foreach ($sectionList as $sectionList_key => $sectionList_value) {
						$sectioncount = $sectioncount+1;
					?>
						<div class="panel panel-default">
							<div class="panel-heading">
								<h4 class="panel-title display-inline-block">
								<?php $sectionID = $sectionList_value->id;?>
								<a class="collapsed get_section_id panel_btnarrow" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $sectionList_key ?>" data-id="<?php echo $sectionID; ?>" aria-expanded="false" aria-controls="collapse<?php echo $sectionList_key ?>">
								<i class="fa fa-angle-down"></i> <?php echo '<b>'.$this->lang->line('section').' '. $sectioncount.'</b>: '. $sectionList_value->section_title; ?></a></h4>
								
								<div class="display-inline-block pull-right pt3 ">
								<?php if($this->rbac->hasPrivilege('online_course_section', 'can_delete')) {?>
									<a href="#" class="btn btn-xs pull-right delete_section_id" data-toggle="modal" data-id="<?php echo $sectionID; ?>" course-data-id="<?php echo $courseID; ?>" data-original-title="<?php echo $this->lang->line('delete_section'); ?>"><i class="fa fa-remove"></i></a>
								<?php } ?>
								
								<?php if($this->rbac->hasPrivilege('online_course_section', 'can_edit')) {?>
									<a href="#" class="btn btn-xs pull-right edit_section_id" data-toggle="modal" data-id="<?php echo $sectionID; ?>" data-target="#edit_section_modal" data-backdrop="static" data-keyboard="false" data-original-title="<?php echo $this->lang->line('edit_section'); ?>"><i class="fa fa-pencil"></i></a>
								<?php } ?>
								
								<?php if($this->rbac->hasPrivilege('online_course_assignment', 'can_add')) { ?>
									<a href="#" class="btn btn-xs pull-right add_assignment_id <?php echo $this->customlib->get_online_course_curriculam_status("online_course_assignment");?>" course-data-id="<?php echo $courseID; ?>" data-toggle="modal" data-id="<?php echo $sectionID; ?>" data-backdrop="static" data-keyboard="false" data-target="#add_assignment_modal" data-original-title="<?php echo $this->lang->line("add_assignment"); ?>"><i class="fa fa-flask ftlayer"></i></a>
								<?php } ?>
								
								<?php if($this->rbac->hasPrivilege('online_course_exam', 'can_add')) { ?>
									<a href="#" class="btn btn-xs pull-right add_online_exam_id  <?php echo $this->customlib->get_online_course_curriculam_status("online_course_exam");?>" course-data-id="<?php echo $courseID; ?>" data-toggle="modal" data-id="<?php echo $sectionID; ?>" data-backdrop="static" data-keyboard="false" data-target="#add_exam_modal" data-original-title="<?php echo $this->lang->line("add_exam"); ?>"><i class="fa fa-rss ftlayer"></i></a>
								<?php } ?>
								
								<?php if($this->rbac->hasPrivilege('online_course_quiz', 'can_add')) { ?>
									<a href="#" class="btn btn-xs pull-right add_quiz_id  <?php echo $this->customlib->get_online_course_curriculam_status("online_course_quiz");?>" data-toggle="modal" course-data-id="<?php echo $courseID; ?>" data-id="<?php echo $sectionID; ?>" data-backdrop="static" data-keyboard="false" data-target="#add_quiz_modal" data-original-title="<?php echo $this->lang->line('add_quiz'); ?>"><i class="fa fa-question-circle"></i></a>
								<?php } ?>

								<?php if($this->rbac->hasPrivilege('online_course_lesson', 'can_add')) { ?>
                                    <a href="#" class="btn btn-xs pull-right add_lesson_id " course-data-id="<?php echo $courseID; ?>" data-toggle="modal" data-id="<?php echo $sectionID; ?>" data-backdrop="static" data-keyboard="false" data-target="#add_lesson_modal" data-original-title="<?php echo $this->lang->line('add_lesson'); ?>"><i class="fa fa-plus"></i></a> 
								<?php } ?>
								</div>
							</div>
							<div id="collapse<?php echo $sectionList_key; ?>" class="panel-collapse collapse in">
								<div class="panel-body">
									<ul class="sortable-item ui-sortable list-group mb0" data-record_name="<?php echo $sectionList_key; ?>">
									<?php 
									if(!empty($lessonquizdetail[$sectionID])){ 
									foreach ($lessonquizdetail[$sectionID] as $lessonquizdetail_key => $lessonquizdetail_value){ 
									if($lessonquizdetail_value['type'] == 'lesson'){ 
									$lessoncount = $lessoncount+1;
									?>
									<?php if($this->rbac->hasPrivilege('online_course_lesson', 'can_view')) {?>
										<li id="<?php echo $lessonquizdetail_value['lesson_id']; ?>" class="list-group-item-sort text-left ">
											<i class="fa fa-play-circle"></i>
											<span class="sort-action">											
											<?php if($this->rbac->hasPrivilege('online_course_lesson', 'can_delete')) {?>
												<a href="#" class="btn btn-xs pull-right delete_lesson_id" data-lesson-id="<?php echo $lessonquizdetail_value['lesson_id']; ?>" course-data-id="<?php echo $courseID; ?>" data-toggle="tooltip"data-original-title="<?php echo $this->lang->line('delete_lesson'); ?>"><i class="fa fa-remove"></i></a>
											<?php } ?>
											<?php if($this->rbac->hasPrivilege('online_course_lesson', 'can_edit')) {?>
												<a href="#" class="btn btn-xs pull-right edit_lesson_id" data-toggle="modal" course-data-id="<?php echo $courseID; ?>"  data-section-id="<?php echo $sectionID; ?>" data-lesson-id="<?php echo $lessonquizdetail_value['lesson_id']; ?>" data-backdrop="static" data-keyboard="false" data-target="#edit_lesson_modal" data-original-title="<?php echo $this->lang->line('edit_lesson'); ?>"><i class="fa fa-pencil"></i></a>						
											<?php } ?>
											</span>
											<?php echo "<b>".$this->lang->line($lessonquizdetail_value['type'])." ".$lessoncount.": "."</b>".$lessonquizdetail_value['lesson_title']; ?>
											<?php 
												if ($lessonquizdetail_value['lesson_type'] != 'video') {?>												

												<a href="#" class="btn btn-xs pull-right download_attachment"  data-toggle="modal" course-data-id="<?php echo $courseID; ?>"  data-section-id="<?php echo $sectionID; ?>" data-lesson-id="<?php echo $lessonquizdetail_value['lesson_id']; ?>" data-backdrop="static" data-keyboard="false" data-target="#download_attachment_modal" data-original-title="<?php echo $this->lang->line('download'); ?>"><i class="fa fa-download"></i></a>	

											<?php } ?>

											<?php if ($lessonquizdetail_value['lesson_type'] == 'video') { ?>
											<span class="pull-right">
												<i class="fa fa-clock-o"></i>
												<?php echo $lessonquizdetail_value['duration']; ?>
											</span>
											<hr>
											<?php if ($lessonquizdetail_value['video_provider'] == 'html5') {?>
											<div class="row">
												<video id="" controls >
													<source src="<?php echo $lessonquizdetail_value['video_url']; ?>" type="video/mp4">
												</video>
												<span class="pull-right"> <?php echo $lessonquizdetail_value['summary']; ?></span>
											</div>
											<?php
											} elseif ($lessonquizdetail_value['video_provider'] == 'youtube') {?>
											<div class="row">
												<div class="col-lg-4 col-md-5 col-sm-12">
													<iframe width="100%" height="200" src="//www.youtube.com/embed/<?php echo $lessonquizdetail_value['video_id']; ?>"></iframe>
												</div>
												<div class="col-lg-8 col-md-7 col-sm-12">
													<span><?php echo $lessonquizdetail_value['summary']; ?></span>
												</div>
											</div>
											<?php
											} elseif ($lessonquizdetail_value['video_provider'] == 'vimeo') {?>
											<div class="row">                                          
												<div class="col-lg-4 col-md-5 col-sm-12">
													<iframe src="https://player.vimeo.com/video/<?php echo $lessonquizdetail_value['video_id']; ?>" width="100%" height="200" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
												</div>
												<div class="col-lg-8 col-md-7 col-sm-12">
													<span><?php echo $lessonquizdetail_value['summary']; ?></span>
												</div>
											</div>
											<?php
											} elseif ($lessonquizdetail_value['video_provider'] == 's3_bucket') {?>
											<div class="row">
												<div class="col-lg-4 col-md-5 col-sm-12">
													<video controls width="100%" height="200">
														<source src="<?php echo $this->aws3->generateUrl($lessonquizdetail_value['video_id']); ?>">
													</video>
												</div>
												<div class="col-lg-8 col-md-7 col-sm-12">
													<span><?php echo $lessonquizdetail_value['summary']; ?></span>
												</div>
											</div>
											<?php }
											}else{ ?>
											<div class="row"><hr>
												<div class="col-lg-4 col-md-5 col-sm-12">

													<img src="<?php echo base_url(); ?>uploads/course_content/<?php echo $sectionID; ?>/<?php echo $lessonquizdetail_value['lesson_id']; ?>/<?php echo $lessonquizdetail_value['thumbnail']; ?>" width="100%" height="200" class="img-responsive">
												</div>
												<div class="col-lg-8 col-md-7 col-sm-12">
													<span><?php echo $lessonquizdetail_value['summary']; ?></span>
												</div>
											</div>
											<?php } ?> 
										</li>
									<?php } } ?>									
									
									<?php if($this->rbac->hasPrivilege('online_course_quiz', 'can_view')) { ?>
									<?php if($lessonquizdetail_value['type'] == 'quiz'){ 	
											$quizcount = $quizcount+1; ?>								
									<div id="accordionn" class="panel-group  <?php echo $this->customlib->get_online_course_curriculam_status("online_course_quiz");?>">
										<div class="panel panel-default">

										<div class="panel-heading">
										<h4 class="panel-title display-inline-block plusblock">
											<a class="collapsed more-less pt5" role="button" data-toggle="collapse" data-parent="#accordionn" href="#<?php echo $sectionList_key ?>_<?php echo $lessonquizdetail_key; ?>" aria-expanded="false" aria-controls="collapse50"><i class="fa fa-plus"></i><?php echo "<b>".$this->lang->line($lessonquizdetail_value['type'])." ".$quizcount.": "."</b>". $lessonquizdetail_value['quiz_title']; ?></a>
										</h4>
											<div class="display-inline-block pull-right pt3">
											
												<?php if($this->rbac->hasPrivilege('online_course_quiz', 'can_delete')) {?>
													<a href="#" class="btn btn-xs pull-right delete_quiz_id" data-quiz-id="<?php echo $lessonquizdetail_value['quiz_id']; ?>" course-data-id="<?php echo $courseID; ?>" data-toggle="tooltip"data-original-title="<?php echo $this->lang->line('delete_quiz'); ?>"><i class="fa fa-remove"></i></a>
												<?php } ?>

												<?php if($this->rbac->hasPrivilege('online_course_quiz', 'can_edit')) {?>
													<a href="#" class="btn btn-xs pull-right edit_quiz_id" data-toggle="modal" data-quiz-id="<?php echo $lessonquizdetail_value['quiz_id']; ?>" data-backdrop="static" data-keyboard="false" course-data-id="<?php echo $courseID; ?>" data-target="#edit_quiz_modal" data-original-title="<?php echo $this->lang->line('edit_quiz'); ?>"><i class="fa fa-pencil"></i></a>
												<?php } ?>

												<?php if (!empty($quizquestiondetail[$lessonquizdetail_value['quiz_id']])) {?>
												<?php if($this->rbac->hasPrivilege('online_course_quiz', 'can_edit') || $this->rbac->hasPrivilege('online_course_quiz', 'can_add')) {?>
													<a href="#" class="btn btn-xs pull-right edit_question_id" data-toggle="modal" data-quiz-id="<?php echo $lessonquizdetail_value['quiz_id']; ?>" data-quiz-question-count="<?php echo count($quizquestiondetail[$lessonquizdetail_value['quiz_id']]); ?>" data-backdrop="static" course-data-id="<?php echo $courseID; ?>" data-keyboard="false" data-target="#edit_question_modal" data-original-title="<?php echo $this->lang->line('quiz_questions'); ?>"><i class="fa fa-plus"></i></a>
												<?php } ?>
												<?php }else{?>
												<?php if($this->rbac->hasPrivilege('online_course_quiz', 'can_add')) {?>
													<a href="#" class="btn btn-xs pull-right question_id" data-toggle="modal" data-quiz-id="<?php echo $lessonquizdetail_value['quiz_id']; ?>" data-backdrop="static" data-keyboard="false" course-data-id="<?php echo $courseID; ?>"  data-target="#question_modal" data-original-title="<?php echo $this->lang->line('quiz_questions'); ?>"><i class="fa fa-plus"></i></a>
												<?php } ?>
												<?php } ?>
											</div>
										</div>

										<div id="<?php echo $sectionList_key ?>_<?php echo $lessonquizdetail_key; ?>" class="panel-collapse collapse">
											<div class="panel-body">
												<ul class="sortable-item ui-sortable list-group mb0" data-record_name="5">
												<?php $count = '';

												if (!empty($quizquestiondetail[$lessonquizdetail_value['quiz_id']])) {
													foreach ($quizquestiondetail[$lessonquizdetail_value['quiz_id']] as $quizquestiondetail_key => $quizquestiondetail_value) {
													$count++;
												?>
													<li id="" class="list-group-item-sort text-left">
														<b><?php echo $count . '. ' . $quizquestiondetail_value['question']; ?></b>
														<hr>
														<?php if (!empty($quizquestiondetail_value['option_1'])) {?>
														
														<?php if (strpos($quizquestiondetail_value['correct_answer'], 'option_1') !== false) {$class = "text-green-dark";} else { $class = "";}?>														<div class="<?php echo $class; ?>">
															<span><?php echo $this->lang->line('option'); ?> 1 : </span>
														<?php echo $quizquestiondetail_value['option_1']; ?>
														</div>						
														
														<?php }if (!empty($quizquestiondetail_value['option_2'])) {?>  
														   <?php if (strpos($quizquestiondetail_value['correct_answer'], 'option_2') !== false) {$class = "text-green-dark";} else { $class = "";}?>
														<div class="<?php echo $class; ?>">
															<span><?php echo $this->lang->line('option'); ?> 2 : </span>
														<?php echo $quizquestiondetail_value['option_2']; ?>
														</div>
														<?php }if (!empty($quizquestiondetail_value['option_3'])) {?>  <?php if (strpos($quizquestiondetail_value['correct_answer'], 'option_3') !== false) {$class = "text-green-dark";} else { $class = "";}?>
														<div class="<?php echo $class; ?>">
															<span><?php echo $this->lang->line('option'); ?> 3 : </span>
														<?php echo $quizquestiondetail_value['option_3']; ?>
														</div>
														<?php }if (!empty($quizquestiondetail_value['option_4'])) {?> <?php if (strpos($quizquestiondetail_value['correct_answer'], 'option_4') !== false) {$class = "text-green-dark";} else { $class = "";}?>
														<div class="<?php echo $class; ?>">
															<span><?php echo $this->lang->line('option'); ?> 4 : </span>
														<?php echo $quizquestiondetail_value['option_4']; ?>
														</div>
														<?php }if (!empty($quizquestiondetail_value['option_5'])) {?>
														<?php if (strpos($quizquestiondetail_value['correct_answer'], 'option_5') !== false) {$class = "text-green-dark";} else { $class = "";}?>
														
														<div class="<?php echo $class; ?>">
															<span><?php echo $this->lang->line('option'); ?> 5 : </span>
														<?php echo $quizquestiondetail_value['option_5']; ?>
														</div>
														<?php }?>
													</li>
												<?php }} ?>                         
												</ul>
											</div>
										</div>
										</div>
									</div>  
									<?php } } ?>

									<?php if($this->rbac->hasPrivilege('online_course_assignment', 'can_view')) {
										if($lessonquizdetail_value['type'] == 'assignment'){ 	
									$assignment = $assignment+1; ?>						 
										<ul class="sortable-item ui-sortable list-group mb0  <?php echo $this->customlib->get_online_course_curriculam_status("online_course_assignment");?>" data-record_name="5">
										<?php $count = '';
											if (!empty($lessonquizdetail_value['description'])) {
										?>
											<li id="" class="list-group-item-sort text-left">
												<i class="fa fa-play-circle"></i>
													<span class="sort-action">
														<?php if($this->rbac->hasPrivilege('online_course_assignment', 'can_delete')) { ?>
															<a href="#" class="btn btn-xs pull-right delete_assignment_id" data-assigment-id="<?php echo $lessonquizdetail_value['course_assignment_id']; ?>" course-data-id="<?php echo $courseID; ?>" data-toggle="tooltip"data-original-title="<?php echo $this->lang->line('delete_assignment'); ?>"><i class="fa fa-remove"></i></a>									
														<?php } ?>
														<?php if($this->rbac->hasPrivilege('online_course_assignment', 'can_edit')) { ?>
															<a href="#" class="btn btn-xs pull-right edit_assignment_id" course-data-id="<?php echo $courseID; ?>" data-toggle="modal" data-id="<?php echo $sectionID; ?>" data-backdrop="static" data-keyboard="false" data-target="#add_assignment_modal" data-original-title="<?php echo $this->lang->line("edit_assignment") ?>"  data-assigment-id="<?php echo $lessonquizdetail_value['course_assignment_id']; ?>" ><i class="fa fa-pencil"></i></a> &nbsp;
														<?php } ?>
														<?php if($this->rbac->hasPrivilege('online_course_evalute_assignment', 'can_view')) { ?>
															<a href="#" class="btn btn-xs pull-right evalute_assignment" onclick="evaluation(<?php echo $lessonquizdetail_value['course_assignment_id']; ?>)" course-data-id="<?php echo $courseID; ?>" data-toggle="modal" data-id="<?php echo $sectionID; ?>" data-backdrop="static" data-keyboard="false" data-target="#evalute_assignment_modal" data-original-title="<?php echo $this->lang->line("evalute_assignment"); ?>"  data-assigment-id="<?php echo $lessonquizdetail_value['course_assignment_id']; ?>" ><i class="fa fa-newspaper-o"></i></a>
														<?php } ?>
													</span>
													<b><?php echo $this->lang->line('assignment'); ?> <?php echo $assignment." : ".$lessonquizdetail_value['assignment_title'];?></b> <hr>													
													<div class="row">
														<div class="col-lg-11 col-md-11 col-sm-11">
															 <b><?php echo $this->lang->line('assignment_date');?></b>: <?php echo date($this->customlib->getSchoolDateFormat(),strtotime($lessonquizdetail_value['assignment_date'])); ?></br>
															 <b><?php echo $this->lang->line('submission_date');?></b>: <?php if($lessonquizdetail_value['submit_date']){echo date($this->customlib->getSchoolDateFormat(),strtotime($lessonquizdetail_value['submit_date'])); } ?></br>
															 <b><?php echo $this->lang->line('max_marks');?></b>: <?php echo $lessonquizdetail_value['marks']; ?></br>
															</br>											 
														</div>
														<div class="col-lg-1 col-md-1 col-sm-1">	
														<?php
														 if($lessonquizdetail_value['document']!=null){ ?>
															<a href="<?php echo base_url() . "onlinecourse/courseassignment/download_assignment/" . $lessonquizdetail_value['course_assignment_id'] ?>" class="btn btn-xs pull-right" data-toggle="tooltip" title="<?php echo $this->lang->line('download'); ?>"><i class="fa fa-download"></i></a> 
														<?php } ?>										 
														</div>
													</div>										 
													</li>
												<?php } ?>                         
												</ul>
									<?php } } ?>
									<?php  if($this->rbac->hasPrivilege('online_course_exam', 'can_view')) {
										if($lessonquizdetail_value['type'] == 'exam'){ 
										$exam_count = $exam_count + 1;
									  ?>						 
										<ul class="sortable-item ui-sortable list-group mb0  <?php echo $this->customlib->get_online_course_curriculam_status("online_course_exam");?>" data-record_name="5">
											<li id="" class="list-group-item-sort text-left">
												<i class="fa fa-play-circle"></i>
													<span class="sort-action">	
														<?php if($this->rbac->hasPrivilege('online_course_exam', 'can_delete')) { ?>
															<a href="#" class="btn btn-xs pull-right delete_exam_id" data-exam-id="<?php echo $lessonquizdetail_value['course_exam_id']; ?>" course-data-id="<?php echo $courseID; ?>" data-toggle="tooltip" data-original-title="<?php echo $this->lang->line("delete_exam"); ?>"><i class="fa fa-remove"></i></a>
														<?php } ?>
														<?php if($this->rbac->hasPrivilege('online_course_exam', 'can_edit')) { ?>
															<a href="#" class="btn btn-xs pull-right edit_exam_id" course-data-id="<?php echo $courseID; ?>" data-toggle="modal" data-id="<?php echo $sectionID; ?>" data-backdrop="static" data-keyboard="false" data-target="#add_exam_modal" data-original-title="<?php echo $this->lang->line("edit_exam"); ?>"  data-exam-id="<?php echo $lessonquizdetail_value['course_exam_id']; ?>" ><i class="fa fa-pencil"></i></a>
														<?php } ?>
														<?php if($this->rbac->hasPrivilege('online_course_add_questions_in_exam', 'can_view')) { ?>
															<a href="#" class="btn btn-xs pull-right" course-data-id="<?php echo $courseID; ?>" data-toggle="modal" data-is_quiz="<?php echo $lessonquizdetail_value['is_quiz']; ?>" data-backdrop="static" data-keyboard="false" data-target="#assign_question_modal" data-original-title="<?php echo $this->lang->line("add_question"); ?>" data-exam-id="<?php echo $lessonquizdetail_value['course_exam_id']; ?>" ><i class="fa fa-question-circle"></i></a> &nbsp;						
															<a href="#" class="btn btn-xs pull-right exam_ques_list" course-data-id="<?php echo $courseID; ?>" data-toggle="modal"  data-backdrop="static" data-keyboard="false" data-target="#exam_question_list" data-original-title="<?php echo $this->lang->line("exam_questions_list"); ?>" data-exam-id="<?php echo $lessonquizdetail_value['course_exam_id']; ?>" ><i class="fa fa-file"></i></a> &nbsp;
															<?php if($lessonquizdetail_value['is_active']==1){ ?>
															<a href="<?php echo site_url('onlinecourse/courseexam/evalution/'.$lessonquizdetail_value['course_exam_id']); ?>" class="btn btn-xs pull-right" target="_blank"  data-toggle="tooltip" course-data-id="<?php echo $courseID; ?>"  data-original-title="<?php echo $this->lang->line("exam_evaluation"); ?>" data-exam-id="<?php echo $lessonquizdetail_value['course_exam_id']; ?>" ><i class="fa fa-newspaper-o"></i></a> &nbsp;
															<?php } ?>
														<?php } ?>
													</span>
													<b><?php echo $this->lang->line("exam"); ?> <?php echo $exam_count ;?></b>: <?php echo $lessonquizdetail_value['course_exam_name']; ?><hr>
													<div class="row">
														<div class="col-lg-12 col-md-12 col-sm-12">
															<b><?php echo $this->lang->line("exam_from"); ?></b>: <?php echo $this->customlib->dateyyyymmddToDateTimeformat($lessonquizdetail_value['exam_from']); ?></br>
															<b><?php echo $this->lang->line("exam_to"); ?></b>: <?php echo $this->customlib->dateyyyymmddToDateTimeformat($lessonquizdetail_value['exam_to']); ?></br>
															<b><?php echo $this->lang->line("exam_duration"); ?></b>: <?php echo $lessonquizdetail_value['exam_duration']; ?></br>
															<b><?php echo $this->lang->line("passing_percentage"); ?></b>: <?php echo $lessonquizdetail_value['passing_percentage']; ?></br>
														</div>														
													</div>									 
													</li>											                      
												</ul>
									<?php } } ?>
									<?php } ?>					
								<?php
							} ?>    
								</ul>
							</div>           
						</div>
					</div>
					<?php }?>
				</div>
				<?php }?>
			</div>
		</div>
		<?php } ?>
	</div>
</div>

</section>
<script type="text/javascript">
$(document).ready(function() {
    $(".collapse.in").each(function() {
        $(this)
        .siblings(".panel-heading")
        .find(".fa-angle-down")
        .addClass("rotate");
    });

$(".collapse")
.on("show.bs.collapse", function() {
$(this)
.parent()
.find(".fa-angle-down")
.addClass("rotate");
})
.on("hide.bs.collapse", function() {
$(this)
.parent()
.find(".fa-angle-down")
.removeClass("rotate");
});
});
</script>

<script>
(function ($) {
"use strict";

	$('.add_section_id').click(function(){
		var courseID = $(this).attr('data-id');
		$('#course_id').val(courseID);
	});

	$('.add_lesson_id').click(function(){
		var courseID = $(this).attr('course-data-id');
		var sectionID = $(this).attr('data-id');
		$('#lesson_course_id').val(courseID);
		$('#add_lesson_section_id').val(sectionID);
		$(".dropify-clear").trigger("click");
	});

	$('.edit_section_id').click(function(){
		var sectionID = $(this).attr('data-id');
		$.ajax({
			url: '<?php echo base_url(); ?>onlinecourse/coursesection/getsinglesection',
			type: 'post',
			data: {sectionID:sectionID},
			dataType: 'json',
			success: function(response){
				var decode = JSON.stringify(response);
				var result = JSON.parse(decode);
				$('#edit_title').val(result.section_title);
				$('#edit_sectionID').val(result.id);
				$('#online_course_id').val(result.online_course_id);
			},
		});
	})

	$('.delete_section_id').click(function(){
		var courseid = $(this).attr('course-data-id');
		var sectionID = $(this).attr('data-id');
		var section_delete_msg = "<?php echo $this->lang->line('section_delete_msg'); ?>";
		var confirmationBox = confirm(section_delete_msg);
		if (confirmationBox == true) {
			$.ajax({
				url: '<?php echo base_url(); ?>onlinecourse/coursesection/deletesection',
				type: 'post',
				data: {sectionID:sectionID},
				dataType: 'json',
				success: function(response){
					if (response.status == "success") {
						successMsg(response.message);
						coursedetail(courseid);
					}else{
						successMsg(response.error);
					}
				}
			});
		}
	})

	$('.edit_lesson_id').click(function(){
		var courseID = $(this).attr('course-data-id');
		var lessonID = $(this).attr('data-lesson-id');
		$.ajax({
			url: '<?php echo base_url(); ?>onlinecourse/courselesson/singlelessondetail',
			type: 'post',
			data: {courseID:courseID,lessonID:lessonID},
			dataType: 'json',
			success: function(response){
				var decode = JSON.stringify(response);
				var result = JSON.parse(decode);
				$('#edit_lesson_course_id').val(courseID);
				$('#lesson_titleID').val(result.lesson_title);
				$('#lessons_summaryID').val(result.summary);
				$('#lesson_section_id').val(result.course_section_id);
				$('#lessons_id').val(result.id);
				$('#lesson_urlID').val(result.video_url);
				$('#lesson_durationID').val(result.duration);
				$('#lesson_selectedID').val(result.lesson_type);
				$('#lesson_old_img_id').val(result.thumbnail);
				$('#old_attachment_img_id').val(result.attachment);
				$('#course_id').val(result.course_id);
				geteditcontent(result.lesson_type); 
				$('#lesson_provider_edit').val(result.video_provider);
				if (typeof window.refreshLessonSubtitleStatus === 'function') {
					window.refreshLessonSubtitleStatus(result.id);
				}
			}
		});
	})

	$('.delete_lesson_id').click(function(){
		var courseid = $(this).attr('course-data-id');
		var lessonID = $(this).attr('data-lesson-id');
		var lesson_delete_msg = "<?php echo $this->lang->line('lesson_delete_msg'); ?>";
		var confirmationBox = confirm(lesson_delete_msg);
		if (confirmationBox == true) {
			$.ajax({
				url: '<?php echo base_url(); ?>onlinecourse/courselesson/deletelesson',
				type: 'post',
				data: {lessonID:lessonID},
				dataType: 'json',
				success: function(response){
					if (response.status == "success") {
						successMsg(response.message);
						coursedetail(courseid);
					}else{
						successMsg(response.error);
					}
				}
			});
		}
	})

	$('.delete_course_id').click(function(){
		var courseID = $(this).attr('data-id');
		var course_delete_msg = "<?php echo $this->lang->line('course_delete_msg'); ?>";
		var confirmationBox = confirm(course_delete_msg);
		if (confirmationBox == true) {
			$.ajax({
				url: '<?php echo base_url(); ?>onlinecourse/course/deletecourse',
				type: 'post',
				data: {courseID:courseID},
				dataType: 'json',
				success: function(response){
					if (response.status == "success") {
						successMsg(response.message);
						location.replace("<?php echo base_url(); ?>onlinecourse/course");
					}else{
						successMsg(response.error);
					}
				}
			});
		}
	})

	$('.add_assignment_id').click(function(){
		$("#add_assignment")[0].reset(); //reset form
		var sectionID = $(this).attr('data-id');
		var courseid = $(this).attr('course-data-id');
		$('#assignment_section_id').val(sectionID);
		$('#assignment_course_id').val(courseid);
		$('#edit_assignment_id').val(0);
		$('#heading').text("<?php echo $this->lang->line('add_assignment'); ?>");
	});
	
	$('.add_online_exam_id').click(function(){
		$("#add_course_exam")[0].reset(); //reset form
		var sectionID = $(this).attr('data-id');
		var courseid = $(this).attr('course-data-id');
		$('#exam_section_id').val(sectionID);
		$('#exam_course_id').val(courseid);
		$('#exam_id').val(0);
		$('#exam_heading').text("<?php echo $this->lang->line('add_exam'); ?>");
	});
	
	$('.add_quiz_id').click(function(){
		var courseid = $(this).attr('course-data-id');
		var sectionID = $(this).attr('data-id');
		$('#sectionId').val(sectionID);
		$('#quiz_courseid').val(courseid);
	});

	$('.edit_quiz_id').click(function(){
		var quizID = $(this).attr('data-quiz-id');
		var courseID = $(this).attr('course-data-id');
		$.ajax({
			url: '<?php echo base_url(); ?>onlinecourse/coursequiz/singlequizlist',
			type: 'post',
			data: {quizID:quizID},
			dataType: 'json',
			success: function(response){
				var decode = JSON.stringify(response);
				var result = JSON.parse(decode);
				$('#edit_quiz_course').val(courseID);
				$('#edit_sectionId').val(result.course_section_id);
				$('#edit_quiz_title').val(result.quiz_title);
				$('#edit_quiz_instruction').val(result.quiz_instruction);
				$('#quizId').val(result.id);
			}
		});
	})

	$('.delete_quiz_id').click(function(){
		var courseid = $(this).attr('course-data-id');
		var quizID = $(this).attr('data-quiz-id');
		var quiz_delete_msg = "<?php echo $this->lang->line('quiz_delete_msg'); ?>";
		var confirmationBox = confirm(quiz_delete_msg);
		if (confirmationBox == true) {
			$.ajax({
				url: '<?php echo base_url(); ?>onlinecourse/coursequiz/delete',
				type: 'post',
				data: {quizID:quizID},
				dataType: 'json',
				success: function(response){
					if (response.status == "success") {
						successMsg(response.message);
						coursedetail(courseid);
					}else{
						successMsg(response.error);
					}
				}
			});
		}
	})

	$('.question_id').click(function(){
		var quiz_id = $(this).attr('data-quiz-id');
		var courseid = $(this).attr('course-data-id');
		$('#quiz_id').val(quiz_id);
		$('#question_course_id').val(courseid);
	});

	$('.edit_question_id').click(function(){
		var quiz_id = $(this).attr('data-quiz-id');
		var courseid = $(this).attr('course-data-id');
		var questioncount = $(this).attr('data-quiz-question-count');
		$('#total_edit_question').html(questioncount);
		$('#editquestion_course_id').val(courseid);
		$.ajax({
			url: '<?php echo base_url(); ?>onlinecourse/coursequiz/getquestion',
			type: 'post',
			data: {quiz_id:quiz_id},
			beforeSend: function () {
				$('#edit_question').html('Loading...  <i class="fa fa-spinner fa-spin"></i>');
			},
			success: function(response){
				$('#edit_question').html(response);
			}
		});
	})

	$('.edit_course_id').click(function(){
		$('#edit_course').html('');
		var courseID = $(this).attr('data-id');
		$.ajax({
			url  : "<?php echo base_url(); ?>onlinecourse/course/editCourse",
			type : 'post',
			data : {courseID:courseID},
			beforeSend: function () {
				$('#edit_course').html('Loading...  <i class="fa fa-spinner fa-spin"></i>');
			},
			success : function(response){
				$('#edit_course').html(response);
			}, 
		});
	});

$('.order_section_id').click(function(){
	$('#order_section').html('');
	var courseid = $(this).attr('data-id');
	$.ajax({
		url  : "<?php echo base_url(); ?>onlinecourse/course/ordersection",
		type : 'post',
		data : {courseid:courseid},
		beforeSend: function () {
			$('#course_preview').html('Loading...  <i class="fa fa-spinner fa-spin"></i>');
		},
		success : function(response){
			$('#order_section').html(response);
		}
	});
})
})(jQuery);

	function stopMediaIn($scope){
		if (!$scope || !$scope.length) { return; }
		$scope.find('video, audio').each(function(){
			try {
				this.pause();
				this.currentTime = 0;
				this.removeAttribute('src');
				$(this).find('source').removeAttr('src');
				this.load();
			} catch(e) {}
		});
		$scope.find('iframe').each(function(){
			try { this.src = 'about:blank'; } catch(e) {}
		});
	}

	function stopvideo(){
		stopMediaIn($('#course_detail_modal'));
		$('#course_detail').html('');
		$('#course_detail_modal').modal('hide');
	}

	$(document).on('hide.bs.modal', '#course_detail_modal', function(){
		stopMediaIn($(this));
	});
	$(document).on('hidden.bs.modal', '#course_detail_modal', function(){
		$('#course_detail').html('');
	});
</script>

<script type="text/javascript">
	
	    $('.edit_assignment_id').click(function(){
		$('#heading').text("<?php echo $this->lang->line('edit_assignment'); ?>");
		var assigment_id = $(this).attr('data-assigment-id');
		var courseID = $(this).attr('course-data-id');
		$.ajax({
			url: '<?php echo base_url(); ?>onlinecourse/courseassignment/getassignment',
			type: 'post',
			data: {assigment_id:assigment_id,courseID:courseID},
			dataType: 'json',
			success: function(response){
				var decode = JSON.stringify(response);
				var result = JSON.parse(decode);
				$('#assignment_course_id').val(courseID);
				$('#assignment_title').val(result.getsinglesection.assignment_title);
				$('#edit_assignment_id').val(result.getsinglesection.id);
				$('#assignment_section_id').val(result.getsinglesection.course_section_id);
				$('#assignment_date').val(result.from_date);
				$('#submit_date').val(result.to_date);
				$('#marks').val(result.getsinglesection.marks);
                $('.wysihtml5-sandbox').contents().find('.wysihtml5-editor').html(result.getsinglesection.description);
			}
		});
	});

	$('.delete_assignment_id').click(function(){
		var courseid = $(this).attr('course-data-id');
		var assigment_id = $(this).attr('data-assigment-id');
		var delete_msg = "<?php echo $this->lang->line('are_you_sure'); ?>";
		var confirmationBox = confirm(delete_msg);
		if (confirmationBox == true) {
			$.ajax({
				url: '<?php echo base_url(); ?>onlinecourse/courseassignment/delete',
				type: 'post',
				data: {assigment_id:assigment_id},
				dataType: 'json',
				success: function(response){
					if (response.status == "success") {
						successMsg(response.message);
						coursedetail(courseid);
					}else{
						successMsg(response.error);
					}
				}
			});
		}
	});

	$('.delete_exam_id').click(function(){
		var courseid = $(this).attr('course-data-id');
		var exam_id = $(this).attr('data-exam-id');
		var delete_msg = "<?php echo $this->lang->line('are_you_sure'); ?>";
		var confirmationBox = confirm(delete_msg);
		if (confirmationBox == true) {
			$.ajax({				 
				url: '<?php echo base_url(); ?>onlinecourse/Courseexam/delete',
				type: 'post',
				data: {exam_id:exam_id},
				dataType: 'json',
				success: function(response){
					if (response.status == "success") {
						successMsg(response.message);
						coursedetail(courseid);
					}else{
						successMsg(response.error);
					}
				}
			});
		}
	});

	$('.edit_exam_id').click(function(){
		var exam_id = $(this).attr('data-exam-id');
		$.ajax({
			url: '<?php echo base_url(); ?>onlinecourse/courseexam/getexam',
			type: 'post',
			data: {exam_id:exam_id},
			dataType: 'json',
			success: function(response){
				var decode = JSON.stringify(response);
				var result = JSON.parse(decode);
				$('#exam_heading').html('<?php echo $this->lang->line('edit_exam'); ?>');
				$('#exam_course_id').val(result.getdata.course_id);
				$('#exam_id').val(result.getdata.id);
				$('#exam_section_id').val(result.getdata.course_section_id);
				$('#exam').val(result.getdata.exam);		
				$('#exam_from').val(result.from_date);
				$('#exam_to').val(result.to_date);
				$('#auto_publish_date').val(result.publish_date);
				$('#duration').val(result.getdata.duration);
				$('#attempt').val(result.getdata.attempt);
				$('#passing_percentage').val(result.getdata.passing_percentage);
				$('#word_limit').val(result.getdata.answer_word_count);
				$('.wysihtml5-sandbox').contents().find('.wysihtml5-editor').html(result.getdata.description);

				if(result.getdata.is_active==1){
					$('#is_active').prop("checked",true);
				}else{
					$('#is_active').prop("checked",false);
				}
				if(result.getdata.publish_result==1){
					$('#publish_result').prop("checked",true);
				}else{
					$('#publish_result').prop("checked",false);
				}
				if(result.getdata.is_neg_marking==1){
					$('#is_neg_marking').prop("checked",true);
				}else{
					$('#is_neg_marking').prop("checked",false);
				}
				if(result.getdata.is_marks_display==1){
					$('#is_marks_display').prop("checked",true);
				}else{
					$('#is_marks_display').prop("checked",false);
				}
				if(result.getdata.is_random_question==1){
					$('#is_random_question').prop("checked",true);
				}else{
					$('#is_random_question').prop("checked",false);
				}
				if(result.getdata.is_quiz==1){
					$('#is_quiz').prop("checked",true);
				}else{
					$('#is_quiz').prop("checked",false);
				}
			}
		});
	});

</script>