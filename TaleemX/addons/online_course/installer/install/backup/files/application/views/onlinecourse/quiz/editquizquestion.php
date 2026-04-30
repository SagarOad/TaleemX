<table id="edit_table_id" class="table">
    <tbody>
		<?php
if (!empty($questionlist)) {
    foreach ($questionlist as $key => $questionlist_value) {
        $key = $key + 1;
        ?>
		<tr  id="rowIDedit<?php echo $key; ?>">
			<td class="border0 pl0" width="75"><?php echo $this->lang->line('question'); ?> <small class="req"> *</small></td>
			<td class="pr0 border0 relative">
				<input type='text' name='question_<?php echo $key; ?>' class="form-control pull-left" value="<?php echo $questionlist_value['question']; ?>">
				<input type="hidden" id="question_id_<?php echo $key; ?>" name="question_id_<?php echo $key; ?>" value="<?php echo $questionlist_value['id']; ?>" >
				<input type="hidden" id="quiz_id" name="quiz_id" value="<?php echo $questionlist_value['course_quiz_id']; ?>" >
				<button type='button' data-toggle='tooltip' data-original-title='<?php echo $this->lang->line('delete_question'); ?>' data-placement="left" data-id='<?php echo $key; ?>' class='delete-edit-row addclose quizplusright'><i class='fa fa-remove'></i></button>
			</td>
		</tr>
		<tr class='optionsedit<?php echo $key; ?>'>
			<td colspan="2" class="pr0 border0 quizopationpad-left">
			<table width="100%" align="right">
				<tr>
					<td width="30">A <small class="req"> *</small></td>
					<td>
						<div class="input-group input-group-full-width">
						<input type='text' name='question_<?php echo $key; ?>_option_0' value="<?php echo $questionlist_value['option_1']; ?>" class="form-control">
						<span class="input-group-addon input-group-addon-bg"><input <?php if (strpos($questionlist_value['correct_answer'], 'option_1') !== false) {echo "checked";}?> type='checkbox'  title='<?php echo $this->lang->line('check_for_correct_option'); ?>' value="option_1" name='question_<?php echo $key; ?>_result_<?php echo $key; ?>[]'></span>
					</div>
					</td>
				</tr>
				<tr>
					<td>B <small class="req"> *</small></td>
					<td>
						<div class="input-group input-group-full-width">
						<input type='text' name='question_<?php echo $key; ?>_option_1' value="<?php echo $questionlist_value['option_2']; ?>" class="form-control">
						<span class="input-group-addon input-group-addon-bg"><input <?php if (strpos($questionlist_value['correct_answer'], 'option_2') !== false) {echo "checked";}?> type='checkbox'  title='<?php echo $this->lang->line('check_for_correct_option'); ?>' value="option_2" name='question_<?php echo $key; ?>_result_<?php echo $key; ?>[]'></span>
					</div>
					</td>
				</tr>
				<tr>
					<td>C</td>
					<td>
						<div class="input-group input-group-full-width">
						<input type='text' name='question_<?php echo $key; ?>_option_2' value="<?php echo $questionlist_value['option_3']; ?>" class="form-control">
						<span class="input-group-addon input-group-addon-bg"><input <?php if (strpos($questionlist_value['correct_answer'], 'option_3') !== false) {echo "checked";}?> type='checkbox'  title='<?php echo $this->lang->line('check_for_correct_option'); ?>' value="option_3" name='question_<?php echo $key; ?>_result_<?php echo $key; ?>[]'></span>
						</div>
					</td>
				</tr>
				<tr>
					<td>D</td>
					<td>
						<div class="input-group input-group-full-width">
						<input type='text' name='question_<?php echo $key; ?>_option_3' value="<?php echo $questionlist_value['option_4']; ?>" class="form-control">
						<span class="input-group-addon input-group-addon-bg"><input <?php if (strpos($questionlist_value['correct_answer'], 'option_4') !== false) {echo "checked";}?> type='checkbox'  title='<?php echo $this->lang->line('check_for_correct_option'); ?>' value="option_4" name='question_<?php echo $key; ?>_result_<?php echo $key; ?>[]'></span>
					</div>
					</td>
				</tr>
				<tr>
					<td>E</td>
					<td>
						<div class="input-group input-group-full-width">
						<input type='text' name='question_<?php echo $key; ?>_option_4' value="<?php echo $questionlist_value['option_5']; ?>" class="form-control">
						<span class="input-group-addon input-group-addon-bg"><input <?php if (strpos($questionlist_value['correct_answer'], 'option_5') !== false) {echo "checked";}?> type='checkbox'  title='<?php echo $this->lang->line('check_for_correct_option'); ?>' value="option_5" name='question_<?php echo $key; ?>_result_<?php echo $key; ?>[]'></span>
					</div>
					</td>
				</tr>
			</table>
			</td>
		</tr>
			<?php }}?>
		<input type="hidden" value="<?php if (!empty($key)) {echo $key;}?>" id="questioncount" name="questioncount" >
		<input type="hidden" value="" id="deleted" name="deleted" >
	</tbody>
</table>