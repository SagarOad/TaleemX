<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Course extends Front_Controller
{

    public function __construct()
    {
        parent::__construct();

        $this->load->library('captchalib');
        $this->load->library('module_lib');
        $this->load->library('course_mail_sms');
        $this->load->library('mailsmsconf');
        $this->banner_content = $this->config->item('ci_front_banner_content');
        $this->load->model(array('course_model', 'coursesection_model', 'courselesson_model', 'studentcourse_model', 'coursequiz_model', 'course_payment_model', 'courseofflinepayment_model', 'coursereport_model', 'coursecategory_model','userlog_model'));
        $this->load->library(array('enc_lib', 'cart', 'auth'));
        $this->setting     = $this->setting_model->get();
        $this->result      = $this->customlib->getLoggedInUserData();
        $this->sch_setting = $this->setting_model->getSetting();
        $ban_notice_type              = $this->config->item('ci_front_notice_content');         
        $this->data['banner_notices'] = $this->cms_program_model->getByCategory($ban_notice_type, array('start' => 0, 'limit' => 5));           
    }

    public function index()
    {
        if (!$this->module_lib->hasActive('online_course')) {
            redirect('page/404-page', 'refresh');
        }
        
        $guest_login = $this->course_model->getOnlineCourseSettings();
        if($guest_login->guest_login != 1){
            redirect('page/404-page', 'refresh');
        }
        
        $this->data['active_menu'] = 'online_admission';
        $page                      = array('title' => 'Online Course', 'meta_title' => 'online course', 'meta_keyword' => 'online course', 'meta_description' => 'online course');
        $this->data['page_side_bar']  = false;
        $this->data['featured_image'] = false;
        $this->data['page']           = $page; 

        $home_page_slug                 = "online_course";
        $setting                        = $this->frontcms_setting_model->get();
        $this->data['active_menu']      = $home_page_slug;      
        $this->data['cookie_consent']   = $setting->cookie_consent;   

        $search_course         = $this->input->get('search_course');
        $data['search_course'] = '';
        $search                = ($this->uri->segment(3)) ? $this->uri->segment(3) : $search_course;
        $coursecount           = '';
        if ($search_course != '') {
            $coursecount           = $this->course_model->studentcourselist('', '', $search);
            $data['search_course'] = $search;
        } else {
            $coursecount = $this->course_model->studentcourselist('', '', '');
        }
        $this->load->library('pagination');

        if ($this->uri->segment(3) > 1) {
            $config['base_url'] = base_url() . "course/index/" . $this->uri->segment(4) . "/$search_course";
        } else {
            $config['base_url'] = base_url() . "course/index/$search_course";
        }

        $config['total_rows'] = count($coursecount);
        $config['per_page']   = 30;
        // custom paging configuration
        $config['use_page_numbers']   = true;
        $config['reuse_query_string'] = true;
        $config['full_tag_open']      = '<div class="pagination">';
        $config['full_tag_close']     = '</div>';
        $config['first_link']         = '<i class="fa fa-step-backward" aria-hidden="true"></i>';
        $config['first_tag_open']     = '<span class="firstlink">';
        $config['first_tag_close']    = '</span>';
        $config['last_link']          = '<i class="fa fa-step-forward" aria-hidden="true"></i>';
        $config['last_tag_open']      = '<span class="lastlink">';
        $config['last_tag_close']     = '</span>';
        $config['next_link']          = '<i class="fa fa-forward" aria-hidden="true"></i>';
        $config['next_tag_open']      = '<span class="nextlink">';
        $config['next_tag_close']     = '</span>';
        $config['prev_link']          = '<i class="fa fa-backward" aria-hidden="true"></i>';
        $config['prev_tag_open']      = '<span class="prevlink">';
        $config['prev_tag_close']     = '</span>';
        $config['cur_tag_open']       = '<span class="curlink">';
        $config['cur_tag_close']      = '</span>';
        $config['num_tag_open']       = '<span class="numlink">';
        $config['num_tag_close']      = '</span>';
        $page_num                     = ($this->uri->segment(3)) ? $this->uri->segment(3) : 1;
        $this->pagination->initialize($config);
        $this->data['userid'] = "";

        if (!empty($this->result["role"])) {

            $role               = $this->result["role"];
            $this->data['role'] = $role;

            if ($role == 'student') {
                $userid = $this->result["student_id"];
            } else {
                $userid = $this->result["guest_id"];
            }
            $this->data['userid'] = $userid;
        }    

        $new_courselist = array();

        foreach ($coursecount as $courselist_value) {
            $lesson_count                         = $this->studentcourse_model->totallessonbycourse($courselist_value['id']);
            $courselist_value['total_lesson']     = $lesson_count[0]['total_lesson'];
            $courselist_value['total_hour_count'] = $this->studentcourse_model->counthours($courselist_value['id']);
            $courserating                         = $this->studentcourse_model->getcourserating($courselist_value['id']);

            $rating            = 0;
            $averagerating     = 0;
            $totalcourserating = 0;

            if (!empty($courserating)) {
                foreach ($courserating as $courserating_value) {
                    $rating = $rating + $courserating_value['rating'];
                }

                $averagerating = $rating / count($courserating);
            }

            $courselist_value['paidstatus'] = $this->courseofflinepayment_model->paidstatus($courselist_value['id'], $this->data['userid']);

            $courselist_value['totalcourserating'] = count($courserating);
            $courselist_value['courserating']      = $averagerating;

            $courseSale                      = $this->course_model->getCourseSale($courselist_value["id"]);
            $courselist_value["course_sale"] = $courseSale;
            $new_courselist[]                = $courselist_value;
        }

        $filterRating                 = $this->course_model->getFilterRating();
        $this->data["filterRating"]   = $filterRating;
        $filterSale                   = $this->course_model->getFilterSale();
        $this->data["filterSale"]     = $filterSale;
        $filterPrice                  = $this->course_model->getFilterPrice();
        $this->data["filterPrice"]    = $filterPrice;
        $this->data['new_courselist'] = $new_courselist;
        $this->data['coursecount']    = $coursecount;

        $this->data['categorylist'] = $this->coursecategory_model->getcategorywithcoursecount();

        $cartdata = $this->cart->contents();
        foreach ($cartdata as $key => $value) {
            $coursedetails = $this->course_model->singlecourselist($value['id']);
            if ($coursedetails['status'] == 0) {
                $this->cart->remove($key);
            }
        }

        $currencies               = get_currency_list();
        $this->data['currencies'] = $currencies;        
        $this->data['course_setting'] = $this->course_model->getOnlineCourseSettings();        
        $this->data['setting_data'] = $this->setting_model->get();
        $this->load_theme('course/index', $data);

    }

    public function coursedetail($slug)
    {
        if (!$this->module_lib->hasActive('online_course')) {
            redirect('page/404-page', 'refresh');
        }
        $guest_login = $this->course_model->getOnlineCourseSettings();
        if($guest_login->guest_login != 1){
            redirect('page/404-page', 'refresh');
        }

        $this->data['active_menu'] = 'online_admission';
        $page                      = array('title' => $slug, 'meta_title' => $slug, 'meta_keyword' => $slug, 'meta_description' => $slug);
        $this->data['page_side_bar']  = false;
        $this->data['featured_image'] = false;
        $this->data['page']           = $page; 

        $home_page_slug = "online_course";
        $setting                   = $this->frontcms_setting_model->get();
        $this->data['active_menu'] = $home_page_slug;      
        $this->data['cookie_consent'] = $setting->cookie_consent; 

        $this->data['userid'] = "";

        if (!empty($this->result["role"])) {

            $role               = $this->result["role"];
            $this->data['role'] = $role;

            if ($role == 'student') {
                $userid = $this->result["student_id"];
            } else {
                $userid = $this->result["guest_id"];
            }
            $this->data['userid'] = $userid;
        }

        $courseID = $this->course_model->getIdBySlug(urldecode($slug));

        $lessonquizcount = $this->studentcourse_model->lessonquizcountbycourseid($courseID, '');

        if (!empty($lessonquizcount['lessoncount'])) {
            $this->data['total_lesson'] = $lessonquizcount['lessoncount'];
        } else {
            $this->data['total_lesson'] = '';
        }

        if (!empty($lessonquizcount['quizcount'])) {
            $this->data['total_quiz'] = $lessonquizcount['quizcount'];
        } else {
            $this->data['total_quiz'] = '';
        }

        if (!empty($lessonquizcount['assignmentcount'])) {
            $this->data['total_assignment'] = $lessonquizcount['assignmentcount'];
        } else {
            $this->data['total_assignment'] = '';
        }
        if (!empty($lessonquizcount['examcount'])) {
            $this->data['total_exam'] = $lessonquizcount['examcount'];
        } else {
            $this->data['total_exam'] = '';
        }        

        $this->data['total_hour_count'] = $this->studentcourse_model->counthours($courseID);

        $coursesList = $this->course_model->singlecourselist($courseID);

        if (!empty($coursesList)) {
            $viewcount['id']         = $courseID;
            $viewcount['view_count'] = $coursesList['view_count'] + 1;
            $this->course_model->add($viewcount);
        }

        $coursesList['paidstatus'] = $this->courseofflinepayment_model->paidstatus($courseID, $this->data['userid']);
        $this->data['coursesList'] = $coursesList;

        $sectionList               = $this->coursesection_model->getsectionbycourse($courseID);
        $this->data['sectionList'] = $sectionList;

        $lessonquizlist_array = array();

        if (!empty($sectionList)) {
            foreach ($sectionList as $sectionList_value) {
                $lessonquizlist_array[$sectionList_value->id] = $this->coursesection_model->lessonquizbysection($sectionList_value->id);
            }
            $this->data['lessonquizdetail'] = $lessonquizlist_array;
        } else {
            $this->data['lessonquizdetail'] = '';
        }

        //course rating code start
        $courserating      = $this->studentcourse_model->getcourserating($courseID);
        $rating            = 0;
        $averagerating     = 0;
        $totalcourserating = 0;

        if (!empty($courserating)) {
            foreach ($courserating as $courserating_value) {
                $rating = $rating + $courserating_value['rating'];
            }
            $averagerating = $rating / count($courserating);
        }

        $this->data['totalcourserating'] = count($courserating);
        $this->data['courserating']      = $averagerating;
        $this->data['coursereview']      = $courserating;

        $otherrelatedcourses = $this->course_model->otherRelatedCourses($courseID, $coursesList['created_by'], $this->data['userid']);

        foreach ($otherrelatedcourses as $key => $othercourse_value) {
            $otherrelatedcourses[$key]['otherpaidstatus'] = $this->courseofflinepayment_model->paidstatus($othercourse_value['id'], $this->data['userid']);
        }

        $this->data["otherrelatedcourses"] = $otherrelatedcourses;

        $avg          = 0;
        $rate         = array();
        $percentvalue = array();

        if (!empty($courserating)) {
            $sumrating   = array_sum(array_column($courserating, 'rating'));
            $totalrating = sizeof($courserating);
            $avg         = $sumrating / $totalrating;
            for ($i = 1; $i <= 5; $i++) {
                $rate[$i]         = $this->course_model->countRating($i, $courseID);
                $percentvalue[$i] = round(($rate[$i] / $totalrating), 2) * 100;
            }
        }

        $this->data['percentvalue'] = $percentvalue;
        $this->data["avgRating"]    = $avg;

        $currencies               = get_currency_list();
        $this->data['currencies'] = $currencies;
        
        $this->data['course_setting'] = $this->course_model->getOnlineCourseSettings();       
        $this->data['setting_data'] = $this->setting_model->get();
        $this->load_theme('course/coursedetail');
    }

    public function guestsignup()
    {
        $this->form_validation->set_rules('name', $this->lang->line('name'), 'required');
        $this->form_validation->set_rules('password', $this->lang->line('password'), 'required');

        $this->form_validation->set_rules(
            'email', $this->lang->line('email'), array('required', 'valid_email',
                array('check_exists', array($this->studentcourse_model, 'valid_guest_email_id')),
            )
        );

        $is_captcha = $this->captchalib->is_captcha('guest_login_signup');
        if ($is_captcha) {
            $this->form_validation->set_rules('captcha', $this->lang->line('captcha'), 'trim|required|callback_check_captcha');
        }

        $data["title"] = "Add Guest";
        $email         = $this->input->post('email');
        if ($this->form_validation->run()) {
            $name           = $this->input->post("name");
            $insert         = true;
            $course_setting = $this->course_model->getOnlineCourseSettings();

            $guest_unique_id = $course_setting->guest_prefix . '' . $course_setting->guest_id_start_from;
            $last_guest      = $this->studentcourse_model->lastRecord();

            if (!empty($last_guest)) {
                $last_guest_id   = str_replace($course_setting->guest_prefix, "", $last_guest->guest_unique_id);
                $guest_unique_id = $course_setting->guest_prefix . ($last_guest_id + 1);
            }

            $check_guest_exists = $this->course_model->check_guest_exists($guest_unique_id);

            if ($check_guest_exists) {
                $insert = false;
            }

            if ($insert) { 

                $setting_result = $this->setting_model->get();
                $default        = $setting_result[0]['lang_id'];

                $login_post = array(
                    'username' => $email,
                    'password' => $this->input->post('password'),
                );

                $data = array(
                    'guest_unique_id' => $guest_unique_id,
                    'guest_name'      => $this->input->post('name'),
                    'email'           => $email,
                    'password'        => $this->enc_lib->passHashEnc($this->input->post('password')),
                    'created_at'      => date("Y-m-d"),
                    'is_active'       => 'yes',
                    'lang_id'         => $default,
                    'currency_id'     => $this->customlib->getSchoolCurrency(),
                );

                $data      = $this->security->xss_clean($data);
                $insert_id = $this->studentcourse_model->addguest($data);

                $sender_details = array('email' => $email, 'guest_user_name' => $this->input->post('name'), 'url' => base_url());

                $this->course_mail_sms->purchasemail('online_course_guest_user_sign_up', $sender_details);

                $login_detail = $this->studentcourse_model->checkLogin($login_post);

                if (isset($login_detail) && !empty($login_detail)) {
                    $user = $login_detail;

                    if ($user->is_active == "yes") {

                        if ($login_detail->type == "student") {
                            $result = $this->user_model->read_user_information($user->id);
                        } elseif ($login_detail->type == "guest") {
                            $result = $this->studentcourse_model->read_user_information($user->id);
                        }

                        if ($result != false) {

                            $sch_setting = $this->setting_model->getSetting();

                            if ($result[0]->lang_id == 0) {
                                $language = array('lang_id' => $sch_setting->lang_id, 'language' => $sch_setting->language);
                            } else {
                                $language = array('lang_id' => $result[0]->lang_id, 'language' => $result[0]->language);
                            }

                            if ($login_detail->type == "student") {
                                $login_username = $result[0]->username;
                                $student_id     = $result[0]->user_id;
                                $role           = $result[0]->role;
                                $image          = $result[0]->image;
                                $guest_id       = "";
                                $username       = $this->customlib->getFullName($result[0]->firstname, $result[0]->middlename, $result[0]->lastname, $sch_setting->middlename, $sch_setting->lastname);
                                $defaultclass   = $this->user_model->get_studentdefaultClass($result[0]->user_id);
                                $this->customlib->setUserLog($result[0]->username, $result[0]->role, $defaultclass['id']);

                            } elseif ($login_detail->type == "guest") {
                                $image          = $result[0]->guest_image;
                                $username       = $result[0]->guest_name;
                                $login_username = "";
                                $student_id     = "";
                                $guest_id       = $result[0]->id;
                                $role           = 'guest';
                            }
							
							$print_heder_image        = $this->setting_model->get_general_purpose_header();

                            $session_data = array(
                                'id'                  => $result[0]->id,
                                'login_username'      => $login_username,
                                'student_id'          => $student_id,
                                'role'                => $role,
                                'username'            => $username,
                                'guest_id'            => $guest_id,
                                'currency_format'     => $sch_setting->currency_format,
                                'date_format'         => $sch_setting->date_format,                                
                                'timezone'            => $sch_setting->timezone,
                                'sch_name'            => $sch_setting->name,
                                'language'            => $language,
                                'is_rtl'              => $sch_setting->is_rtl,
								'theme'                  => [
									'theme_color' => $setting_result[0]['theme_color'],
									'theme_shadow' => $setting_result[0]['theme_shadow'],
									'theme_background' => $setting_result[0]['theme_background'],
									'theme_content' => $setting_result[0]['theme_content'],
									'theme_type' => $setting_result[0]['theme_type'],
									'theme_navigation' => $setting_result[0]['theme_navigation'],
									'theme_font_color' => $setting_result[0]['theme_font_color'],
								],
                                'start_week'          => $sch_setting->start_week,
                                'image'               => $image,
                                'gender'              => $result[0]->gender,
                                'currency'            => ($result[0]->currency == 0) ? $setting_result[0]['currency_id'] : $result[0]->currency,
                                'currency_base_price' => ($result[0]->base_price == 0) ? $setting_result[0]['base_price'] : $result[0]->base_price,
                                'currency_format'     => $setting_result[0]['currency_format'],
                                'currency_symbol'     => ($result[0]->symbol == "0") ? $setting_result[0]['currency_symbol'] : $result[0]->symbol,
                                'db_array'            => ['base_url'    =>  $setting_result[0]['base_url'],
                                                     'folder_path'  =>  $setting_result[0]['folder_path'],
                                                     'db_group' =>  'default'],
								'print_heder_image' =>  IsNullOrEmptyString($print_heder_image) ? "": $print_heder_image,
								'saas_key'               	=> $setting_result[0]['saas_key'],

                            );

                            $language_result1 = $this->language_model->get($language['lang_id']);
                            if ($this->customlib->get_rtl_languages($language_result1['short_code'])) {
                                $session_data['is_rtl'] = 'enabled';
                            }
							
							$this->customlib->setUserLog($result[0]->email, $role, null);
							
                            $this->session->set_userdata('student', $session_data);

                            if ($this->input->post('checkout_status') == 1) {
                                $redirect_url = $_SERVER['HTTP_REFERER'];
                            } else {
                                $redirect_url = ($role == "student") ? site_url('user/user/dashboard') : site_url("user/studentcourse");
                            }

                            $message    = $this->lang->line('login_successfully');
                            $json_array = array('status' => '1', 'error' => '', 'message' => $message, 'redirect_url' => $redirect_url);
                        } else {

                            $message = array(
                                'suspended' => $this->lang->line('account_suspended'),
                            );
                            $json_array = array('status' => '0', 'error' => $message, 'message' => $message);
                        }
                    } else {

                        $message = array(
                            'disabled' => $this->lang->line('your_account_is_disabled_please_contact_to_administrator'),
                        );
                        $json_array = array('status' => '0', 'error' => $message, 'message' => $message);
                    }
                } else {

                    $message = array(
                        'username' => $this->lang->line('invalid_email_id_or_password'),
                    );
                    $json_array = array('status' => '0', 'error' => $message, 'message' => '');
                }

            } else {
                $message = array(
                    'disabled' => $this->lang->line('guest') . ' ' . $guest_unique_id . ' ' . $this->lang->line('already_exists'),
                );
                $json_array = array('status' => 'fail', 'error' => $message, 'message' => '');
            }

        } else {
            $message2 = array();
            $message1 = array(
                'name'     => form_error('name'),
                'email'    => form_error('email'),
                'password' => form_error('password'),
            );

            if ($is_captcha) {
                $message2 = array(
                    'captcha' => form_error('captcha'),
                );
            }
            $message    = array_merge($message1, $message2);
            $json_array = array('status' => 'fail', 'error' => $message, 'message' => '');
        }
        if (isset($insert_id)) {
            $sender_detail = array('student_id' => $insert_id, 'contact_no' => '', 'email' => $email);
        }
        echo json_encode($json_array);
    }

    public function logout()
    {
        $items = $this->cart->contents();
        if (!empty($items)) {
            $this->cart->destroy();
        }
        $student_session = $this->session->userdata('student');
        $this->auth->userlogout();
        redirect('course');
    }
    
    public function guestlogin()
    {       
        $data           = array();
        $data['title']  = 'Login';
        $site           = $this->setting_model->get();
        $notice_content = $this->config->item('ci_front_notice_content');
        $notices        = $this->cms_program_model->getByCategory($notice_content, array('start' => 0, 'limit' => 5));
        $data['notice'] = $notices;
        $data['school'] = $site[0];

        $this->form_validation->set_rules('username', $this->lang->line('email_id'), 'trim|required');
        $this->form_validation->set_rules('password', $this->lang->line('password'), 'trim|required|xss_clean');

        $is_captcha = $this->captchalib->is_captcha('guest_login_signup');

        if ($is_captcha) {
            $this->form_validation->set_rules('captcha', $this->lang->line('captcha'), 'trim|required|callback_check_captcha');
        }

        if ($this->form_validation->run() == false) {
            $message2 = array();
            $message1 = array(
                'username' => form_error('username'),
                'password' => form_error('password'),
            );
            if ($is_captcha) {
                $message2 = array(
                    'captcha' => form_error('captcha'),
                );
            }

            $message    = array_merge($message1, $message2);
            $json_array = array('status' => '0', 'error' => $message, 'message' => '');
        } else {
            
            $login_post = array(
                'username' => $this->input->post('username'),
                'password' => $this->input->post('password'),
            );
            
            $login_detail = $this->studentcourse_model->checkLogin($login_post);

            if (isset($login_detail) && !empty($login_detail)) {
                
                $user = $login_detail;

                if ($user->is_active == "yes") {
                                        
                    $result = $this->studentcourse_model->read_user_information($user->id);
          
                    if ($result != false) {                      
                   
                        $result[0]->{"role"}    =   $login_detail->type;                    
                        $gauthenticate          =   false;
                        $login_through_url      =   true;
                        $message    =   "";

                        $user_role  =   ($login_detail->type == "guest") ? 'guest': 'user';       

                        if ($this->module_lib->hasModule('google_authenticator') && $this->module_lib->hasActive('google_authenticator')) {                       

                            $this->load->model("google_authenticator/gauthuser_model");
                            $user_using_authenticate = $this->gauthuser_model->check_user_exists($user_role, $result[0]->id);

                            if(is_active_2fa() && $user_using_authenticate){
 
                                $gauthenticate           = true;
                                $login_through_url      =false;
                            }
                            
                            $json_array = array('status' => '1', 'error' => '', 'message' => $message,'gauthenticate'=>$gauthenticate);
                            
                        }

                        if($login_through_url){
                            
                            $this->user_session($result,$login_detail);
                            
                            if ($this->input->post('checkout_status') == 1) {
                                
                                $redirect_url = $_SERVER['HTTP_REFERER'];
                                
                            } else {
                                
                                $redirect_url = site_url("user/studentcourse");
                                
                            }
                        
                            $cart_data = $this->cart->contents();
                         
                            if(!empty($cart_data)){
                                $count = 0 ;
                                $course_name ='';
                                foreach ($cart_data as $key => $value) {
                                    $checkpurchase = $this->customlib->getPurchasedCourseId($user->id, $value['id']); 
                                
                                    if (!empty($checkpurchase)) { 
                                
                                        $rowid = $value['rowid'];
                                        $this->cart->remove($rowid); 
                                        $count = 1 ;                                    
                                        $course_name = $course_name .'<li>'. $value['name'].'</li>';
                                     
                                    }
                                } 
                            
                                if($count == 0){
                                    $message    = $this->lang->line('login_successfully');
                                }else{
                                    $message    = $this->lang->line('login_successfully');
                                
                                    $message1    = $this->lang->line('following_already_purchased_courses_has_been_removed_from_your_cart') .' -' . ltrim($course_name, ',');                                
                                    $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $message1 . '</div>');                                
                                }
                            
                            }else{                          
                                $message    = $this->lang->line('login_successfully');
                            }
                        
                            $json_array = array('status' => '1', 'error' => '', 'message' => $message, 'redirect_url' => $redirect_url,'gauthenticate'=>$gauthenticate);
                        }       
                  
                    } else {
                        $message = array(
                            'Suspended' => $this->lang->line('account_suspended'),
                        );
                        $json_array = array('status' => '0', 'error' => $message, 'message' => $message);
                    }
                } else {
                    $message = array(
                        'disabled' => $this->lang->line('your_account_is_disabled_please_contact_to_administrator'),
                    );
                    $json_array = array('status' => '0', 'error' => $message, 'message' => $message);
                }
            } else {
                $message = array(
                    'username' => $this->lang->line('invalid_email_id_or_password'),
                );

                $json_array = array('status' => '0', 'error' => $message, 'message' => '');
            }
        }

        echo json_encode($json_array);
    }


    public function user_submit_login()
    {
        $this->form_validation->set_error_delimiters('<p>', '</p>');
        $this->form_validation->set_rules('username', $this->lang->line('username'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('password', $this->lang->line('password'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('gauth_code', $this->lang->line('verification_code'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $data = array(
                'username' => form_error('username'),
                'password' => form_error('password'),
                'gauth_code'     => form_error('gauth_code'),
            );
            $array = array('status' => 0, 'error' => $data);
            echo json_encode($array);
        } else {
            $login_post = array(
                'username' => $this->input->post('username'),
                'password' => $this->input->post('password'),
            );

            $login_details = $this->studentcourse_model->checkLogin($login_post);           

            if (isset($login_details) && !empty($login_details)) {
                $user = $login_details;
                    
                    $user_role=($login_details->type == "guest") ? 'guest': 'user';
                    
                    $result = $this->studentcourse_model->read_user_information($user->id);

                    $this->load->model("google_authenticator/gauthuser_model");

                    $gauth = $this->gauthuser_model->getByUser($user_role, $result[0]->id);
                    
                    if (!$gauth) {
                        $data['error_message'] = "<div class='alert alert-danger'>" . $this->lang->line('something_went_wrong') . "</div>";
                        $array                 = array('status' => 1, 'error' => $data);
                        echo json_encode($array);
                        exit();
                    
                    } else {
                        $this->load->library('Google_authenticator');
                        $verify_code = $this->google_authenticator->verifyQR($gauth->secret_code, $this->input->post('gauth_code'));
    
                        if (!$verify_code) {
                            $data['error_message'] = $this->lang->line('invalid_code');
                            $array                 = array('status' => 1, 'error' => $data);
                            echo json_encode($array);
                            exit();
                        }
                    }

                    if ($user->is_active == "yes") {                 
                
                        if ($result != false) {
                            
                            $this->user_session($result,$login_details);                            
                            
                            $redirect_url = site_url("user/studentcourse");
                            
                            $array = array('status' => 2, 'error' => "", 'redirect_to' => $redirect_url);
                            echo json_encode($array);
                        } else {
                            $data['error_message'] = 'Account Suspended';
                            $array                 = array('status' => 1, 'error' => $data);
                            echo json_encode($array);
                        }
                    } else {
                        $data['error_message'] = $this->lang->line('your_account_is_disabled_please_contact_to_administrator');
                        $array                 = array('status' => 1, 'error' => $data);
                        echo json_encode($array);
                    }
            } else {
                $data['error_message'] = $this->lang->line('invalid_username_or_password');
                $array                 = array('status' => 1, 'error' => $data);
                echo json_encode($array);
            }
        }
    }
    
    public function user_session($result,$login_detail)
    {	
        $sch_setting = $this->setting_model->getSetting();
        
        if ($result[0]->lang_id == 0) {
            $language = array('lang_id' => $sch_setting->lang_id, 'language' => $sch_setting->language);
        } else {
            $language = array('lang_id' => $result[0]->lang_id, 'language' => $result[0]->language);
        }      
                                                
        $image          = $result[0]->guest_image;
        $username       = $result[0]->guest_name;
        $login_username = "";
        $student_id     = "";
        $guest_id       = $result[0]->id;
        $role           = 'guest';
		
		$print_heder_image        = $this->setting_model->get_general_purpose_header();
		
        $session_data = array(
            'id'                  => $result[0]->id,
            'login_username'      => $login_username,
            'student_id'          => $student_id,
            'role'                => $role,
            'username'            => $username,
            'guest_id'            => $guest_id,
            'currency'            => ($result[0]->currency == 0) ? $sch_setting->currency_id : $result[0]->currency,
            'currency_base_price' => ($result[0]->base_price == 0) ? $sch_setting->base_price : $result[0]->base_price,
            'currency_symbol'     => ($result[0]->symbol == "0") ? $sch_setting->currency_symbol : $result[0]->symbol,
            'currency_name'       => ($result[0]->currency_name == 0) ? $sch_setting->currency : $result[0]->currency_name,
            'currency_format'     => $sch_setting->currency_format,
            'date_format'         => $sch_setting->date_format,
            'timezone'            => $sch_setting->timezone,
            'sch_name'            => $sch_setting->name,
            'language'            => $language,
            'is_rtl'              => $sch_setting->is_rtl,		
			'theme'               => [
                            'theme_color' => $sch_setting->theme_color,
                            'theme_shadow' => $sch_setting->theme_shadow,
                            'theme_background' => $sch_setting->theme_background,
                            'theme_content' => $sch_setting->theme_content,
                            'theme_type' => $sch_setting->theme_type,
                            'theme_navigation' => $sch_setting->theme_navigation,
                            'theme_font_color' => $sch_setting->theme_font_color,
                        ],
						
            'start_week'          => $sch_setting->start_week,
            'image'               => $image,
            'gender'              => $result[0]->gender,
            'email'               => $result[0]->email,
            'db_array'            => ['base_url'    =>  $sch_setting->base_url,
                                    'folder_path'  =>  $sch_setting->folder_path,
                                    'db_group' =>  'default'],
			'print_heder_image' =>  IsNullOrEmptyString($print_heder_image) ? "": $print_heder_image,			 
			'saas_key'               	=> $sch_setting->saas_key,
        );		
		
		$this->customlib->setUserLog($result[0]->email, $role, null);		
		
        $language_result1 = $this->language_model->get($language['lang_id']);
        if ($this->customlib->get_rtl_languages($language_result1['short_code'])) {
            $session_data['is_rtl'] = 'enabled';
        }
        
        $this->session->set_userdata('student', $session_data);

    }    
    
    public function filterRecords()
    {
        $this->data['userid'] = "";
        if (!empty($this->result["role"])) {
            $role               = $this->result["role"];
            $this->data['role'] = $role;

            if ($role == 'student') {
                $userid = $this->result["student_id"];
            } else {
                $userid = $this->result["guest_id"];
            }
            $this->data['userid'] = $userid;
        }

        $menu_list                = $this->cms_menu_model->getBySlug('main-menu');
        $this->data['main_menus'] = $this->cms_menuitems_model->getMenus($menu_list['id']);
        reset($this->data['main_menus']);

        $home_page_slug = "online_course";

        $setting                      = $this->frontcms_setting_model->get();
        $this->data['active_menu']    = $home_page_slug;
        $this->data['page_side_bar']  = $setting->is_active_sidebar;
        $this->data['cookie_consent'] = $setting->cookie_consent;
        $result                       = $this->cms_program_model->getByCategory($this->banner_content);
        $this->data['page']           = $this->cms_page_model->getBySlug($home_page_slug);
        if (!empty($result)) {
            $this->data['banner_images'] = $this->cms_program_model->front_cms_program_photos($result[0]['id']);
        }
        $fields = $this->input->post('searchdata');

        $data["fields"]    = $fields;
        $courselistdata    = array();
        $coursedisplaydata = array();

        if (!empty($fields)) {
            foreach ($fields as $key => $arrvalue) {
                if (!empty($arrvalue)) {
                    $courselistdata[] = $this->course_model->filterRecords($arrvalue);
                }
            }
        }

        if (!empty($courselistdata)) {
            foreach ($courselistdata as $skey => $courselist) {
                $rating       = 0;
                $courseRating = 0;
                foreach ($courselist as $key => $value) {
                    $lesson_count                         = $this->course_model->countlesson($value["id"]);
                    $courselist[$key]["total_lesson"]     = $lesson_count;
                    $hours_count                          = $this->course_model->counthours($value["id"]);
                    $courselist[$key]["total_hour_count"] = $hours_count;
                    $courseSale                           = $this->course_model->getCourseSale($value["id"]);
                    $courselist[$key]["course_sale"]      = $courseSale;
                    $courseRating                         = $this->studentcourse_model->getcourserating($value['id']);
                    $rating                               = 0;
                    $averagerating                        = 0;
                    if (!empty($courseRating)) {
                        foreach ($courseRating as $courserating_value) {
                            $rating = $rating + $courserating_value['rating'];
                        }
                        $averagerating = $rating / count($courseRating);
                    }

                    $courselist[$key]['totalcourserating'] = count($courseRating);
                    $courselist[$key]['courserating']      = $averagerating;
                    $courselist[$key]['paidstatus']        = $this->courseofflinepayment_model->paidstatus($value['id'], $this->data['userid']);
                }
                $coursedisplaydata[] = $courselist;
            }
        }

        if (!empty($newrating)) {
            $data['newrating'] = $newrating;
        }

        $this->data["courselist"] = $coursedisplaydata;
        $this->load->view('themes/_searchResults', $this->data);

    }

    public function filterRecordsByPrice()
    {
        $this->data['userid'] = "";
        if (!empty($this->result["role"])) {
            $role               = $this->result["role"];
            $this->data['role'] = $role;
            if ($role == 'student') {
                $userid = $this->result["student_id"];
            } else {
                $userid = $this->result["guest_id"];
            }
            $this->data['userid'] = $userid;
        }

        $menu_list                = $this->cms_menu_model->getBySlug('main-menu');
        $this->data['main_menus'] = $this->cms_menuitems_model->getMenus($menu_list['id']);
        reset($this->data['main_menus']);

        $home_page_slug = "online_course";

        $setting                      = $this->frontcms_setting_model->get();
        $this->data['active_menu']    = $home_page_slug;
        $this->data['page_side_bar']  = $setting->is_active_sidebar;
        $this->data['cookie_consent'] = $setting->cookie_consent;
        $result                       = $this->cms_program_model->getByCategory($this->banner_content);
        $this->data['page']           = $this->cms_page_model->getBySlug($home_page_slug);
        if (!empty($result)) {
            $this->data['banner_images'] = $this->cms_program_model->front_cms_program_photos($result[0]['id']);
        }

        $coursedisplaydata = array();
        $startrange        = $this->input->post("startrange");
        $endrange          = $this->input->post("endrange");

        $courselist = $this->course_model->filterRecordsByPrice($startrange, $endrange);

        foreach ($courselist as $key => $value) {
            $lesson_count                          = $this->course_model->countlesson($value["id"]);
            $courselist[$key]["total_lesson"]      = $lesson_count;
            $hours_count                           = $this->course_model->counthours($value["id"]);
            $courselist[$key]["total_hour_count"]  = $hours_count;
            $courseSale                            = $this->course_model->getCourseSale($value["id"]);
            $courselist[$key]["course_sale"]       = $courseSale;
            $courseRating                          = $this->studentcourse_model->getcourserating($value['id']);
            $rating                                = 0;
            $averagerating                         = 0;
            $courselist[$key]['totalcourserating'] = count($courseRating);
            if (!empty($courseRating)) {
                foreach ($courseRating as $courserating_value) {
                    $courserating_value['rating'];
                    $rating = $rating + $courserating_value['rating'];
                }
                $averagerating = $rating / count($courseRating);
            }

            $courselist[$key]['paidstatus'] = $this->courseofflinepayment_model->paidstatus($value['id'], $this->data['userid']);

            $courselist[$key]['courserating'] = $averagerating;
            $coursedisplaydata[0]             = $courselist;
        }
        foreach ($courselist as $nac) {
            $courseRating = $this->course_model->getCourseRating($nac["id"]);
            if (!empty($courseRating)) {
                $totalrating           = sizeof($courseRating);
                $newrating[$nac["id"]] = $totalrating;
            }
        }
        if (!empty($newrating)) {
            $this->data['newrating'] = $newrating;
        }
        $courselist               = array_map("unserialize", array_unique(array_map("serialize", $courselist)));
        $this->data["courselist"] = $coursedisplaydata;
        $this->load->view('themes/_searchResults', $this->data);
    }

    public function sortRecords()
    {
        $this->data['userid'] = "";

        if (!empty($this->result["role"])) {

            $role               = $this->result["role"];
            $this->data['role'] = $role;

            if ($role == 'student') {
                $userid = $this->result["student_id"];
            } else {
                $userid = $this->result["guest_id"];
            }
            $this->data['userid'] = $userid;
        }

        $menu_list                = $this->cms_menu_model->getBySlug('main-menu');
        $this->data['main_menus'] = $this->cms_menuitems_model->getMenus($menu_list['id']);      

        $home_page_slug = "online_course";

        $setting                      = $this->frontcms_setting_model->get();
        $this->data['page_side_bar']  = $setting->is_active_sidebar;
        $this->data['cookie_consent'] = $setting->cookie_consent;
        $this->data['page']           = $this->cms_page_model->getBySlug($home_page_slug);

        $search_radio = $this->input->post('searchradio');
        $search_check = $this->input->post('searchcheck');
        $startrange   = $this->input->post("pricestartrange");
        $endrange     = $this->input->post("priceendrange");
        $sortdata     = $this->input->post('sortdata');
        $coursedata   = $this->input->post('coursedata');
        $sort         = '';
        $sort_type    = SORT_DESC;
        if ($sortdata == 'bestsell') {
            $sort = 'sale';
        }
        if ($sortdata == 'bestrated') {
            $sort      = 'rating';
            $sort_type = SORT_DESC;
        }
        if ($sortdata == 'newest') {
            $sort = 'created_date';
        }
        if ($sortdata == 'price-asc') {
            $sort      = 'present_price';
            $sort_type = SORT_ASC;
        }
        if ($sortdata == 'price-desc') {
            $sort      = 'present_price';
            $sort_type = SORT_DESC;
        }
        $fields            = $search_radio;
        $courselistdata    = array();
        $coursedisplaydata = array();
        if (!empty($fields)) {
            foreach ($fields as $key => $arrvalue) {
                if (!empty($arrvalue)) {
                    $result = $this->course_model->filterRecords($arrvalue);
                    if (!empty($sort)) {
                        array_multisort(array_column($result, $sort), $sort_type, $result);
                    }
                    $courselistdata[] = $result;
                }
            }
        } else if ((!empty($startrange)) && (!empty($endrange))) {
            $result = $this->course_model->filterRecordsByPrice($startrange, $endrange);
            if (!empty($sort)) {
                if (count(array_column($result, $sort)) == count($result)) {
                    array_multisort(array_column($result, $sort), $sort_type, array_column($result, 'id'), $sort_type, $result);
                }
            }
            $courselistdata[] = $result;
        } else {
            if (!empty($coursedata)) {
                $result = $this->course_model->filterRecords($arrvalue = array());
                if (!empty($sort)) {
                    array_multisort(array_column($result, $sort), $sort_type, $result);
                }
                $courselistdata[] = $result;
            }
            $courselistdata[] = array();
        }

        $rating            = 0;
        $averagerating     = 0;
        $totalcourserating = 0;

        if (!empty($courselistdata)) {
            foreach ($courselistdata as $skey => $courselist) {
                foreach ($courselist as $key => $value) {

                    $lesson_count                         = $this->course_model->countlesson($value["id"]);
                    $courselist[$key]["total_lesson"]     = $lesson_count;
                    $hours_count                          = $this->course_model->counthours($value["id"]);
                    $courselist[$key]["total_hour_count"] = $hours_count;
                    $courseRating                         = $this->studentcourse_model->getcourserating($value['id']);

                    $rating                                = 0;
                    $averagerating                         = 0;
                    $courselist[$key]['totalcourserating'] = count($courseRating);
                    if (!empty($courseRating)) {
                        foreach ($courseRating as $courserating_value) {

                            $courserating_value['rating'];
                            $rating = $rating + $courserating_value['rating'];
                        }

                        $averagerating = $rating / count($courseRating);
                    }

                    $courselist[$key]['paidstatus'] = $this->courseofflinepayment_model->paidstatus($value['id'], $this->data['userid']);
                    $courselist[$key]['courserating'] = $averagerating;
                    $courseSale                      = $this->course_model->getCourseSale($value["id"]);
                    $courselist[$key]["course_sale"] = $courseSale;

                }
                $coursedisplaydata[] = $courselist;
                //-----------------
            }
        }

        $this->data["courselist"] = $coursedisplaydata;
        $this->load->view('themes/_searchResults', $this->data);

    }

    public function addclass()
    {
        $session_data = array('class_name' => $_POST['class_name']);
        $this->session->set_userdata('active_class_name', $session_data);
    }

    public function courselist()
    {
        if (!$this->module_lib->hasActive('online_course')) {
            redirect('page/404-page', 'refresh');
        }
        $guest_login = $this->course_model->getOnlineCourseSettings();
        if($guest_login->guest_login != 1){
            redirect('page/404-page', 'refresh');
        }

        $menu_list                = $this->cms_menu_model->getBySlug('main-menu');
        $this->data['main_menus'] = $this->cms_menuitems_model->getMenus($menu_list['id']);
        reset($this->data['main_menus']);    

        $home_page_slug = "online_course";

        $setting                      = $this->frontcms_setting_model->get();
        $this->data['active_menu']    = $home_page_slug;
        $this->data['page_side_bar']  = $setting->is_active_sidebar;
        $this->data['cookie_consent'] = $setting->cookie_consent;
        $result                       = $this->cms_program_model->getByCategory($this->banner_content);
        $this->data['page']           = $this->cms_page_model->getBySlug($home_page_slug);
        if (!empty($result)) {
            $this->data['banner_images'] = $this->cms_program_model->front_cms_program_photos($result[0]['id']);
        }

        $search_course         = $this->input->get('search_course');
        $data['search_course'] = '';
        $search                = ($this->uri->segment(3)) ? $this->uri->segment(3) : $search_course;

        $coursecount = '';
        if ($search_course != '') {
            $coursecount           = $this->course_model->studentcourselist('', '', $search);
            $data['search_course'] = $search;
        } else {
            $coursecount = $this->course_model->studentcourselist('', '', '');
        }

        $this->load->library('pagination');

        if ($this->uri->segment(3) > 1) {
            $config['base_url'] = base_url() . "course/index/" . $this->uri->segment(4) . "/$search_course";
        } else {
            $config['base_url'] = base_url() . "course/index/$search_course";
        }

        $config['total_rows'] = count($coursecount);
        $config['per_page']   = 3;
        // custom paging configuration
        $config['use_page_numbers']   = true;
        $config['reuse_query_string'] = true;
        $config['full_tag_open']      = '<div class="pagination">';
        $config['full_tag_close']     = '</div>';

        $config['first_link']      = '<i class="fa fa-step-backward" aria-hidden="true"></i>';
        $config['first_tag_open']  = '<span class="firstlink">';
        $config['first_tag_close'] = '</span>';

        $config['last_link']      = '<i class="fa fa-step-forward" aria-hidden="true"></i>';
        $config['last_tag_open']  = '<span class="lastlink">';
        $config['last_tag_close'] = '</span>';

        $config['next_link']      = '<i class="fa fa-forward" aria-hidden="true"></i>';
        $config['next_tag_open']  = '<span class="nextlink">';
        $config['next_tag_close'] = '</span>';

        $config['prev_link']      = '<i class="fa fa-backward" aria-hidden="true"></i>';
        $config['prev_tag_open']  = '<span class="prevlink">';
        $config['prev_tag_close'] = '</span>';

        $config['cur_tag_open']  = '<span class="curlink">';
        $config['cur_tag_close'] = '</span>';

        $config['num_tag_open']  = '<span class="numlink">';
        $config['num_tag_close'] = '</span>';
        $page_num                = ($this->uri->segment(3)) ? $this->uri->segment(3) : 1;

        $this->pagination->initialize($config);

        $this->data['userid'] = "";

        if (!empty($this->result["role"])) {

            $role               = $this->result["role"];
            $this->data['role'] = $role;

            if ($role == 'student') {
                $userid = $this->result["student_id"];
            } else {
                $userid = $this->result["guest_id"];
            }
            $this->data['userid'] = $userid;
        }

        $courselist = '';
        if ($search_course != '') {
            $courselist            = $this->course_model->studentcourselist($config['per_page'], $this->uri->segment(3), $search);
            $data['search_course'] = $search;
        } else {
            $offset     = ($page_num - 1) * $config['per_page'];
            $courselist = $this->course_model->studentcourselist($config['per_page'], $offset, '');
        }

        $new_courselist       = array();
        $multipalsectionarray = array();
        foreach ($courselist as $courselist_value) {
            $lesson_count                         = $this->studentcourse_model->totallessonbycourse($courselist_value['id']);
            $courselist_value['total_lesson']     = $lesson_count[0]['total_lesson'];
            $courselist_value['total_hour_count'] = $this->studentcourse_model->counthours($courselist_value['id']);
            $courserating                         = $this->studentcourse_model->getcourserating($courselist_value['id']);

            $rating            = 0;
            $averagerating     = 0;
            $totalcourserating = 0;

            if (!empty($courserating)) {
                foreach ($courserating as $courserating_value) {
                    $rating = $rating + $courserating_value['rating'];
                }

                $averagerating = $rating / count($courserating);
            }

            $courselist_value['paidstatus']        = $this->courseofflinepayment_model->paidstatus($courselist_value['id'], $this->data['userid']);
            $courselist_value['totalcourserating'] = count($courserating);
            $courselist_value['courserating']      = $averagerating;
            $courseSale                            = $this->course_model->getCourseSale($courselist_value["id"]);
            $courselist_value["course_sale"]       = $courseSale;
            $new_courselist[]                      = $courselist_value;
        }

        $filterRating                 = $this->course_model->getFilterRating();
        $this->data["filterRating"]   = $filterRating;
        $filterSale                   = $this->course_model->getFilterSale();
        $this->data["filterSale"]     = $filterSale;
        $filterPrice                  = $this->course_model->getFilterPrice();
        $this->data["filterPrice"]    = $filterPrice;
        $this->data['new_courselist'] = $new_courselist;        
        $this->data['course_setting'] = $this->course_model->getOnlineCourseSettings();
        
        $page                         = $this->load->view($this->load_theme('course/_courselist'), $data, true);
        echo json_encode(array('page' => $page));
    }

    public function searchcourse()
    {
        $menu_list                = $this->cms_menu_model->getBySlug('main-menu');
        $this->data['main_menus'] = $this->cms_menuitems_model->getMenus($menu_list['id']);

        $home_page_slug = "online_course";

        $setting                     = $this->frontcms_setting_model->get();
        $this->data['page_side_bar'] = $setting->is_active_sidebar;
        $this->data['page']          = $this->cms_page_model->getBySlug($home_page_slug);
        $this->data['userid']        = "";
        $coursedisplaydata           = array();
        if (!empty($this->result["role"])) {

            $role               = $this->result["role"];
            $this->data['role'] = $role;

            if ($role == 'student') {
                $userid = $this->result["student_id"];
            } else {
                $userid = $this->result["guest_id"];
            }
            $this->data['userid'] = $userid;
        }
        $search_text = $this->input->post('search_text');
        $courselist  = $this->course_model->searchcourse($search_text);
        if (!empty($courselist)) {
            foreach ($courselist as $key => $value) {
                $lesson_count                          = $this->course_model->countlesson($value["id"]);
                $courselist[$key]["total_lesson"]      = $lesson_count;
                $hours_count                           = $this->course_model->counthours($value["id"]);
                $courselist[$key]["total_hour_count"]  = $hours_count;
                $courseSale                            = $this->course_model->getCourseSale($value["id"]);
                $courselist[$key]["course_sale"]       = $courseSale;
                $courseRating                          = $this->course_model->getCourseRating($value["id"]);
                $courselist[$key]['totalcourserating'] = count($courseRating);

                if (!empty($courseRating)) {
                    $totalrating             = sizeof($courseRating);
                    $newrating[$value["id"]] = $totalrating;
                }
                $courselist[$key]["courserating"] = $courseRating;
                $courselist[$key]['paidstatus']   = $this->courseofflinepayment_model->paidstatus($value['id'], $this->data['userid']);
            }
            $coursedisplaydata[] = $courselist;
        }
        $this->data["courselist"] = $coursedisplaydata;
        $this->load->view('themes/_searchResults', $this->data);
    }

    public function check_captcha($captcha)
    {
        if (!empty($captcha)) {
            if ($captcha != $this->session->userdata('captchaCode')):
                $this->form_validation->set_message('check_captcha', $this->lang->line('incorrect_captcha'));
                return false;
            else:
                return true;
            endif;
        } else {
            $this->form_validation->set_message('check_captcha', $this->lang->line('the_captcha_field_is_required'));
            return false;
        }
    }

    //reset password - final step for forgotten password
    public function resetpassword($role = null, $verification_code = null)
    {
        $app_name     = $this->setting_model->get();
        $data['name'] = $app_name[0]['name'];

        $user = $this->studentcourse_model->getusercodebyrole($role, $verification_code);

        if ($user) {
            //if the code is valid then display the password reset form
            $this->form_validation->set_rules('password', $this->lang->line('password'), 'required');
            $this->form_validation->set_rules('confirm_password', $this->lang->line('confirm_password'), 'required|matches[password]');
            if ($this->form_validation->run() == false) {

                $data['role']              = $role;
                $data['verification_code'] = $verification_code;
                //render
                $this->load->view('themes/resetpassword', $data);
            } else {            
                $update_record['verification_code'] = '';

                if ($role != 'guest') {
                    $update_record['password'] = $this->input->post('password');
                } else {
                    $update_record['password'] = $this->enc_lib->passHashEnc($this->input->post('password'));
                }

                if ($role == 'student') {
                    $update_record['id'] = $user->user_tbl_id;
                    $table               = 'users';
                } elseif ($role == 'guest') {
                    $update_record['id'] = $user->id;
                    $table               = 'guest';
                } elseif ($role == 'parent') {
                    $update_record['id'] = $user->parent_id;
                    $table               = 'users';
                }

                $change = $this->studentcourse_model->savenewpassword($table, $update_record);
                if ($change) {
                    //if the password was successfully changed
                    $this->session->set_flashdata('message', $this->lang->line('password_reset_successfully'));
                    redirect('course', 'refresh');
                } else {
                    $this->session->set_flashdata('message', $this->lang->line("something_went_wrong"));
                    redirect('course/resetpassword/' . $role . '/' . $verification_code, 'refresh');
                }
            }
        } else {
            //if the code is invalid then send them back to the forgot password page
            redirect('page/404-page', 'refresh');
        }
    }

    public function forgotpassword()
    {
        $app_name     = $this->setting_model->get();
        $data['name'] = $app_name[0]['name'];
        $this->form_validation->set_rules('username', $this->lang->line('email'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $msg = array(
                'username'  => form_error('username'),                
            );
            $array = array('status' => '0', 'error' => $msg, 'message' => '');
            echo json_encode($array);
        } else {
            $email    = $this->input->post('username');           
            $result = $this->studentcourse_model->forgotPassword($email);

            if (!empty($result)) {

                $verification_code = $this->enc_lib->encrypt(uniqid(mt_rand()));
                $usertype  = 'guest';
                $table     = 'guest';
                $userid    = $result->id;
                $guestdata = $this->studentcourse_model->read_user_information($result->id);
                $name      = $guestdata[0]->guest_name;
                $username  = $guestdata[0]->guest_name;
                $update_record = array('id' => $userid, 'verification_code' => $verification_code);
                $this->studentcourse_model->updateverifactioncode($table, $update_record);
                $resetPassLink  = site_url('course/resetpassword') . '/' . $usertype . "/" . $verification_code;
                $sender_details = array('email' => $email, 'resetPassLink' => $resetPassLink, 'name' => $name, 'username' => $username);                         
                $msg = $this->lang->line("please_check_your_email_to_recover_your_password");             
                $array = array('status' => '1', 'error' => '', 'message' => $msg);
                $this->mailsmsconf->mailsms('forgot_password', $sender_details);  
                echo json_encode($array);
                
            } else {
                $msg = array(
                    'message' => $this->lang->line('invalid_email'),
                );
                $array = array('status' => '0', 'error' => $msg, 'message' => '');
                echo json_encode($array);
            }
        }
    }

    //=========================added========================//
    // online course certificate download by qr code   
     
     public function downloadcertificatepdf($certificate_id,$student_id,$course_id,$role=null){
        $this->load->model(array('coursecertificate_model'));
        $this->sch_setting_detail = $this->setting_model->getSetting();
        $get_certificate_date   =   $this->coursecertificate_model->get($certificate_id);
        $coursesdata            =   $this->course_model->singlecourselist($course_id);
        $completiondate         =   $this->studentcourse_model->getcoursecompletiondate($course_id,$student_id,$role);
        $startdate              =   $this->studentcourse_model->getcoursestartdate($course_id,$student_id,$role);
        $completion_date        =   $completiondate['completion_date'];
        $start_date             =   $startdate['start_date'];
        $course_title           =   $coursesdata['title'];
        $course_category        =   $coursesdata['category_name'];
        $assign_teacher         =   $coursesdata['staff_name'] . ' ' . $coursesdata['staff_surname'] .' ('. $coursesdata['assign_employee_id'].')';

        if($role==null || $role==""){
            $role = $this->result["role"];
        }
            if ($role == 'student' || $role == 'parent') {
                $get_student_data =   $this->student_model->get($student_id);
                $student_name     =   $this->customlib->getFullName($get_student_data['firstname'], $get_student_data['middlename'], $get_student_data['lastname'],$this->sch_setting_detail->middlename, $this->sch_setting_detail->lastname)." (".$get_student_data['admission_no'].")";
                $class_name       =   $get_student_data['class'];
                $section_name     =   rtrim($get_student_data["section"], ", ");
            } elseif ($role == 'guest') {
                $get_guest_data  =   $this->studentcourse_model->get_guest_details($student_id);
                $student_name    =   $get_guest_data['guest_name']." (".$get_guest_data['guest_unique_id'].")";
                $class_name      =   "";
                $section_name    =   "";
            }

            foreach($get_certificate_date as $certificate_key=>$certificate_value){
              $variable_data[] = array(
                "student_name"          =>   $student_name,
                "class_name"            =>   $class_name,
                "section_name"          =>   $section_name,
                "course_name"           =>   $course_title,
                "current_date"          =>   date($this->customlib->getSchoolDateFormat(), strtotime(date("d-m-Y"))),
                "start_date"            =>   date($this->customlib->getSchoolDateFormat(), strtotime($start_date)),
                "completion_date"       =>   date($this->customlib->getSchoolDateFormat(), strtotime($completion_date)),
                "assign_teacher"        =>   $assign_teacher
            );

            $certificate_text       = $this->getCertificateTextContent($variable_data, $certificate_value['certificate_text']);
            
            //certificate text with variable values
            $certificate_text_key   =   ($certificate_value['certificate_text']);
            $certificate_text_trim  =   ($certificate_text);
            $show_qr                =   base_url('course/show_qr/'.$certificate_id.'/'.$student_id.'/'.$course_id.'/'.$role);

            $variable_data2[] = array(
                "student_name"          =>   $student_name,
                "class_name"            =>   $class_name,
                "section_name"          =>   $section_name,
                "course_name"           =>   $course_title,
                "current_date"          =>   date($this->customlib->getSchoolDateFormat(), strtotime(date("d-m-Y"))),
                "start_date"            =>   date($this->customlib->getSchoolDateFormat(), strtotime($start_date)),
                "completion_date"       =>   date($this->customlib->getSchoolDateFormat(), strtotime($completion_date)),
                "assign_teacher"        =>   $assign_teacher,
                "qr_code"               =>   "<img height='60px' width='60px' src='$show_qr' alt='QR Code'>",
            );

            //update template text
            $updated_template= str_replace($certificate_value['certificate_text'], $certificate_text, $certificate_value['certificate_template']);
            //update template text
			
			$basePath = FCPATH . 'uploads/course_content/online_course_certificate/';

            $data["certificate_templatedat"]  = $this->getCertificateTextContent_for_template($variable_data2, $updated_template);
			
			$data["certificate_templatedat"] = preg_replace(
				'/<img([^>]+)src="([^"]+)"/i',
				'<img$1src="' . $basePath . '$2"',
				$data["certificate_templatedat"]
			);		 

        }

        $html = $this->load->view('user/studentcourse/downloadcertificatepdf', $data, true);;
        $this->load->library('m_pdf');

        $mpdf = $this->m_pdf->load([
            'mode' => 'utf-8',
            'format' => [195, 140.2], // width, height in mm
            // 'format' => [254,190.5], // width, height in mm//standard certificate size for pdf but in pdf text not set on right place
            // 'orientation' => 'P', // or 'L' if needed
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_left' => 0,
            'margin_right' => 0,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);
        
        $mpdf->autoScriptToLang  = true;
        $mpdf->baseScript        = 1;
        $mpdf->autoLangToFont    = true;
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        $fileName = "your_certificate"."."."pdf";       
        $mpdf->Output($fileName,'D');      
    }

    public function getCertificateTextContent_for_template($data, $template)
    {
        preg_match_all('/\{(.*?)\}/', $template, $matches);
        foreach ($matches[1] as $key=>$value) {
            if (isset($key)) {
                $template = str_replace('{'.$value.'}', $data[0]["$value"], $template);
            }
        }
        return $template;
    } 

    public function getCertificateTextContent($data, $template)
    {
        preg_match_all('/\[(.*?)\]/', $template, $matches);
        foreach ($matches[1] as $key=>$value) {
            if (isset($key)) {
                $template = str_replace("[$value]", $data[0]["$value"], $template);
            }
        }
        return $template;
    }

    public function show_qr($certificate_id,$student_id,$course_id,$role)
    {
        $this->load->library('QR_Code');
        $url=base_url("course/downloadcertificatepdf/$certificate_id/$student_id/$course_id/$role");
        $this->qr_code->generateQRCodeForCertificateDownload($url);
    }
    // online course certificate download by qr code


}