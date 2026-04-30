<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Courseexam extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('course_model', 'coursesection_model', 'courselesson_model', 'studentcourse_model', 'coursequiz_model', 'course_payment_model', 'courseofflinepayment_model', 'coursereport_model','courseassignment_model','courseexam_model'));
        $this->auth->addonchk('ssoclc', site_url('onlinecourse/course/setting'));         
        $this->load->library('media_storage');
        $this->sch_setting_detail = $this->setting_model->getSetting();
    }
     
	public function add_course_exam()
    {		 
        $this->form_validation->set_rules('word_limit', $this->lang->line('answer_word_limit'), 'trim|required|xss_clean|callback_validate_word_limit');
        $this->form_validation->set_rules('exam', $this->lang->line('exam_title'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('attempt', $this->lang->line('attempt'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('exam_from', $this->lang->line('exam_from'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('duration', $this->lang->line('time_duration'), 'trim|required|callback_validate_duration');
        $this->form_validation->set_rules('description', $this->lang->line('description'), 'trim|required');
        $this->form_validation->set_rules('passing_percentage', $this->lang->line('passing_percentage'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('exam_course_id', 'course_id', 'trim|required|xss_clean');
        $this->form_validation->set_rules('exam_section_id', 'section_id', 'trim|required|xss_clean');
		
        if ($this->form_validation->run() == false) {
            $msg = array(
                'word_limit'         => form_error('word_limit'),
                'exam'               => form_error('exam'),
                'attempt'            => form_error('attempt'),
                'exam_from'          => form_error('exam_from'),
                'duration'           => form_error('duration'),
                'description'        => form_error('description'),
                'passing_percentage' => form_error('passing_percentage'),
                'exam_course_id' 	 => form_error('exam_course_id'),
                'exam_section_id' 	 => form_error('exam_section_id'),
            );
            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $is_active          = 0;
            $publish_result     = 0;
            $is_marks_display   = 0;
            $is_neg_marking     = 0;
            $is_random_question = 0;
            $is_quiz            = 0;
            $auto_publish_date  = "";
            if (isset($_POST['is_active'])) {
                $is_active = 1;
            }
            if (isset($_POST['publish_result'])) {
                $publish_result = 1;
            }
            if (isset($_POST['is_marks_display'])) {
                $is_marks_display = 1;
            }
            if (isset($_POST['is_neg_marking'])) {
                $is_neg_marking = 1;
            }
            if (isset($_POST['is_random_question'])) {
                $is_random_question = 1;
            }
            if (isset($_POST['auto_publish_date']) && $_POST['auto_publish_date'] != "") {

                $auto_publish_date = date('Y-m-d H:i:s', $this->customlib->dateTimeformatTwentyfourhour($this->input->post('auto_publish_date'), false));
            } else {
                $auto_publish_date = null;
            }
			
            if (isset($_POST['exam_to']) && $_POST['exam_to'] != "") {

                $exam_to = date('Y-m-d H:i:s', $this->customlib->dateTimeformatTwentyfourhour($this->input->post('exam_to'), false));
            } else {
                $exam_to = null;
            }

            $insert_data = array(
                'answer_word_count'     => $this->input->post('word_limit'),
                'exam'               	=> $this->input->post('exam'),
                'attempt'            	=> $this->input->post('attempt'),
                'exam_from'          	=> date('Y-m-d H:i:s', $this->customlib->dateTimeformatTwentyfourhour($this->input->post('exam_from'), false)),
                'exam_to'            	=> $exam_to,
                'auto_publish_date'  	=> $auto_publish_date,
                'duration'           	=> $this->input->post('duration'),
                'description'        	=> $this->input->post('description'),               
                'is_active'          	=> $is_active,
                'publish_result'     	=> $publish_result,
                'is_marks_display'   	=> $is_marks_display,
                'is_neg_marking'     	=> $is_neg_marking,
                'is_random_question' 	=> $is_random_question,
                'passing_percentage' 	=> $this->input->post('passing_percentage'),
                'course_id' 			=> $this->input->post('exam_course_id'),
                'course_section_id' 			=> $this->input->post('exam_section_id'),
            );
            if (isset($_POST['is_quiz']) && $_POST['is_quiz'] != "") {
                $insert_data['publish_result']    = 0;
                $insert_data['auto_publish_date'] = null;
                $insert_data['is_quiz']           = 1;
            } else {
                $insert_data['is_quiz'] = $is_quiz;
            }

            $id = $this->input->post('exam_id');
            if ($id != 0) {
                $insert_data['id'] = $id;
            }

            $lastid = $this->courseexam_model->add($insert_data);		 
			
            if ($id != 0) {
                $exam_notification = $this->courseexam_model->getexamstatus($id);
                if ($is_active == 1 && $exam_notification['publish_exam_notification'] == 0) {
                     
                    $publish_exam_notification['id']                        = $id;
                    $publish_exam_notification['publish_exam_notification'] = '1';
                    $this->courseexam_model->add($publish_exam_notification);
                }

                if ($publish_result == 1 && $exam_notification['publish_result_notification'] == 0) {
                    $publish_result_notification['id']                          = $id;
                    $publish_result_notification['publish_result_notification'] = '1';
                    $this->courseexam_model->add($publish_result_notification);
                }
            }
			
			if($id==0 || $id==""){
				$orderData = array(
					'type'              => 'exam',
					'course_section_id' => $this->input->post('exam_section_id'),
					'lesson_quiz_id'    => $lastid,
            );
            $this->coursesection_model->addlessonquizorder($orderData);
            }
			$msg   = $this->lang->line('success_message');
            $array = array('status' => 'success', 'error' => '', 'message' => $msg);
        }
        echo json_encode($array);
    }

	public function validate_word_limit($str){
        if ($this->input->post('word_limit') != "") {
            if ($this->input->post('word_limit') == "0") {
                $this->form_validation->set_message('validate_word_limit', '%s '.$this->lang->line('can_not_be_zero'));
                return false;
            }
            return true;
        }
        return true;
    }
	
	public function validate_duration($str)
    {
        if ($this->input->post('duration') != "") {
            if ($this->input->post('duration') != "00:00:00") {
                if (!preg_match('/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $str)) {
                    $this->form_validation->set_message('validate_duration', '%s ' . $this->lang->line('field_must_be'));
                    return false;
                }
            } else {
                $this->form_validation->set_message('validate_duration', '%s ' . $this->lang->line('field_can_not_be'));
                return false;
            }
            return true;
        }
        return true;
    }
	
	 public function delete(){        
        $exam_id = $this->input->post('exam_id');
        if (!empty($exam_id)) {
            // This is used to delete quiz
            $this->coursesection_model->deletequizlesson($exam_id, 'exam');
            $this->courseexam_model->remove($exam_id);
            $arrays = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('delete_message'));
            echo json_encode($arrays);
        } else {
            $arrays = array('status' => 'fail', 'error' => 'some thing went wrong', 'message' => $this->lang->line('delete_message'));
            echo json_encode($arrays);
        }
    }

    public function getexam(){
        $exam_id                    =        $this->input->post('exam_id');
        $getsinglesection           =        $this->courseexam_model->getexam($exam_id);
        $data['from_date']          =        $this->customlib->dateyyyymmddToDateTimeformat($getsinglesection->exam_from, false);
        $data['to_date']            =        $this->customlib->dateyyyymmddToDateTimeformat($getsinglesection->exam_to, false);
        $data['publish_date']       =        $this->customlib->dateyyyymmddToDateTimeformat($getsinglesection->auto_publish_date, false);
        $data['getdata']            =        $getsinglesection;
        if (!empty($data)) {
            echo json_encode($data);
        }
    }

    public function questionAdd(){
        $this->form_validation->set_rules('question_id', $this->lang->line('exam'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('onlineexam_id', $this->lang->line('attempt'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('ques_mark', $this->lang->line('marks'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('ques_neg_mark', $this->lang->line('negative_marks'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $msg = array(
                'question_id'   => form_error('question_id'),
                'online_course_exam_id' => form_error('onlineexam_id'),
                'ques_mark'     => form_error('ques_mark'),
                'ques_neg_mark' => form_error('ques_neg_mark'),
            );
            $array = array('status' => 0, 'error' => $msg, 'message' => '');
        } else {
            $insert_data = array(
                'question_id'   => $this->input->post('question_id'),
                'online_course_exam_id' => $this->input->post('onlineexam_id'),
                'marks'         => $this->input->post('ques_mark'),
                'neg_marks'     => $this->input->post('ques_neg_mark'),
            );
            $this->courseexam_model->insertExamQuestion($insert_data);
            $array = array('status' => 1, 'error' => '', 'message' => $this->lang->line('success_message'));
        }
        echo json_encode($array);
    }

    public function getExamQuestions(){
        $exam_id                  = $this->input->post('recordid');
        $exam                     = $this->courseexam_model->getexam($exam_id);
        $data['exam']             = $exam;
        $data['questions']        = $this->courseexam_model->getExamQuestions($exam_id);
        $data['questiontag']      = $this->courseexam_model->getExamQuestionSubjects($exam_id);
        $data['question_type']    = $this->config->item('question_type');
        $data['question_level']   = $this->config->item('question_level');
        $questionList = $this->load->view('onlinecourse/courseexam/_getexamquestions', $data, true);
        echo json_encode(array('status' => 1, 'result' => $questionList, 'exam' => $exam));
    }

    public function deleteExamQuestions(){
        $this->form_validation->set_rules('question_id', $this->lang->line('question'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $msg = array(
                'question_id' => form_error('question_id'),
            );
            $array = array('status' => 0, 'error' => $msg, 'message' => '');
        } else {
            $this->courseexam_model->remove_question($this->input->post('question_id'));
            $array = array('status' => 1, 'error' => '', 'message' => $this->lang->line('delete_message'));
        }
        echo json_encode($array);
    }

    public function evalution($id){      
        $data=array();
        $data['id']         = $id;
        $onlineexam_questions         = $this->courseexam_model->getByExamNoLimit($id,'descriptive');
        $data['onlineexam_questions'] = $onlineexam_questions;
        $this->load->view('layout/header', $data);
        $this->load->view('onlinecourse/courseexam/evalution', $data);
        $this->load->view('layout/footer', $data);
    }

    public function downloadattachment($id){
        $get_exam_result =  $this->courseexam_model->get_online_course_exam_result($id);
        $this->load->helper('download');
        $filepath = "./uploads/course_content/online_course_exam_result/" . $get_exam_result['attachment_upload_name'];
        $data     = file_get_contents($filepath);
        $name     = $get_exam_result['attachment_upload_name'];
        force_download($name, $data);
    }

    public function getDescQues(){
        $pag_content    = '';
        $pag_navigation = '';

        if (isset($_POST['data']['page'])) {
            $page     = $_POST['data']['page']; /* The page we are currently at */
            $max      = $_POST['data']['post_max']; /* Number of items to display per page */
            $cur_page = $page;
            $page -= 1;
            $per_page     = $max ? $max : 40; 
            $previous_btn = true;
            $next_btn     = true;
            $first_btn    = true;
            $last_btn     = true;
            $start        = $page * $per_page;
            $count        = 0;
            $where_search = array();         

            if (!empty($_POST['data']['question_id'])) {
                $where_search['question_id'] = $_POST['data']['question_id'];
            }

            /* Retrieve all the posts */
            $all_items = $this->courseexam_model->getDescriptionRecord($per_page, $start, $where_search, $_POST['data']['onlineexam_id']);

            /* Check if our query returns anything. */
            if ($all_items) {
                $result              = json_decode($all_items);
                $data['result']      = $result;
                $data['total_row']   = $result->total_row;
                $data['start']       = ($cur_page * $per_page) - $per_page + 1;
                $data['upto']        = ($result->total_row < ($cur_page * $per_page)) ? $result->total_row : ($cur_page * $per_page);
                $data['sch_setting'] = $this->sch_setting_detail;
                $pag_content         = $this->load->view('onlinecourse/courseexam/_getDescQues', $data, true);
                /* If the query returns nothing, we throw an error message */
            } else {
                $pag_content = '';
            }
            $no_of_paginations = ceil($result->total_row / $per_page);

            if ($cur_page >= 7) {
                $start_loop = $cur_page - 3;
                if ($no_of_paginations > $cur_page + 3) {
                    $end_loop = $cur_page + 3;
                } else if ($cur_page <= $no_of_paginations && $cur_page > $no_of_paginations - 6) {
                    $start_loop = $no_of_paginations - 6;
                    $end_loop   = $no_of_paginations;
                } else {
                    $end_loop = $no_of_paginations;
                }
            } else {
                $start_loop = 1;
                if ($no_of_paginations > 7) {
                    $end_loop = 7;
                } else {
                    $end_loop = $no_of_paginations;
                }
            }

            $pag_navigation .= "<ul class='pagination'>";

            if ($first_btn && $cur_page > 1) {
                $pag_navigation .= "<li p='1' class='active_v'><a>" . $this->lang->line('first') . "</a></li>";
            } else if ($first_btn) {
                $pag_navigation .= "<li p='1' class='disabled'><a>" . $this->lang->line('first') . "</a></li>";
            }

            if ($previous_btn && $cur_page > 1) {
                $pre = $cur_page - 1;
                $pag_navigation .= "<li p='$pre' class='active_v'><a>" . $this->lang->line('previous') . "</a></li>";
            } else if ($previous_btn) {
                $pag_navigation .= "<li class='disabled'><a>" . $this->lang->line('previous') . "</a></li>";
            }
            for ($i = $start_loop; $i <= $end_loop; $i++) {

                if ($cur_page == $i) {
                    $pag_navigation .= "<li p='$i' class = 'active' ><a href='#'>{$i}</a></li>";
                } else {
                    $pag_navigation .= "<li p='$i' class='active_v'><a>{$i}</a></li>";
                }
            }
            if ($next_btn && $cur_page < $no_of_paginations) {
                $nex = $cur_page + 1;
                $pag_navigation .= "<li p='$nex' class='active_v'><a>" . $this->lang->line('next') . "</a></li>";
            } else if ($next_btn) {
                $pag_navigation .= "<li class='disabled'><a>" . $this->lang->line('next') . "</a></li>";
            }

            if ($last_btn && $cur_page < $no_of_paginations) {
                $pag_navigation .= "<li p='$no_of_paginations' class='active_v'><a>" . $this->lang->line('last') . "</a></li>";
            } else if ($last_btn) {
                $pag_navigation .= "<li p='$no_of_paginations' class='disabled'><a>" . $this->lang->line('last') . "</a></li>";
            }

            $pag_navigation = $pag_navigation . "</ul>";
        }
        $response = array(
            'content'    => $pag_content,
            'navigation' => $pag_navigation,
        );
        echo json_encode($response);
        exit();
    }

    public function fillmarks(){
        $this->form_validation->set_rules('result_id', $this->lang->line('exam'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('fill_mark', $this->lang->line('marks'), 'trim|xss_clean|callback_validate_marks');
        $this->form_validation->set_rules('question_marks', $this->lang->line('question_marks'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $msg = array(
                'result_id'      => form_error('result_id'),
                'fill_mark'      => form_error('fill_mark'),
                'question_marks' => form_error('question_marks'),
            );
            $array = array('status' => 0, 'error' => $msg, 'message' => '');
        } else {
            $insert_data = array(
                'id'     => $this->input->post('result_id'),
                'marks'  => $this->input->post('fill_mark'),
                'remark' => $this->input->post('remark'),
            );
            $this->courseexam_model->update($insert_data);
            $array = array('status' => 1, 'error' => '', 'message' => $this->lang->line('success_message'));
        }
        echo json_encode($array);
    }

    public function validate_marks($str){
        if (($this->input->post('fill_mark') != "") && ($this->input->post('question_marks') != "")) {
            if (preg_match('/^[+-]?([0-9]*[.])?[0-9]+$/', $str)) {
                if ($this->input->post('question_marks') < $this->input->post('fill_mark')) {
                    $this->form_validation->set_message('validate_marks', 'The %s field must be between 0 and ' . $this->input->post('question_marks'));
                    return false;
                }
                return true;
            } else {
                $this->form_validation->set_message('validate_marks', '%s ' . $this->lang->line('field_can_only_contain_numbers'));
                return false;
            }
        } elseif ($this->input->post('fill_mark') != "") {
            $this->form_validation->set_message('validate_marks', '%s ' . $this->lang->line('field_is_required'));
            return false;
        }
    }



}