<?php if (!empty($singlequestionlist)) {
    $correctAnswer = '';
    $checked1      = '';
    $checked2      = '';
    $checked3      = '';
    $checked4      = '';
    $checked5      = '';

    if (!empty($answerlist['answer']) && !empty($singlequestionlist['option_1'])) {
        $correctAnswer = json_decode($answerlist['answer']);
        if (in_array('option_1', $correctAnswer)) {
            $checked1 = 'checked';
        }
    }

    if (!empty($answerlist['answer']) && !empty($singlequestionlist['option_2'])) {
        $correctAnswer = json_decode($answerlist['answer']);
        if (in_array('option_2', $correctAnswer)) {
            $checked2 = 'checked';
        }
    }

    if (!empty($answerlist['answer']) && !empty($singlequestionlist['option_3'])) {
        $correctAnswer = json_decode($answerlist['answer']);
        if (in_array('option_3', $correctAnswer)) {
            $checked3 = 'checked';
        }
    }

    if (!empty($answerlist['answer']) && !empty($singlequestionlist['option_4'])) {
        $correctAnswer = json_decode($answerlist['answer']);
        if (in_array('option_4', $correctAnswer)) {
            $checked4 = 'checked';
        }
    }

    if (!empty($answerlist['answer']) && !empty($singlequestionlist['option_5'])) {
        $correctAnswer = json_decode($answerlist['answer']);
        if (in_array('option_5', $correctAnswer)) {
            $checked5 = 'checked';
        }
    }
    ?>
  <div class="">
    <div class="row">
      <div class="pagebg">  
       <div class="scroll-area-fullheight">
        <div class="col-lg-8 col-md-8 col-sm-12">
          <form id="add_quiz_answer_formID" action="post" class="">
          <div class="form-group">
            <h3 class="mtmius1 font21"><?php echo $this->lang->line('question'); ?>: <span id="question_numberID">1</span></h3>
            <input type="hidden" name="quizID" value="<?php echo $singlequestionlist['course_quiz_id']; ?>">            
            <input type="hidden" name="question_id" id="questionID" value="<?php echo $singlequestionlist['id']; ?>">   
            <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">
<?php if (!empty($answerlist['id'])) {?>
    <input type="hidden" name="answerID" value="<?php echo $answerlist['id'] ?>">
<?php }?>
            <div class="qustion"><?php echo $singlequestionlist['question']; ?></div>
          </div>

          <?php if (!empty($singlequestionlist['option_1'])) {
        ?>

          <div class="form-group checkboxes checkbox">
           <label><input type="checkbox" name="answer_1" id="answer_1" class="form-check-input" value="<?php echo $singlequestionlist['option_1']; ?>" <?php echo $checked1; ?>>
           <?php echo $singlequestionlist['option_1']; ?></label>
          </div>

          <?php }?>
           <?php if (!empty($singlequestionlist['option_2'])) {?>

          <div class="form-group checkboxes checkbox">
            <label><input type="checkbox" name="answer_2" id="answer_2" class="form-check-input" value="<?php echo $singlequestionlist['option_2']; ?>" <?php echo $checked2; ?>>
            <?php echo $singlequestionlist['option_2']; ?></label>
          </div>

          <?php }?>
           <?php if (!empty($singlequestionlist['option_3'])) {?>

          <div class="form-group checkboxes checkbox">
           <label><input type="checkbox" name="answer_3" id="answer_3" class="form-check-input" value="<?php echo $singlequestionlist['option_3']; ?>" <?php echo $checked3; ?>>
            <?php echo $singlequestionlist['option_3']; ?></label>
          </div>

          <?php }?>
           <?php if (!empty($singlequestionlist['option_4'])) {?>

          <div class="form-group checkboxes checkbox">
           <label><input type="checkbox" name="answer_4" id="answer_4" class="form-check-input" value="<?php echo $singlequestionlist['option_4']; ?>" <?php echo $checked4; ?>>
            <?php echo $singlequestionlist['option_4']; ?></label>
          </div>

          <?php }?>
           <?php if (!empty($singlequestionlist['option_5'])) {?>
          <div class="form-group form-check checkbox">
            <label> <input type="checkbox" name="answer_5" id="answer_5" class="form-check-input" value="<?php echo $singlequestionlist['option_5']; ?>" <?php echo $checked5; ?>>
            <?php echo $singlequestionlist['option_5']; ?></label>
          </div>

          <?php }?>
      </form>
    </div>
    <div class="col-lg-3 col-lg-offset-1 col-md-3 col-md-offset-1 col-sm-12 col-sm-offset-0">
      <div class="">
        <h3 class="mtmius1 font21"><?php echo $this->lang->line('question_map'); ?></h3>
        <?php
          if (!empty($questionlist)) {
          $questioncount = 1;
          foreach ($questionlist as $questionlist_value) {
              $question_id = $questionlist_value['id'];?>

              <button class="question_click_id btn-sm btn btn-default <?php if (!empty($color[$question_id])) {echo $color[$question_id];} else {echo 'alert-danger';}?>" course-data-id="<?php echo $courseid; ?>" id="question_click_id<?php echo $questionlist_value['id']; ?>" question-data-id="<?php echo $questionlist_value['id']; ?>" increment-data-id="<?php echo $questioncount; ?>" data-id="<?php echo $questionlist_value['course_quiz_id']; ?>"><?php echo $questioncount; ?></button>
            
        <?php $questioncount++;
          }}
          ?>
       
      </div>    
    </div>  
</div> 
</div> 
          <div class="quizfooter">
              <button id="previous_btn_clickID" class="qbtn-previous afterangleremove" data-id="<?php echo $singlequestionlist['course_quiz_id']; ?>" question-data-id="<?php echo $singlequestionlist['id']; ?>"><?php echo $this->lang->line('previous'); ?></button>
              <button id="next_btn_clickID" class="next qbtn-next afterangleremove" question-data-id= "<?php echo $singlequestionlist['course_quiz_id']; ?>" data-id="<?php echo $singlequestionlist['id']; ?>"><?php echo $this->lang->line('save_and_next'); ?></button>
              <button id="submit_btn_clickID" class="next qbtn-next" question-data-id= "<?php echo $singlequestionlist['course_quiz_id']; ?>" data-id="<?php echo $singlequestionlist['id']; ?>"><?php echo $this->lang->line('submit'); ?></button>
          </div>    
   
  </div><!--./row-->
</div>   
<?php }?>

<script>
(function ($) {
  "use strict";

  var count = $('#question_numberID').text();
  if(count == 1){
    $("#previous_btn_clickID").hide();
    $("#submit_btn_clickID").hide();
  }

  var questionscount = '<?php echo $total_questions; ?>';
  if(questionscount == 1){
    $("#next_btn_clickID").hide();
    $("#submit_btn_clickID").show();
  }

  $('#previous_btn_clickID').click(function(){
    var incrementID = $('#question_numberID').text();
    var incrementID = parseInt(incrementID) - 1;
    var quizID = $(this).attr('data-id');
    var questionID = $(this).attr('question-data-id');

    $.ajax({
        url: '<?php echo base_url(); ?>user/studentcourse/create',
        type: 'post',
        data: {quizID:quizID,previousID:questionID},
        success: function(response){
          $('#video_id').html(response);
          $('#question_numberID').html(incrementID);
          if(incrementID == 1){
            $("#previous_btn_clickID").hide();
          }else{
            $("#previous_btn_clickID").show();
          }
          $("#submit_btn_clickID").hide();
        }
    });
  });

  $('#next_btn_clickID').click(function(){
    var questionID = $('#questionID').val();
    var incrementID = $('#question_numberID').text();
    var incrementID = parseInt(incrementID) + 1;
    var questionCount = "<?php echo $total_questions; ?>";
    var formData = new FormData($('#add_quiz_answer_formID')[0]);

    $.ajax({
          url: '<?php echo base_url(); ?>user/studentcourse/create',
          type: 'post',
          data: formData,
          contentType: false,
          processData: false,
          success: function(response){
            $('#video_id').html(response);
            $('#question_numberID').html(incrementID);
            $("#previous_btn_clickID").show();
            if(incrementID < parseInt(questionCount)){
              $("#next_btn_clickID").show();
              $("#submit_btn_clickID").hide();
            }else{
              $("#next_btn_clickID").hide();
              $("#submit_btn_clickID").show();
            }
          }
       });
  });

  $('.question_click_id').click(function(){
      var courseid = $(this).attr('course-data-id');
      var quizID = $(this).attr('data-id');
      var quizquestionID = $(this).attr('question-data-id');
      var incrementID = $(this).attr('increment-data-id');
      var questionCount = "<?php echo $total_questions; ?>";

      $.ajax({
        url : '<?php echo base_url(); ?>user/studentcourse/quizquestion',
        data: {quizID:quizID,quizquestionID:quizquestionID,courseid:courseid},
        type:'post',
        success : function(response){
          $('#video_id').html(response);
          $('#question_numberID').html(incrementID);
          if(incrementID == 1){
            $("#previous_btn_clickID").hide();
          }else{
            $("#previous_btn_clickID").show();
          }
          if(incrementID < parseInt(questionCount)){
              $("#next_btn_clickID").show();
              $("#submit_btn_clickID").hide();
            }else{
              $("#next_btn_clickID").hide();
              $("#submit_btn_clickID").show();
            }
        }
      });
  });

  $('#submit_btn_clickID').click(function(){
    var questionID = $('#questionID').val();
    var incrementID = $('#question_numberID').text();
    var incrementID = parseInt(incrementID) + 1;
    var questionCount = "<?php echo $total_questions; ?>";
    var status = 1;
    var formData = new FormData($('#add_quiz_answer_formID')[0]);
    formData.append('status', status);

    if (confirm("<?php echo $this->lang->line('are_you_sure_you_want_to_submit_your_exam_?'); ?>")) {
      $('#video_id').html('');
      $.ajax({
            url: '<?php echo base_url(); ?>user/studentcourse/getresult',
            type: 'post',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response){
              $('#video_id').html(response);
            }
      });
    }
  });
})(jQuery);
</script>