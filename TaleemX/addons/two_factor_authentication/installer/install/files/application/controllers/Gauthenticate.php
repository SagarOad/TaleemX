<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Gauthenticate extends Public_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->check_installation();
        if ($this->config->item('installed') == true) {
            $this->db->reconnect();
        }

        $this->load->model(array("staff_model", "google_authenticator/gauthuser_model"));
        $this->load->library('Auth');
        $this->load->library('Enc_lib');
        $this->load->library('Google_authenticator');
        $this->load->library('captchalib');

        $this->sch_setting = $this->setting_model->getSetting();
    }

    /*
    This function is used to check install folder deleted or not
     */
    private function check_installation()
    {
        if ($this->uri->segment(1) !== 'install') {
            $this->load->config('migration');
            if ($this->config->item('installed') == false && $this->config->item('migration_enabled') == false) {
                redirect(base_url() . 'install/start');
            } else {
                if (is_dir(APPPATH . 'controllers/install')) {
                    echo '<h3>Delete the install folder from application/controllers/install</h3>';
                    die;
                }
            }
        }
    }

    /*
    This function is used to admin login 
     */
    public function login()
    {
        if ($this->auth->logged_in()) {
            $this->auth->is_logged_in(true);
        }
        if (
            !$this->module_lib->hasModule('google_authenticator')
            || !$this->module_lib->hasActive('google_authenticator')
        ) {

            redirect('site/login');
        } else {

            $app_name = $this->setting_model->get();
            $app_name = $app_name[0]['name'];

            $data          = array();
            $data['title'] = 'Login';
            $school        = $this->setting_model->get();
            $data['name'] = $app_name;
            $is_captcha            = $this->captchalib->is_captcha('login');
            $data["is_captcha"]    = $is_captcha;
            $captcha               = $this->captchalib->generate_captcha();
            $data['captcha_image'] = isset($captcha['image']) ? $captcha['image'] : "";
            $school        = $this->setting_model->get();
            $data['school']     = $school[0];
            $notice_content     = $this->config->item('ci_front_notice_content');
            $notices            = $this->cms_program_model->getByCategory($notice_content, array('start' => 0, 'limit' => 5));
            $data['notice']     = $notices;
            $this->load->view('admin/gauthenticate/login', $data);
        }
    }

    /*
    This function is used to user login 
     */
    public function userlogin()
    {
        $school = $this->setting_model->get();

        // if (!$school[0]['student_panel_login']) {
        //     redirect('site/login', 'refresh');
        // }

        if ($school[0]['student_panel_login'] == 0) {
            $student_login_status = 0;
        } else {
            $student_login_status = 1;
        }
        if ($school[0]['parent_panel_login'] == 0) {
            $parent_login_status = 0;
        } else {
            $parent_login_status = 1;
        }
        if ($student_login_status == 0 && $parent_login_status == 0) {
            redirect('site/login', 'refresh');
        }


        if ($this->auth->user_logged_in()) {
            $this->auth->user_redirect();
        }

        $data           = array();
        $data['title']  = 'Login';
        $school         = $this->setting_model->get();
        $data['name']   = $school[0]['name'];
        $notice_content = $this->config->item('ci_front_notice_content');
        $notices        = $this->cms_program_model->getByCategory($notice_content, array('start' => 0, 'limit' => 5));
        $data['notice'] = $notices;
        $data['school'] = $school[0];
        $is_captcha     = $this->captchalib->is_captcha('userlogin');
        $data["is_captcha"]    = $is_captcha;
        $captcha = $this->captchalib->generate_captcha();

        if ($captcha !== false && isset($captcha['image'])) {
            $data['captcha_image'] = $captcha['image'];
        } else {
            $data['captcha_image'] = '';
        }

        $this->load->view('user/gauthenticate/login', $data);
    }

    /*
    This function is used to validate admin login if google authenticate Two Factor Authentication is disabled
     */
    public function verfiy_login()
    {
        $this->form_validation->set_error_delimiters('<p>', '</p>');

        if ($this->captchalib->is_captcha('login')) {
            $this->form_validation->set_rules('captcha', $this->lang->line('captcha'), 'trim|required|callback_check_captcha', array('required' => '%s required'));
        }

        $this->form_validation->set_rules('username', $this->lang->line('username'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('password', $this->lang->line('password'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $data = array(
                'username' => form_error('username'),
                'password' => form_error('password'),
                'captcha'  => form_error('captcha'),
            );
            $array = array('status' => 0, 'error' => $data);
            echo json_encode($array);
        } else {

            $login_post = array(
                'email'    => $this->input->post('username'),
                'password' => $this->input->post('password'),
            );

            $setting_result = $this->setting_model->get();
            $result         = $this->staff_model->checkLogin($login_post);

            if (!empty($result->language_id)) {
                $lang_array = array('lang_id' => $result->language_id, 'language' => $result->language);
            } else {
                $lang_array = array('lang_id' => $setting_result[0]['lang_id'], 'language' => $setting_result[0]['language']);
            }

            if ($result) {

                if ($result->is_active) {
                    $gauthenticate           = true;
                    $user_using_authenticate = $this->gauthuser_model->check_user_exists('staff', $result->id);
                    $redirect_url            = "";

                    if (!$user_using_authenticate || !is_active_2fa()) {
                        $gauthenticate = false;
                        $this->admin_session($result, $setting_result, $lang_array);
                        $redirect_url = site_url('admin/admin/dashboard');
                        if (isset($_SESSION['redirect_to'])) {
                            $redirect_url = $_SESSION['redirect_to'];
                        }
                    }

                    $array = array('status' => 2, 'error' => "", 'authenticator' => $gauthenticate, 'redirect_to' => $redirect_url);
                    echo json_encode($array);
                } else {
                    $data['error_message'] = "<div class='alert alert-danger'>" . $this->lang->line('your_account_is_disabled_please_contact_to_administrator') . "</div>";
                    $array                 = array('status' => 1, 'error' => $data);
                    echo json_encode($array);
                }
            } else {
                $data['error_message'] = "<div class='alert alert-danger'>" . $this->lang->line('invalid_username_or_password') . "</div>";
                $array                 = array('status' => 1, 'error' => $data);
                echo json_encode($array);
            }
        }
    }

    /*
    This function is used to set all admin details in session 
     */
    public function admin_session($result, $setting_result, $lang_array)
    {
        if ($result->surname != "") {
            $logusername = $result->name . " " . $result->surname;
        } else {
            $logusername = $result->name;
        }

        if (!empty($result->language_id)) {

            $lang_array = array('lang_id' => $result->language_id, 'lang_short_code' => $result->short_code,  'language' => $result->language);


            if ($result->is_rtl == 1) {
                $is_rtl = "enabled";
            } else {
                $is_rtl = "disabled";
            }
        } else {

            $lang_array = array('lang_id' => $setting_result[0]['lang_id'],  'lang_short_code' => $setting_result[0]['language_code'], 'language' => $setting_result[0]['language']);
            if ($setting_result[0]['is_rtl'] == 1) {
                $is_rtl = "enabled";
            } else {
                $is_rtl = "disabled";
            }
        }

        $print_heder_image        = $this->setting_model->get_general_purpose_header();

        $session_data = array(
            'id'                     => $result->id,
            'username'               => $logusername,
            'email'                  => $result->email,
            'image'                  => $result->image,
            'roles'                  => $result->roles,
            'date_format'            => $setting_result[0]['date_format'],
            'currency'               => ($result->currency == 0) ? $setting_result[0]['currency'] : $result->currency,
            'currency_base_price'    => ($result->base_price == 0) ? $setting_result[0]['base_price'] : $result->base_price,
            'currency_format'        => $setting_result[0]['currency_format'],
            'currency_symbol'        => ($result->symbol == "0") ? $setting_result[0]['currency_symbol'] : $result->symbol,
            'currency_place'         => $setting_result[0]['currency_place'],
            'start_month'            => $setting_result[0]['start_month'],
            'start_week'             => date("w", strtotime($setting_result[0]['start_week'])),
            'school_name'            => $setting_result[0]['name'],
            'timezone'               => $setting_result[0]['timezone'],
            'sch_name'               => $setting_result[0]['name'],
            'language'               => $lang_array,
            'is_rtl'                 => $is_rtl,
            // 'theme'                  => $setting_result[0]['theme'],

            'theme'                  => [
                'theme_color' => $setting_result[0]['theme_color'],
                'theme_shadow' => $setting_result[0]['theme_shadow'],
                'theme_background' => $setting_result[0]['theme_background'],
                'theme_content' => $setting_result[0]['theme_content'],
                'theme_type' => $setting_result[0]['theme_type'],
                'theme_navigation' => $setting_result[0]['theme_navigation'],
                'theme_font_color' => $setting_result[0]['theme_font_color'],
            ],

            'gender'                 => $result->gender,
            'superadmin_restriction' => $setting_result[0]['superadmin_restriction'],
            'db_array'               => [
                'base_url'  => $setting_result[0]['base_url'],
                'folder_path' => $setting_result[0]['folder_path'],
                'db_group' => 'default'
            ],
            'session_array' => $setting_result[0]['current_session'],

            'saas_key'               => $setting_result[0]['saas_key'],
            'admin_panel_whatsapp'           => $setting_result[0]['admin_panel_whatsapp'],
            'admin_panel_whatsapp_mobile'   => $setting_result[0]['admin_panel_whatsapp_mobile'],
            'admin_panel_whatsapp_from'       => $setting_result[0]['admin_panel_whatsapp_from'],
            'admin_panel_whatsapp_to'          => $setting_result[0]['admin_panel_whatsapp_to'],
            'print_heder_image' =>  IsNullOrEmptyString($print_heder_image) ? "" : $print_heder_image
        );

        $language_result1 = $this->language_model->get($lang_array['lang_id']);
        if ($this->customlib->get_rtl_languages($language_result1['short_code'])) {
            $session_data['is_rtl'] = 'enabled';
        } else {
            $session_data['is_rtl'] = 'disabled';
        }

        $this->session->set_userdata('admin', $session_data);

        $role      = $this->customlib->getStaffRole();
        $role_name = json_decode($role)->name;
        $this->customlib->setUserLog($this->input->post('username'), $role_name);
        return true;
    }

    /*
    This function is used to validate admin login if google authenticate Two Factor Authentication is enabled
     */
    public function submit_login()
    {
        $this->form_validation->set_error_delimiters('<p>', '</p>');
        $this->form_validation->set_rules('username', $this->lang->line('username'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('password', $this->lang->line('password'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('code', $this->lang->line('verification_code'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $data = array(
                'code'     => form_error('code'),
                'username' => form_error('username'),
                'password' => form_error('password'),
            );
            $array = array('status' => 0, 'error' => $data);
            echo json_encode($array);
        } else {

            $login_post = array(
                'code'     => $this->input->post('code'),
                'email'    => $this->input->post('username'),
                'password' => $this->input->post('password'),
            );

            $setting_result = $this->setting_model->get();
            $result         = $this->staff_model->checkLogin($login_post);

            if (!empty($result->language_id)) {
                $lang_array = array('lang_id' => $result->language_id, 'language' => $result->language);
            } else {
                $lang_array = array('lang_id' => $setting_result[0]['lang_id'], 'language' => $setting_result[0]['language']);
            }

            if ($result) {
                $gauth = $this->gauthuser_model->getByUser('staff', $result->id);
                if (!$gauth) {
                    $data['error_message'] = "<div class='alert alert-danger'>" . $this->lang->line('something_went_wrong') . "</div>";
                    $array                 = array('status' => 1, 'error' => $data);
                    echo json_encode($array);
                    exit();
                } else {
                    $verify_code = $this->google_authenticator->verifyQR($gauth->secret_code, $this->input->post('code'));

                    if (!$verify_code) {
                        $data['error_message'] = "<div class='alert alert-danger'>" . $this->lang->line('invalid_code') . "</div>";
                        $array                 = array('status' => 1, 'error' => $data);
                        echo json_encode($array);
                        exit();
                    }
                }

                if ($result->is_active) {

                    $this->admin_session($result, $setting_result, $lang_array);

                    $redirect_url = site_url('admin/admin/dashboard');
                    if (isset($_SESSION['redirect_to'])) {
                        $redirect_url = $_SESSION['redirect_to'];
                    }

                    $array = array('status' => 2, 'error' => "", 'redirect_to' => $redirect_url);
                    echo json_encode($array);
                } else {
                    $data['error_message'] = "<div class='alert alert-danger'>" . $this->lang->line('your_account_is_disabled_please_contact_to_administrator') . "</div>";
                    $array                 = array('status' => 1, 'error' => $data);
                    echo json_encode($array);
                }
            } else {

                $data['error_message'] = "<div class='alert alert-danger'>" . $this->lang->line('invalid_username_or_password') . "</div>";
                $array                 = array('status' => 1, 'error' => $data);
                echo json_encode($array);
            }
        }
    }

    /*
    This function is used to validate user login if google authenticate Two Factor Authentication is disabled
     */
    public function verfiy_userlogin()
    {
        $school = $this->setting_model->get();

        if ($school[0]['student_panel_login'] == 0) {
            $student_login_status = 0;
        } else {
            $student_login_status = 1;
        }
        if ($school[0]['parent_panel_login'] == 0) {
            $parent_login_status = 0;
        } else {
            $parent_login_status = 1;
        }
        if ($student_login_status == 0 && $parent_login_status == 0) {
            redirect('site/login', 'refresh');
        }

        $this->form_validation->set_error_delimiters('<p>', '</p>');

        if ($this->captchalib->is_captcha('userlogin')) {
            $this->form_validation->set_rules('captcha', $this->lang->line('captcha'), 'trim|required|callback_check_captcha', array('required' => '%s required'));
        }

        $this->form_validation->set_rules('username', $this->lang->line('username'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('password', $this->lang->line('password'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $data = array(
                'username' => form_error('username'),
                'password' => form_error('password'),
                'captcha' => form_error('captcha'),
            );
            $array = array('status' => 0, 'error' => $data);
            echo json_encode($array);
        } else {

            $login_post = array(
                'username' => $this->input->post('username'),
                'password' => $this->input->post('password'),
            );

            $login_details = $this->user_model->checkLogin($login_post);
            $school = $this->setting_model->get();

            if (isset($login_details) && !empty($login_details)) {
                $user = $login_details[0];

                if ($user->is_active == "yes") {
                    if ($user->role == "student" && $student_login_status == 1) {
                        $result = $this->user_model->read_user_information($user->id);
                    } else if ($user->role == "parent"  && $parent_login_status == 1) {
                        if ($school[0]['parent_panel_login']) {
                            $result = $this->user_model->checkLoginParent($login_post);
                        } else {
                            $result = false;
                        }
                    } else {
                        $data['error_message'] = $this->lang->line('account_suspended');
                        $result = false;
                    }

                    if ($result != false) {

                        $user_using_authenticate = $this->gauthuser_model->check_user_exists('user', $result[0]->id);
                        $redirect_url            = "";
                        $gauthenticate           = true;

                        if (!$user_using_authenticate || !is_active_2fa()) {
                            $gauthenticate = false;
                            $this->user_session($result);
                            $redirect_url = site_url('user/user/choose');
                        }

                        // ===
                        $array = array('status' => 2, 'error' => "", 'authenticator' => $gauthenticate, 'redirect_to' => $redirect_url);

                        echo json_encode($array);
                    } else {
                        $data['error_message'] = "<div class='alert alert-danger'>Account Suspended</div>";
                        $array                 = array('status' => 1, 'error' => $data);
                        echo json_encode($array);
                    }
                } else {
                    $data['error_message'] = "<div class='alert alert-danger'>" . $this->lang->line('your_account_is_disabled_please_contact_to_administrator') . "</div>";
                    $array                 = array('status' => 1, 'error' => $data);
                    echo json_encode($array);
                }
            } else {
                $data['error_message'] = "<div class='alert alert-danger'>" . $this->lang->line('invalid_username_or_password') . "</div>";
                $array                 = array('status' => 1, 'error' => $data);
                echo json_encode($array);
            }
        }
    }

    /*
    This function is used to validate user login if google authenticate Two Factor Authentication is enabled
    */
    public function user_submit_login()
    {
        $this->form_validation->set_error_delimiters('<p>', '</p>');
        $this->form_validation->set_rules('username', $this->lang->line('username'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('password', $this->lang->line('password'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('code', $this->lang->line('verification_code'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $data = array(
                'username' => form_error('username'),
                'password' => form_error('password'),
                'code'     => form_error('code'),
            );
            $array = array('status' => 0, 'error' => $data);
            echo json_encode($array);
        } else {
            $login_post = array(
                'username' => $this->input->post('username'),
                'password' => $this->input->post('password'),
            );

            $login_details = $this->user_model->checkLogin($login_post);

            if (isset($login_details) && !empty($login_details)) {
                $user = $login_details[0];

                $gauth = $this->gauthuser_model->getByUser('user', $user->id);
                if (!$gauth) {
                    $data['error_message'] = "<div class='alert alert-danger'>" . $this->lang->line('something_went_wrong') . "</div>";
                    $array                 = array('status' => 1, 'error' => $data);
                    echo json_encode($array);
                    exit();
                } else {
                    $verify_code = $this->google_authenticator->verifyQR($gauth->secret_code, $this->input->post('code'));

                    if (!$verify_code) {
                        $data['error_message'] = "<div class='alert alert-danger'>" . $this->lang->line('invalid_code') . "</div>";
                        $array                 = array('status' => 1, 'error' => $data);
                        echo json_encode($array);
                        exit();
                    }
                }

                if ($user->is_active == "yes") {
                    if ($user->role == "student") {
                        $result = $this->user_model->read_user_information($user->id);
                    } else if ($user->role == "parent") {
                        $result = $this->user_model->checkLoginParent($login_post);
                    }

                    if ($result != false) {
                        $this->user_session($result);
                        $redirect_url = site_url('user/user/choose');

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

    /*
    This function is used to set user details in session 
     */
    public function user_session($result)
    {
        $setting_result = $this->setting_model->get();
        if ($result[0]->lang_id == 0) {
            $language = array('lang_id' => $setting_result[0]['lang_id'], 'lang_short_code' => $setting_result[0]['language_code'], 'language' => $setting_result[0]['language']);
        } else {
            $language = array('lang_id' => $result[0]->lang_id, 'lang_short_code' => $setting_result[0]['language_code'], 'language' => $result[0]->language);
        }

        if ($result[0]->role == "parent") {
            $username = $result[0]->guardian_name;
            if ($result[0]->guardian_relation == "Father") {
                $image = $result[0]->father_pic;
            } else if ($result[0]->guardian_relation == "Mother") {
                $image = $result[0]->mother_pic;
            } else if ($result[0]->guardian_relation == "Other") {
                $image = $result[0]->guardian_pic;
            }
        } elseif ($result[0]->role == "student") {
            $image        = $result[0]->image;
            $username     = $this->customlib->getFullName($result[0]->firstname, $result[0]->middlename, $result[0]->lastname, $this->sch_setting->middlename, $this->sch_setting->lastname);
            $defaultclass = $this->user_model->get_studentdefaultClass($result[0]->user_id);
            $this->customlib->setUserLog($result[0]->username, $result[0]->role, $defaultclass['id']);
        }

        $print_heder_image        = $this->setting_model->get_general_purpose_header();

        $session_data = array(
            'id'                     => $result[0]->id,
            'login_username'         => $result[0]->username,
            'student_id'             => $result[0]->user_id,
            'role'                   => $result[0]->role,
            'username'               => $username,
            'currency'               => ($result[0]->currency == 0) ? $setting_result[0]['currency_id'] :  $result[0]->currency,
            'currency_base_price'    => ($result[0]->base_price == 0) ? $setting_result[0]['base_price'] :  $result[0]->base_price,
            'currency_format'        => $setting_result[0]['currency_format'],
            'currency_symbol'        => ($result[0]->symbol == "0") ? $setting_result[0]['currency_symbol'] : $result[0]->symbol,
            'currency_name'        => ($result[0]->currency_name == "0") ? $setting_result[0]['currency'] : $result[0]->currency_name,
            'currency_place'         => $setting_result[0]['currency_place'],
            'date_format'            => $setting_result[0]['date_format'],
            'start_week'             => date("w", strtotime($setting_result[0]['start_week'])),
            'timezone'               => $setting_result[0]['timezone'],
            'sch_name'               => $setting_result[0]['name'],
            'language'               => $language,
            // 'theme'                  => $setting_result[0]['theme'],

            'theme'                  => [
                'theme_color' => $setting_result[0]['theme_color'],
                'theme_shadow' => $setting_result[0]['theme_shadow'],
                'theme_background' => $setting_result[0]['theme_background'],
                'theme_content' => $setting_result[0]['theme_content'],
                'theme_type' => $setting_result[0]['theme_type'],
                'theme_navigation' => $setting_result[0]['theme_navigation'],
                'theme_font_color' => $setting_result[0]['theme_font_color'],
            ],

            'image'                  => $image,
            'gender'                 => $result[0]->gender,
            'db_array'               => [
                'base_url'           => $setting_result[0]['base_url'],
                'folder_path'        => $setting_result[0]['folder_path'],
                'db_group' => 'default'
            ],
            'superadmin_restriction' => $setting_result[0]['superadmin_restriction'],
            'session_array'            => $setting_result[0]['current_session'],
            'admin_panel_whatsapp'           => $setting_result[0]['admin_panel_whatsapp'],
            'admin_panel_whatsapp_mobile'   => $setting_result[0]['admin_panel_whatsapp_mobile'],
            'admin_panel_whatsapp_from'       => $setting_result[0]['admin_panel_whatsapp_from'],
            'admin_panel_whatsapp_to'          => $setting_result[0]['admin_panel_whatsapp_to'],
            'saas_key'                   => $setting_result[0]['saas_key'],
            'print_heder_image' =>  IsNullOrEmptyString($print_heder_image) ? "" : $print_heder_image,
            'login_token'                   => $result[0]->login_token,

        );

        $language_result1 = $this->language_model->get($language['lang_id']);
        if ($language_result1['is_rtl']) {
            $session_data['is_rtl'] = 'enabled';
        } else {
            $session_data['is_rtl'] = 'disabled';
        }


        $this->session->set_userdata('student', $session_data);
        if ($result[0]->role == "parent") {
            $this->customlib->setUserLog($result[0]->username, $result[0]->role);
        }
        return true;
    }

    /*
    This function is used to validate captcha
     */
    public function check_captcha($captcha)
    {
        if ($captcha != "") {

            if ($captcha != $this->session->userdata('captchaCode')) {
                $this->form_validation->set_message('check_captcha', $this->lang->line('incorrect_captcha'));
                return false;
            }
            return true;
        }
        return true;
    }
}
