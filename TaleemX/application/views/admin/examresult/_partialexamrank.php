
<?php
$grouped_students = array();
$students         = array();

if (empty($studentList)) {
    ?>
                                                    <?php
} else {
    $count = 1;
    foreach ($studentList as $student_key => $student_value) {
       
        
        $std               = new stdClass;
        $result_status     = 1;
        $no_subject_result = 0;
        $std->exam_group_class_batch_exam_student_id         = $student_value->exam_group_class_batch_exam_student_id;
        $std->rank         = $student_value->rank;
        $std->student_id   = $student_value->student_id;
        $std->admission_no = $student_value->admission_no;
        $std->class = $student_value->class;
        $std->section = $student_value->student_section;
    
        $std->exam_roll_no = ($exam_details->use_exam_roll_no == 0) ? $student_value->roll_no : (($student_value->exam_roll_no != 0) ? $student_value->exam_roll_no : "-");

        $std->student_name = $this->customlib->getFullName($student_value->firstname, $student_value->middlename, $student_value->lastname, $sch_setting->middlename, $sch_setting->lastname);

        if (!empty($subjectList)) {
            $total_marks         = 0;
            $get_marks           = 0;
            $total_percentage    = 0;
            $total_credit_hour   = 0;
            $total_quality_point = 0;
            $student_exam_status = 1;

            foreach ($subjectList as $subject_key => $subject_value) {

                $subject_status = 1;
                $total_marks    = $total_marks + $subject_value->max_marks;

                $result = getSubjectMarks($student_value->subject_results, $subject_value->subject_id);
                if ($result) {
                    $no_subject_result = 1;
                    if ($exam_details->exam_group_type == "gpa") {
                        $get_marks           = $get_marks + $result->get_marks;
                        $subject_credit_hour = $subject_value->credit_hours;
                        $total_credit_hour   = $total_credit_hour + $subject_value->credit_hours;

                        $percentage_grade    = ($result->get_marks * 100) / $result->max_marks;
                        $point               = findGradePoints($exam_grades, $percentage_grade);
                        $total_quality_point = $total_quality_point + ($point * $subject_credit_hour);

                    } else {

                        $get_marks = $get_marks + $result->get_marks;

                        if ($result->get_marks < $subject_value->min_marks) {

                            $student_exam_status = 0;
                        }
                    }
                }
            }

            $std->total_marks         = $total_marks;
            $std->get_marks           = $get_marks;
            $std->total_credit_hour   = $total_credit_hour;
            $std->total_quality_point = $total_quality_point;
            $std->total_percentage    = ($total_marks > 0) ? (($get_marks * 100) / $total_marks) : 0;
            $std->student_exam_status = $student_exam_status;

            if ($exam_details->exam_group_type == "average_passing") {
                $student_exam_status = ($exam_details->passing_percentage > $std->total_percentage) ? 0 : 1;
            }

            $group_key = $std->class . '|' . $std->section;
            if (!isset($grouped_students[$group_key])) {
                $grouped_students[$group_key] = array();
            }
            $grouped_students[$group_key][] = $std;
        }
    }
}
?>

<?php 
if (!empty($grouped_students)) {
    foreach ($grouped_students as $group_key => $group_data) {
        $group_students = $group_data;
        usort($group_students, function ($a, $b) {
            if ($a->total_percentage == $b->total_percentage) {
                return 0;
            }
            return ($a->total_percentage < $b->total_percentage) ? 1 : -1;
        });
        if (!empty($group_students)) {
            $group_rank = 1;
            foreach ($group_students as $group_student_key => $group_student_value) {
                $group_students[$group_student_key]->generated_rank = $group_rank;
                $group_rank++;
            }
            $students = array_merge($students, $group_students);
        }
    }
}

 ?>

<?php 

if(!empty($students)){
if($exam_details->is_rank_generated)
{
 ?>
<div class="alert alert-info" role="alert">
<?php echo $this->lang->line('rank_has_already_generated_you_can_update_rank'); ?>
</div>
            <?php 
}

    ?>

<form method="POST" action="<?php echo site_url('admin/examresult/updaterank'); ?>" class="updaterank">
  <div class="row"> 
    <div class="col-lg-12">
    <input type="hidden" name="exam_group_class_batch_exam_id" value="<?php echo $exam_details->id; ?>">
    <button type="submit" class="btn btn-primary pull-right mb10" data-loading-text="<i class='fa fa-spinner fa-spin '></i> Please wait" data-exam-id="<?php echo $exam_details->id; ?>"><?php echo $this->lang->line('generate_rank'); ?></button>
    </div>
</div>  
 <div class="table-responsive">
      <table class="table table-striped table-bordered table-hover example" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th><?php echo $this->lang->line('admission_no'); ?></th>
                <?php if ($sch_setting->roll_no) { ?>
                <th><?php echo $this->lang->line('roll_number'); ?></th> 
                <?php } ?>               
                <th><?php echo $this->lang->line('class'); ?></th>                
                <th><?php echo $this->lang->line('section'); ?></th>                
                <th><?php echo $this->lang->line('student_name'); ?></th>
                <th><?php echo $this->lang->line('result'); ?></th>
                <?php 
if($exam_details->exam_group_type != "gpa"){
    ?>
  <th><?php echo $this->lang->line('percent') ?> (%)</th>
    <?php
}
                 ?>              
                <th><?php echo $this->lang->line('rank') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
                foreach ($students as $student_f_key => $student_f_value) {  
                            
                   ?>
                   <tr>
                    <input type="hidden" name="exam_group_class_batch_exam_student_id[]" value="<?php echo $student_f_value->exam_group_class_batch_exam_student_id; ?>">
                    <input type="hidden" name="exam_group_class_batch_exam_student_id_<?php echo $student_f_value->exam_group_class_batch_exam_student_id; ?>" value="<?php echo $student_f_value->generated_rank; ?>">
                       <td><?php echo $student_f_value->admission_no; ?> </td>
                        <?php if ($sch_setting->roll_no) { ?>
                        <td><?php echo $student_f_value->exam_roll_no; ?></td>
                        <?php } ?>
                        <td><?php echo $student_f_value->class; ?> </td>
                        <td><?php echo $student_f_value->section; ?> </td>
                       <td><?php echo $student_f_value->student_name; ?>  </td>
                        <td>
                         <?php

                         if($exam_details->exam_group_type == "gpa"){

                if ($student_f_value->total_credit_hour > 0) {

                    $percentage_grade = ($student_f_value->get_marks * 100) / $student_f_value->total_marks;

                    $exam_qulity_point = number_format($student_f_value->total_quality_point / $student_f_value->total_credit_hour, 2, '.', '');
                    echo $student_f_value->total_quality_point . "/" . $student_f_value->total_credit_hour . "=" . $exam_qulity_point . " [" . get_ExamGrade($exam_grades, $percentage_grade) . "]";
                } else {
                    echo "--";
                }
                         }else{

                             echo number_format($student_f_value->get_marks, 2, '.', '') . "/" . number_format($student_f_value->total_marks, 2, '.', ''); 
                         }
                             ?>                                

                            </td>

                                 <?php 
if($exam_details->exam_group_type != "gpa"){
    ?>
   <td>
                        <?php
                            $total_percentage = ($student_f_value->total_marks > 0) ? (($student_f_value->get_marks * 100) / $student_f_value->total_marks) : 0;
                            echo number_format($total_percentage, 2, '.', '');
                        ?>
                       </td>
    <?php
}
                 ?>                      
                       <td>
                           <?php echo $student_f_value->generated_rank; ?>      
                       </td>
                   </tr>

                   <?php
                }
             ?>
            
        </tbody>
</table>
</div>
</form>
    <?php
}else { ?>
<div class="alert alert-info"><?php echo $this->lang->line('no_record_found'); ?></div>
<?php   
    
}

 ?>
                                        <?php

function getSubjectMarks($subject_results, $subject_id)
{
    if (!empty($subject_results)) {
        foreach ($subject_results as $subject_result_key => $subject_result_value) {
            if ($subject_id == $subject_result_value->subject_id) {
                return $subject_result_value;
            }
        }
    }
    return false;
}

function get_ExamGrade($exam_grades, $percentage)
{
    if (!empty($exam_grades)) {
        foreach ($exam_grades as $exam_grade_key => $exam_grade_value) {

            if ($exam_grade_value->mark_from >= $percentage && $exam_grade_value->mark_upto <= $percentage) {
                return $exam_grade_value->name;
            }
        }
    }
    return "-";
}

function findGradePoints($exam_grades, $percentage)
{
    if (!empty($exam_grades)) {
        foreach ($exam_grades as $exam_grade_key => $exam_grade_value) {

            if ($exam_grade_value->mark_from >= $percentage && $exam_grade_value->mark_upto <= $percentage) {
                return $exam_grade_value->point;
            }
        }
    }
    return 0;
}
?>