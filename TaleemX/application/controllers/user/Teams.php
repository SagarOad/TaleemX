<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Teams extends Student_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('teams_model', 'teamshistory_model', 'teamssetting_model'));
    }

    public function index()
    {
        $this->session->set_userdata('top_menu', 'Teams');
        $student_current_class = $this->customlib->getStudentCurrentClsSection();
        $data['conferences']   = $this->teams_model->getByClassSection($student_current_class->class_id, $student_current_class->section_id);
        $data['teams_setting'] = $this->teamssetting_model->get();
        $data['role']          = $this->customlib->getUserRole();

        $this->load->view('layout/student/header');
        $this->load->view('user/teams/timetable', $data);
        $this->load->view('layout/student/footer');
    }

    public function add_history()
    {
        $this->form_validation->set_rules('id', $this->lang->line('id'), 'required|trim|xss_clean');
        if ($this->form_validation->run() == false) {
            $array = array('status' => 'fail', 'error' => array('id' => form_error('id')));
            echo json_encode($array);
        } else {
            $data_insert = array(
                'teams_id'   => $this->input->post('id'),
                'student_id' => $this->customlib->getStudentSessionUserID(),
            );
            $this->teamshistory_model->updatehistory($data_insert, 'student');
            echo json_encode(array('status' => 1, 'error' => ''));
        }
    }
}
