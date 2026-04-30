<?php $this->load->view('layout/course_css.php'); ?>
<div class="" id="divid">
					<div class="quizwell">
					
						<div class="row">
							<div class="col-lg-9 col-md-9 col-sm-9">
								<div class="row">
									<div class="col-lg-9 col-md-9 col-sm-9">
										<div class="form-group">
											<label><?php echo $this->lang->line('quiz'); ?>: </label>
											<?php echo $singlequizlist['quiz_title']; ?>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							
							<div class="col-lg-9 col-md-9 col-sm-9">
								<div class="row">
									<div class="col-lg-3 col-md-3 col-sm-6">
										<div class="form-group">
											<label><?php echo $this->lang->line('total_question'); ?>:
<?php if (!empty($questioncount)) {
    echo $questioncount['question_count'];
}?></label>
										</div>
									</div>
									<div class="col-lg-3 col-md-3 col-sm-6">
										<div class="form-group">
											<label><?php echo $this->lang->line('correct_answer'); ?>: </label>
											<?php echo $answercount; ?>
										</div>
									</div>
									<div class="col-lg-3 col-md-3 col-sm-6">
										<div class="form-group">
											<label><?php echo $this->lang->line('wrong_answer'); ?>:</label>
											<?php echo $wronganswer; ?>
										</div>
									</div>
									<div class="col-lg-3 col-md-3 col-sm-6">
										<div class="form-group">
											<label><?php echo $this->lang->line('not_attempted'); ?>:</label>
											<?php echo $not_attempted; ?>
										</div>
									</div>
								</div>
							</div>
							<div class="col-lg-3 col-md-3 col-sm-3">
								<div class="form-group pull-right" id="hideonprint">
									<button onclick="printDiv('divid')" class="btn btn-sm btn-primary" title="<?php echo $this->lang->line('print'); ?>"><i class="fa fa-print"></i></button>
									<button id="reset_btn" quiz-data-id="<?php echo $quizid; ?>" class="btn btn-sm btn-primary "><?php echo $this->lang->line('reset'); ?></button>
								</div>
							</div>
						</div>
					</div>

				<div class="pagebg">
					<div class="row" >
						<div class="col-lg-8 col-md-8 col-sm-12">
						<div class="scroll-full" id="remove_scroll">
				<?php
$count = 1; 
if(!empty($questionlist)){
  foreach ($questionlist as $questionlist_value) {
$result = '';
if (!empty($answerlist[$questionlist_value['id']])) { 
    ?>
		<?php foreach ($answerlist[$questionlist_value['id']] as $answerlist_value) {
        if (!empty($answerlist_value)) {
            $submit_answer = json_decode($answerlist_value);

            foreach ($submit_answer as $key => $submit_answer_value) {

                if (!empty($submit_answer_value)) {
                    $key = $key + 1;
                    if ($key == 1) {
                        $result = "option_1,";
                    }
                    if ($key == 2) {
                        $result = $result . "option_2,";
                    }
                    if ($key == 3) {
                        $result = $result . "option_3,";
                    }
                    if ($key == 4) {
                        $result = $result . "option_4,";
                    }
                    if ($key == 5) {
                        $result = $result . "option_5";
                    }
                }
            }
            $result = rtrim($result, ',');
        }
       $result;
    } } ?>

							<div class="form-group">
								<?php 							
								
								if ($questionlist_value['correct_answer'] == $result) {
            ?>
									<i class="fa fa-check text-success font16"></i>
								<?php } else {?>
									<i class="fa fa-times text-danger font16"></i>
								<?php }?>
								<b><?php echo $this->lang->line('question'); ?>: <?php echo $count; ?></b>
								<div class="fontmedium mt-5 "><?php echo $questionlist_value['question']; ?></div>
							</div>
							<?php if (!empty($questionlist_value['option_1'])) {?>
							<div class="anslistlineh">
								<?php if (strpos($questionlist_value['correct_answer'], 'option_1') !== false) {$class = "text-green-dark";} else { $class = "";}?>
								<span class="<?php echo $class ?>"><b>A</b></span>
								<span class="<?php echo $class ?>">
									<?php echo $questionlist_value['option_1']; ?>
								</span>
							</div>
							<?php }?>
							<?php if (!empty($questionlist_value['option_2'])) {?>
							<div class="anslistlineh">
								<?php if (strpos($questionlist_value['correct_answer'], 'option_2') !== false) {$class = "text-green-dark";} else { $class = "";}?>
								<span class="<?php echo $class ?>"><b>B</b></span>
								<span class="<?php echo $class ?>">
									<?php echo $questionlist_value['option_2']; ?>
								</span>
							</div>
							<?php }?>
							<?php if (!empty($questionlist_value['option_3'])) {?>
							<div class="anslistlineh">
								<?php if (strpos($questionlist_value['correct_answer'], 'option_3') !== false) {$class = "text-green-dark";} else { $class = "";}?>
								<span class="<?php echo $class ?>"><b>C</b></span>
								<span class="<?php echo $class ?>">
									<?php echo $questionlist_value['option_3']; ?>
								</span>
							</div>
							<?php }?>
							<?php if (!empty($questionlist_value['option_4'])) {?>
							<div class="anslistlineh">
								<?php if (strpos($questionlist_value['correct_answer'], 'option_4') !== false) {$class = "text-green-dark";} else { $class = "";}?>
								<span class="<?php echo $class ?>"><b>D</b></span>
								<span class="<?php echo $class ?>">
									<?php echo $questionlist_value['option_4']; ?>
								</span>
							</div>
							<?php }?>
							<?php if (!empty($questionlist_value['option_5'])) {?>
							<div class="anslistlineh">

								<?php if (strpos($questionlist_value['correct_answer'], 'option_5') !== false) {$class = "text-green-dark";} else { $class = "";}?>
								<span class="<?php echo $class ?>"><b>E</b></span>
								<span class="<?php echo $class ?>">
									<?php echo $questionlist_value['option_5']; ?>
			 					</span>
							</div>
							<?php }?>
							<div class=""> <br>
								<b><?php echo $this->lang->line('your_answer'); ?>: </b>
								<?php

if ($answerlist[$questionlist_value['id']] != '') { 
    foreach ($answerlist[$questionlist_value['id']] as $answerlist_value) {

	if (!empty($answerlist_value)) {
	    $submit_answer = json_decode($answerlist_value);
	    $result = '';
	    foreach ($submit_answer as $key => $submit_answer_value) {
	    	if (array_filter($submit_answer)) {
	            if (!empty($submit_answer_value)) {
	                $key = $key + 1;
	                if ($key == 1) {
	                    echo "A, ";
	                }if ($key == 2) {
	                    echo "B, ";
	                }if ($key == 3) {
	                    echo "C, ";
	                }if ($key == 4) {
	                    echo "D, ";
	                }if ($key == 5) {
	                    echo "E";
	                }
	            }
	        }else{
	        	$result =$this->lang->line('not_answered');
	        }
	    }
	    echo $result;
	} } 
}else{
   echo $this->lang->line('not_answered');
} ?>
		       	<hr>
							</div>
					<?php $count++;} } ?>
					</div>
					</div>

					<div class="col-md-4 col-lg-4 col-sm-12" id="hidediv">
					 	<div class="scroll-area padd-10">
					 		<div class="box-body border-bottom mb10">
                                <div class="chart-responsive">
                                    <canvas id="doughnut-chart"></canvas>
                                    <div class="pb10"></div>
                                </div>
                            </div>
							<?php if(!empty($totalquiz)){ ?>
					 		<div class="box-body border-bottom mb10">
                                <div class="chart-responsive">
                                    <canvas id="horizontal-chart"></canvas>
                                    <div class="pb10"></div>
                                </div>
                            </div>
							<?php } ?>

						</div>
					</div>
				</div>
		</div>
</div>

<script>
(function ($) {
  "use strict";

  $('#reset_btn').click(function(){
  	$('#video_id').html('');
  	var courseid = "<?php echo $courseid; ?>";
    var quizID = $(this).attr('quiz-data-id');
    $.ajax({
      url : '<?php echo base_url(); ?>user/studentcourse/reset',
      data: {quizID:quizID,courseid:courseid},
      type:'post',
      success : function(response){
        $('#video_id').html(response);
      }
    });
  })
})(jQuery);
</script>

<script>
(function ($) {
  "use strict";

	new Chart(document.getElementById("doughnut-chart"), {
	    type: 'pie',
	    data: {
	      labels: ["<?php echo $this->lang->line('correct_answer'); ?>", "<?php echo $this->lang->line('wrong_answer'); ?>", "<?php echo $this->lang->line('not_attempted'); ?>"],
	      datasets: [{
	        backgroundColor: ["#52d726", "#f93939", "#c9cbcf"],
	        data: [<?php echo $graphdata['correct_answer']; ?>, <?php echo $graphdata['wrong_answer']; ?>, <?php echo $graphdata['not_answer']; ?>]
	      }]
	    },
	    options: {
	      title: {
	        display: true,
	        text: "<?php echo $this->lang->line('current_quiz_performance'); ?>"
	      }
	    }
	});



var data_length=<?php echo count($totalquiz);?>;
var colors = ["#ff7300","#9c46d0","#7cdddd","#1baa2f","#97d9ff"]; 
var bgColors = [];
for (var i = 0; i < data_length; i++) {
  bgColors.push(colors[i % colors.length]);  
}

	var data = {
	  labels: [<?php foreach ($totalquiz as $totalquiz_value) {?>"<?php echo $totalquiz_value->quiz_title; ?>", <?php }?>],
	  datasets: [{
	    backgroundColor: bgColors,
	    borderWidth: 1,
	    data: [<?php foreach ($totalmarks as $totalmarks_value) {?>"<?php echo $totalmarks_value; ?>", <?php }?>],
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

	new Chart(document.getElementById("horizontal-chart"), {
	  type: 'horizontalBar',
	  data: data,
	  options: options
	});

})(jQuery);
</script>
<script type="text/javascript">
	function printDiv(divId) {
		$('#hideonprint').addClass('hide');
		$('#hidediv').addClass('hide');
		$('#remove_scroll').removeClass('scroll-full');
		
		var printContents = document.getElementById(divId).innerHTML;
		var originalContents = document.body.innerHTML;
		document.body.innerHTML = "<html><head><title></title></head><body>" + printContents + "</body>";	   
		window.print();
		document.body.innerHTML = originalContents;
		setTimeout(function() {
            var courseID	=	"<?php echo $courseid; ?>";
			afterprint(courseID);				
           }, 1000);
	}    
</script>