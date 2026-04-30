 <input type="hidden" name="recordid" value="<?php echo $recordid; ?>">
 <div class="row">
                    <div class="form-group col-md-3">
                    <label for="question_tag"><?php echo $this->lang->line('question_tag'); ?></label><small class="req"> *</small>
                    <select class="form-control select2"  multiple name="question_tag[]" id="question_tag">
                      <option value=""><?php echo $this->lang->line('select'); ?></option>
                        <?php
                           foreach ($gettags as $tag_key => $tag_val){  ?>
                                <option value="<?php echo $tag_val['id']; ?>" 
                            <?php echo set_select('question_tag', $tag_key, (set_value('question_tag', $tag_val['id']) == $question_result->question_tag) ? true : false); ?>>
                            <?php echo $tag_val['tag_name']; ?></option>
                        <?php } ?>
                        </select>
                    <span class="text text-danger question_tag_error"></span>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="question_type"><?php echo $this->lang->line('question_type'); ?></label><small class="req"> *</small>
                        <select class="form-control" name="question_type" id="question_type">
                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                            <?php
foreach ($question_type as $question_type_key => $question_type_value) {
    ?>
    <option value="<?php echo $question_type_key; ?>" <?php echo set_select('question_type', $question_type_key, (set_value('question_type', $question_type_key) == $question_result->question_type) ? true : false); ?>><?php echo $question_type_value; ?></option>
                                <?php
}
?>
                        </select>
                        <span class="text text-danger question_type_error"></span>
                    </div>
                      <div class="form-group col-md-3">
                        <label for="question_level"><?php echo $this->lang->line('question_level'); ?></label><small class="req"> *</small>
                        <select class="form-control" name="question_level">
                       <option value=""><?php echo $this->lang->line('select'); ?></option>
                            <?php
foreach ($question_level as $question_level_key => $question_level_value) {
    ?>
    <option value="<?php echo $question_level_key; ?>" <?php echo set_select('question_level', $question_level_key, (set_value('question_level', $question_level_key) == $question_result->level) ? true : false); ?>><?php echo $question_level_value; ?></option>
                                <?php
}
?>
                        </select>
                        <span class="text text-danger question_level_error"></span>
                    </div>
                    </div>
                    <div class="form-group">
                        <label><?php echo $this->lang->line('question') ?></label><small class="req"> *</small>
                    <button class="btn btn-primary pull-right btn-xs" type="button" id="question" data-toggle="modal" data-location="question" data-target="#myimgModal"><i class="fa fa-plus"></i> <?php echo $this->lang->line('add_image'); ?></button>
                        <textarea class="form-control ckeditor" id="question_textbox" name="question"><?php echo $question_result->question; ?></textarea>
                        <span class="text text-danger question_error"></span>
                    </div>
     <div class="option_list" <?php echo ($question_result->question_type == "multichoice" || $question_result->question_type == "singlechoice" || $question_result->question_type == "") ? "style='display: block;'" : "" ?> >
                    <?php
foreach ($questionOpt as $question_opt_key => $question_opt_value) {
    ?>
                        <div class="form-group">
                            <label for="<?php echo $question_opt_key; ?>"><?php echo $this->lang->line('option_' . $question_opt_value); ?><?php if ($question_opt_value != 'E') {?><small class="req"> *</small><?php }?></label>

   <button class="btn btn-primary pull-right btn-xs" type="button" data-location="<?php echo $question_opt_key; ?>" id="<?php echo $question_opt_key; ?>" data-toggle="modal" data-target="#myimgModal"><i class="fa fa-plus"></i>  <?php echo $this->lang->line('add_image'); ?></button>

                            <textarea class="form-control ckeditor" name="<?php echo $question_opt_key; ?>" id="<?php echo $question_opt_key . "_textbox"; ?>"><?php echo $question_result->{$question_opt_key} ?></textarea>
                            <span class="text text-danger <?php echo $question_opt_key; ?>_error"></span>
                        </div>
                        <?php
}
?>
   </div>
                    <div class="form-group ans" <?php echo ($question_result->question_type == "singlechoice" || $question_result->question_type == "") ? "style='display: block;'" : "" ?> >
                        <label><?php echo $this->lang->line('answer') ?></label><small class="req"> *</small>
                        <select class="form-control" name="correct">
                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                            <?php
foreach ($questionOpt as $question_opt_key => $question_opt_value) {
    ?>
                                <option value="<?php echo $question_opt_key; ?>" <?php echo set_select('correct', $question_opt_key, (set_value('correct', $question_result->correct) == $question_opt_key) ? true : false); ?> ><?php echo $question_opt_value; ?></option>
                                <?php
}
?>
                        </select>
                        <span class="text text-danger correct_error"></span>
                        </div>
                        <div class="form-group ans_true_false" <?php echo ($question_result->question_type == "true_false") ? "style='display: block;'" : "" ?> >
                        <label><?php echo $this->lang->line('answer') ?></label><small class="req"> *</small>
                        <select class="form-control" name="correct_true_false">
                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                            <?php
foreach ($question_true_false as $question_true_false_key => $question_true_false_value) {
    ?>
                                <option value="<?php echo $question_true_false_key; ?>" <?php echo set_select('correct', $question_true_false_key, (set_value('correct', $question_result->correct) == $question_true_false_key) ? true : false); ?> ><?php echo $question_true_false_value; ?></option>
                                <?php
}
?>
                        </select>
                        <span class="text text-danger correct_error"></span>
                    </div>
  <div class="form-group ans_checkbox" <?php echo ($question_result->question_type == "multichoice") ? "style='display: block;'" : "" ?>>
                        <label for="subject_id"><?php echo $this->lang->line('answer') ?></label><small class="req"> *</small>
                        <div>
                        
                        <?php
    foreach ($questionOpt as $question_opt_key => $question_opt_value) {
    ?>
     <label class="checkbox-inline">
      <input type="checkbox" name="ans[]" value="<?php echo $question_opt_key; ?>" <?php echo isJSON($question_result->correct) ? (findarray(json_decode($question_result->correct), $question_opt_key) ? 'checked' : '') : ""; ?>><?php echo $question_opt_value; ?>
    </label>
                                <?php
}
?>
                        </div>
                        <span class="text text-danger ans_error"></span>
                    </div>
                    <?php

function findarray($array, $find)
{

    if (!empty($array)) {
        foreach ($array as $array_key => $array_value) {

            if ($array_value == $find) {
                return true;
            }
        }
        return false;
    }
}
?>

<script>
$(".select2").select2();
</script>