<?php $this->load->view('layout/course_css.php'); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.3.0/Chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
<div class="content-wrapper">
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header ptbnull">                     
                       <div class="col-lg-12 col-md-12 col-sm-12">
                            <h3 class="box-title header_tab_style"><?php echo $this->lang->line('course_performance'); ?></h3>
                       </div>                                         
                   </div>
				   <div class="box-body">
				   
						<section class="course-wrap">
						<div class="row">
							<div class="col-md-12">
								<div class="col-md-4">
									<div class="form-group">
										<b><?php echo $this->lang->line('total_lesson'); ?>: </b>
										<span class="fontmedium mt-5 "><?php echo $lesson_count; ?></span>
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group mt20">
										<div class="col-md-10 col-sm-10 col-xs-10">
										<div class="courssprogress">		
												<div class="progress-bar <?php if($course_progress < '100'){ ?> progress-bar-warning <?php }elseif($course_progress == '100'){ ?> progress-bar-info <?php } ?>" role="progressbar" aria-valuenow="45" id="progressbar<?= $courseid; ?>"  aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $course_progress;?>%">
												</div>				  
										</div>					  
										</div>
										<div class="col-md-2 col-sm-2 col-xs-2 text-right">
											<span id="progressbarval<?= $courseid; ?>">
											<?php echo $course_progress; ?>%
											</span>
										</div>
									</div>  
								</div> 
								<div class="col-md-4  <?php echo $this->customlib->get_online_course_curriculam_status("online_course_quiz");?>">
									<div class="form-group pull-right">
										<b><?php echo $this->lang->line('total_quiz'); ?>: </b>
										<span class="fontmedium mt-5 "><?php echo $quiz_count; ?></span>
									</div>
								</div>
							</div>
						</div>					
						<div class="row">
							<div class="col-md-12">
								<div class="col-md-6">
									<div class="form-group">
										<b><?php echo $this->lang->line('completed_lesson'); ?>: </b>
										<span class="fontmedium mt-5 "><?php echo $completedlesson; ?></span>
									</div>
								</div>
								<div class="col-md-6 <?php echo $this->customlib->get_online_course_curriculam_status("online_course_quiz");?>">
									<div class="form-group pull-right">
										<b><?php echo $this->lang->line('completed_quiz'); ?>: </b>
										<span class="fontmedium mt-5 "><?php echo $completedquiz; ?></span>
									</div>
								</div>
							</div>
						</div>		
						<div class="row">
							<div class="col-md-12">
								<div class="col-md-6  <?php echo $this->customlib->get_online_course_curriculam_status("online_course_assignment");?>">
									<div class="form-group">
										<b><?php echo $this->lang->line('total_assignment'); ?>: </b>
										<span class="fontmedium mt-5 "><?php echo $assignment_count;?></span>
								  </div>
								</div>
								<div class="col-md-6  <?php echo $this->customlib->get_online_course_curriculam_status("online_course_exam");?>">
									<div class="form-group pull-right">
										<b><?php echo $this->lang->line('total_exams'); ?>: </b>
										<span class="fontmedium mt-5 "><?php echo $exam_count;?></span>
								  </div>
								</div>
							</div>
						</div>
						<div class="row">
						<div class="col-md-12">
							<div class="col-md-6  <?php echo $this->customlib->get_online_course_curriculam_status("online_course_assignment");?>">
								<div class="form-group">
									<b><?php echo $this->lang->line('assignment_completed'); ?>: </b>
									<span class="fontmedium mt-5 "><?php echo $completedassignment;?></span>
							  </div>
							</div>
							<div class="col-md-6  <?php echo $this->customlib->get_online_course_curriculam_status("online_course_exam");?>">
								<div class="form-group pull-right">
									<b><?php echo $this->lang->line('exams_completed'); ?>: </b>
									<span class="fontmedium mt-5 "><?php echo $completedexam;?></span>
							  </div>
							</div>
						</div>
					</div>
					<hr>
					<div class="row  <?php echo $this->customlib->get_online_course_curriculam_status("online_course_quiz");?>">
						<div class="col-md-12">
							<div class="form-group">
								<h4 class="box-title"><b> <?php echo $this->lang->line('quiz_performance'); ?></b></h4>
							</div>
						</div>
					</div>						
						<div class="row  <?php echo $this->customlib->get_online_course_curriculam_status("online_course_quiz");?>">
							<?php
										
										if (!empty($quizperformancedata)) {  
?>
	<div class="col-md-8">
								<div class="scroll-area-fullheight">
									<?php
										$count = 0;
										$chart_count = 0;
										if (!empty($quizperformancedata)) {  
									 		foreach ($quizperformancedata as $quizperformancedata_value) {				 
											$count++;
									?>
									<div class="whatyou overflow-hidden mb10">
										<div class="row">
											<div class="col-md-8 col-lg-8 col-sm-4">
												<div class="form-group">
													<b><?php echo $this->lang->line('quiz'); ?>: <?php echo $count; ?></b>
													<div class="fontmedium mt-5 "><?php echo $quizperformancedata_value['quiz_title']; ?></div>
												</div>                           
												<div class="anslistlineh ">
													<b><?php echo $this->lang->line('total_question'); ?>: </b>
													<span>									
														<?php echo $quizperformancedata_value['total_question']; ?>
													</span>
												</div>
												<div class="anslistlineh ">
													<b><?php echo $this->lang->line('correct_answer'); ?>: </b>
													<span>
														<?php echo $quizperformancedata_value['correct_answer']; ?>
													</span>
												</div>
												<div class="anslistlineh">
													<b><?php echo $this->lang->line('wrong_answer'); ?>: </b>
													<span>
														<?php echo $quizperformancedata_value['wrong_answer']; ?>
													</span>
												</div>
												<div class="anslistlineh ">
													<b><?php echo $this->lang->line('not_attempted'); ?>: </b>
													<span>
														<?php echo $quizperformancedata_value['not_answer']; ?>
													</span>
												</div>
											</div>
											<div class="col-md-4 col-lg-4 col-sm-4">
												<div class="form-group">			
												<div class="box-body">
													<div class="chart-responsive"><canvas id="doughnuts-chart_<?php echo $chart_count; ?>" ></canvas>
													</div>
												</div>							
												</div>
											</div>
										</div>
									</div>
									<?php 
	                        $chart_count++;							
									 } 
									} 
									?>
								</div><!--./scroll-area-fullheight-->
							</div><!--./col-lg-8-->				
							<div class="col-md-4">
								<div class="scroll-area-fullheight">
									<?php 
if (!empty($quizperformancedata)) {  

	?>

									<div class="box-body">
										<div class="chart-responsive">
											<canvas id="horizontals-charts"></canvas>
										</div>
									</div>

	<?php
}
									 ?>
								</div>
							</div>
<?php
										}else{
?>
<div class="col-md-12">
	<div class="alert alert-info"><?php echo $this->lang->line('no_record_found'); ?></div>
</div>
<?php
										}
										?>
						
						</div>
						</section>
					</div>
				</div><!--./box box-primary -->
			</div>
		</div>
    </section>
</div>

<script>
(function ($) {
  "use strict";

	var data = {
	  labels: [<?php foreach ($quizperformancedata as $quizperformancedata_value) {?>"<?php echo $quizperformancedata_value['quiz_title']; ?>", <?php }?>],
	  datasets: [{
	    backgroundColor: ["#ff7300","#9c46d0","#7cdddd","#1baa2f","#97d9ff"],
	    borderWidth: 1,
	    data: [<?php if(!empty($totalmarks)){ foreach ($totalmarks as $totalmarks_value) { if($totalmarks_value > 0){ ?>"<?php echo $totalmarks_value; ?>", <?php } } } ?>],
	  }]
	};

	// invert the sign of each of the values.
	data.datasets[0].data.map((currentValue, index, array) => {
	  array[index] = currentValue * -1;
	});

	var options = {
	  scales: {
	    yAxes: [{
	      position: 'right' // right-align axis.
	    }],

	    xAxes: [{
	      id: "bar-x-axis1",
	      stacked: false,
	      ticks: {
	        callback: function(value, index, values) {
	          return value * -1; // invert the sign for tick labelling.
	        },
	        beginAtZero: true
	      }
	    }]
	  },
	  legend: { display: false },
	  title: {
	    display: true,
	    text: "<?php echo $this->lang->line('all_quiz_performance') . ' ' . '(%)'; ?>"
	  }
	};

	new Chart(document.getElementById("horizontals-charts"), {
	  type: 'horizontalBar',
	  data: data,
	  options: options
	});
})(jQuery);
</script>
<script>
	doughnutData=<?php echo json_encode($graph_data);?>;

$(document).ready(function(){
$.each(doughnutData, function(key,value) {
	
var ctx = document.getElementById("doughnuts-chart_"+key).getContext("2d");
				window.myDoughnut = new Chart(ctx).Pie(value, {responsive : true, rotation: 90,plugins: {
            legend: {
                display: true,
                labels: {
                    color: 'rgb(255, 99, 132)'
                }
            }
        }});
}); 
});
</script>