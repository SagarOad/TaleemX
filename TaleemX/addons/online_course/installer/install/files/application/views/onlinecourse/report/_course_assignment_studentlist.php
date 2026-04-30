<?php 
if (empty($get_course_student_list) && empty($get_course_guest_list)) {  ?>
   <div class="alert alert-info"><?php echo $this->lang->line('no_record_found'); ?></div>
<?php } else { ?>
    <div class="table-responsive" id="myTable">
    <div class="download_label"><?php echo $this->lang->line('student_list'); ?></div>
     <table id="headerTable" class="table table-striped table-bordered table-hover example " id="ViewData">
        <thead>
            <tr>
                <th><?php echo $this->lang->line('student'); ?>/<?php echo $this->lang->line('guest'); ?></th>
                <th class="text-center"><?php echo $this->lang->line('submitted'); ?></th>
                <th class="text-center"><?php echo $this->lang->line('submitted_date'); ?></th>
                <th class="text-center"><?php echo $this->lang->line('evaluated'); ?></th> 
                <th class="text-center"><?php echo $this->lang->line('evaluated_date'); ?></th> 
            </tr>
        </thead>
   <tbody>
   <?php foreach ($get_course_student_list as $student_key => $student) { ?>
    <tr>
        <td><?php  echo $this->customlib->getFullName($student['firstname'],$student['middlename'],$student['lastname'],$sch_setting->middlename, $sch_setting->lastname)." (".$this->lang->line('student')." - ".$student['admission_no'].") ";  ?></td>
        <td class="text-center"><?php if(isset($student['submit_date'])){ echo "<i class='fa fa-check'></li>";}else{ echo '<i class="fa fa-exclamation-circle" aria-hidden="true"></i>';}; ?></td>
        <td class="text-center"><?php if(isset($student['submit_date'])){ echo  date($this->customlib->getSchoolDateFormat(), strtotime($student['submit_date']));;} ?></td>
        <td class="text-center"><?php if(isset($student['is_evaluated'])){ echo "<i class='fa fa-check'></li>";}else{ echo '<i class="fa fa-exclamation-circle" aria-hidden="true"></i>';}; ?></td>
        <td class="text-center"><?php if(isset($student['evaluate_date'])){ echo  date($this->customlib->getSchoolDateFormat(), strtotime($student['evaluate_date']));;} ?></td>
        </tr>
     <?php   }  ?>
    <?php foreach ($get_course_guest_list as $guest_key => $guest) { ?>
    <tr>
        <td><?php echo $guest['guest_name']." (".$this->lang->line('guest')." - ".$guest['guest_unique_id'].")"; ?></td>
        <td class="text-center"><?php if(isset($guest['submit_date'])){ echo "<i class='fa fa-check'></li>";}else{ echo '<i class="fa fa-exclamation-circle" aria-hidden="true"></i>';}; ?></td>
        <td class="text-center"><?php if(isset($guest['submit_date'])){ echo  date($this->customlib->getSchoolDateFormat(), strtotime($guest['submit_date']));;} ?></td>
        <td class="text-center"><?php if(isset($guest['is_evaluated'])){ echo "<i class='fa fa-check'></li>";}else{ echo '<i class="fa fa-exclamation-circle" aria-hidden="true"></i>';}; ?></td>
        <td class="text-center"><?php if(isset($guest['evaluate_date'])){ echo  date($this->customlib->getSchoolDateFormat(), strtotime($guest['evaluate_date']));;} ?></td>
        </tr>
     <?php   }  ?>
    </tbody>
</table>
</div>
<?php
}
?>
