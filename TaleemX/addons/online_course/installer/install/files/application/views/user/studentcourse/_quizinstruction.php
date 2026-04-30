<?php

if (!empty($singlequizlist)) {?>
    <div class="margintop40">
     <div class="pagebg">
      <div class="scroll-area">
         <div class="form-group">
           <p class="mtmius1"><?php echo $this->lang->line('quiz_title'); ?>: <b><?php echo $singlequizlist['quiz_title']; ?></b> </p>
           <p><?php echo $this->lang->line('number_of_question'); ?>: <b><?php echo $questioncount['question_count']; ?></b> </p>
		   <?php if (!empty($singlequizlist['quiz_instruction'])) {?>
           <div class="form-group">
              <p><b><?php echo $this->lang->line('instruction'); ?>:</b></p>  
                <p><?php echo  nl2br($singlequizlist['quiz_instruction']); ?></p>	  
           </div> 
    	  <?php } ?>
            <?php if (!empty($singlequizlist['id']) && !empty($questionlist['id'])) {?>
           <button type="button" question-data-id= "<?php echo $questionlist['id']; ?>" course-data-id="<?php echo $courseid; ?>" data-id="<?php echo $singlequizlist['id']; ?>" class="btn btn-primary start_btn_id"><?php echo $this->lang->line('start_quiz'); ?></button>
           <?php }?>
         </div>
        </div>
    </div>    
  </div>
<?php }?>

<script>
(function ($) {
  "use strict";

  $('.start_btn_id').click(function(){
    $('#video_id').html('');
    var quizID = $(this).attr('data-id');
    var questionID = $(this).attr('question-data-id');
    var courseid = $(this).attr('course-data-id');
    $.ajax({
      url : '<?php echo base_url(); ?>user/studentcourse/quizquestion',
      data: {quizID:quizID,quizquestionID:questionID,courseid:courseid},
      type:'post',
      success : function(response){
        $('#video_id').html(response);
      }
    });
  });
})(jQuery);
</script>