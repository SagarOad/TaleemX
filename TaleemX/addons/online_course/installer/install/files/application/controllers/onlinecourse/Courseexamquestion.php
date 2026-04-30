<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Courseexamquestion extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('courseexamquestion_model');
        $this->load->model('coursequiz_model');
        $this->load->model('course_model');
        $this->load->model('class_model');
        $this->load->model('cms_media_model');
        $this->load->model('coursetag_model');
        $this->load->library('media_storage');

        $config = array(
            'field' => 'slug',
            'title' => 'title',
            'table' => 'online_courses',
            'id'    => 'id',
        );
        $this->load->library('slug', $config);
        $this->sch_setting_detail = $this->setting_model->getSetting();
    }


    public function index($offset = 0){
        $data                   = array();
        $class                  = $this->class_model->get();
        $data['classlist']      = $class;         
        $data['question_type']  = $this->config->item('question_type');
        $data['question_level'] = $this->config->item('question_level');
        $questionList               = $this->courseexamquestion_model->getquestioncreatedstaff();
        $data['staff_list']         = $questionList;
        $question_tag               = $this->coursetag_model->gettags();
        $data['question_tag']       = $question_tag;
        $this->load->view('layout/header', $data);
        $this->load->view('onlinecourse/courseexamquestion/index', $data);
        $this->load->view('layout/footer', $data);
    }

    public function addform(){
        $data                        = array();
        $data['classList']           = $this->class_model->get();
        $subject_result              = $this->subject_model->get();
        $data['subjectlist']         = $subject_result;
        $data['question_true_false'] = $this->config->item('question_true_false');
        $data['question_type']       = $this->config->item('question_type');
        $data['question_level']      = $this->config->item('question_level');
        $questionOpt                 = $this->customlib->getQuesOption();
        $data['questionOpt']         = $questionOpt;
        $data['recordid']            = $this->input->post('recordid');
        $gettags                     = $this->coursetag_model->gettags();
        $data['gettags']             = $gettags;
        $page                        = $this->load->view('onlinecourse/courseexamquestion/addform_modal', $data, true);
        echo json_encode(array('status' => 1, 'page' => $page));
    }

	public function editform(){
        $data                        = array();
        $data['recordid']            = $this->input->post('recordid');
        $question_result             = $this->courseexamquestion_model->get($data['recordid']);
        $data['question_result']     = $question_result;
        $data['question_true_false'] = $this->config->item('question_true_false');
        $data['question_type']       = $this->config->item('question_type');
        $data['question_level']      = $this->config->item('question_level');
        $questionOpt                 = $this->customlib->getQuesOption();
        $data['questionOpt']         = $questionOpt;     
        $gettags                     = $this->coursetag_model->gettags();
        $data['gettags']             = $gettags;
        $page = $this->load->view('onlinecourse/courseexamquestion/editform_modal', $data, true);
        echo json_encode(array('status' => 1, 'page' => $page));
	}

    public function add(){
        $this->form_validation->set_rules('question_tag[]', $this->lang->line('question_tag'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('question', $this->lang->line('question'), 'trim|required');
        $this->form_validation->set_rules('question_type', $this->lang->line('question_type'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('question_level', $this->lang->line('question_level'), 'trim|required|xss_clean');
       
        if ($this->input->post('question_type') == "singlechoice") {
            $this->form_validation->set_rules('opt_a', $this->lang->line('option_a'), 'trim|required');
            $this->form_validation->set_rules('opt_b', $this->lang->line('option_b'), 'trim|required');
            $this->form_validation->set_rules('correct', $this->lang->line('answer'), 'trim|required');
        } elseif ($this->input->post('question_type') == "true_false") {
            $this->form_validation->set_rules('correct_true_false', $this->lang->line('answer'), 'trim|required|xss_clean');
        } elseif ($this->input->post('question_type') == "multichoice") {
            $this->form_validation->set_rules('opt_a', $this->lang->line('option_a'), 'trim|required');
            $this->form_validation->set_rules('opt_b', $this->lang->line('option_b'), 'trim|required');
            $this->form_validation->set_rules('ans[]', $this->lang->line('answer'), 'trim|required|xss_clean');
        }

        if ($this->form_validation->run() == false) {

            $msg = array(              
                'question'       => form_error('question'),
                'question_type'  => form_error('question_type'),
                'question_level' => form_error('question_level') ,             
                'question_tag' => form_error('question_tag[]'),              
            );

            if ($this->input->post('question_type') == "singlechoice") {
                $msg['opt_a']   = form_error('opt_a');
                $msg['opt_b']   = form_error('opt_b');
                $msg['correct'] = form_error('correct');
            } elseif ($this->input->post('question_type') == "true_false") {
                $msg['correct_true_false'] = form_error('correct_true_false');
            } elseif ($this->input->post('question_type') == "multichoice") {
                $msg['opt_a'] = form_error('opt_a');
                $msg['opt_b'] = form_error('opt_b');
                $msg['ans']   = form_error('ans[]');
            }

            $array = array('status' => 0, 'error' => $msg, 'message' => '');
        } else {

            $question_tag=$this->input->post('question_tag[]');

            foreach($question_tag as $key=>$value){
            $tag_id=$value;
            $insert_data = array(
                'question_tag'   => $value,
                'question'      => $this->input->post('question'),
                'question_type' => $this->input->post('question_type'),
                'level'         => $this->input->post('question_level'),               
                'staff_id'      => $this->customlib->getStaffID(),
            );           

            if ($this->input->post('question_type') == "singlechoice") {
                $insert_data['opt_a']   = $this->input->post('opt_a');
                $insert_data['opt_b']   = $this->input->post('opt_b');
                $insert_data['opt_c']   = $this->input->post('opt_c');
                $insert_data['opt_d']   = $this->input->post('opt_d');
                $insert_data['opt_e']   = $this->input->post('opt_e');
                $insert_data['correct'] = $this->input->post('correct');
            } elseif ($this->input->post('question_type') == "true_false") {
                $insert_data['opt_a']   = "";
                $insert_data['opt_b']   = "";
                $insert_data['opt_c']   = "";
                $insert_data['opt_d']   = "";
                $insert_data['opt_e']   = "";
                $insert_data['correct'] = $this->input->post('correct_true_false');
            } elseif ($this->input->post('question_type') == "multichoice") {
                $insert_data['opt_a']   = $this->input->post('opt_a');
                $insert_data['opt_b']   = $this->input->post('opt_b');
                $insert_data['opt_c']   = $this->input->post('opt_c');
                $insert_data['opt_d']   = $this->input->post('opt_d');
                $insert_data['opt_e']   = $this->input->post('opt_e');
                $insert_data['correct'] = json_encode($this->input->post('ans'));
            } elseif ($this->input->post('question_type') == "descriptive") {
                $insert_data['opt_a']   = "";
                $insert_data['opt_b']   = "";
                $insert_data['opt_c']   = "";
                $insert_data['opt_d']   = "";
                $insert_data['opt_e']   = "";
                $insert_data['correct'] = "";
            }

            $recored_id = $this->input->post('recordid');
            $tag_exist_status=$this->courseexamquestion_model->check_tag_exist($tag_id,$recored_id);

            if(!empty($tag_exist_status)){
                if ($recored_id != 0) {
                    $insert_data['id'] = $recored_id;
                }
            }
            $this->courseexamquestion_model->add($insert_data);
            }
            $array = array('status' => 1, 'error' => '', 'message' => $this->lang->line('success_message'));
        }
        echo json_encode($array);
    }

	public function questionsearchvalidation(){
        $question_type  = $this->input->post('question_type');
        $question_level = $this->input->post('question_level');
        $created_by     = $this->input->post('created_by');
        $srch_type      = $this->input->post('search_type');         
        $question_tag   = $this->input->post('question_tag');         
        $params = array('srch_type' => $srch_type, 'question_type' => $question_type, 'question_level' => $question_level, 'created_by' => $created_by,'question_tag'=>$question_tag);
            $array  = array('status' => 1, 'error' => '', 'params' => $params);
            echo json_encode($array);        
    }
   
    public function getDatatable(){
        $getStaffRole          = $this->customlib->getStaffRole();
        $staffrole             = json_decode($getStaffRole);
        $superadmin_restriction= $this->session->userdata['admin']['superadmin_restriction'];
        $search_question_type  = $this->input->post('question_type');
        $search_question_level = $this->input->post('question_level');
        $created_by            = $this->input->post('created_by');
        $search_question_tag   = $this->input->post('question_tag');
        $question_type         = $this->config->item('question_type');
        $question_level        = $this->config->item('question_level');
        $question_dt           = $this->courseexamquestion_model->getAllRecord($search_question_type, $search_question_level,$created_by,$search_question_tag);
        $question_dt           = json_decode($question_dt);
        $dt_data            = array();
        $recordsTotal_flter = "";
        $userdata           = $this->customlib->getUserData();
        $role_id            = $userdata["role_id"];
        if (!empty($question_dt->data)) {
            foreach ($question_dt->data as $key => $value) {

                $delete       = "'" . $this->lang->line("delete_confirm") . "'";
                $delete_title = "'" . $this->lang->line("delete") . "'";
                $editbtn      = "";
                $deletebtn    = "";
                $del_checkbox = "";
                $viewbtn = "";
					
					if($this->rbac->hasPrivilege('online_course_question_bank', 'can_delete')) {
						$deletebtn    = '<a href="' . base_url() . 'onlinecourse/courseexamquestion/delete/' . $value->id . '" class="btn btn-primary btn-xs"  data-toggle="tooltip" title=' . $delete_title . ' onclick="return confirm(' . $delete . ')"><i class="fa fa-remove"></i></a>';
					}
					
                    $del_checkbox = "<input type='checkbox' name='question_" . $value->id . "' data-question-id='" . $value->id . "' value='" . $value->id . "'>";

					if($this->rbac->hasPrivilege('online_course_question_bank', 'can_view')) {
						$viewbtn = '<a target="_blank" href="' . site_url('onlinecourse/courseexamquestion/read/' . $value->id) . '" class="btn btn-primary btn-xs"  data-toggle="tooltip" title=' . $this->lang->line("view") . ' ><i class="fa fa-eye"></i></a>';
					}
					
					if($this->rbac->hasPrivilege('online_course_question_bank', 'can_delete')) {
						$editbtn = '<button type="button" class="btn btn-primary btn-xs question-btn-edit" data-toggle="tooltip" id="load" data-recordid="' . $value->id . '" title="' . $this->lang->line("edit") . '" ><i class="fa fa-pencil"></i></button>';               
					}
					
                $row = array();
                $row[] = $del_checkbox;
                $row[] = $value->id;
                $row[] = $value->tag_name;
                $row[] = ($value->question_type != "") ? $question_type[$value->question_type] : "";
                $row[] = ($value->level != "") ? $question_level[$value->level] : "";
                $row[] = readmorelink($value->question, site_url('onlinecourse/courseexamquestion/read/' . $value->id));                
                
                if($superadmin_restriction == 'disabled'){
                    if($staffrole->id == 7){
                        $row[] = $value->staff_name. ' ' .$value->staff_surname. ' (' .$value->employee_id. ')';
                    }else{
                        if($value->created_role != 7){
                            $row[] = $value->staff_name. ' ' .$value->staff_surname. ' (' .$value->employee_id. ')'; 
                        }else{
                            $row[] = '';
                        } 
                    }
                }else{
                    $row[] = $value->staff_name. ' ' .$value->staff_surname. ' (' .$value->employee_id. ')';
                }              
                
                $row[] = $viewbtn . ' ' . $editbtn . ' ' . $deletebtn;
				$dt_data[] = $row;              
            }
        }

        $json_data = array(
            "draw"            => intval($question_dt->draw),
            "recordsTotal"    => intval($question_dt->recordsTotal),
            "recordsFiltered" => intval($question_dt->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    public function read($id){        
        $question                    = $this->courseexamquestion_model->get($id);
        $data['question']            = $question;
        $data['question_type']       = $this->config->item('question_type');
        $data['question_level']      = $this->config->item('question_level');
        $data['question_true_false'] = $this->config->item('question_true_false');
        $questionOpt                 = $this->customlib->getQuesOption();
        $data['questionOpt']         = $questionOpt;
        $this->load->view('layout/header', $data);
        $this->load->view('onlinecourse/courseexamquestion/read', $data);
        $this->load->view('layout/footer', $data);
    }

    public function delete($id){
        $this->courseexamquestion_model->remove($id);
        redirect('onlinecourse/courseexamquestion/index', 'refresh');
    }

    public function bulkdelete(){
        $question_array  = $this->input->post('recordid');
        $question_result = $this->courseexamquestion_model->bulkdelete($question_array);
        echo json_encode(array('status' => 1, 'message' => $this->lang->line('delete_message')));
    }

    public function getimages(){
        $keyword         = "";
        $page            = $this->input->post('page');
        $keyword         = $this->input->post('query');
        $per_page_record = 12;
        if ($page > 1) {
            $start = (($page - 1) * $per_page_record);
            $page  = $page;
        } else {
            $start = 0;
        }
        $file_type = "image";
        $result       = $this->cms_media_model->fetch_details($per_page_record, $start, $keyword, $file_type);
        $result_count = $this->cms_media_model->count_all($keyword, $file_type);

        $data['result']       = $result;
        $data['result_count'] = $result_count;
        $data['pagination']   = $this->getpagination($result_count, $per_page_record, $page);
       
        $page = $this->load->view('onlinecourse/courseexamquestion/getimages', $data, true);
        echo json_encode(array('page' => $page, 'count' => $data['result_count'], 'pagination' => $data['pagination']));
    }

    public function getpagination($total_data, $limit, $page){

        $output = "";
        $output .= '<ul class="pagination">';
        $total_links   = ceil($total_data / $limit);
        $previous_link = '';
        $next_link     = '';
        $page_link     = '';

        $page_array = array();
        if ($total_links > 4) {
            if ($page < 5) {
                for ($count = 1; $count <= 5; $count++) {
                    $page_array[] = $count;
                }
                $page_array[] = '...';
                $page_array[] = $total_links;
            } else {
                $end_limit = $total_links - 5;
                if ($page > $end_limit) {
                    $page_array[] = 1;
                    $page_array[] = '...';
                    for ($count = $end_limit; $count <= $total_links; $count++) {
                        $page_array[] = $count;
                    }
                } else {
                    $page_array[] = 1;
                    $page_array[] = '...';
                    for ($count = $page - 1; $count <= $page + 1; $count++) {
                        $page_array[] = $count;
                    }
                    $page_array[] = '...';
                    $page_array[] = $total_links;
                }
            }
        } else {

            for ($count = 1; $count <= $total_links; $count++) {
                $page_array[] = $count;
            }
        }

        for ($count = 0; $count < count($page_array); $count++) {
            if ($page == $page_array[$count]) {
                $page_link .= '
    <li class="page-item active">
      <a class="page-link" href="#">' . $page_array[$count] . ' <span class="sr-only">(current)</span></a>
    </li>
    ';

                $previous_id = $page_array[$count] - 1;
                if ($previous_id > 0) {
                    $previous_link = '<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page_number="' . $previous_id . '">Previous</a></li>';
                } else {
                    $previous_link = '
      <li class="page-item disabled">
        <a class="page-link" href="#">Previous</a>
      </li>
      ';
                }
                $next_id = $page_array[$count] + 1;
                if ($next_id >= $total_links) {
                    $next_link = '
      <li class="page-item disabled">
        <a class="page-link" href="#">Next</a>
      </li>
        ';
                } else {
                    $next_link = '<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page_number="' . $next_id . '">Next</a></li>';
                }
            } else {
                if ($page_array[$count] == '...') {
                    $page_link .= '
      <li class="page-item disabled">
          <a class="page-link" href="#">...</a>
      </li>
      ';
                } else {
                    $page_link .= '
      <li class="page-item"><a class="page-link" href="javascript:void(0)" data-page_number="' . $page_array[$count] . '">' . $page_array[$count] . '</a></li>
      ';
                }
            }
        }

        $output .= $previous_link . $page_link . $next_link;
        $output .= '</ul>';
        return $output;
    }

	public function search_question(){
        $recordsTotal_flter = "";
        $userdata           = $this->customlib->getUserData();
        $role_id            = $userdata["role_id"];
        $data               = array();
        $pag_content        = '';
        $pag_navigation     = '';
        $is_quiz            = $this->input->post('is_quiz');
        $page               = $this->input->post('page');   
        $keyword            = $this->input->post('keyword');
        $question_type      = $this->input->post('question_type');
        $question_level     = $this->input->post('question_level');         
        $exam_id            = $this->input->post('exam_id');         

        if (isset($page)) {
            $max      = 100;
            $cur_page = $page;
            $page -= 1;
            $per_page      = $max ? $max : 40;
            $previous_btn  = true;
            $next_btn      = true;
            $first_btn     = true;
            $last_btn      = true;
            $start         = $page * $per_page;
            $where_search  = array();
            $show_from     = ($cur_page * $max) - ($max - 1);          
            $show_to       = $cur_page * $max;
            $total_display = 0;

            /* Check if there is a string inputted on the search box */
            if(
                ($_POST['question_tag'] != "") ||
                ($_POST['keyword'] != "") ||
                ($_POST['question_level'] != "") ||
                ($_POST['question_type'] != "")
            ){
                $question_tag                   = $this->input->post('question_tag');
                $where_search['question_tag']   = $question_tag;
                $where_search['keyword']        = $keyword;
                $where_search['question_level'] = $question_level;
                $where_search['question_type']  = $question_type;                
            }
            $where_search['is_quiz'] = $is_quiz;
            $data['question_type']   = $this->config->item('question_type');
            $data['question_level']  = $this->config->item('question_level');
            $questionList            = $this->courseexamquestion_model->getByExamID($per_page, $start, $where_search,$exam_id);

            $dt_data                = array();
            $data['questionList']   = $questionList;
            $recordsTotal_flter     = count($questionList);
            $count                  = $this->courseexamquestion_model->getCountByExamID($where_search);
            $total_display          = $recordsTotal_flter;

            /* Check if our query returns anything. */
            if ($count) {
                $pag_content = $this->load->view('onlinecourse/courseexam/_searchQuestionByExamID', $data, true);
                /* If the query returns nothing, we throw an error message */
            }

            $no_of_paginations = ceil($count / $per_page);

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

            $pag_navigation .= "<ul class='pagination pull-right'>";

            if ($first_btn && $cur_page > 1) {
                $pag_navigation .= "<li p='1' class='activee'><a href='#'><i class='fa fa-angle-double-left'></i></a></li>";
            } else if ($first_btn) {

                $pag_navigation .= "<li p='1' class='disabled'><a href='#'><i class='fa fa-angle-double-left'></i></a></li>";
            }

            if ($previous_btn && $cur_page > 1) {
                $pre = $cur_page - 1;
                $pag_navigation .= "<li p='$pre' class='activee'><a href='#'><i class='fa fa-angle-left'></i></a></li>";
            } else if ($previous_btn) {
                $pag_navigation .= "<li  class='disabled'><a href='#'><i class='fa fa-angle-left'></i></a></li>";
            }

            for ($i = $start_loop; $i <= $end_loop; $i++) {
                if ($cur_page == $i) {
                    $pag_navigation .= "<li p='$i' class='active'><a href='#'>{$i}</a></li>";
                } else {
                    $pag_navigation .= "<li p='$i'  class='activee'><a href='#'>{$i}</a></li>";
                }
            }

            if ($next_btn && $cur_page < $no_of_paginations) {
                $nex = $cur_page + 1;
                $pag_navigation .= "<li p='$nex' class='activee'><a href='#'><i class='fa fa-angle-right'></i></a></li>";
            } else if ($next_btn) {
                $pag_navigation .= "<li class='disabled'><a href='#'><i class='fa fa-angle-right'></i></a></li>";
            }

            if ($last_btn && $cur_page < $no_of_paginations) {
                $pag_navigation .= "<li p='$no_of_paginations'  class='activee'><a href='#'><i class='fa fa-angle-double-right'></i></a></li>";
            } else if ($last_btn) {
                $pag_navigation .= "<li p='$no_of_paginations' class='disabled'><a href='#'><i class='fa fa-angle-double-right'></i></a></li>";
            }
            $pag_navigation = $pag_navigation . "</ul>";
        }

        $response = array(
            'content'       => $pag_content,
            'navigation'    => $pag_navigation,
            'show_from'     => ($total_display <= 0) ? 0 : $show_from,
            'show_to'       => ($show_to > $total_display) ? $total_display : $show_to,
            'total_display' => $total_display,
        );
        echo json_encode($response);
    }

    public function uploadfile(){
        $this->form_validation->set_rules('file', $this->lang->line('image'), 'callback_handle_upload');       
        if ($this->form_validation->run() == false) {
            $data = array(                
                'file'       => form_error('file'),
            );
            $array = array('status' => 0, 'error' => $data);
            echo json_encode($array);
        } else {
            $insert_array = array();
            if (isset($_FILES["file"]) && !empty($_FILES['file']['name'])) {

                $fileName = $_FILES["file"]["tmp_name"];
                if (isset($_FILES["file"]) && !empty($_FILES['file']['name']) && $_FILES["file"]["size"] > 0) {
                    $file = fopen($fileName, "r");
                    $flag = true;
                    while (($column = fgetcsv($file, 10000, ",")) !== false) {
                        if ($flag) {
                            $flag = false;
                            continue;
                        }

                        if (trim($column['0']) != "" && trim($column['1']) != "" && trim($column['2']) != "") {
                            $insert_array[] = array(
                                'staff_id'      => $this->customlib->getStaffID(),                               
                                'question_type' => trim($column['0']),
                                'level'         => trim($column['1']),
                                'question'      => trim($column['2']),
                                'opt_a'         => trim($column['3']),
                                'opt_b'         => trim($column['4']),
                                'opt_c'         => trim($column['5']),
                                'opt_d'         => trim($column['6']),
                                'opt_e'         => trim($column['7']),
                                'correct'       => trim($column['8']),
                                'question_tag'  => trim($column['9']),
                            );
                        }
                    }
                }
                if (!empty($insert_array)) {
                    $this->courseexamquestion_model->add_question_bulk($insert_array);
                }
                $array = array('status' => '1', 'error' => '', 'message' => count($insert_array) . ' ' . $this->lang->line('questions_are_successfully_imported'));
                echo json_encode($array);
            }
        }
    }

    public function handle_upload(){

        $image_validate = $this->config->item('csv_validate');
        if (isset($_FILES["file"]) && !empty($_FILES['file']['name']) && $_FILES["file"]["size"] > 0) {

            $file_type         = $_FILES["file"]['type'];
            $file_size         = $_FILES["file"]["size"];
            $file_name         = $_FILES["file"]["name"];
            $allowed_extension = $image_validate['allowed_extension'];
            $ext               = pathinfo($file_name, PATHINFO_EXTENSION);
            $allowed_mime_type = $image_validate['allowed_mime_type'];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mtype = finfo_file($finfo, $_FILES['file']['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mtype, $allowed_mime_type)) {
                $this->form_validation->set_message('handle_upload', $this->lang->line('file_type_not_allowed'));
                return false;
            }

            if (!in_array($ext, $allowed_extension) || !in_array($file_type, $allowed_mime_type)) {
                $this->form_validation->set_message('handle_upload', $this->lang->line('extension_not_allowed'));
                return false;
            }

            if ($file_size > $image_validate['upload_size']) {
                $this->form_validation->set_message('handle_upload', $this->lang->line('file_size_shoud_be_less_than') . number_format($image_validate['upload_size'] / 1048576, 2) . " MB");
                return false;
            }

            return true;
        } else {
            $this->form_validation->set_message('handle_upload', $this->lang->line('please_choose_a_file_to_upload'));
            return false;
        }
    }

    public function exportformat(){
        $this->load->helper('download');
        $filepath   = "./backend/import/import_online_course_exam_question_sample_file.csv";
        $data       = file_get_contents($filepath);
        $name       = 'import_online_course_exam_question_sample_file.csv';
        force_download($name, $data);
    }









}