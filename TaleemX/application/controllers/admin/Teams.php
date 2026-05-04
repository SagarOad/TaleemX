<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Teams extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('teams_mail_sms');
        $this->load->model(array('teams_model', 'teamshistory_model', 'teamssetting_model'));
        $this->sch_setting_detail = $this->setting_model->getSetting();
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('teams_setting', 'can_view')) {
            access_denied();
        }

        $this->session->set_userdata('top_menu', 'teams');
        $this->session->set_userdata('sub_menu', 'teams/teams_setting');

        $setting = $this->teamssetting_model->get();
        if (empty($setting)) {
            $setting                    = new stdClass();
            $setting->parent_live_class = 0;
        }

        $data['setting'] = $setting;
        $this->form_validation->set_rules('parent_live_class', $this->lang->line('parent_live_class'), 'required|trim|xss_clean');

        if ($this->form_validation->run() === true) {
            $this->teamssetting_model->add(array(
                'parent_live_class' => $this->input->post('parent_live_class'),
            ));
            $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/teams');
        }

        $this->load->view('layout/header', $data);
        $this->load->view('admin/teams/index', $data);
        $this->load->view('layout/footer', $data);
    }

    public function timetable()
    {
        if (!$this->rbac->hasPrivilege('teams_live_classes', 'can_view')) {
            access_denied();
        }

        $this->session->set_userdata('top_menu', 'teams');
        $this->session->set_userdata('sub_menu', 'teams/live_class');

        $role                  = json_decode($this->customlib->getStaffRole());
        $staff_id              = $this->customlib->getStaffID();
        $data['role']          = $role;
        $data['logged_staff_id'] = $staff_id;
        $data['classlist']     = $this->class_model->get();
        $data['stafflist']     = $this->staff_model->getEmployee(2);
        $data['teams_setting'] = $this->teamssetting_model->get();
        $data['superadmin_visible'] = $this->customlib->superadmin_visible();

        if ($role->id == 2) {
            $data['teams'] = $this->teams_model->getByStaff($staff_id);
        } else {
            $data['teams'] = $this->teams_model->getByStaff();
        }

        $this->load->view('layout/header');
        $this->load->view('admin/teams/timetable', $data);
        $this->load->view('layout/footer');
    }

    public function add()
    {
        if (!$this->rbac->hasPrivilege('teams_live_classes', 'can_add')) {
            access_denied();
        }

        $this->form_validation->set_rules('title', $this->lang->line('class_title'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('date', $this->lang->line('class_date_time'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('section_id[]', $this->lang->line('section'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('staff_id', $this->lang->line('staff'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('duration', $this->lang->line('class_duration_minutes'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('url', $this->lang->line('teams_url'), 'required|trim|xss_clean');

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata('msg', '<div class="alert alert-danger">' . validation_errors() . '</div>');
            redirect('admin/teams/timetable');
        }

        if (!$this->teams_datetime_must_be_future($this->input->post('date'))) {
            $this->session->set_flashdata('msg', '<div class="alert alert-danger">' . $this->lang->line('teams_datetime_cannot_be_in_the_past') . '</div>');
            redirect('admin/teams/timetable');
        }

        $insert_array = array(
            'staff_id'    => $this->input->post('staff_id'),
            'title'       => $this->input->post('title'),
            'date'        => date('Y-m-d H:i:s', $this->customlib->dateTimeformat($this->input->post('date'))),
            'duration'    => $this->input->post('duration'),
            'created_id'  => $this->customlib->getStaffID(),
            'description' => $this->input->post('description'),
            'timezone'    => $this->customlib->getTimeZone(),
            'url'         => $this->input->post('url'),
            'type'        => 'manual',
        );

        $is_affected = $this->teams_model->add($insert_array, $this->input->post('section_id[]'));
        if ($is_affected) {
            $sender_details = array(
                'class_section_id' => $this->input->post('section_id[]'),
                'title'            => $this->input->post('title'),
                'date'             => $this->input->post('date'),
                'duration'         => $this->input->post('duration'),
            );
            $this->teams_mail_sms->mailsms('teams_online_classes', $sender_details);
            $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('success_message') . '</div>');
        } else {
            $this->session->set_flashdata('msg', '<div class="alert alert-danger">' . $this->lang->line('something_went_wrong') . '</div>');
        }

        redirect('admin/teams/timetable');
    }

    public function delete($id)
    {
        if (!$this->rbac->hasPrivilege('teams_live_classes', 'can_delete')) {
            access_denied();
        }
        $this->teams_model->remove($id);
        $this->session->set_flashdata('msg', '<div class="alert alert-danger text-left">' . $this->lang->line('delete_message') . '</div>');
        redirect('admin/teams/timetable');
    }

    public function update_live_class()
    {
        if (!$this->rbac->hasPrivilege('teams_live_classes', 'can_add')) {
            access_denied();
        }

        $this->form_validation->set_rules('teams_id', $this->lang->line('id'), 'required|trim|xss_clean|integer');
        $this->form_validation->set_rules('title', $this->lang->line('class_title'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('date', $this->lang->line('class_date_time'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('section_id[]', $this->lang->line('section'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('staff_id', $this->lang->line('staff'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('duration', $this->lang->line('class_duration_minutes'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('url', $this->lang->line('teams_url'), 'required|trim|xss_clean');

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata('msg', '<div class="alert alert-danger">' . validation_errors() . '</div>');
            redirect('admin/teams/timetable');
        }

        if (!$this->teams_datetime_must_be_future($this->input->post('date'))) {
            $this->session->set_flashdata('msg', '<div class="alert alert-danger">' . $this->lang->line('teams_datetime_cannot_be_in_the_past') . '</div>');
            redirect('admin/teams/timetable');
        }

        $teams_id = (int) $this->input->post('teams_id');
        $row      = $this->teams_model->getByIdSession($teams_id);
        if (empty($row) || (int) $row->status !== 0 || (int) $row->created_id !== (int) $this->customlib->getStaffID()) {
            $this->session->set_flashdata('msg', '<div class="alert alert-danger">' . $this->lang->line('something_went_wrong') . '</div>');
            redirect('admin/teams/timetable');
        }

        $update = array(
            'staff_id'    => $this->input->post('staff_id'),
            'title'       => $this->input->post('title'),
            'date'        => date('Y-m-d H:i:s', $this->customlib->dateTimeformat($this->input->post('date'))),
            'duration'    => $this->input->post('duration'),
            'description' => $this->input->post('description'),
            'timezone'    => $this->customlib->getTimeZone(),
            'url'         => $this->input->post('url'),
        );

        $this->teams_model->update($teams_id, $update);
        $this->teams_model->replaceSections($teams_id, $this->input->post('section_id[]'));
        $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('update_message') . '</div>');
        redirect('admin/teams/timetable');
    }

    public function chgstatus()
    {
        $response = array();
        $this->form_validation->set_rules('teams_id', $this->lang->line('id'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('chg_status', $this->lang->line('status'), 'required|trim|xss_clean');

        if ($this->form_validation->run() == false) {
            $response = array('status' => 0, 'error' => validation_errors());
        } else {
            $this->teams_model->update($this->input->post('teams_id'), array('status' => $this->input->post('chg_status')));
            $response = array('status' => 1, 'message' => $this->lang->line('update_message'));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($response));
    }

    public function start($id)
    {
        $result = $this->teams_model->get_urlByTeamsId($id);
        if (empty($result)) {
            redirect('admin/teams/timetable');
        }

        $class_section = $this->teams_model->getClassSectionByTeamsID($id);
        foreach ($class_section as $value) {
            $sender_details = array(
                'class_section_id' => $value->cls_section_id,
                'title'            => $result['title'],
                'date'             => $result['date'],
                'duration'         => $result['duration'],
            );
            $this->teams_mail_sms->mailsms('teams_online_classes_start', $sender_details);
        }

        header("Location: " . $result['url']);
    }

    private function teams_datetime_must_be_future($date_post)
    {
        $ts = $this->customlib->dateTimeformat($date_post);
        if (empty($ts)) {
            return false;
        }
        return ($ts >= time());
    }
}
