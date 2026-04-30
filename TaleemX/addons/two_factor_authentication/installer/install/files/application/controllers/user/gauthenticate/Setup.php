<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Setup extends MY_Addon_GAUserController
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('custom');
        if(!is_active_2fa() || !$this->auth->addonchk('sstfa', false)){
            redirect('user/user/unauthorized');
        }
    }

    /*
    This function is used to setup 2fa method
    */
    public function index()
    {
        $data            = array();
        $data['title']   = $this->lang->line('google_authenticator_setting');
        $data['version'] = $this->config->item('version');
        $user_role=($this->customlib->getUserRole() == "guest") ? 'guest': 'user';
        $user            = $this->gauthuser_model->getByUser($user_role, $this->customlib->getUsersID());
        $data['user']    = $user;

        $setting = $this->gauth_model->get();
        if (empty($setting)) {
            $setting                    = new stdClass();
            $setting->use_authenticator = 0;
        }       

        $userdata=$this->customlib->getLoggedInUserData();
        if(array_key_exists('guest_id', $userdata) && !empty($userdata['guest_id'])){
           
            $this->load->model('studentcourse_model');
            $guest_login_detail=$this->studentcourse_model->read_user_information($this->customlib->getUsersID());
            $guest_login_detail[0]->username=$guest_login_detail[0]->guest_unique_id;
            $user_login_detail=$guest_login_detail[0];

        }else{
             $user_login_detail=$this->user_model->getById($this->customlib->getUsersID());
        }
        
        $qr_detail         = $this->google_authenticator->generateQR($user_login_detail->username);
        $data['qr_detail'] = json_decode($qr_detail);
        $data['setting']   = $setting;
        $this->form_validation->set_rules('use_authenticator', $this->lang->line('use_authenticator'), 'required|trim|xss_clean');
        if ($this->form_validation->run() === true) {
            $data_insert = array(
                'use_authenticator' => $this->input->post('use_authenticator'),
            );

            $this->gauth_model->add($data_insert);
            $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/gauthenticate/admin');
        }
    
        $this->load->view('layout/student/header', $data);
        $this->load->view('user/gauthenticate/index', $data);
        $this->load->view('layout/student/footer', $data);
    }

    /*
    This function is used to verify code
    */
    public function verify_setup()
    {
        $this->form_validation->set_rules('app_code', $this->lang->line('app_code'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('secret_code', $this->lang->line('secret_code'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {

            $msg = array(
                'app_code'    => form_error('app_code'),
                'secret_code' => form_error('secret_code'),
            );

            $array = array('status' => 0, 'error' => $msg);
        } else {
            $code   = $this->input->post('app_code');
            $secret = $this->input->post('secret_code');
            $verify = $this->google_authenticator->verifyQR($secret, $code);
            if ($verify) {

                $data_insert = array(
                    'secret_code' => $secret
                );

                 if($this->customlib->getUserRole() == "guest"){
                    $data_insert['guest_id'] =$this->customlib->getUsersID();
                }else{
                   $data_insert['user_id'] =$this->customlib->getUsersID();
                }


                $this->gauthuser_model->add($data_insert);

                $array = array('status' => 1, 'error' => '', 'message' => $this->lang->line('success_message'));
            } else {
                $msg   = array('error' => $this->lang->line('invalid_code_please_try_again'));
                $array = array('status' => 0, 'error' => $msg);
            }
        }

        echo json_encode($array);
    }

    /*
    This function is used to delete 2fa setup
    */
    public function delete_setup()
    {
        $user_role=($this->customlib->getUserRole() == "guest") ? 'guest': 'user';
        $this->gauthuser_model->deleteUser($user_role, $this->customlib->getUsersID());
        $array = array('status' => 1, 'error' => '', 'message' => $this->lang->line('success_message'));
        echo json_encode($array);
    }

}
