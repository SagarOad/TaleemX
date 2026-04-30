<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Thermalprint extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('media_storage');
        $this->load->library('upload');
        $this->load->model(array('thermal_print_model'));
    }

    public function index()
    {
        $this->session->set_userdata('top_menu', 'System Settings');
        $this->session->set_userdata('sub_menu', 'schsettings/index');
        $this->session->set_userdata('subsub_menu', 'admin/thermalprint/index');

        $setting                        = $this->thermal_print_model->get();    
 	
        $data['result']                 = $setting;       
		
        $this->load->view('layout/header');
        $this->load->view('admin/thermalprint/index', $data);
        $this->load->view('layout/footer');
    }

    public function save()
    {
        $this->form_validation->set_rules('is_print', $this->lang->line('thermal_print'), 'trim|required|xss_clean');         
        $this->form_validation->set_rules('school_name', $this->lang->line('school_name'), 'trim|required|xss_clean');         

        if ($this->form_validation->run() == false) {
            $data = array(
                'is_print' => form_error('is_print'),
                'school_name' => form_error('school_name')
            );
            $array = array('status' => 'fail', 'error' => $data);
            echo json_encode($array);
        } else {
            
            $data    = array(
                'id'            => $this->input->post('sch_id'),                
                'is_print'    => $this->input->post('is_print'),
                'school_name'   => $this->input->post('school_name'),
                'address'  		=> $this->input->post('address'),
                'footer_text'   => $this->input->post('footer_text')
            );

            $this->thermal_print_model->add($data);
            $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
            echo json_encode($array);
        }
    } 


    
}
