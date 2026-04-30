<?php if (!empty($coursesList)) {
    $currency_symbol = $this->customlib->getSchoolCurrencyFormat();
    $result    = $this->customlib->getUserData();
    $image     = $result["image"];
    $free_course     = $coursesList['free_course'];
    $discount        = $coursesList['discount'];
    $price           = $coursesList['price'];
    $discount_price  = '';
    if ($discount != '0.00') {
        $discount_price = ($price - $price * $discount / 100);
    }
    ?>
    <div class="flex-row row"> 
        <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
            <div class="whatyou coursebox-body mbDM15">
                <?php if (!empty($coursesList['course_url']) || (!empty($coursesList['video_id']))) { ?>
				<div class="coursebox mb0">	
                    <?php	if ($coursesList['course_provider'] == "html5") {	?>   
                        <div class="course-video-height"> 		
                            <video id="videoPlayer" controls>
                                <source src="<?php echo $coursesList['course_url']; ?>" type="video/mp4">
                            </video>	
                        </div>    			
                    <?php	} elseif ($coursesList['course_provider'] == "youtube") {	?>    
                        <div class="course-video-height">        
                            <iframe width="100%" src="//www.youtube.com/embed/<?php echo $coursesList['video_id']; ?>" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen=""></iframe>
                        </div>  
                    <?php	} elseif ($coursesList['course_provider'] == "vimeo") { 	?>   
        				<iframe src="<?php echo $coursesList['course_url']; ?>" width="640" height="300" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
                    <?php	}elseif ($coursesList['course_provider'] == "s3_bucket") { ?>	        				
        				<video controls width="100%">
        					<source src="<?php echo $this->aws3->generateUrl($coursesList['video_id']); ?>">
        				</video>        				
                    <?php } ?>                     
				</div>
                <?php } else {?>
                <div class="coursebox mb0">
                    <div class="coursebox-img">
                       <img src="<?php echo base_url(); ?>uploads/course/course_thumbnail/<?php echo $coursesList['course_thumbnail']; ?>" class="img-responsive">
                    </div>   
                </div>
                <?php }?>
            </div>
        </div><!--./col-lg-7-->
        <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
            <div class="whatyou coursebox-body relative">
                <div class="author-block-center text-center">
                    <?php if (!empty($coursesList['image'])) {
                        ?>
                        <img class="img-circle" src="<?php echo base_url(); ?>uploads/staff_images/<?php echo $coursesList['image']; ?>" alt="User Image">
                    <?php } else {
                    if($coursesList['gender']=='Female'){
                        $file= "uploads/staff_images/default_female.jpg";
                    }else{
                        $file ="uploads/staff_images/default_male.jpg";
                    }
                        ?>
                        <img class="img-circle" src="<?php echo base_url(); ?><?php echo $file; ?>" alt="">
                    <?php }?>
                    <span class="authornamebig"><?php echo $coursesList['staff_name'].' '.$coursesList['staff_surname']; ?> (<?php echo $coursesList['assign_employee_id']; ?>)</span>
                    <span class="descriptionbig"><?php echo $this->lang->line('last_updated'); ?> <span>
                    <?php echo date($this->customlib->getSchoolDateFormat(), strtotime($coursesList['updated_date'])); ?>
                     </span></span>
                </div>
                <ul class="lessonsblock ptt10 ">
                    <li><i class="fa fa-list-alt"></i><?php echo $this->lang->line('class'); ?>: <?php echo $coursesList['class']; ?> (<?php echo rtrim($section, ", "); ?>)</li>
                   <?php if ($total_lesson !='' && $total_lesson !='0') {?>
                    <li class="<?php ?>">
                        <i class="fa fa-play-circle"></i><?php echo $this->lang->line('lesson') . ": " . $total_lesson;?>
                    </li>
                    <?php } ?>
                    <?php if ($total_quiz !='' && $total_quiz !='0') {?>          
                    <li class="<?php echo $this->customlib->get_online_course_curriculam_status("online_course_quiz");?>">
                        <i class="fa fa-question-circle"></i><?php echo $this->lang->line('quiz') . ": " . $total_quiz; ?>
                    </li> 
                    <?php } ?> 

                    <?php if ($total_exam !='' && $total_exam !='0') {?>          
                    <li class="<?php echo $this->customlib->get_online_course_curriculam_status("online_course_exam");?>">
                        <i class="fa fa-rss ftlayer"></i><?php echo $this->lang->line('exam') . ": " . $total_exam; ?>
                    </li> 
                    <?php } ?> 

                    <?php if ($total_assignment !='' && $total_assignment !='0') {?>          
                    <li class="<?php echo $this->customlib->get_online_course_curriculam_status("online_course_assignment");?>">
                        <i class="fa fa-flask ftlayer"></i><?php echo $this->lang->line('assignment') . ": " . $total_assignment; ?>
                    </li> 
                    <?php } ?> 

                    <?php if (!empty($total_hour_count) && $total_hour_count != '00:00:00') {?>        
                    <li>
                        <i class="fa fa-clock-o"></i><?php echo $total_hour_count ." ".$this->lang->line('hrs'); ?>
                    </li>
                   <?php } ?>
                    <li>
                        <?php 
                            if ($free_course == '1' ) {
                                if($price > '0.00'){
                                   echo $this->lang->line('free').' '.'<del>'.$currency_symbol.''.amountFormat($price).'</del>';
                                }else{
                                    echo $this->lang->line('free');
                                }
                            } else {
                                if (!empty($discount_price)) {                                    
                                  echo $currency_symbol . ' ' . amountFormat($discount_price);?>                             
                                  
                                  <del><?php echo $currency_symbol . ' ' . amountFormat($price); ?></del>
                            <?php } else {
                                  echo $currency_symbol . '' . amountFormat($price);?>
                        <?php }  } ?> 
                    </li>
                    <li>
                        <?php echo $this->lang->line('created_by'); ?>: <?php echo $coursesList['name'].' '.$coursesList['surname']. ' (' . $coursesList['employee_id'].')'; ?>
                    </li>
                </ul>
            </div>    
        </div><!--./col-lg-5-->
     </div><!--./detailmodalbg-->  
 <div class="row">     
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="imgbottomtext">
            <h3 class="modal-title pb3 fontmedium"><?php echo $coursesList['title']; ?></h3>
            <p><?php echo $coursesList['description']; ?>.</p>
        </div>
    </div><!--./col-lg-9-->
    
<?php }?>
<?php if (!empty($sectionList)) { ?>
    <?php 
		$outcomes = json_decode($coursesList['outcomes']);
        $check_empty = '';
        if (array_filter($outcomes)) {
            $check_empty = $outcomes;
        } else {
            $check_empty = '';
        }
    ?>
    <div class="col-lg-12 col-md-12 col-sm-12">
        <?php if (!empty($check_empty)){ ?>
        <div class="coursecard whatyou">
			<h3 class="fontmedium"><?php echo $this->lang->line('what_will_i_learn'); ?></h3>
			<?php $outcomes = json_decode($coursesList['outcomes']); ?>
			<ul class="whatlearn">
				<li>
					<?php foreach ($outcomes as $outcomes_value) {?>
					<?php echo $outcomes_value; ?>
					<?php }?>
				</li>
			</ul>
        </div><!--./coursecard-->
        <?php } ?>
        <div class="coursecard ptt10">
            <h4 class="fontmedium"><?php echo $this->lang->line('curriculum_for_this_course'); ?> </h4>
            <div class="panel-group faq mb10" id="accordionplus">
				<div class="panel panel-info">
				<?php $lessoncount=0; $quizcount=0; $sectioncount=0; $examcount=0; $assignmentcount=0;
                foreach ($sectionList as $sectionList_key => $sectionList_value) {  $sectioncount = $sectioncount+1; ?>
                <?php $sectionID = $sectionList_value->id;?>
					<div class="panel-heading" data-toggle="collapse" data-parent="#accordionplus" data-target="#<?php echo $sectionList_key; ?>" aria-expanded="true">
						<h4 class="panelh3 accordion-togglelpus"><?php echo "<b>".$this->lang->line('section').' '. $sectioncount.'</b>: '. $sectionList_value->section_title; ?><span class="mr0"><i class="fa fa-play-circle"></i><?php if (!empty($sectionList_value->total_lessons)) {echo $sectionList_value->total_lessons;}?> <?php echo $this->lang->line('lesson'); ?></span></h4>
					</div>
					<div id="<?php echo $sectionList_key; ?>" class="panel-collapse collapse in" aria-expanded="true">	
						<ul class="introlist">
							<?php if (!empty($lessonquizdetail[$sectionID])) { ?>
								<?php 
									foreach ($lessonquizdetail[$sectionID] as $lessonquizdetail_value) {   
										if ($lessonquizdetail_value['type'] == 'lesson') { 
										$lessoncount = $lessoncount+1; ?>
											<?php if($lessonquizdetail_value['type'] !=''){ ?>
												<li class="<?php ?>"><i class="fa fa-play-circle"></i><?php echo "<b>".$this->lang->line($lessonquizdetail_value['type'])." ".$lessoncount.": "."</b>". $lessonquizdetail_value['lesson_title']; ?><span><?php if($lessonquizdetail_value['lesson_type'] == 'video'){ echo $lessonquizdetail_value['duration']; } ?></span></li>
											<?php } ?>					
										<?php }elseif ($lessonquizdetail_value['type'] == 'quiz'){ 
											$quizcount = $quizcount+1;?>
											<?php if($lessonquizdetail_value['type'] !=''){ ?>
												<li  class="<?php echo $this->customlib->get_online_course_curriculam_status("online_course_quiz");?>"><i class="fa fa-question-circle"></i><?php echo "<b>".$this->lang->line($lessonquizdetail_value['type'])." ".$quizcount.": "."</b>". $lessonquizdetail_value['quiz_title']; ?></li>
											<?php } ?>					
										<?php }elseif ($lessonquizdetail_value['type'] == 'exam'){ 
											$examcount = $examcount+1;?>
											<?php if($lessonquizdetail_value['type'] !=''){ ?>
												<li  class="<?php echo $this->customlib->get_online_course_curriculam_status("online_course_exam");?>"><i class="fa fa-rss ftlayer"></i><?php echo "<b>".$this->lang->line($lessonquizdetail_value['type'])." ".$examcount.": "."</b>". $lessonquizdetail_value['course_exam_name']; ?></li>
											<?php } ?>					
										<?php }elseif ($lessonquizdetail_value['type'] == 'assignment'){ 
											$assignmentcount = $assignmentcount+1;?>
											<?php if($lessonquizdetail_value['type'] !=''){ ?>
												<li  class="<?php echo $this->customlib->get_online_course_curriculam_status("online_course_assignment");?>"><i class="fa fa-flask ftlayer"></i><?php echo "<b>".$this->lang->line($lessonquizdetail_value['type']).$assignmentcount.": "."</b>".$lessonquizdetail_value['assignment_title']; ?></li>
											<?php } ?>					
										<?php }										
									}
							}	?>
						</ul>						
					</div><!--#/collapseOne-->
					<?php }?>
				</div><!--./panel-info-->
            </div><!--./panel-group-->
        </div><!--./coursecard-->
    </div><!--./col-lg-8-->
  </div><!--./row-->  
<?php }?>