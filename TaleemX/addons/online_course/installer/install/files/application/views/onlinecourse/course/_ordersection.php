<div class="row">
    <div class="col-md-12">       
        <div class="box-body scroll-area">
            <div class="panel-body">	
                <?php if (!empty($sectionlist)) { ?>
                <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">
                    <ul class="sortable-section ui-sortable pl0">
                    <?php $lessoncount=0; $quizcount=0; $sectioncount=0; $assignmentcount=0; $examcount=0;
                    foreach ($sectionlist as $sectionlist_value) { $sectioncount = $sectioncount+1;
                        ?>
                        <li id="<?php echo $sectionlist_value->id; ?>" class="list-group-item-sort text-left mb20">
                            <span class="sort-action">
                            </span> <i class="fa fa-arrows"></i> 
							
							<?php
                            echo "<b>".$this->lang->line('section').' '. $sectioncount.'</b>: '.($sectionlist_value->section_title);
                            ?>
						<ul class="sortable-lesson-quiz ui-sortable ist-group pt15">
							<?php 
							if(!empty($lessonquizdetail[$sectionlist_value->id])){
								foreach ($lessonquizdetail[$sectionlist_value->id] as $lessonquizdetail_value) { 

                                    if($lessonquizdetail_value['type'] == 'lesson'){ 
										$lessoncount = $lessoncount+1; 
										if($lessonquizdetail_value['type'] !=''){ ?>
											<li id="<?php echo $lessonquizdetail_value['id']; ?>" class="list-group-item-sort text-left">
												<span class="sort-action"></span> <i class="fa fa-arrows"></i> 
												<?php 
													echo "<b>".$this->lang->line($lessonquizdetail_value['type'])." ".$lessoncount.": "."</b>";
													echo ($lessonquizdetail_value['lesson_title']);
												?>
											</li>
										<?php } 
									}elseif($lessonquizdetail_value['type'] == 'quiz' && $this->customlib->get_online_course_curriculam_status("online_course_quiz")==""){ 
										$quizcount = $quizcount+1;
										if($lessonquizdetail_value['type'] !=''){ ?>
											<li id="<?php echo $lessonquizdetail_value['id']; ?>" class="list-group-item-sort text-left">
												<span class="sort-action"></span> <i class="fa fa-arrows"></i> 
												<?php 
													echo "<b>".$this->lang->line($lessonquizdetail_value['type'])." ".$quizcount.": "."</b>";
													echo ($lessonquizdetail_value['quiz_title']);
												?>
											</li>
										<?php } 
									}elseif($lessonquizdetail_value['type'] == 'exam' && $this->customlib->get_online_course_curriculam_status("online_course_exam")==""){ 
										$examcount = $examcount+1;
										if($lessonquizdetail_value['type'] !=''){ ?>
											<li id="<?php echo $lessonquizdetail_value['id']; ?>" class="list-group-item-sort text-left">
												<span class="sort-action"></span> <i class="fa fa-arrows"></i> 
												<?php 
													echo "<b>".$this->lang->line($lessonquizdetail_value['type'])." ".$examcount.": "."</b>";
													echo ($lessonquizdetail_value['course_exam_name']);
												?>
											</li>
										<?php } 
									} elseif($lessonquizdetail_value['type'] == 'assignment' && $this->customlib->get_online_course_curriculam_status("online_course_assignment")==""){ 
										$assignmentcount = $assignmentcount+1;
										if($lessonquizdetail_value['type'] !=''){ ?>
											<li id="<?php echo $lessonquizdetail_value['id']; ?>" class="list-group-item-sort text-left">
												<span class="sort-action"></span> <i class="fa fa-arrows"></i> 
												<?php 
                                                    echo "<b>".$this->lang->line($lessonquizdetail_value['type'])." ".$assignmentcount.": "."</b>";
													echo ($lessonquizdetail_value['assignment_title']);
												?>
											</li>
										<?php } 
									} 
								} 
							} ?>
						</ul>
						</li>
						
                    <?php } } else { ?>
                        <div class="alert alert-danger">
                            <?php echo $this->lang->line('no_record_found') ?>
                        </div>
                    <?php } ?>
				</ul>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    function toggleIcon(e) {
        $(e.target)
            .prev('.panel-heading')
            .find(".more-less")
            .toggleClass('glyphicon-plus glyphicon-minus');
    }

(function ($) {
  "use strict";

    $('.panel-group').on('hidden.bs.collapse', toggleIcon);
    $('.panel-group').on('shown.bs.collapse', toggleIcon);

    $('.sortable-section').sortable({
        connectWith: '.sortable-section',
        update: function (event, ui) {
            $(this).closest('div.box-body').addClass("sdfdsfs");
            var sectionarray = $(this).sortable('toArray');
            $.ajax({
                type: "POST",
                url: "<?php echo base_url(); ?>onlinecourse/course/updatesectionorder",
                data: {"sectionarray": sectionarray},
                dataType: "json",

                beforeSend: function () {
                    $('#fade,#modal').css({'display': 'block'});
                },
                success: function (data) {
                    if (data.status) {
                        successMsg(data.msg);
                    } else {
                        errorMsg(data.msg);
                    }
                    $('#fade,#modal').css({'display': 'none'});
                },
                error: function (xhr) { // if error occured
                    alert("Error occured.please try again");
                    $('#fade,#modal').css({'display': 'none'});
                },
                complete: function () {
                    $('#fade,#modal').css({'display': 'none'});
                }
            });
        }
    });

    $('.sortable-lesson-quiz').sortable({
        connectWith: '.sortable-lesson-quiz',
        update: function (event, ui) {
            $(this).closest('div.box-body').addClass("sdfdsfs");
            var lessonquizarray = $(this).sortable('toArray');
            $.ajax({
                type: "POST",
                url: "<?php echo base_url(); ?>onlinecourse/course/updatelessonquizorder",
                data: {"lessonquizarray": lessonquizarray},
                dataType: "json",

                beforeSend: function () {
                    $('#fade,#modal').css({'display': 'block'});
                },
                success: function (data) {
                    if (data.status) {
                        successMsg(data.msg);
                    } else {
                        errorMsg(data.msg);
                    }
                    $('#fade,#modal').css({'display': 'none'});
                },
                error: function (xhr) { // if error occured
                    alert("Error occured.please try again");
                    $('#fade,#modal').css({'display': 'none'});
                },
                complete: function () {
                    $('#fade,#modal').css({'display': 'none'});
                }
            });
        }
    });
})(jQuery);
</script>