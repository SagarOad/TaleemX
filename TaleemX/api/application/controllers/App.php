<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();

        $this->load->model('student_model');
        $this->load->model('event_model');
    }

    public function index()
    {
        json_output(200, array(
            'status' => 200,
            'message' => 'App endpoint is reachable.',
            'timestamp' => date('c'),
        ));
    }

    public function index1()
    {        
        $student_id = 2;
        $student    = $this->student_model->get($student_id);
        $examList   = $this->examschedule_model->getExamByClassandSection($student['class_id'], $student['section_id']);
        $response   = array();
        if (!empty($examList)) {
            $new_array = array();
            foreach ($examList as $ex_key => $ex_value) {
                $array   = array();
                $x       = array();
                $exam_id = $ex_value['exam_id'];
                $student['id'];
                $exam_subjects = $this->examschedule_model->getresultByStudentandExam($exam_id, $student['id']);
                $total_marks   = 0;
                $get_marks     = 0;
                $result        = "Pass";

                foreach ($exam_subjects as $key => $value) {

                    $total_marks = $total_marks + $value['full_marks'];
                    $get_marks   = $get_marks + $value['get_marks'];

                    if (($value['get_marks'] < $value['passing_marks']) || ($value['attendence'] != 'pre')) {
                        $result = 'Fail';
                    }
                }

                $exam_result              = new stdClass();
                $exam_result->total_marks = $total_marks;
                $exam_result->get_marks   = $get_marks;
                $exam_result->percentage  = number_format((($get_marks * 100) / $total_marks), 2) . '%';
                $exam_result->grade       = $this->getGradeByMarks($get_marks);
                $exam_result->result      = $result;
                $array['exam_name']       = $ex_value['name'];
                $array['exam_result']     = $exam_result;
                $new_array[]              = $array;
            }
            $response = $new_array;
        }
    }

    public function getGradeByMarks($marks = 0)
    {
        $gradeList = $this->grade_model->get();

        if (empty($gradeList)) {
            return "empty list";
        } else {
            foreach ($gradeList as $grade_key => $grade_value) {
                if ($marks >= $grade_value['mark_from'] && $marks <= $grade_value['mark_upto']) {
                    return $grade_value['name'];
                }
            }
            return "no record found";
        }
    }

}
