<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Setting extends MY_Addon_QRAttendanceController {

    function __construct() {
        parent::__construct();
    }

    
    public function index()
    {
        if (!$this->rbac->hasPrivilege('qr_code_setting', 'can_view')) {
            access_denied();
        }

        $this->session->set_userdata('top_menu', 'setting');
        $this->session->set_userdata('sub_menu', 'admin/qrattendance/setting/index');
     
        $data['version'] = $this->config->item('version');
        $setting = $this->qrsetting_model->get();
        if (empty($setting)) {
            $setting                    = new stdClass();
           
            $setting->auto_attendance   = 0;
            $setting->camera_type      = 'environmental';
      
        }

        $data['setting'] = $setting;

        $this->form_validation->set_rules('auto_attendance', $this->lang->line("auto_attendance"), 'required|trim|xss_clean');
        $this->form_validation->set_rules('camera_type', $this->lang->line("select_camera"), 'required|trim|xss_clean');
      

        if ($this->form_validation->run() === false) {
                     
            $this->load->view('layout/header');
            $this->load->view('admin/qrattendance/setting',$data);
            $this->load->view('layout/footer');
        } else {

            $data_insert = array(
                'auto_attendance'    => $this->input->post('auto_attendance'),
                'camera_type' => $this->input->post('camera_type')
            );

            $this->qrsetting_model->add($data_insert);
            $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/qrattendance/setting');
        }
    }
    
}