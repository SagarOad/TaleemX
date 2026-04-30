<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Offlinepayment extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('course_model', 'coursesection_model', 'courselesson_model', 'studentcourse_model', 'coursequiz_model', 'course_payment_model', 'courseofflinepayment_model', 'coursereport_model'));
        $this->auth->addonchk('ssoclc', site_url('onlinecourse/course/setting'));
        $this->sch_setting_detail = $this->setting_model->getSetting();
        $this->load->library('media_storage');

        $this->thermal_print_module     = 0;
        $this->thermal_print_enable     = 0;
        if ($this->module_lib->hasModule('thermal_print') && $this->module_lib->hasActive('thermal_print')) {
            $this->load->model("thermal_print_model");
            $this->thermal_print_result     = $this->thermal_print_model->get();            
            $this->thermal_print_module     = 1;            
            $this->thermal_print_enable     = $this->thermal_print_result['is_print'];               
        }
    }

    /*This is used to show offline payment list*/
    public function payment()
    {
        if (!$this->rbac->hasPrivilege('online_course_offline_payment', 'can_view')) {
            access_denied();
        }       
        
        $this->session->set_userdata('top_menu', 'onlinecourse');
        $this->session->set_userdata('sub_menu', 'onlinecourse/offlinepayment/index');       
        $data['student_id'] = '';
        $data['classlist']  = $this->class_model->get();
        $this->load->view('layout/header');
        $this->load->view('onlinecourse/offlinepayment/offlinepayment', $data);
        $this->load->view('layout/footer');
    }

    /*This is used to check validation for search form*/
    public function checkvalidation()
    {
        $search = $this->input->post('search');
        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('section_id', $this->lang->line('section'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('student_id', $this->lang->line('student'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $msg = array(
                'class_id'   => form_error('class_id'),
                'section_id' => form_error('section_id'),
                'student_id' => form_error('student_id'),
            );
            $json_array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $class_section_id = $this->input->post('section_id');
            $student_id       = $this->input->post('student_id');
            $class_id         = $this->input->post('class_id');
            $params           = array('class_section_id' => $class_section_id, 'student_id' => $student_id, "class_id" => $class_id);
            $json_array       = array('status' => 'success', 'error' => '', 'params' => $params);
        }
        echo json_encode($json_array);
    }

    /*This is used to get course list using datatable by class section id and student id*/
    public function courselist()
    {
        $class_section_id = $this->input->post('class_section_id');
        $studentid        = $this->input->post('student_id');
        $class_id         = $this->input->post('class_id');
        $coursedata       = $this->courseofflinepayment_model->courselist($class_section_id);
        $coursedata       = json_decode($coursedata);

        $dt_data = array();
        if (!empty($coursedata->data)) {
            $doc = "";
            foreach ($coursedata->data as $key => $value) {
                $paidstatus = $this->courseofflinepayment_model->getpaidstatus($value->id, $studentid);
                if(!empty($value)){
                if (empty($paidstatus) || $paidstatus['payment_type'] != 'Online') {
                    $free_course     = $value->free_course;
                    $discount        = $value->discount;
                    $price           = $value->price;
                    $discount_price  = '';
                    $price           = '';
                    $lessonquizcount = $this->studentcourse_model->lessonquizcountbycourseid($value->id, '');

                    $lesson_count       = $lessonquizcount['lessoncount'];
                    $quiz_count         = $lessonquizcount['quizcount'];
                    $assignment_count   = $lessonquizcount['assignmentcount'];
                    $exam_count         = $lessonquizcount['examcount'];
                    $section_total      = $this->coursesection_model->getsectioncount($value->id);

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
                        $price = "Free";
                    } elseif (!empty($value->price) && (!empty($value->discount))) {
                        $discount = amountFormat($discount);
                        if ($value->price > '0.00') {
                            $courseprice = amountFormat($value->price);
                        } else {
                            $courseprice = '';
                        }
                        $price = $courseprice;
                    } else {
                        $price = amountFormat($value->price);
                    }

                    $row   = array();
                    $row[] = $value->title;
                    $row[] = $section_total;
                    $row[] = $lessonquizcount['lessoncount'];
                    
                    if($this->customlib->get_online_course_curriculam_status("online_course_quiz")==""){
                        $row[] = $lessonquizcount['quizcount'];
                    }
                    if($this->customlib->get_online_course_curriculam_status("online_course_exam")==""){
                        $row[] = $lessonquizcount['examcount'];
                    }
                    if($this->customlib->get_online_course_curriculam_status("online_course_assignment")==""){
                        $row[] = $lessonquizcount['assignmentcount'];
                    }
                    
                    $row[] = $this->lang->line($value->course_provider);
                    $row[] = $price;
                    $row[] = $discount;
                    if ($paidstatus) {
                        if ($this->rbac->hasPrivilege('online_course_offline_payment', 'can_add')) {
                            $revert = '<button data-backdrop="static" data-id=' . $value->id . ' user-data-id=' . $studentid . ' class-section-id=' . $class_section_id . ' class_id=' . $class_id . ' class="btn btn-danger btn-xs pull-right revert_btn"><i class="fa fa-undo"> </i> ' . $this->lang->line("revert") . ' </button>';
                        } else {
                            $revert = '';
                        }

                        $row[] = $revert . '
                    <button data-backdrop="static" data-id=' . $value->id . ' user-data-id=' . $studentid . '  class="btn btn-primary btn-xs pull-right print_btn"><i class="fa fa-print"></i> ' . $this->lang->line("print") . '</button>';
                    } else {
                        if ($this->rbac->hasPrivilege('online_course_offline_payment', 'can_add')) {
                            $row[] = '<button data-backdrop="static" data-id=' . $value->id . ' user-data-id=' . $studentid . ' class-section-id=' . $class_section_id . ' class_id=' . $class_id . ' data-keyboard="false" data-toggle="modal" data-target="#payment_modal" class="btn btn-primary pull-right btn-xs paid_btn"><i class="fa fa-money"></i> ' . $this->lang->line("pay") . '</button>';
                        } else {
                            $row[] = '';
                        }
                    }
                    $dt_data[] = $row;
                }
                }
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

    /*This is used to get student list by class and section*/
    public function studentlist()
    {
        $class_section_id = $this->input->post('class_section_id');
        $studentlist      = $this->courseofflinepayment_model->studentlist($class_section_id);
        echo json_encode($studentlist);
    }

    /*This is used to show course list by class and section*/
    public function search()
    {
        $this->form_validation->set_rules('section_id', $this->lang->line('section'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('student_id', $this->lang->line('class'), 'trim|required|xss_clean');

        $class             = $this->class_model->get();
        $data['classlist'] = $class;
        if ($this->form_validation->run() == false) {
            $this->load->view('layout/header');
            $this->load->view('onlinecourse/offlinepayment/offlinepayment', $data);
            $this->load->view('layout/footer');
        } else {
            $class_id   = $this->input->post('class_id');
            $section_id = $this->input->post('section_id');
            $student_id = $this->input->post('student_id');
            $courselist = $this->courseofflinepayment_model->courselist($class_id, $section_id);

            $new_courselist = array();
            foreach ($courselist as $courselist_value) {
                $courselist_value['paidstatus'] = $this->courseofflinepayment_model->paidstatus($courselist_value['id'], $student_id);
                $new_courselist[]               = $courselist_value;
            }
            $data['student_id'] = $student_id;
            $data['courselist'] = $new_courselist;
            $this->load->view('layout/header');
            $this->load->view('onlinecourse/offlinepayment/offlinepayment', $data);
            $this->load->view('layout/footer');
        }
    }

    /*This is for payment of course*/
    public function paid()
    {
        $class_section_id         = $this->input->post('class_section_id');
        $class_id                 = $this->input->post('class_id');
        $course_id                = $this->input->post('courseid');
        $studentid                = $this->input->post('studentid');
        $data['studentid']        = $studentid;
        $data['class_section_id'] = $class_section_id;
        $data['class_id']         = $class_id;
        $courseslist              = $this->course_model->singlecourselist($course_id);
        $discount                 = '';
        $price                    = '';
        if (!empty($courseslist['discount'])) {
            $discount = $courseslist['price'] - (($courseslist['price'] * $courseslist['discount']) / 100);
        }
        if (($courseslist["free_course"] == 1) && (empty($courseslist["price"]))) {
            $price = 'Free';
        } elseif (($courseslist["free_course"] == 1) && (!empty($courseslist["price"]))) {
            if ($courseslist['price'] > '0.00') {
                $courseprice = $courseslist['price'];
            } else {
                $courseprice = '';
            }
            $price = $courseprice;
        } elseif (!empty($courseslist["price"]) && (!empty($courseslist["discount"]))) {
            $discount = $discount;
            if ($courseslist['price'] > '0.00') {
                $courseprice = $courseslist['price'];
            } else {
                $courseprice = '';
            }
            $price = $discount;
        } else {
            $price = $courseslist['price'];
        }

        $paymentdata = array(
            'actual_amount' => $courseslist['price'],
            'discount'      => $courseslist['discount'],
            'total_amount'  => $price,
            'course_id'     => $course_id,
            'course_name'   => $courseslist['title'],
            'description'   => $courseslist['description'],
        );
        $data['paymentdata'] = $paymentdata;
        $this->load->view('onlinecourse/offlinepayment/_paid', $data);
    }

    /*This is for success payment of course*/
    public function success()
    {
        $this->form_validation->set_rules('collected_date', $this->lang->line('date'), 'required|xss_clean');

        if ($this->form_validation->run() == false) {
            $msg = array(
                'collected_date' => form_error('collected_date'),
            );
            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $date             = date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('collected_date')));
            $student_id       = $this->input->post('student_id');
            $class_section_id = $this->input->post('class_section_id');
            $class_id         = $this->input->post('pay_class_id');
            $payment_data     = array(
                'date'              => $date,
                'student_id'        => $student_id,
                'online_courses_id' => $this->input->post('courses_id'),
                'course_name'       => $this->input->post('course_name'),
                'actual_price'      => $this->input->post('actual_price'),
                'paid_amount'       => $this->input->post('paid_amount'),
                'payment_type'      => 'Offline',
                'note'              => $this->input->post('fee_note'),
                'payment_mode'      => $this->input->post('payment_mode_fee'),
            );
            $this->course_payment_model->add($payment_data);
            $params = array('class_section_id' => $class_section_id, 'student_id' => $student_id, "class_id" => $class_id);
            $array  = array('status' => 'success', 'student_id' => $student_id, 'class_id' => $class_id, 'class_section_id' => $class_section_id, 'error' => '', 'message' => $this->lang->line('success_message'), 'params' => $params);
        }
        echo json_encode($array);
    }

    /*This is used to print course payment detail*/
    function print() {
        $studentid           = $this->input->post('studentid');
        $courseid            = $this->input->post('courseid');
        $data['courselist']  = $this->courseofflinepayment_model->courseprint($courseid, $studentid);
        $setting_result      = $this->setting_model->get();
        $data['settinglist'] = $setting_result;
        $data['sch_setting'] = $this->sch_setting_detail;

        if($this->thermal_print_module == 1 && $this->thermal_print_enable == 1){  
            $data['thermal_print'] = $this->thermal_print_result;
            $this->load->view('onlinecourse/print/thermal_printfees', $data);   //thermal print    
        }else{
            $this->load->view('onlinecourse/print/printfees', $data); //normal full screen print
        }
    }

    /*This is used to revert course*/
    public function revert()
    {
        if (!$this->rbac->hasPrivilege('online_course_offline_payment', 'can_add')) {
            access_denied();
        }
        $courseid         = $this->input->post('courseid');
        $studentid        = $this->input->post('studentid');
        $class_id         = $this->input->post('class_id');
        $class_section_id = $this->input->post('class_section_id');
        $this->courseofflinepayment_model->delete($courseid, $studentid);
        $params     = array('class_section_id' => $class_section_id, 'student_id' => $studentid, "class_id" => $class_id);
        $json_array = array('status' => 'success', 'error' => '', 'params' => $params);
        echo json_encode($json_array);

    }
}
