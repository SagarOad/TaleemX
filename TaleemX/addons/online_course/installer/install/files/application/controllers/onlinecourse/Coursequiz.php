<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Coursequiz extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('course_model', 'coursesection_model', 'courselesson_model', 'studentcourse_model', 'coursequiz_model', 'course_payment_model', 'courseofflinepayment_model', 'coursereport_model'));
        $this->auth->addonchk('ssoclc', site_url('onlinecourse/course/setting'));
        $this->load->model('coursequiz_model');
        $this->load->model('course_model');
    }

    /*
    This is used to create quiz
     */
    public function add()
    {
        if (!$this->rbac->hasPrivilege('online_course_quiz', 'can_add')) {
            access_denied();
        }
        $this->form_validation->set_rules('quiz_title', $this->lang->line('quiz_title'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $msg = array(
                'quiz_title' => form_error('quiz_title'),
            );
            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $quizData = array(
                'quiz_title'        => $this->input->post('quiz_title'),
                'course_section_id' => $this->input->post('sectionId'),
                'quiz_instruction'  => $this->input->post('quiz_instruction'),
                'created_date'      => date('Y-m-d h:i:s'),
            );
            // This is used to add quiz
            $lastid = $this->coursequiz_model->add($quizData);

            $orderData = array(
                'type'              => 'quiz',
                'course_section_id' => $this->input->post('sectionId'),
                'lesson_quiz_id'    => $lastid,
            );
            $this->coursesection_model->addlessonquizorder($orderData);

            $updatecourse = array(
                'updated_date' => date("Y-m-d h:i:s"),
                'id'           => $this->input->post('quiz_courseid'),
            );
            $this->course_model->add($updatecourse);
            $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
        }
        echo json_encode($array);
    }

    /*
    This is used to get single quiz list
     */
    public function singlequizlist()
    {
        $quizID           = $this->input->post('quizID');
        $getsinglesection = $this->coursequiz_model->getsinglequiz($quizID);
        if (!empty($getsinglesection)) {
            echo json_encode($getsinglesection);
        }
    }

    /*
    This is used to edit quiz
     */
    public function edit()
    {
        if (!$this->rbac->hasPrivilege('online_course_quiz', 'can_edit')) {
            access_denied();
        }
        $this->form_validation->set_rules('edit_quiz_title', $this->lang->line('quiz_title'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $msg = array(
                'edit_quiz_title' => form_error('edit_quiz_title'),
            );
            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $quizID   = $this->input->post('quizId');
            $quizData = array(
                'id'                => $quizID,
                'quiz_title'        => $this->input->post('edit_quiz_title'),
                'course_section_id' => $this->input->post('edit_sectionId'),
                'quiz_instruction'  => $this->input->post('edit_quiz_instruction'),
            );
            // This is used to edit quiz
            $this->coursequiz_model->add($quizData);
            $updatecourse = array(
                'updated_date' => date("Y-m-d h:i:s"),
                'id'           => $this->input->post('edit_quiz_course'),
            );
            $this->course_model->add($updatecourse);
            $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
        }
        echo json_encode($array);
    }

    /*
    This is used to delete quiz
     */
    public function delete()
    {
        if (!$this->rbac->hasPrivilege('online_course_quiz', 'can_delete')) {
            access_denied();
        }
        $quizID = $this->input->post('quizID');
        if (!empty($quizID)) {
            // This is used to delete quiz
            $this->coursesection_model->deletequizlesson($quizID, 'quiz');
            $this->coursequiz_model->remove($quizID);
            $arrays = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('delete_message'));
            echo json_encode($arrays);
        } else {
            $arrays = array('status' => 'fail', 'error' => 'some thing went wrong', 'message' => $this->lang->line('delete_message'));
            echo json_encode($arrays);
        }
    }

    /*
    This is used to add new question for quiz
     */
    public function addnewquestion()
    {
        if (!$this->rbac->hasPrivilege('online_course_quiz', 'can_add')) {
            access_denied();
        }
        $quiz_id            = $this->input->post('quiz_id');
        $question           = $this->input->post('title_question');
        $question_count     = $this->input->post('question_count');
        $question_course_id = $this->input->post('question_course_id');
        $atleastone         = $this->input->post('question_0');

        if ($question_count > '-1') {

            $validatequestion = 1;
            $optionsfield     = 1;
            $resultfield      = 1;
            $result           = '';
            for ($count = 0; $count <= $question_count; $count++) {
                $questionvalidation = $this->input->post('question_' . $count);
                $option0            = $this->input->post("question_" . $count . "_options_0");
                $option1            = $this->input->post("question_" . $count . "_options_1");
                $questionresult     = $this->input->post("question_" . $count . "_result_" . $count);

                if ($option0 == '') {
                    $optionsfield = 0;
                }

                if ($option1 == '') {
                    $optionsfield = 0;
                }

                if (empty($questionresult)) {
                    $resultfield = 0;
                }

                if ($questionvalidation == '') {
                    $validatequestion = 0;
                }
            }

            if ($validatequestion == 0 && $optionsfield != 0 && $resultfield != 0) {
                $msg   = array('question' => $this->lang->line('question_field_is_required'));
                $array = array('status' => 'fail', 'error' => $msg, 'message' => '');

            } elseif ($validatequestion != 0 && $optionsfield == 0 && $resultfield != 0) {
                $msg   = array('option' => $this->lang->line('option_field_is_required'));
                $array = array('status' => 'fail', 'error' => $msg, 'message' => '');

            } elseif ($validatequestion != 0 && $optionsfield != 0 && $resultfield == 0) {
                $msg   = array('result' => $this->lang->line('please_select_correct_answer'));
                $array = array('status' => 'fail', 'error' => $msg, 'message' => '');

            } elseif ($validatequestion == 0 && $optionsfield == 0 && $resultfield != 0) {
                $msg = array(
                    'question' => $this->lang->line('question_field_is_required').'<br>',
                    'option'   => $this->lang->line('option_field_is_required'),
                );
                $array = array('status' => 'fail', 'error' => $msg, 'message' => '');

            } elseif ($validatequestion != 0 && $optionsfield == 0 && $resultfield == 0) {
                $msg = array(
                    'option' => $this->lang->line('option_field_is_required').'<br>',
                    'result' => $this->lang->line('please_select_correct_answer'),
                );
                $array = array('status' => 'fail', 'error' => $msg, 'message' => '');

            } elseif ($validatequestion == 0 && $optionsfield != 0 && $resultfield == 0) {
                $msg = array(
                    'question' => $this->lang->line('question_field_is_required').'<br>',
                    'result'   => $this->lang->line('please_select_correct_answer'),
                );
                $array = array('status' => 'fail', 'error' => $msg, 'message' => '');

            } elseif ($optionsfield == 0 && $validatequestion == 0 && $resultfield == 0) {
                $msg = array(
                    'question' => $this->lang->line('question_field_is_required').'<br>',
                    'option'   => $this->lang->line('option_field_is_required').'<br>',
                    'result'   => $this->lang->line('please_select_correct_answer'),
                );
                $array = array('status' => 'fail', 'error' => $msg, 'message' => '');

            } else {
                for ($count = 0; $count <= $question_count; $count++) {
                    $data['question'] = $this->input->post('question_' . $count);
                    $questionresult   = $this->input->post("question_" . $count . "_result_" . $count);
                    if (!empty($questionresult)) {
                        $checkBox               = implode(',', $questionresult);
                        $data['correct_answer'] = $checkBox;
                    }
                    $data['course_quiz_id'] = $quiz_id;

                    for ($count_option = 0; $count_option <= 4; $count_option++) {
                        $new_option                    = $count_option + 1;
                        $data['option_' . $new_option] = $this->input->post("question_" . $count . "_options_" . $count_option);
                    }
                    $this->coursequiz_model->addquizquestion($data);
                }

                $updatecourse = array(
                    'updated_date' => date("Y-m-d h:i:s"),
                    'id'           => $this->input->post('question_course_id'),
                );
                $this->course_model->add($updatecourse);

                $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
            }
        } else {
            $msg = array(
                'required' => $this->lang->line('atleast_one_question_field_is_required'),
            );
            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        }
        echo json_encode($array);
    }

    /*
    This is used to load edit quiz question model
     */
    public function getquestion()
    {
        $quiz_id              = $this->input->post('quiz_id');
        $questionlist         = $this->coursequiz_model->questionlist($quiz_id);
        $data['questionlist'] = $questionlist;
        $data['all_options']  = array('option_1', 'option_2', 'option_3', 'option_4', 'option_5');
        $this->load->view('onlinecourse/quiz/editquizquestion', $data);
    }

    /*
    This is used to update edit quiz question
     */
    public function editnewquestion()
    {
        if (!$this->rbac->hasPrivilege('online_course_quiz', 'can_edit')) {
            access_denied();
        }
        $questioncount    = $this->input->post('questioncount');
        $validatequestion = 1;
        $optionsfield     = 1;
        $resultfield      = 1;
        $result           = '';
        if ($questioncount > 0) {
            for ($count = 1; $count <= $questioncount; $count++) {
                $questionvalidation = $this->input->post('question_' . $count);
                $option0            = $this->input->post("question_" . $count . "_option_0");
                $option1            = $this->input->post("question_" . $count . "_option_1");
                $questionresult     = $this->input->post("question_" . $count . "_result_" . $count);

                if ($option0 == '') {
                    $optionsfield = 0;
                }
                if ($option1 == '') {
                    $optionsfield = 0;
                }
                if (empty($questionresult)) {
                    $resultfield = 0;
                }
                if ($questionvalidation == '') {
                    $validatequestion = 0;
                }
            }

            if ($validatequestion == 0 && $optionsfield != 0 && $resultfield != 0) {
                $msg   = array('question' => $this->lang->line('question_field_is_required'));
                $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
            } elseif ($validatequestion != 0 && $optionsfield == 0 && $resultfield != 0) {
                $msg   = array('option' => $this->lang->line('option_field_is_required'));
                $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
            } elseif ($validatequestion != 0 && $optionsfield != 0 && $resultfield == 0) {
                $msg   = array('result' => $this->lang->line('please_select_correct_answer'));
                $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
            } elseif ($validatequestion == 0 && $optionsfield == 0 && $resultfield != 0) {
                $msg = array(
                    'question' => $this->lang->line('question_field_is_required'),
                    'option'   => $this->lang->line('option_field_is_required'),
                );
                $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
            } elseif ($validatequestion != 0 && $optionsfield == 0 && $resultfield == 0) {
                $msg = array(
                    'option' => $this->lang->line('option_field_is_required'),
                    'result' => $this->lang->line('please_select_correct_answer'),
                );
                $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
            } elseif ($validatequestion == 0 && $optionsfield != 0 && $resultfield == 0) {
                $msg = array(
                    'question' => $this->lang->line('question_field_is_required'),
                    'result'   => $this->lang->line('please_select_correct_answer'),
                );
                $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
            } elseif ($optionsfield == 0 && $validatequestion == 0 && $resultfield == 0) {
                $msg = array(
                    'question' => $this->lang->line('question_field_is_required'),
                    'option'   => $this->lang->line('option_field_is_required'),
                    'result'   => $this->lang->line('please_select_correct_answer'),
                );
                $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
            } else {
                $quizcount = count($this->coursequiz_model->questionlist($this->input->post('quiz_id')));
                for ($question = 1; $question <= $questioncount; $question++) {

                    $data['question']       = $this->input->post('question_' . $question);
                    $data['course_quiz_id'] = $this->input->post('quiz_id');

                    if ($question <= $quizcount) {
                        $id = $this->input->post('question_id_' . $question);
                    } else {
                        $id = 0;
                    }
                    for ($option = 0; $option <= 4; $option++) {
                        $new_option                    = $option + 1;
                        $data['option_' . $new_option] = $this->input->post('question_' . $question . '_option_' . $option);
                    }
                    $questionresult = $this->input->post("question_" . $question . "_result_" . $question);
                    if (!empty($questionresult)) {
                        $checkBox               = implode(',', $questionresult);
                        $data['correct_answer'] = $checkBox;
                    }
                    if ($id == 0) {
                        $data['id'] = '';
                        $this->coursequiz_model->addquizquestion($data);
                    } else {
                        $data['id'] = $id;
                        $this->coursequiz_model->addquizquestion($data);
                    }
                }

                $deleted             = $this->input->post('deleted');
                $deleted_question_id = (explode(",", $deleted));

                foreach ($deleted_question_id as $i => $deleted_question_id) {
                    $this->coursequiz_model->removequestion($deleted_question_id);
                }

                $updatecourse = array(
                    'updated_date' => date("Y-m-d h:i:s"),
                    'id'           => $this->input->post('editquestion_course_id'),
                );
                $this->course_model->add($updatecourse);

                $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
            }} else {
            $msg = array(
                'required' => $this->lang->line('atleast_one_question_field_is_required'),
            );
            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        }

        echo json_encode($array);
    }

    /*
    This is used to delete quiz question
     */
    public function deletequizquestion()
    {
        if (!$this->rbac->hasPrivilege('online_course_quiz', 'can_delete')) {
            access_denied();
        }
        $quizmanageID = $this->input->post('quizmanageID');
        if (!empty($quizmanageID)) {
            // This is used to delete question
            $this->coursequiz_model->removequizquestion($quizmanageID);
            $arrays = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('delete_message'));
            echo json_encode($arrays);
        } else {
            $arrays = array('status' => 'fail', 'error' => 'some thing went wrong', 'message' => $this->lang->line('delete_message'));
            echo json_encode($arrays);
        }
    }

    /*
    This is used to delete options of question for edit question case
     */
    public function deleteoption()
    {
        if (!$this->rbac->hasPrivilege('online_course_quiz', 'can_delete')) {
            access_denied();
        }
        $questionid                  = $this->input->post('questionid');
        $optionID                    = $this->input->post('optionID');
        $data['id']                  = $questionid;
        $data['option_' . $optionID] = '';
        $this->coursequiz_model->addquizquestion($data);
        $arrays = array('status' => '1');
        echo json_encode($arrays);
    }
}
