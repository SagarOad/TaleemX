<?php $currency_symbol = $this->customlib->getSchoolCurrencyFormat(); ?>

	<div class="col-md-12" >
		<div class="alert alert-info" id="setmsg"><?php echo $this->lang->line('note'); ?>: <?php echo $this->lang->line('fee_already_assigned'); ?> </div>
	</div>
	<div class="table-responsive col-md-12"  id="viewinstallment">
        <p class="pull-right">
            <button class="btn btn-default btn-xs displayinline"  type="button" title="<?php echo $this->lang->line('export'); ?>" id="btnExport" onclick="fnExcelReport();"> <i class="fa fa-file-excel-o"></i> </button>
            <button class="btn btn-default btn-xs displayinline"  type="button"  title="<?php echo $this->lang->line('print'); ?>" id="print" onclick="printDiv()" ><i class="fa fa-print"></i></button> </p>
		
		 <table class="table table-striped table-bordered table-hover"  id="headerTable">
			<thead>
				<tr>
					<th ><?php echo $this->lang->line('fees_group'); ?></th>
					<th ><?php echo $this->lang->line('fees_type'); ?></th>
					<th ><?php echo $this->lang->line('fees_code'); ?></th>
					<th ><?php echo $this->lang->line('due_date'); ?></th>
					<th class="text-right"><?php echo $this->lang->line("fine_amount"); ?> (<?php echo $currency_symbol; ?>)</th>                                                    
					<th class="text-right"><?php echo $this->lang->line('amount'); ?> (<?php echo $currency_symbol; ?>)</th>
				</tr>
			</thead>
			<tbody>		
				
				<?php $sl=0; foreach ($feemasterList as $feetype_key => $feetype_value) {	$sl++; ?>	
					<tr>
						<?php if($sl == 1){?>
						<td class="mailbox-name">												 
							<input type="hidden" name="student_session_id" id="student_session_id" value="<?php echo $student_session_id; ?>" /> 
							<input type="hidden" name="fee_groups_id" id="fee_groups_id" value="<?php echo $group->id; ?>" /> 
							<input type="hidden" name="fee_session_groups_id" id="fee_session_groups_id" value="<?php echo $group->fee_session_groups_id; ?>" /> 
							<a href="#" data-toggle="popover" class="detail_popover"><?php echo $group->name; ?></a>
						</td>
						<?php }else{ echo "<td></td>"; } ?>
						<td><?php echo $feetype_value->type; ?></td>
						<td><?php echo $feetype_value->code; ?></td>
						<td><?php if(!empty($feetype_value->due_date)){ echo date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($feetype_value->due_date)); }?></td>
						<td class="text-right"><?php echo amountFormat($feetype_value->fine_amount); ?></td>
						<td class="text-right"><?php echo amountFormat($feetype_value->amount); ?></td>					 
					</tr>
				<?php } ?>
				
			</tbody>
		</table>		 
	</div>	



	