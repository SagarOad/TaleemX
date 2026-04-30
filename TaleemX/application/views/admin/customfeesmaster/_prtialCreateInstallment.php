<?php $currency_symbol = $this->customlib->getSchoolCurrencyFormat(); 
$total_installment_amount = 0;?>
<table class="table table-striped table-bordered table-hover  ">		
	<tr>
		<th width="17%"><?php echo $this->lang->line('fees_group'); ?> <small class="req"> *</small></th>		
		<th width="22%"><?php echo $this->lang->line('fees_type'); ?> <small class="req"> *</small></th>		
		<th width="22%"><?php echo $this->lang->line('fees_code'); ?> <small class="req"> *</small></th>		
		<th width="12%"><?php echo $this->lang->line('due_date'); ?> </th>		
		<th width="12%"><?php echo $this->lang->line("fine_amount"); ?> (<?php echo $currency_symbol; ?>)</th>
		<th width="12%"><?php echo $this->lang->line('amount'); ?> (<?php echo $currency_symbol; ?>) <small class="req"> *</small></th>		
	</tr>								
									
<?php	
		
	$installmentamount = $balance_fees / $no_of_installment ;
 
	if($down_payment != ''){			
		$no_of_installment = $no_of_installment+1;			
	}else{			
		$no_of_installment = $no_of_installment;			
	}
	$month=0;	
	for($i = 1; $i <= $no_of_installment; $i++){
	
	if($day != 'none'){
		$due_date	    = date("Y-m-$day", strtotime("+$month month"));
		$fees_due_date  = date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($due_date));
		$month++;
		
	}else{
		$fees_due_date	= '';
	}	

		if($down_payment != '' && $i == 1){			
			$amount = $down_payment;			
		}else{			
			$amount = $installmentamount;			
		} 

		if($fine_type == 'none'){
			$fine_amount	=	0;
		}else if ($fine_type == 'fix'){
			$fine_amount	=	$fine_type_value;
		}else if ($fine_type == 'percentage'){
			$fine_amount	=	( $amount * $fine_type_value ) / 100;
		}
	?>
 
	<tr class="dark-gray">
	
	<?php if($i==1){ ?>
		<td rowspan="<?php echo $no_of_installment;?>">
			<input type="text"  id="group_name_<?php echo $i; ?>" name="group_name_<?php echo $i; ?>" 
			value="<?php echo $group_name; ?>" class="form-control">
		</td>	
	<?php } ?>
		<td>
			<input type="text"  id="fees_type_<?php echo $i; ?>" name="fees_type_<?php echo $i; ?>" 
			value="<?php echo " ".$student_name." - Installment-$i"; ?>" class="form-control">
		</td>	
		<td>
			<input type="text"  id="fees_code_<?php echo $i; ?>" name="fees_code_<?php echo $i; ?>" 
			value="<?php echo " ".$student_name." - Installment-$i"; ?>" class="form-control">
		</td>	
		<td><input type="text" class="form-control date"  id="installmentdate_<?php echo $i; ?>" name="installmentdate_<?php echo $i; ?>" value="<?php echo $fees_due_date; ?>" /> </td>
		<td><input id="fine_type_amount_<?php echo $i; ?>" name="fine_type_amount_<?php echo $i; ?>" class="form-control" type="text" value="<?php echo round($fine_amount,2); ?>" /></td>
		<td><input type="text" class="form-control installmentamount" id="installmentamount_<?php echo $i; ?>" name="installmentamount_<?php echo $i; ?>" value="<?php echo round($amount,2); ?>" /> <span class="text-danger"><?php echo form_error("installmentamount_$i"); ?></span> </td>
	</tr> 

<?php $total_installment_amount = $total_installment_amount + $amount ;} ?>

	<tr class="box box-solid total-bg">
		<th></th>
		<th></th>
		<th></th>
		<th></th>
		<th><?php echo $this->lang->line('total_fees'); ?> <input type="hidden" id="totalinstallmentamount" name="totalinstallmentamount" value="<?php echo $total_installment_amount; ?>"></th>
		<th><?php echo $currency_symbol; ?><span id="total_installment_amount"><?php echo $total_installment_amount; ?></span></th>		
	</tr> 
</table>

<script>
	$(".installmentamount").on("keyup",function(event) {
		var no_of_installment	=	"<?php echo $no_of_installment; ?>";			
		var sum_installment_amount = 0;
		let j;
		for(j= 1; j <= no_of_installment ; j++){
			var  installment_amount	=	$('#installmentamount_'+j).val();			
			var sum_installment_amount 	=	parseFloat(sum_installment_amount) + parseFloat(installment_amount);
		}			
		$('#total_installment_amount').html(parseFloat(sum_installment_amount).toFixed(2));  
		$('#totalinstallmentamount').val(parseFloat(sum_installment_amount).toFixed(2));
		
	});
</script>


