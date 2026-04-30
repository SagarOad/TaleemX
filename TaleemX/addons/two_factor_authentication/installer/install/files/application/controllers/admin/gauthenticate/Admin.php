<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Admin extends MY_Addon_GAController
{

    public function __construct()
    {
        parent::__construct();

    }
    
    /*
    This function is used to disabled/enabled two factor authentication
     */
    public function index()
    {        
        if (!$this->rbac->hasPrivilege('google_authenticate_setting', 'can_view')) {
            access_denied();
        }
        
        $data            = array();
        $data['title']   = $this->lang->line('google_authenticator_setting');
        $data['version'] = $this->config->item('version');

        $setting = $this->gauth_model->get();
        if (empty($setting)) {
            $setting                    = new stdClass();
            $setting->use_authenticator = 0;
        }

        $data['setting'] = $setting;
        
        $this->form_validation->set_rules('use_authenticator', $this->lang->line('use_authenticator'), 'required|trim|xss_clean');

        if ($this->form_validation->run() === true) {

            $data_insert = array(             
                'use_authenticator' => $this->input->post('use_authenticator'),
            );

            $this->gauth_model->add($data_insert);
            $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/gauthenticate/admin');
        }
        $this->load->view('layout/header', $data);
        $this->load->view('admin/gauthenticate/admin', $data);
        $this->load->view('layout/footer', $data);

    }
    
    /*
    This function is used to setup two factor authentication
     */
    public function setup()
    {       
        if (!$this->rbac->hasPrivilege('google_authenticate_setup_two_fa', 'can_view')) {
            access_denied();
        }
        
        $data            = array();
        $data['title']   = 'Google Authenticate Setting';
        $data['version'] = $this->config->item('version');
        $user            = $this->gauthuser_model->getByUser('staff', $this->customlib->getStaffID());
        $data['user']    = $user;

        $setting = $this->gauth_model->get();
        if (empty($setting)) {
            $setting                    = new stdClass();
            $setting->use_authenticator = 0;
        }
        $staff=$this->staff_model->get($this->customlib->getStaffID());
        
        $qr_detail         = $this->google_authenticator->generateQR($staff['employee_id']);
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
        $this->load->view('layout/header', $data);
        $this->load->view('admin/gauthenticate/setup', $data);
        $this->load->view('layout/footer', $data);

    }
    
    /*
    This function is used to validate code
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
                    'staff_id'    => $this->customlib->getStaffID(),
                    'secret_code' => $secret,
                );
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
    This function is used to remove 2FA account
     */
    public function delete_setup()
    {
        $this->gauthuser_model->deleteUser('staff', $this->customlib->getStaffID());
        $array = array('status' => 1, 'error' => '', 'message' => $this->lang->line('success_message'));
        echo json_encode($array);
    }

}
