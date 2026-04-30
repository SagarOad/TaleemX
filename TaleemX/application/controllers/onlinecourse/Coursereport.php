<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Coursereport extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('course_model', 'coursesection_model', 'courselesson_model', 'studentcourse_model', 'coursequiz_model', 'course_payment_model', 'courseofflinepayment_model', 'coursereport_model', 'setting_model','courseassignment_model','courseexam_model'));
        $this->auth->addonchk('ssoclc', site_url('onlinecourse/course/setting'));
    }

    /*
    This is used to show course report page
     */
    public function coursepurchase()
    {
        $userdata            = $this->customlib->getUserData();
        $data['teacher_restricted_mode']=false;
        if (($userdata["role_id"] == 2) && ($userdata["class_teacher"] == "yes")) {
            $data['teacher_restricted_mode']=true;
        }
        if (!$this->rbac->hasPrivilege('student_course_purchase_report', 'can_view')) {
            access_denied();
        }

        $this->session->set_userdata('top_menu', 'onlinecourse');
        $this->session->set_userdata('sub_menu', 'onlinecourse/coursereport/report');
        $this->session->set_userdata('subsub_menu', 'onlinecourse/coursereport/coursepurchase');

        $payment_type = array(
            'all'     => $this->lang->line('all'),
            'Online'  => $this->lang->line('online'),
            'Offline' => $this->lang->line('offline'),
        );

        $payment_status = array(
            'success'    => $this->lang->line('success'),
            'processing' => $this->lang->line('processing'),
        );

        $users_type = array(
            'all'     => $this->lang->line('all'),
            'student' => $this->lang->line('student'),
            'guest'   => $this->lang->line('guest'),
        );

        $data['payment_status'] = $payment_status;
        $data['payment_type']   = $payment_type;
        $data['users_type']     = $users_type;
        $data['searchlist']     = $this->customlib->get_searchtype();

        $this->load->view('layout/header');
        $this->load->view('onlinecourse/report/coursepurchase', $data);
        $this->load->view('layout/footer');
    }

    /*
    This is used to check validation for search form
     */
    public function checkvalidation()
    {
        $search = $this->input->post('search');
        $this->form_validation->set_rules('search_type', $this->lang->line('search_type'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('payment_type', $this->lang->line('payment_type'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $msg = array(
                'search_type'  => form_error('search_type'),
                'payment_type' => form_error('payment_type'),
            );
            $json_array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $param = array(
                'search_type'    => $this->input->post('search_type'),
                'payment_type'   => $this->input->post('payment_type'),
                'date_from'      => $this->input->post('date_from'),
                'date_to'        => $this->input->post('date_to'),
                'payment_status' => $this->input->post('payment_status'),
                'users_type'     => $this->input->post('users_type'),
            );

            $json_array = array('status' => 'success', 'error' => '', 'param' => $param, 'message' => $this->lang->line('success_message'));
        }
        echo json_encode($json_array);
    }

    /*
    This is used to get course list by class section id and student id
     */
    public function coursereport()
    {
        $search['search_type']  = $this->input->post('search_type');
        $search['payment_type'] = $this->input->post('payment_type');
        $search['date_from']    = $this->input->post('date_from');
        $search['date_to']      = $this->input->post('date_to');
        $start_date = '';
        $end_date   = '';

        if ($search['search_type'] == 'period') {
            $start_date = date('Y-m-d', $this->customlib->datetostrtotime($search['date_from']));
            $end_date   = date('Y-m-d', $this->customlib->datetostrtotime($search['date_to']));
        } else {
            if (isset($search['search_type']) && $search['search_type'] != '') {
                $dates               = $this->customlib->get_betweendate($search['search_type']);
                $data['search_type'] = $search['search_type'];
            } else {
                $dates               = $this->customlib->get_betweendate('this_year');
                $data['search_type'] = '';
            }
            $start_date = date('Y-m-d', strtotime($dates['from_date']));
            $end_date   = date('Y-m-d', strtotime($dates['to_date']));
        }

        $users_type = $_POST['users_type'];

        if ($_POST['payment_status'] == 'processing') {
            $coursedata = $this->coursereport_model->course_processingreport($users_type,$search['payment_type'], $start_date, $end_date);
        } else { 
            $coursedata = $this->coursereport_model->coursereport($search['payment_type'], $start_date, $end_date, $users_type);
        }
        
        $coursedata = json_decode($coursedata);

        $dt_data = array();
        if (!empty($coursedata->data)) {
            $doc = "";
            $grand_total     = 0;
            foreach ($coursedata->data as $key => $value) {

                $username    = '';
                $sch_setting = $this->setting_model->getSetting();
                if (!empty($value->student_id)) {
                    $user_data = $this->student_model->getRecentRecord($value->student_id);
                    if (!empty($user_data)) {
                        $username = $this->customlib->getFullName($user_data['firstname'], $user_data['middlename'], $user_data['lastname'], $sch_setting->middlename, $sch_setting->lastname) . ' (' . $this->lang->line('student') . ' - ' . $user_data['admission_no'] . ')';
                    }
                } elseif (!empty($value->guest_id)) {
                    $user_data = $this->studentcourse_model->read_user_information($value->guest_id);
                    if (!empty($user_data)) {
                        $username = $user_name = $user_data[0]->guest_name . ' (' . $this->lang->line('guest') . ' - ' . $user_data[0]->guest_unique_id . ')';
                    }
                }

                $row = array();
               
                $grand_total += $value->paid_amount;
                 
                $row[] = $username;
                $row[] = date($this->customlib->getSchoolDateFormat(), strtotime($value->date));
                $row[] = $value->title;
                $row[] = $this->lang->line($value->course_provider);
                $row[] = $this->lang->line(strtolower($value->payment_type));
                if ($value->payment_type == 'Online') {
                    $row[] = $value->payment_mode . ' (' . $this->lang->line('txn_id') . ' - ' . $value->transaction_id . ')';
                } else {
                    $row[] = $this->lang->line($value->payment_mode);
                }

                $row[]     = amountFormat($value->paid_amount);
                $dt_data[] = $row;
            }
            $footer_row   = array();
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = "";
            $footer_row[] = "<b>" . $this->lang->line('grand_total') . "</b>";
            $footer_row[] = "<b>" . amountFormat($grand_total) . "</b>";
            $dt_data[]    = $footer_row;
        }

        $json_data = array(
            "draw"            => intval($coursedata->draw),
            "recordsTotal"    => intval($coursedata->recordsTotal),
            "recordsFiltered" => intval($coursedata->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    /*
    This is used to show top selling course list
     */
    public function coursesellreport()
    {
        if (!$this->rbac->hasPrivilege('course_sell_count_report', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'onlinecourse');
        $this->session->set_userdata('sub_menu', 'onlinecourse/coursereport/report');
        $this->session->set_userdata('subsub_menu', 'onlinecourse/coursereport/coursesellreport');
        $this->load->view('layout/header');
        $this->load->view('onlinecourse/report/coursesellreport');
        $this->load->view('layout/footer');
    }

    /*
    This is used to show top trending course list
     */
    public function trendingreport()
    {
        if (!$this->rbac->hasPrivilege('course_trending_report', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'onlinecourse');
        $this->session->set_userdata('sub_menu', 'onlinecourse/coursereport/report');
        $this->session->set_userdata('subsub_menu', 'onlinecourse/coursereport/trendingreport');
        $this->load->view('layout/header');
        $this->load->view('onlinecourse/report/coursetrending');
        $this->load->view('layout/footer');
    }

    /*
    This is used to show student list by purchasing course
     */
    public function saledata()
    {
        $courseid           = $this->input->post('courseid');
        $data['coursename'] = $this->input->post('coursename');
        $data['courseid']   = $courseid;
        $this->load->view('onlinecourse/report/_selllist', $data);
    }

    /*
    This is used to get seller data by course id
     */
    public function getsalelist($courseid)
    {
        $m = $this->coursereport_model->studentdata($courseid);

        $m = json_decode($m);

        $dt_data = array();
        if (!empty($m->data)) {
            foreach ($m->data as $key => $value) {
                $name = $username = $admission_no = '';
                $sch_setting = $this->setting_model->getSetting();
                if ($value->student_id != '') {
                    $user_data = $this->student_model->getstudentdetailbyid($value->student_id);
                    if (!empty($user_data)) {
                        $username = $this->customlib->getFullName($user_data['firstname'], $user_data['middlename'], $user_data['lastname'], $sch_setting->middlename, $sch_setting->lastname) . ' (' . $this->lang->line('student') . ' - ' . $user_data['admission_no'] . ')';
                    }else{
                        $username = $this->lang->line('no_record_found');
                    }

                } elseif ($value->guest_id != '') {
                    $user_data = $this->studentcourse_model->read_user_information($value->guest_id);
                    if (!empty($user_data)) {
                        $username = $user_data[0]->guest_name . ' (' . $this->lang->line('guest') . ' - ' . $user_data[0]->guest_unique_id . ')';
                    }else{
                        $username = $this->lang->line('no_record_found');
                    }
                }  

                $row   = array();
                $row[] = $username;
                $row[] = date($this->customlib->getSchoolDateFormat(), strtotime($value->date));
                $row[] = amountFormat($value->paid_amount);
                $dt_data[] = $row;
            }
        }

        $json_data = array(
            "draw"            => intval($m->draw),
            "recordsTotal"    => intval($m->recordsTotal),
            "recordsFiltered" => intval($m->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    /*
    This is used to get course data
     */
    public function getcourselist()
    {
        $superadmin_visible = $this->customlib->superadmin_visible();
        $role  = json_decode($this->customlib->getStaffRole());
        $m       = $this->coursereport_model->trendingreport();
        $m       = json_decode($m);
        $dt_data = array();
        if (!empty($m->data)) {
            foreach ($m->data as $key => $value) {
                $free_course = $value->free_course;
                $discount    = $value->discount;

                $discount_price = '';
                $price          = '';

                if (!empty($value->discount)) {
                    $discount = $value->price - (($value->price * $value->discount) / 100);
                }

                if (($value->free_course == 1) && (empty($value->price))) {
                    $price = 'Free';
                } elseif (($value->free_course == 1) && (!empty($value->price))) {
                    if ($value->price > '0.00') {
                        $courseprice = amountFormat($value->price);
                    } else {
                        $courseprice = '';
                    }
                    $price = $this->lang->line('free');
                } elseif (!empty($value->price) && (!empty($value->discount))) {
                    $discount = $discount;
                    if ($value->price > '0.00') {
                        $courseprice = amountFormat($value->price);
                    } else {
                        $courseprice = '';
                    }
                    $price = $courseprice;
                } else {
                    $price = amountFormat($value->price);
                }

                $multipalsection = $this->course_model->multipalsection($value->id);

                $section       = "";
                $store_section = array();
                foreach ($multipalsection as $multipalsection_value) {
                    if (!in_array($multipalsection_value['section'], $store_section)) {
                        $store_section[] = $multipalsection_value['section'];
                        $section .= $multipalsection_value['section'] . ", ";
                    }
                }

                $row       = array();
                $row[]     = $value->title;
                $row[]     = $value->class;
                $row[]     = rtrim($section, ", ");
                $row[]     = $value->view_count;                
                $row[]     = $value->assign_name . ' ' . $value->assign_surname . ' (' . $value->assign_employee_id . ')';
                
                if($role->id == 7){ 
                    $row[]     = $value->name . ' ' . $value->surname . ' (' . $value->employee_id . ')';   
                }else{
                    if($superadmin_visible == 'disabled' && $value->role_id == 7){
                        $row[]     = '';               
                    }else{
                        $row[]     = $value->name . ' ' . $value->surname . ' (' . $value->employee_id . ')';
                    }
                }
                
                $row[]     = $price;
                $row[]     = amountFormat($discount);
                $dt_data[] = $row;
            }
        }

        $json_data = array(
            "draw"            => intval($m->draw),
            "recordsTotal"    => intval($m->recordsTotal),
            "recordsFiltered" => intval($m->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    /*
    This is used to get seller data for report
     */
    public function getsellreport()
    {
        $superadmin_visible = $this->customlib->superadmin_visible();
        $role  = json_decode($this->customlib->getStaffRole());
        $m       = $this->coursereport_model->sellreport();
        $m       = json_decode($m);
        $dt_data = array();
        if (!empty($m->data)) {
            foreach ($m->data as $key => $value) {

                $multipalsection = $this->course_model->multipalsection($value->online_courses_id);
                $sellcount       = $this->course_model->coursesellcount($value->online_courses_id);
                $section         = "";
                $store_section   = array();
                foreach ($multipalsection as $multipalsection_value) {
                    if (!in_array($multipalsection_value['section'], $store_section)) {
                        $store_section[] = $multipalsection_value['section'];
                        $section .= $multipalsection_value['section'] . ", ";
                    }
                }

                $btn       = "<button type='button' class='btn btn-primary btn-xs' data-id=" . $value->title . " course-data-id=" . $value->online_courses_id . " title=" . $this->lang->line('view') . " data-toggle='modal' data-backdrop='static' data-keyboard='false' data-target='#sale_modal' onclick='loadcoursedetail(" . '"' . $value->online_courses_id . '"' . ',' . '"' . $value->title . '"' . "  )'  data-original-title='' title='' autocomplete='off'><i class='fa fa-reorder'></i> </button>";
                $row       = array();
                $row[]     = $value->title;
                $row[]     = $value->class;
                $row[]     = rtrim($section, ", ");
                $row[]     = count($sellcount);
                $row[]     = $value->assign_name . ' ' . $value->assign_surname . ' (' . $value->assign_employee_id . ')';
                
                if($role->id == 7){
                    $row[]     = $value->name . ' ' . $value->surname . ' (' . $value->employee_id . ')';
                }else{
                    if($superadmin_visible == 'disabled' && $value->role_id == 7){
                        $row[]     = '';               
                    }else{
                        $row[]     = $value->name . ' ' . $value->surname . ' (' . $value->employee_id . ')';
                    }
                }
                
                $row[]     = $btn;
                $dt_data[] = $row;
            }
        }

        $json_data = array(
            "draw"            => intval($m->draw),
            "recordsTotal"    => intval($m->recordsTotal),
            "recordsFiltered" => intval($m->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    /*
    This is used to show top selling course list
     */
    public function report()
    {
        $this->session->set_userdata('top_menu', 'onlinecourse');
        $this->session->set_userdata('sub_menu', 'onlinecourse/coursereport/report');
        $this->session->set_userdata('subsub_menu', '');
        $this->load->view('layout/header');
        $this->load->view('onlinecourse/report/report');
        $this->load->view('layout/footer');
    }

    /*
    This is used to show course complete report
     */
    public function completereport()
    {
        if (!$this->rbac->hasPrivilege('course_complete_report', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'onlinecourse');
        $this->session->set_userdata('sub_menu', 'onlinecourse/coursereport/report');
        $this->session->set_userdata('subsub_menu', 'onlinecourse/coursereport/completereport');
        $data['student_id'] = '';
        $data['classlist']  = $this->class_model->get();
        
        $userdata = $this->customlib->getUserData();
        if (($userdata["role_id"] == 2) && ($userdata["class_teacher"] == "yes") ) {            
            $users_type = array(
                'student' => $this->lang->line('student'),                
            );
        }else{
            $users_type = array(
                'student' => $this->lang->line('student'),
                'guest'   => $this->lang->line('guest'),
            );            
        }

        $data['users_type'] = $users_type;
        $this->load->view('layout/header');
        $this->load->view('onlinecourse/report/coursecompletereport', $data);
        $this->load->view('layout/footer');
    }

    /*
    This is used to check validation for course complete report form
     */
    public function validation()
    {
        $search     = $this->input->post('search');
        $users_type = $this->input->post('users_type');

        if ($users_type != 'guest') {
            $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required|xss_clean');
            $this->form_validation->set_rules('section_id', $this->lang->line('section'), 'trim|required|xss_clean');
        }

        $this->form_validation->set_rules('course_id', $this->lang->line('course'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('users_type', $this->lang->line('users_type'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $msg = array(
                'class_id'   => form_error('class_id'),
                'section_id' => form_error('section_id'),
                'course_id'  => form_error('course_id'),
                'users_type' => form_error('users_type'),
            );
            $json_array = array('status' => 'fail', 'error' => $msg, 'message' => '');

        } else {
            $class_section_id = $this->input->post('section_id');
            $course_id        = $this->input->post('course_id');
            $users_type       = $this->input->post('users_type');
            $params = array('class_section_id' => $class_section_id, 'course_id' => $course_id, 'users_type' => $users_type);
            $json_array = array('status' => 'success', 'error' => '', 'params' => $params);
        }
        echo json_encode($json_array);
    }

    /*
    This is used to get course list by class and section
     */
    public function courselist()
    {
        $class_section_id = $this->input->post('class_section_id');
        $users_type       = $this->input->post('users_type');
        $courselist       = $this->coursereport_model->courselist($class_section_id, $users_type);
        echo json_encode($courselist);
    }

    /*
    This is used to get course list by class section id and student id
     */
    public function coursecompletelist()
    {
        $course_id        = $this->input->post('course_id');
        $class_section_id = $this->input->post('class_section_id');
        $users_type       = $this->input->post('users_type');

        if ($users_type == 'guest') {
            $studentdata = $this->studentcourse_model->guestprogresslist();
        } else {
            $studentdata = $this->coursereport_model->coursecompletereport($class_section_id);
        }

        $studentdata = json_decode($studentdata);
        $sch_setting = $this->setting_model->getSetting();

        $dt_data = array();
        if (!empty($studentdata->data)) {
            $doc          = "";
            $student_name = '';
            foreach ($studentdata->data as $key => $value) {

                if ($users_type == 'guest') {
                    $student_name = $value->guest_name . ' (' . $this->lang->line('guest') . ' - ' . $value->guest_unique_id . ')';
                } else {
                    $student_name = $this->customlib->getFullName($value->firstname, $value->middlename, $value->lastname, $sch_setting->middlename, $sch_setting->lastname) . ' (' . $this->lang->line('student') . ' - ' . $value->admission_no . ')';
                }
                $lessonquizcount        =   $this->studentcourse_model->lessonquizcountbycourseid($course_id, $value->id, $users_type);
                $courseprogresscount    =   0;
                $total_lesson           =   $lessonquizcount['lessoncount'];
                $total_quiz             =   $lessonquizcount['quizcount'];
                $assignment_count       =   $lessonquizcount['assignmentcount'];
                $exam_count             =   $lessonquizcount['examcount'];

                foreach($lessonquizcount['courseprogresscount'] as $key=>$val){
                    $lesson_quiz_type=$val['lesson_quiz_type'];
                    if($lesson_quiz_type==1){
                        $courseprogresscount++;
                    }
                    if($lesson_quiz_type==2 && $this->customlib->get_online_course_curriculam_status("online_course_quiz")==""){
                        $courseprogresscount++;
                    }
                    if($lesson_quiz_type==3 && $this->customlib->get_online_course_curriculam_status("online_course_assignment")==""){
                        $courseprogresscount++;
                    }
                    if($lesson_quiz_type==4 && $this->customlib->get_online_course_curriculam_status("online_course_exam")==""){
                        $courseprogresscount++;
                    }
                }

                //check is curriculam status is active or inactive if curriculam mode is inactive or hide then its value will be 0
                if($this->customlib->get_online_course_curriculam_status("online_course_assignment")!=""){
                    $assignment_count=0;
                }
                if($this->customlib->get_online_course_curriculam_status("online_course_quiz")!=""){
                    $total_quiz=0;
                }
                if($this->customlib->get_online_course_curriculam_status("online_course_exam")!=""){
                    $exam_count=0;
                }

                $total_quiz_lession = $total_lesson + $total_quiz + $assignment_count + $exam_count;

                $course_progress = 0;
                if ($total_quiz_lession > 0) {
                    if (!empty($courseprogresscount)) {
                        $course_progress = (($courseprogresscount) / $total_quiz_lession) * 100;
                    }
                }

                $row   = array();
                $row[] = $student_name;
                $row[] = intval($course_progress);
                $row[] = '<a data-backdrop="static" target="_blank" class="btn btn-primary pull-right btn-xs performance_btn" href="' . base_url() . "onlinecourse/coursereport/quizperformance/" . $value->id . "/" . $course_id . "/" . $users_type . '"
                ><i class="fa fa-moneys"></i> ' . $this->lang->line("course_performance") . '</a>';

                $dt_data[] = $row;
            }
        }

        $json_data = array(
            "draw"            => intval($studentdata->draw),
            "recordsTotal"    => intval($studentdata->recordsTotal),
            "recordsFiltered" => intval($studentdata->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    /*
    This is used to get quiz list for quiz performance report
     */
    public function quizperformance()
    {
        $userid           = $this->uri->segment(4);
        $courseid         = $this->uri->segment(5);
        $user_type        = $this->uri->segment(6);
        $data['courseid'] = $courseid;

        // for bar graph start
        $totalmarks         = $this->quizgraph($courseid, $userid, $user_type);
        $data['totalmarks'] = $totalmarks['totalmarks'];
        $data['quizdata']   = $totalmarks['totalquiz'];
        $data['quizcount']  = count($totalmarks['totalquiz']);
     
        // quiz progress start
        $lessonquizcount = $this->studentcourse_model->lessonquizcountbycourseid($courseid, $userid, $user_type);
        $data['lesson_count']       = $total_lesson         = $lessonquizcount['lessoncount'];
        $data['quiz_count']         = $total_quiz           = $lessonquizcount['quizcount'];
        $data['assignment_count']   = $assignment_count     = $lessonquizcount['assignmentcount'];
        $data['exam_count']         = $exam_count           = $lessonquizcount['examcount'];

        $courseprogresscount=0;
        foreach($lessonquizcount['courseprogresscount'] as $key=>$value){
            $lesson_quiz_type=$value['lesson_quiz_type'];
            if($lesson_quiz_type==1){
                $courseprogresscount++;
            }
            if($lesson_quiz_type==2 && $this->customlib->get_online_course_curriculam_status("online_course_quiz")==""){
                $courseprogresscount++;
            }
            if($lesson_quiz_type==3 && $this->customlib->get_online_course_curriculam_status("online_course_assignment")==""){
                $courseprogresscount++;
            }
            if($lesson_quiz_type==4 && $this->customlib->get_online_course_curriculam_status("online_course_exam")==""){
                $courseprogresscount++;
            }
        }

        //check is curriculam status is active or inactive if curriculam mode is inactive or hide then its value will be 0
        if($this->customlib->get_online_course_curriculam_status("online_course_assignment")!=""){
            $assignment_count=0;
        }
        if($this->customlib->get_online_course_curriculam_status("online_course_quiz")!=""){
            $total_quiz=0;
        }
        if($this->customlib->get_online_course_curriculam_status("online_course_exam")!=""){
            $exam_count=0;
        }

        $total_quiz_lession = $total_lesson + $total_quiz + $assignment_count + $exam_count;
        $course_progress = 0;
        if($total_quiz_lession > 0){
            $course_progress = ($courseprogresscount / $total_quiz_lession) * 100;
        }
        $data['course_progress'] = intval($course_progress);
      
        // for completed status start
        $completedquiz = $this->studentcourse_model->completelessonquizbycourse($courseid, $userid, $user_type);
        $quiz               = 0;
        $lesson             = 0;
        $assignmentcount    = 0;
        $examcount          = 0;

        if (!empty($completedquiz['quiz']) && $this->customlib->get_online_course_curriculam_status("online_course_quiz")==""){
            $quiz = $completedquiz['quiz'];
        } 
        if (!empty($completedquiz['lesson'])){
            $lesson = $completedquiz['lesson'];
        } 
        if (!empty($completedquiz['assignmentcount']) && $this->customlib->get_online_course_curriculam_status("online_course_assignment")==""){
            $assignmentcount = $completedquiz['assignmentcount'];
        } 
        if (!empty($completedquiz['examcount']) && $this->customlib->get_online_course_curriculam_status("online_course_exam")==""){
            $examcount = $completedquiz['examcount'];
        }

        $data['completedassignment']    = $assignmentcount;
        $data['completedquiz']          = $quiz;
        $data['completedlesson']        = $lesson;
        $data['completedexam']          = $examcount;
        // end

        $data['quizperformancedata'] = $this->studentcourse_model->quizstatusbycourseid($courseid, $userid, $user_type);
        $doughnut_chart              = array();

        if (!empty($data['quizperformancedata'])) {
            foreach ($data['quizperformancedata'] as $quiz_key => $quiz_value) {
                $a = array();
                $a[]              = array('value' => $quiz_value['correct_answer'], 'color' => "#52d726", 'highlight' => "#36a2eb", 'label' => $this->lang->line('correct_answer'));
                $a[]              = array('value' => $quiz_value['wrong_answer'], 'color' => "#f93939", 'highlight' => "#73c8b8", 'label' => $this->lang->line('wrong_answer'));
                $a[]              = array('value' => $quiz_value['not_answer'], 'color' => "#c9cbcf", 'highlight' => "#3bb9ab", 'label' => $this->lang->line('not_attempted'));
                $doughnut_chart[] = $a;
            }
        }

        $data['graph_data'] = ($doughnut_chart);
        $this->load->view('layout/header');
        $this->load->view('onlinecourse/report/_quizperformance', $data);
        $this->load->view('layout/footer');
    }

    /*
    This is used to get quiz data for quiz progress graph
     */
    public function quizgraph($courseid, $userid, $user_type)
    {
        $totalquiz          = $this->studentcourse_model->quizbycourse($courseid);
        $data['totalquiz']  = $totalquiz;
        $data['totalmarks'] = '';

        $totalmarks_array = array();
        foreach ($totalquiz as $totalquiz_value) {
            $totalmarks = $this->studentcourse_model->quizgraphadmin($totalquiz_value->id, $userid, $user_type);
            if (!empty($totalmarks['total_question']) and $totalmarks['total_question'] != 0) {
                $marks              = ($totalmarks['right_answer'] * 100) / $totalmarks['total_question'];
                $totalmarks_array[] = number_format((float) $marks, 2, '.', '');
            } else {
                $totalmarks_array[] = '';
            }
        }
        if (!empty($totalmarks_array)) {
            $data['totalmarks'] = $totalmarks_array;
        }
        return $data;
    }

    /*
    This is used to add rating
     */
    public function courseratingreport()
    {
        if (!$this->rbac->hasPrivilege('course_rating_report', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'onlinecourse');
        $this->session->set_userdata('sub_menu', 'onlinecourse/coursereport/report');
        $this->session->set_userdata('subsub_menu', 'onlinecourse/coursereport/courseratingreport');
        $this->load->view('layout/header');
        $this->load->view('onlinecourse/report/courseratingreport');
        $this->load->view('layout/footer');
    }

    /*
    This is used to get all course list to show in datatable
     */
    public function dtgetcourserating()
    {
        if (!$this->rbac->hasPrivilege('course_rating_report', 'can_view')) {
            access_denied();
        }
        $courselist = $this->course_model->rating();

        $m       = json_decode($courselist);
        $dt_data = array();
        if (!empty($m->data)) {
            foreach ($m->data as $key => $value) {
                $courserating  = $this->studentcourse_model->getcourseratingcount($value->course_id);
                $rating        = 0;
                $averagerating = 0;

                if (!empty($courserating)) {
                    foreach ($courserating as $courserating_value) {
                        $rating = $rating + $courserating_value['rating'];
                    }

                    $averagerating = $rating / count($courserating);
                }

                $row   = array();
                $row[] = $value->title;
                $row[] = $value->class . ' (' . $value->section . ')';

                //enter how many stars to enable
                $enable     = $averagerating;
                $max_stars  = 5; //enter maximum no.of stars
                $star_rate  = is_int($enable) ? 1 : 0;
                $rating_row = array();
                for ($i = 1; $i <= $max_stars; $i++) {

                    if (round($enable) == $i && !$star_rate) {
                        $rating_row[] = '<span class="fa fa-star-half " style="color:orange"></span>';
                    } elseif (round($enable) >= $i) {
                        $rating_row[] = '<span class="fa fa-star" style="color:orange"></span>';
                    } else {
                        $rating_row[] = '<span class="fa fa-star disable"></span>';
                    }
                }

                $row[] = implode(" ", $rating_row) . ' <p class="hide">' . round($averagerating, 2) . '</p>';
                $row[] = count($courserating);

                $row[] = '<a data-toggle="tab" class="btn btn-primary btn-xs btn-add detail_id" data-id="' . $value->course_id . '"  data-target="#rating_detail_modal" title="' . $this->lang->line('view') . '" detail="" aria-expanded="true"><i class="fa fa-reorder"></i></a>';

                $dt_data[] = $row;
            }
        }

        $json_data = array(
            "draw"            => intval($m->draw),
            "recordsTotal"    => intval($m->recordsTotal),
            "recordsFiltered" => intval($m->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    /*
    This is used get rating
     */
    public function courseratingdetail()
    {
        if (!$this->rbac->hasPrivilege('course_rating_report', 'can_view')) {
            access_denied();
        }
        $courseid             = $this->input->post('courseid');
        $data['courserating'] = $this->studentcourse_model->getcourseratingcount($courseid);
        $page                 = $this->load->view('onlinecourse/report/_courseratingdetail', $data, true);
        $array                = array('page' => $page, 'status' => 'success');
        echo json_encode($array);
    }

    /*
    This is used delete rating
     */
    public function deleterating()
    {
        if (!$this->rbac->hasPrivilege('course_rating_report', 'can_delete')) {
            access_denied();
        }
        $ratingid = $this->input->post('ratingid');
        if ($ratingid != '') {
            $this->course_model->deleterating($ratingid);
            $arrays = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('delete_message'));
        } else {
            $arrays = array('status' => 'fail', 'error' => $this->lang->line('some_thing_went_wrong'), 'message' => '');
        }
        echo json_encode($arrays);
    }
    
    public function guestlist()
    {
        if (!$this->rbac->hasPrivilege('guest_report', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'onlinecourse');
        $this->session->set_userdata('sub_menu', 'onlinecourse/coursereport/report');
        $this->session->set_userdata('subsub_menu', 'onlinecourse/coursereport/guestlist');
        $this->load->view('layout/header');
        $this->load->view('onlinecourse/report/guestlist');
        $this->load->view('layout/footer');
    }

    public function getguestlist()
    {
        if (!$this->rbac->hasPrivilege('guest_report', 'can_view')) {
            access_denied();
        }
        $coursedata = $this->course_model->getguestlist();
        $coursedata = json_decode($coursedata);
        $dt_data    = array();
        if (!empty($coursedata->data)) {
            $doc = "";
            foreach ($coursedata->data as $key => $value) {

                $row = array();
                $src = '';
                if (!empty($value->guest_image)) {
                    $src = base_url() . "uploads/guest_images/" . $value->guest_image;
                } else {
                    if ($value->gender == 'Female') {
                        $src = base_url() . "uploads/student_images/default_female.jpg";
                    } elseif ($value->gender == 'Male') {
                        $src = base_url() . "uploads/student_images/default_male.jpg";
                    }else{
                        $src = base_url() . "uploads/student_images/no_image.png";
                    }
                }

                if ($this->rbac->hasPrivilege('guest_report', 'can_delete')) {
                    $delete_btn = "<a href='#' onclick='deleteguest(" . '"' . $value->id . '"' . "  )' class='btn btn-primary btn-xs'  data-backdrop='static' data-keyboard='false' data-placement='top' data-toggle='modal' title=" . $this->lang->line('delete') . "><i class='fa fa-remove'></i></a>";
                } else {
                    $delete_btn = '';
                }

                $checked = '';
                if ($value->is_active == "yes") {
                    $checked = "checked";
                }

                if ($this->rbac->hasPrivilege('guest_report', 'can_edit')) {
                    $disable_btn = '<span onclick="changestatus(' . $value->id . ',' . "'" . $value->is_active . "'" . ')" class="material-switch pull-right"><input name="someSwitchOption001" type="checkbox" data-role="guest" class="chk" value="checked" ' . $checked . ' /><label for="guest' . $value->id . '" class="label-info-success"></label></span>';
                } else {
                    $disable_btn = '';
                }

                if ($value->gender) {
                    $gender = $this->lang->line(strtolower($value->gender));
                } else {
                    $gender = '';
                }

                if ($value->dob) {
                    $dob = date($this->customlib->getSchoolDateFormat(), strtotime($value->dob));
                } else {
                    $dob = '';
                }

                $row[] = "<img class='userimg24' src='$src' width = '100%'> ";
                $row[] = $value->guest_name;
                $row[] = $value->guest_unique_id;
                $row[] = $value->email;
                $row[] = $value->mobileno;
                $row[] = $dob;
                $row[] = $gender;
                $row[] = $value->address;
                $row[] = $disable_btn . ' ' . $delete_btn;
                $dt_data[] = $row;
            }
        }

        $json_data = array(
            "draw"            => intval($coursedata->draw),
            "recordsTotal"    => intval($coursedata->recordsTotal),
            "recordsFiltered" => intval($coursedata->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    public function delete()
    {
        if (!$this->rbac->hasPrivilege('guest_report', 'can_delete')) {
            access_denied();
        }
        $id = $this->input->post('id');
        $this->studentcourse_model->deleteguest($id);
        echo json_encode(array('status' => 1, 'msg' => $this->lang->line('delete_message')));
    }

    public function changestatus()
    {
        if (!$this->rbac->hasPrivilege('guest_report', 'can_edit')) {
            access_denied();
        }
        $id     = $this->input->post('id');
        $status = $this->input->post('status');

        if ($status == 'yes') {
            $status = 'no';
        } elseif ($status == 'no') {
            $status = 'yes';
        }

        $data   = array('id' => $id, 'is_active' => $status);
        $result = $this->studentcourse_model->addguest($data);

        if ($result) {
            $response = array('status' => 1, 'msg' => $this->lang->line('status_change_successfully'));
            echo json_encode($response);
        }
    }

    public function course_assignment_report()
    {
		if (!$this->rbac->hasPrivilege('course_assignment_report', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'onlinecourse');
        $this->session->set_userdata('sub_menu', 'onlinecourse/coursereport/report');
        $this->session->set_userdata('subsub_menu', 'onlinecourse/coursereport/course_assignment_report');
        $this->load->view('layout/header');
        $this->load->view('onlinecourse/report/course_assignment_report');
        $this->load->view('layout/footer');
    }

    public function get_course_assignment_report()
    {
        $coursedata = $this->courseassignment_model->get_course_assignment_report(); 
        $coursedata = json_decode($coursedata);
        $getStaffRole  = $this->customlib->getStaffRole();
        $staffrole     = json_decode($getStaffRole);
        $superadmin_visible = $this->customlib->superadmin_visible();

        $dt_data    = array();
        if (!empty($coursedata->data)) {
            $doc = "";
            $get_course_students = "";
            $total_assignment_submited = "";

            foreach ($coursedata->data as $key => $value) {
				 
                $total_students_and_guests=0;
                $total_submit=0;
                $get_course_students        =   $this->courseassignment_model->get_course_students($value->courseid);  
                $total_assignment_submited  =   $this->courseassignment_model->get_assignment_submitted($value->id,$value->courseid);  //assignment id
                $get_total_guests           =   $this->courseassignment_model->get_total_guests($value->courseid,$value->free_course); 
                if(count($get_course_students)==0 || count($total_assignment_submited)==0){
                    continue;    
                }

                $row   = array();
                $src   = '';
                $row[] = $value->title;
                $row[] = $value->assignment_title ;
                $row[] = date($this->customlib->getSchoolDateFormat(), strtotime($value->assignment_date));
				
				if($value->submit_date){
					$row[] = date($this->customlib->getSchoolDateFormat(), strtotime($value->submit_date));
				}else{
					$row[] ='';
				}

                if ($staffrole->id != 7) {
                    if ($superadmin_visible == 'disabled') {
                        if ($value->created_by_role_id == 7) {
                            $row[] = '';
                        } else {
                               $row[] = $value->assignemnt_created_by;
                        }
                    } else {
                           $row[] = $value->assignemnt_created_by;
                    }
                } else {
                       $row[] = $value->assignemnt_created_by;
                }

                if ($staffrole->id != 7) {
                    if ($superadmin_visible == 'disabled') {
                        if ($value->evaluated_by_role_id == 7) {
                            $row[] = '';
                        } else {
                               $row[] = $value->assignemnt_evaluated_by;
                        }
                    } else {
                          $row[] = $value->assignemnt_evaluated_by;
                    }
                } else {
                        $row[] = $value->assignemnt_evaluated_by;
                }

                $row[] = $get_course_students[0]['total_students'];
                $row[] = $total_assignment_submited[0]['total_student_submitted'];
                $row[] = $total_assignment_submited[0]['total_student_eval'] ? $total_assignment_submited[0]['total_student_eval'] : "0";
                $row[] = $get_total_guests[0]['total_guest'];
                $row[] = $total_assignment_submited[0]['total_guest_submitted'];
                $row[] = $total_assignment_submited[0]['total_guest_eval'] ? $total_assignment_submited[0]['total_guest_eval'] : "0";
                $row[] = "<a href='#' class='pull-right' onclick='view_student_list($value->courseid,$value->id,$value->free_course)'>".($get_course_students[0]['total_students']+$get_total_guests[0]['total_guest'])."</a>";
                $dt_data[] = $row;

            }
        }
        $json_data = array(
            "draw"            => intval($coursedata->draw),
            "recordsTotal"    => intval($coursedata->recordsTotal),
            "recordsFiltered" => intval($coursedata->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    public function getstudents()
    {
        $this->sch_setting_detail   =   $this->setting_model->getSetting();
        $data['sch_setting']        =   $this->sch_setting_detail;
        $courseid                   =   $this->input->post('courseid');
        $free_course                =   $this->input->post('free_course');
        $assignment_id              =   $this->input->post('assignment_id');
        $data['get_course_student_list']    =   $this->courseassignment_model->get_course_student_list($courseid,$assignment_id,$free_course);
        $data['get_course_guest_list']      =   $this->courseassignment_model->get_course_guest_list($courseid,$assignment_id,$free_course);
        $status=1;
        if(empty($data['get_course_student_list']) && empty($data['get_course_guest_list'])){
            $status=0;
        }
        $page  =  $this->load->view('onlinecourse/report/_course_assignment_studentlist', $data, true);
        echo json_encode(array('status' => $status, 'page' => $page));
    }

    public function course_exam_report()
    {
		if (!$this->rbac->hasPrivilege('course_exam_report', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'onlinecourse');
        $this->session->set_userdata('sub_menu', 'onlinecourse/coursereport/report');
        $this->session->set_userdata('subsub_menu', 'onlinecourse/coursereport/course_exam_report');
        $course_list            = $this->coursereport_model->get_online_course_list(); 
        $data['course_list']    = $course_list;
        $this->load->view('layout/header', $data);
        $this->load->view('onlinecourse/report/course_exam_report');
        $this->load->view('layout/footer', $data);
    }
  
    public function get_exam_form_parameter()
    {
        $course_id = $this->input->post('course_id');
        $this->form_validation->set_rules('course_id', $this->lang->line('course'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $msg = array(
                'course_id'  => form_error('course_id'),
            );
            $json_array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $param = array(
                'course_id'    => $this->input->post('course_id'),
            );
            $json_array = array('status' => 'success', 'error' => '', 'params' => $param, 'message' => $this->lang->line('success_message'));
        }
        echo json_encode($json_array);
    }

    public function get_exam_report_data()
    {
        $course_id = $this->input->post('course_id');;
        $this->sch_setting_detail = $this->setting_model->getSetting();
        $sch_setting    = $this->sch_setting_detail;
        $results        = $this->coursereport_model->get_exam_report_data($course_id);
        $resultlist     = json_decode($results);
        $dt_data    = array();

        if (!empty($resultlist->data)) {
            foreach ($resultlist->data as $resultlist_key => $subject_value) {
                //find total student and total guest to show
                $get_course_students        =   $this->courseassignment_model->get_course_students($subject_value->course_id);  
                $get_total_guests           =   $this->courseassignment_model->get_total_guests($subject_value->course_id,$subject_value->free_course); 
                $total_student_count        =   $get_course_students[0]['total_students'];
                $get_total_guests_count     =   $get_total_guests[0]['total_guest'];
                //find total student and total guest to show

                if ($subject_value->is_active == 1) {
                    $publish_btn = " <i class='fa fa-check-square-o'></i><span style='display:none'>" . $this->lang->line('yes') . "</span>";
                } else {
                    $publish_btn = " <i class='fa fa-exclamation-circle'></i><span style='display:none'>" . $this->lang->line('no') . "</span>";
                }
                if ($subject_value->publish_result == 1 || $subject_value->is_quiz) {
                    $result_publish = " <i class='fa fa-check-square-o'></i><span style='display:none'>" . $this->lang->line('yes') . "</span>";
                } else {
                    $result_publish = "<i class='fa fa-exclamation-circle'></i><span style='display:none'>" . $this->lang->line('no') . "</span>";
                }

                $row   = array();
                $row[] = $subject_value->title;
                $row[] = $subject_value->exam;
                $row[] = $subject_value->attempt;
                $row[] = $this->customlib->dateyyyymmddToDateTimeformat($subject_value->exam_from, false);
                $row[] = $this->customlib->dateyyyymmddToDateTimeformat($subject_value->exam_to, false);
                $row[] = $subject_value->duration;
                $row[] = $total_student_count;
                $row[] = $get_total_guests_count;
                $row[] = $total_student_count+$get_total_guests_count;
                $row[] = $subject_value->questions;
                $row[] = $publish_btn;
                $row[] = $result_publish;
                $dt_data[] = $row;
            }
        }
        $json_data = array(
            "draw"            => intval($resultlist->draw),
            "recordsTotal"    => intval($resultlist->recordsTotal),
            "recordsFiltered" => intval($resultlist->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }
  
    public function course_exam_attempt_report()
    {
		if (!$this->rbac->hasPrivilege('course_exam_attempt_report', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'onlinecourse');
        $this->session->set_userdata('sub_menu', 'onlinecourse/coursereport/report');
        $this->session->set_userdata('subsub_menu', 'onlinecourse/coursereport/course_exam_attempt_report');
        $course_list            = $this->coursereport_model->get_online_course_list(); 
        $data['course_list']    = $course_list;
        $this->load->view('layout/header', $data);
        $this->load->view('onlinecourse/report/course_exam_attempt_report', $data);
        $this->load->view('layout/footer', $data);
    }
    
    public function exam_attempt_validation()
    {
        $course_id = $this->input->post('course_id');
        $this->form_validation->set_rules('course_id', $this->lang->line('course'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $msg = array(
                'course_id'  => form_error('course_id'),
            );
            $json_array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $param = array(
                'course_id'    => $this->input->post('course_id'),
            );
            $json_array = array('status' => 'success', 'error' => '', 'params' => $param, 'message' => $this->lang->line('success_message'));
        }
        echo json_encode($json_array);
    }

    public function get_atttempt_report()
    {
        $course_id      = $this->input->post('course_id');
        $result         = $this->coursereport_model->get_total_attempts($course_id);
        $this->sch_setting_detail = $this->setting_model->getSetting();
        $sch_setting    = $this->sch_setting_detail;
        $resultlist     = json_decode($result);
        $dt_data        = array();

        if (!empty($resultlist->data)) {
            foreach ($resultlist->data as $resultlist_key => $student_value) {
                $exams = explode(',', $student_value->exams);
                $course_name             = "";
                $exam_name               = "";
                $exam_from               = "";
                $exam_to                 = "";
                $exam_duration           = "";
                $exam_publish            = "";
                $exam_resultpublish      = "";
                $exam_publishprint       = "";
                $exam_resultpublishprint = "";

                foreach ($exams as $exams_key => $exams_value) {
                    $exam_details = explode('@', $exams_value);
                    
                    if(count($exam_details) == 10){
                        $exam_name          .= $exam_details[1];
                        $exam_from          .= date($this->customlib->getSchoolDateFormat(), $this->customlib->dateYYYYMMDDtoStrtotime($exam_details[2]));
                        $exam_to            .= date($this->customlib->getSchoolDateFormat(), $this->customlib->dateYYYYMMDDtoStrtotime($exam_details[3]));
                        $exam_duration      .= $exam_details[4];
                        $exam_publish       .= ($exam_details[6] == 1) ? "<i class='fa fa-check-square-o' aria-hidden='true'></i>" : "<i class='fa fa-exclamation-circle' aria-hidden='true'></i>";
                        $exam_resultpublish .= ($exam_details[7] == 1 || $exam_details[9]==1) ? "<i class='fa fa-check-square-o' aria-hidden='true'></i>" : "<i class='fa fa-exclamation-circle' aria-hidden='true'></i>";
                        $course_name        .= $exam_details[8];
                        $course_name        .= '<br>';
                        $exam_name          .= '<br>';
                        $exam_from          .= "<br>";
                        $exam_to            .= "<br>";
                        $exam_duration      .= "<br>";
                        $exam_publish       .= "<br>";
                        $exam_resultpublish .= "<br>";
                    }
                }

                if($student_value->studentid!=""){
                    $user_name=$this->customlib->getFullName($student_value->firstname, $student_value->middlename, $student_value->lastname, $sch_setting->middlename, $sch_setting->lastname)." (".$this->lang->line('student')." - $student_value->admission_no )";
                }else if($student_value->guestid){
                    $user_name=$student_value->guest_name." (".$this->lang->line('guest')." - $student_value->guest_unique_id )";
                }

                $row   = array();
                $row[] = $user_name;
                $row[] = $exam_name;
                $row[] = $exam_from;
                $row[] = $exam_to;
                $row[] = $exam_duration;
                $row[] = $exam_publish . $exam_publishprint;
                $row[] = $exam_resultpublish . $exam_resultpublishprint;
                $dt_data[] = $row;
            }
        }
        $json_data = array(
            "draw"            => intval($resultlist->draw),
            "recordsTotal"    => intval($resultlist->recordsTotal),
            "recordsFiltered" => intval($resultlist->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    public function exam_result_report_validation()
    {
        $course_id  = $this->input->post('course_id');
        $exam_id    = $this->input->post('exam_id');
        $this->form_validation->set_rules('course_id', $this->lang->line('course'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('exam_id', $this->lang->line('exam'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $error = array();
            $error['course_id'] = form_error('course_id');
            $error['exam_id']    = form_error('exam_id');
            $array               = array('status' => 'fail', 'error' => $error);
          
        } else {
           $param = array(
                'course_id'    => $this->input->post('course_id'),
                'exam_id'      => $this->input->post('exam_id'),
            );
            $array = array('status' => 'success', 'error' => '','param' => $param);
        }
        echo json_encode($array);
    }

    public function get_result_report_data()
    {
        $exam_id        = $this->input->post('exam_id');
        $this->sch_setting_detail = $this->setting_model->getSetting();
        $sch_setting    = $this->sch_setting_detail;
        $results        = $this->coursereport_model->get_online_course_result($exam_id);
        $resultlist     = json_decode($results);
        $dt_data        = array();
        $button         ="";

        if (!empty($resultlist->data)) {
            foreach ($resultlist->data as $resultlist_key => $value) {
                if($value->student_id!=0){
                    $student_or_guest_name= $this->customlib->getFullName($value->firstname,$value->middlename,$value->lastname,$sch_setting->middlename, $sch_setting->lastname)." (".$this->lang->line('student')." - ".$value->admission_no.") ";
                    $student_or_guest_id=$value->student_id;
                    $type="student";
                }else if($value->guest_id!=0){
                    $student_or_guest_name=  $value->guest_name." (".$this->lang->line('guest')." - ".$value->guest_unique_id.")";
                    $student_or_guest_id=$value->guest_id;
                    $type="guest";
                }

                $button ='<button type="button" title="" class="btn btn-info btn-xs student_result" data-toggle="tooltip" data-type="'.$type.'" 
                data-examid="'.$value->exam_id.'" data-student_or_guest_id="'.$student_or_guest_id.'" data-loading-text="<i class=fa fa-spinner fa-spin></i>" data-original-title="View" autocomplete="off"><i class="fa fa-eye"></i></button>';
       
                $row   = array();
                $row[] = $student_or_guest_name;
                $row[] = $value->attempt;
                $row[] = $value->attempt-$value->total_used_attempmts;
                $row[] = date($this->customlib->getSchoolDateFormat(), strtotime($value->created_at));
                $row[] = $button;
                $dt_data[] = $row;
            }
        }
        $json_data = array(
            "draw"            => intval($resultlist->draw),
            "recordsTotal"    => intval($resultlist->recordsTotal),
            "recordsFiltered" => intval($resultlist->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    public function result_report()
    {
		if (!$this->rbac->hasPrivilege('course_exam_result_report', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'onlinecourse');
        $this->session->set_userdata('sub_menu', 'onlinecourse/coursereport/report');
        $this->session->set_userdata('subsub_menu', 'onlinecourse/coursereport/result_report');
		
        $this->sch_setting_detail = $this->setting_model->getSetting();
        $course_list            = $this->coursereport_model->get_online_course_list();
        $data['course_list']    = $course_list;
        $class               = $this->class_model->get();
        $data['classlist']   = $class;
        $data['sch_setting'] = $this->sch_setting_detail;
        $this->load->view('layout/header', $data);
        $this->load->view('onlinecourse/report/result_report', $data);
        $this->load->view('layout/footer', $data);
    }

    public function get_exam_list()
    {
        $course_id             =   $_POST['course_id'];
        $data['examList']      =   $this->coursereport_model->get_online_course_exam_list($course_id);
        echo json_encode($data);
    }

    public function exam_details()
    {
        $this->sch_setting_detail     =   $this->setting_model->getSetting();
        $exam_id                      =     $this->input->post('examid');
        $studentid_or_guestid         =     $this->input->post('student_or_guest_id');
        $type                         =     $this->input->post('type');
        $data['exam_id']              =     $exam_id;
        $data['sch_setting']          =     $this->sch_setting_detail;
        $filetype                     =     $this->filetype_model->get();
        $data['allowed_extension']    =     array_map('trim', array_map('strtolower', explode(',', $filetype->file_extension)));
        $data['allowed_mime_type']    =     array_map('trim', array_map('strtolower', explode(',', $filetype->file_mime)));
        $data['allowed_upload_size']  =     $filetype->file_size;
        $data['question_type']        =     $this->courseexam_model->getquestion_type($exam_id);
        $data['exam']                 =     $this->courseexam_model->getexam($exam_id);
        $userdata                     =     $this->customlib->getLoggedInUserData();
        $data['userdata']             =     $userdata ;
        $data['student']              =     $this->student_model->get($studentid_or_guestid); 
        $data['guest_datails']        =     $this->courseexam_model->get_guest_details($studentid_or_guestid); 
        $data['submitstatus']         =     0;  
        $data['question_true_false']  =     $this->config->item('question_true_false');
        $data['question_result']      =     $this->coursereport_model->online_course_exam_result($type,$studentid_or_guestid,$exam_id);
        $questionOpt                  =     $this->customlib->getQuesOption();
        $data['questionOpt']          =     $questionOpt;
        $data['type']                 =     $type;
        $page = $this->load->view("onlinecourse/report/_exam_details", $data, true);
        echo json_encode(array('status' => 1, 'page' => $page));
    }

}
