<script src="<?php echo base_url() ?>backend/plugins/ckeditor/plugins/ckeditor_wiris/integration/WIRISplugins.js?viewer=image"></script>

<style type="text/css">
.inpwidth40{width: 50px;height: 20px;}
</style>

<?php 
if (!empty($questions)) { ?>
        <ul class="nav nav-pills tag_pills pull-right mb10">
          <li class="active"><a href="#" data-subject-id="0"><?php echo $this->lang->line('all'); ?></a></li>
          <?php
foreach ($questiontag as $questiontag_key => $questiontag_value) {
        ?>
<li><a href="#" data-subject-id="<?php echo $questiontag_value->tag_id; ?>"><?php echo $questiontag_value->tag_name; ?></a></li>
<?php
}
    ?>
    </ul>
<?php
foreach ($questions as $question_key => $question_value) { ?>

<div class="question_row_<?php echo $question_value->exam_question_marks_id; ?> tag_div_<?php echo $question_value->question_tag; ?>" >
                <div class="">
                    <div class="">
                         <div class="text text-danger float-left float-rtl-right clear-initial">
							<?php if($this->rbac->hasPrivilege('online_course_add_questions_in_exam', 'can_delete')) {?> 
							<button type="button" class="btn btn-primary btn-xs del_exam_question" data-original-title="<?php echo $this->lang->line('delete') ?>" data-toggle="modal" data-target="#mydeleteModal" data-exam-id="<?php echo $exam->exam; ?>" data-onlineexam-question-id="<?php echo $question_value->exam_question_marks_id; ?>">
                            <i class="fa fa-trash" aria-hidden="true"></i></button>
							<?php } ?>
                        </div>
                       <div class="rltpaddleft">
                         <span class="font-weight-bold"> <?php echo $this->lang->line('q_id') ?> : <?php echo $question_value->id; ?> </span>
                         <br/>
                         <?php echo $question_value->question; ?>
                       <div class="pt5">
                            <div class="row">
                                 <div class="col-lg-2 col-md-6 col-sm-12">
                                    <label for="email"><?php echo $this->lang->line('question_type'); ?>:</label>
                                        <?php echo ($question_value->question_type != "") ? $question_type[$question_value->question_type] : ""; ?>
                                </div>
                                <div class="col-lg-2 col-md-6 col-sm-12">
                                    <label for="email"><?php echo $this->lang->line('level'); ?>:</label>
                                        <?php echo ($question_value->level != "") ? $question_level[$question_value->level] : ""; ?>
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <label for="email"><?php echo $this->lang->line('tag_name'); ?>:</label>
                                        <?php echo $question_value->tag_name; ?>
                                </div>
                            </div>
                          </div>
                     </div>
                    </div>
                </div>
                <div class="hrexamtopbottom"></div>
            </div>
    <?php
}

} else {
    ?>
    <div class="alert alert-info"><?php echo $this->lang->line('no_record_found'); ?></div>
<?php
}
?>
</form>