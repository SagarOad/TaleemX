<table class="table table-striped table-bordered table-hover course-list" data-export-title="<?php echo $coursename; ?>">
	<thead>
		<tr>
			<th><?php echo $this->lang->line('student'); ?> / <?php echo $this->lang->line('guest'); ?></th>
			<th><?php echo $this->lang->line('purchase_date'); ?></th>
			<th><?php $currency_symbol = $this->customlib->getSchoolCurrencyFormat(); echo $this->lang->line('price').' ('.$currency_symbol.')'; ?></th>
		</tr>
	</thead>
	<tbody>
	</tbody>
</table> 
<script>
	var courseid = "<?php echo $courseid ; ?>" ;
	( function ( $ ) {
	'use strict';
	$(document).ready(function () {
		initDatatable('course-list','onlinecourse/coursereport/getsalelist/'+courseid,[],[],100);
		 });
	} ( jQuery ) )
</script>