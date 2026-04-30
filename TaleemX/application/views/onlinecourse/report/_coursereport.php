<div class="row">
    <div class="col-md-12">
        <div class="box box-primary border0 mb0 margesection">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-search"></i>  <?php echo $this->lang->line('online_course_report'); ?></h3>
            </div>
            <div class="">
                <ul class="reportlists">
					<?php if($this->rbac->hasPrivilege('student_course_purchase_report', 'can_view')){ ?>
					
                        <li class="col-lg-4 col-md-4 col-sm-6 <?php echo set_SubSubmenu('onlinecourse/coursereport/coursepurchase'); ?>"><a href="<?php echo base_url(); ?>onlinecourse/coursereport/coursepurchase"><i class="fa fa-file-text-o"></i><?php echo $this->lang->line('student_course_purchase_report') ?></a></li>
						
					<?php } if($this->rbac->hasPrivilege('course_sell_count_report', 'can_view')){ ?>
					
                        <li class="col-lg-4 col-md-4 col-sm-6 <?php echo set_SubSubmenu('onlinecourse/coursereport/coursesellreport'); ?>"><a href="<?php echo base_url(); ?>onlinecourse/coursereport/coursesellreport"><i class="fa fa-file-text-o"></i><?php echo $this->lang->line('course_sell_count_report') ?></a></li>
						
					<?php } if($this->rbac->hasPrivilege('course_trending_report', 'can_view')){ ?>
					
                        <li class="col-lg-4 col-md-4 col-sm-6 <?php echo set_SubSubmenu('onlinecourse/coursereport/trendingreport'); ?>"><a href="<?php echo base_url(); ?>onlinecourse/coursereport/trendingreport"><i class="fa fa-file-text-o"></i><?php echo $this->lang->line('course_trending_report') ?></a></li>
						
					<?php } if($this->rbac->hasPrivilege('course_complete_report', 'can_view')){ ?>
					
                        <li class="col-lg-4 col-md-4 col-sm-6 <?php echo set_SubSubmenu('onlinecourse/coursereport/completereport'); ?>"><a href="<?php echo base_url(); ?>onlinecourse/coursereport/completereport"><i class="fa fa-file-text-o"></i><?php echo $this->lang->line('course_complete_report') ?></a></li>
						
					<?php } if($this->rbac->hasPrivilege('course_rating_report', 'can_view')){ ?>
					
						<li class="col-lg-4 col-md-4 col-sm-6 <?php echo set_SubSubmenu('onlinecourse/coursereport/courseratingreport'); ?>"><a href="<?php echo base_url(); ?>onlinecourse/coursereport/courseratingreport"><i class="fa fa-file-text-o"></i><?php echo $this->lang->line('course_rating_report') ?></a></li>
						
					<?php } if($this->rbac->hasPrivilege('guest_report', 'can_view')){ ?>
					
						<li class="col-lg-4 col-md-4 col-sm-6 <?php echo set_SubSubmenu('onlinecourse/coursereport/guestlist'); ?>"><a href="<?php echo base_url(); ?>onlinecourse/coursereport/guestlist"><i class="fa fa-file-text-o"></i><?php echo  $this->lang->line('guest_report') ?></a></li>	
					
					<?php } if($this->rbac->hasPrivilege('course_assignment_report', 'can_view')){ ?>					

						<li class="col-lg-4 col-md-4 col-sm-6 <?php echo set_SubSubmenu('onlinecourse/coursereport/course_assignment_report'); ?>"><a href="<?php echo base_url(); ?>onlinecourse/coursereport/course_assignment_report"><i class="fa fa-file-text-o"></i><?php echo  $this->lang->line("course_assignment_report"); ?></a></li>

					<?php } if($this->rbac->hasPrivilege('course_exam_result_report', 'can_view')){ ?>

						<li class="col-lg-4 col-md-4 col-sm-6 <?php echo set_SubSubmenu('onlinecourse/coursereport/result_report'); ?>"><a href="<?php echo base_url(); ?>onlinecourse/coursereport/result_report"><i class="fa fa-file-text-o"></i><?php echo  $this->lang->line("course_exam_result_report"); ?></a></li>	
						
					<?php } if($this->rbac->hasPrivilege('"course_exam_report"', 'can_view')){ ?>

						<li class="col-lg-4 col-md-4 col-sm-6 <?php echo set_SubSubmenu('onlinecourse/coursereport/course_exam_report'); ?>"><a href="<?php echo base_url(); ?>onlinecourse/coursereport/course_exam_report"><i class="fa fa-file-text-o"></i><?php echo  $this->lang->line("course_exam_report"); ?></a></li>	
					
					<?php } if($this->rbac->hasPrivilege('course_exam_attempt_report', 'can_view')){ ?>

						<li class="col-lg-4 col-md-4 col-sm-6 <?php echo set_SubSubmenu('onlinecourse/coursereport/course_exam_attempt_report'); ?>"><a href="<?php echo base_url(); ?>onlinecourse/coursereport/course_exam_attempt_report"><i class="fa fa-file-text-o"></i><?php echo  $this->lang->line("course_exam_attempt_report"); ?></a></li>	
					
					<?php } ?>						 
					
                </ul>
            </div>
        </div> 
    </div>
</div>