<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Gmeet extends Admin_Controller
{
	
	public $gmeet_version = "";
	
    public function __construct()
    {
        parent::__construct();
        $this->load->library('gmeet_mail_sms');
        $this->load->model(array('gmeet_model', 'gmeethistory_model', 'gmeetsetting_model')); 
		$this->load->config('gmeet-config');
        $this->gmeet_version = $this->config->item('version');
        $this->sch_setting_detail = $this->setting_model->getSetting();
        if ($this->router->fetch_method() != "index") {
            $this->auth->addonchk('ssglc', site_url('admin/gmeet'));
        }
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('gmeet_setting', 'can_view')) {
            access_denied();
        }

        $this->session->set_userdata('top_menu', 'gmeet');
        $this->session->set_userdata('sub_menu', 'gmeet/gmeet_setting');
        $data              = array();
        $data['title']     = 'Gmeet Setting'; 
		$data['version']   = $this->gmeet_version;
        $addon_ver         = $this->config->item('addon_ver');
        $data['addon_ver'] = $addon_ver;

        $setting = $this->gmeetsetting_model->get();
        if (empty($setting)) {
            $setting                  = new stdClass();
            $setting->parent_live_class = 0;
        }

        $data['setting'] = $setting;
        $this->form_validation->set_rules('parent_live_class', $this->lang->line('parent_live_class'), 'required|trim|xss_clean');
        if ($this->form_validation->run() === true) {
            $data_insert = array(
                'api_key'           => '',
                'api_secret'        => '',
                'use_api'           => 0,
                'parent_live_class' => $this->input->post('parent_live_class'),
            );
            $this->gmeetsetting_model->add($data_insert);
            $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('update_message') . '</div>');
            redirect('admin/gmeet');
        }

        $this->load->view('layout/header', $data);
        $this->load->view('admin/gmeet/index', $data);
        $this->load->view('layout/footer', $data);
    }

    public function timetable()
    {
        //======
        $userdata = array(
            'back_url' => current_url(),
        );
        $this->session->set_userdata($userdata);
        //======
        $this->session->set_userdata('top_menu', 'gmeet');
        $this->session->set_userdata('sub_menu', 'gmeet/live_class');
        $data             = array();
        $role             = json_decode($this->customlib->getStaffRole());
        $gmeet_setting         = $this->gmeetsetting_model->get();
        $data['auth_url']     = '';
        $class                 = $this->class_model->get();
        $data['classlist']    = $class;
        $data['role']         = $role;
        $staff_id             = $this->customlib->getStaffID();
        $data['logged_staff_id'] = $staff_id;
        $data['gmeet_setting']   = $gmeet_setting;
        $data['link_status']     = 0;
        if ($role->id == 2) {
            $stafflist         = $this->staff_model->getEmployee(2);
            $data['stafflist'] = $stafflist;
            $data['timetable'] = array();
            $days              = $this->customlib->getDaysname();
            $data['gmeets']    = $this->gmeet_model->getByStaff($this->customlib->getStaffID());
            $userdata          = $this->customlib->getUserData();
            $role_id           = $userdata["role_id"];
            $condition         = "";

        $class_section_array=$this->customlib->get_myClassSection();
          
        foreach ($days as $day_key => $day_value) {
            $data['timetable'][$day_key] = $this->subjecttimetable_model->getSyllabussubject($staff_id, $day_key, $class_section_array);
        }

        } else {
            $data['gmeets'] = $this->gmeet_model->getByStaff();
        }
        
        $data['superadmin_visible'] = $this->customlib->superadmin_visible();
        
        $this->load->view('layout/header');
        if ($role->id == 2) {
            $this->load->view('admin/gmeet/timetable', $data);
        } else {
            $roles         = $this->role_model->get();
            $data['roles'] = $roles;
            $this->load->view('admin/gmeet/stafftimetable', $data);
        }
        $this->load->view('layout/footer');
    }

    public function delete($id)
    {
        $this->session->set_flashdata('msg', '<div class="alert alert-danger text-left">' . $this->lang->line('delete_message') . '</div>');
        $this->gmeet_model->remove($id);
        redirect($_SERVER['HTTP_REFERER'], 'refresh');
    }

    public function addByOther()
    {
        $response      = array();
        $gmeet_setting = $this->gmeetsetting_model->get();

        $this->form_validation->set_rules('title', $this->lang->line('class_title'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('date', $this->lang->line('class_date_time'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('section_id[]', $this->lang->line('section'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('staff_id', $this->lang->line('staff'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('role_id', $this->lang->line('role'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('duration', $this->lang->line('class_duration_minutes'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('url', $this->lang->line('gmeet_url'), 'required|trim|xss_clean');
        if ($this->form_validation->run() == false) {
            $form_error = array(
                'title'      => form_error('title'),
                'date'       => form_error('date'),
                'class_id'   => form_error('class_id'),
                'section_id' => form_error('section_id[]'),
                'staff_id'   => form_error('staff_id'),
                'role_id'    => form_error('role_id'),
                'duration'   => form_error('duration'),
                'url'        => form_error('url'),
            );
            $response = array('status' => 0, 'error' => $form_error);
        } else {
            if (!$this->gmeet_datetime_must_be_future($this->input->post('date'))) {
                $response = array('status' => 0, 'error' => array('date' => $this->lang->line('gmeet_datetime_cannot_be_in_the_past')));
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode($response));
            }
            $status = true;

            $insert_array = array(
                'staff_id'    => $this->input->post('staff_id'),
                'title'       => $this->input->post('title'),
                'date'        => date('Y-m-d H:i:s', $this->customlib->dateTimeformat($this->input->post('date'))),
                'duration'    => $this->input->post('duration'),
                'created_id'  => $this->customlib->getStaffID(),
                'description' => $this->input->post('description'),
                'timezone'    => $this->customlib->getTimeZone(),
                'url'         => $this->input->post('url'),
            );
            if ($status) {
                $is_affected = $this->gmeet_model->add($insert_array, $this->input->post('section_id[]'));
                if ($is_affected) {
                    //==============
                    $sender_details = array('class_section_id' => $this->input->post('section_id[]'), 'title' => $this->input->post('title'), 'date' => $this->input->post('date'), 'duration' => $this->input->post('duration'));
                    $this->gmeet_mail_sms->mailsms('gmeet_online_classes', $sender_details);
                    //================
                    $response = array('status' => 1, 'message' => $this->lang->line('success_message'));
                } else {
                    $response = array('status' => 0, 'error' => array('Something went wrong.'));
                }
            } else {
                $response = array('status' => 0, 'error' => array('Something went wrong.'));
            }
        }
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($response));
    }

    public function addByClassTeacher()
    {
        $response      = array();
        $gmeet_setting = $this->gmeetsetting_model->get();
        $this->form_validation->set_rules('title', $this->lang->line('class_title'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('date', $this->lang->line('class_date_time'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('section_id[]', $this->lang->line('section'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('staff_id', $this->lang->line('staff'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('duration', $this->lang->line('class_duration_minutes'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('url', $this->lang->line('gmeet_url'), 'required|trim|xss_clean');
        if ($this->form_validation->run() == false) {
            $form_error = array(
                'title'      => form_error('title'),
                'date'       => form_error('date'),
                'class_id'   => form_error('class_id'),
                'section_id' => form_error('section_id[]'),
                'staff_id'   => form_error('staff_id'),
                'duration'   => form_error('duration'),
                'url'        => form_error('url'),
            );
            $response = array('status' => 0, 'error' => $form_error);
        } else {
            if (!$this->gmeet_datetime_must_be_future($this->input->post('date'))) {
                $response = array('status' => 0, 'error' => array('date' => $this->lang->line('gmeet_datetime_cannot_be_in_the_past')));
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode($response));
            }
            $status       = true;
            $insert_array = array(
                'staff_id'    => $this->input->post('staff_id'),
                'title'       => $this->input->post('title'),
                'date'        => date('Y-m-d H:i:s', $this->customlib->dateTimeformat($this->input->post('date'))),
                'duration'    => $this->input->post('duration'),
                'created_id'  => $this->customlib->getStaffID(),
                'description' => $this->input->post('description'),
                'timezone'    => $this->customlib->getTimeZone(),
                'url'         => $this->input->post('url'),
            );
            if ($status) {
                $is_affected = $this->gmeet_model->add($insert_array, $this->input->post('section_id[]'));
                if ($is_affected) {
                    //==============
                    $sender_details = array('class_section_id' => $this->input->post('section_id[]'), 'title' => $this->input->post('title'), 'date' => $this->input->post('date'), 'duration' => $this->input->post('duration'));
                    $this->gmeet_mail_sms->mailsms('gmeet_online_classes', $sender_details);
                    //================
                    $response = array('status' => 1, 'message' => $this->lang->line('success_message'));
                } else {
                    $response = array('status' => 0, 'error' => array('Something went wrong.'));
                }
            } else {
                $response = array('status' => 0, 'error' => array('Something went wrong.'));
            }
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($response));
    }

    public function add()
    {
        $response      = array();
        $gmeet_setting = $this->gmeetsetting_model->get();
        $this->form_validation->set_rules('title', $this->lang->line('class_title'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('date', $this->lang->line('class_date_time'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('section_id[]', $this->lang->line('section'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('duration', $this->lang->line('class_duration_minutes'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('url', $this->lang->line('gmeet_url'), 'required|trim|xss_clean');

        if ($this->form_validation->run() == false) {
            $form_errors = array(
                'title'      => form_error('title'),
                'date'       => form_error('date'),
                'class_id'   => form_error('class_id'),
                'section_id' => form_error('section_id[]'),
                'duration'   => form_error('duration'),
                'url'        => form_error('url'),
            );
            $response = array('status' => 0, 'error' => $form_errors);
        } else {
            if (!$this->gmeet_datetime_must_be_future($this->input->post('date'))) {
                $response = array('status' => 0, 'error' => array('date' => $this->lang->line('gmeet_datetime_cannot_be_in_the_past')));
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode($response));
            }
            $status = true;

            $insert_array = array(
                'staff_id'    => $this->customlib->getStaffID(),
                'title'       => $this->input->post('title'),
                'date'        => date('Y-m-d H:i:s', $this->customlib->dateTimeformat($this->input->post('date'))),
                'duration'    => $this->input->post('duration'),
                'created_id'  => $this->customlib->getStaffID(),
                'description' => $this->input->post('description'),
                'timezone'    => $this->customlib->getTimeZone(),
                'url'         => $this->input->post('url'),
            );
            if ($status) {
                $is_affected = $this->gmeet_model->add($insert_array, $this->input->post('section_id[]'));
                if ($is_affected) {
                    //==============
                    $sender_details = array('class_section_id' => $this->input->post('section_id[]'), 'title' => $this->input->post('title'), 'date' => $this->input->post('date'), 'duration' => $this->input->post('duration'));
                    $this->gmeet_mail_sms->mailsms('gmeet_online_classes', $sender_details);
                    //================
                    $response = array('status' => 1, 'message' => $this->lang->line('success_message'));
                } else {
                    $response = array('status' => 0, 'error' => array('Something went wrong.'));
                }
            } else {
                $response = array('status' => 0, 'error' => array('Something went wrong.'));
            }
        }
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($response));
    }

    public function meeting()
    {
        $this->session->set_userdata('top_menu', 'gmeet');
        $this->session->set_userdata('sub_menu', 'gmeet/live_meeting');
        //======
        $userdata = array(
            'back_url' => current_url(),
        );
        $this->session->set_userdata($userdata);
        //======
        $data             = array();
        $gmeet_setting         = $this->gmeetsetting_model->get();
        $data['auth_url']     = '';
        $role                  = json_decode($this->customlib->getStaffRole());
        $data['role']          = $role;
        $data['gmeet_setting'] = $gmeet_setting;
        $data['link_status']   = 0;
        $data['logged_staff_id'] = $this->customlib->getStaffID();
        if ($role->id == 7) {
            $data['meetings'] = $this->gmeet_model->getStaffMeeting();
        } else {
            $data['meetings'] = $this->gmeet_model->getStaffMeeting($data['logged_staff_id']);
        }
        $data['staffList'] = $this->staff_model->get();
        $data['superadmin_visible'] = $this->customlib->superadmin_visible();
        $this->load->view('layout/header');
        $this->load->view('admin/gmeet/meeting', $data);
        $this->load->view('layout/footer');
    }

    public function addMeeting()
    {
        $response      = array();
        $gmeet_setting = $this->gmeetsetting_model->get();
        $this->form_validation->set_rules('title', $this->lang->line('meeting') . ' ' . $this->lang->line('title'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('date', $this->lang->line('meeting_date_time'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('duration', $this->lang->line('meeting_duration_minutes'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('staff[]', $this->lang->line('staff'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('url', $this->lang->line('gmeet_url'), 'required|trim|xss_clean');
        if ($this->form_validation->run() == false) {
            $form_error = array(
                'title'    => form_error('title'),
                'date'     => form_error('date'),
                'staff[]'  => form_error('staff[]'),
                'duration' => form_error('duration'),
                'url'      => form_error('url'),
            );
            $response = array('status' => 0, 'error' => $form_error);
        } else {
            if (!$this->gmeet_datetime_must_be_future($this->input->post('date'))) {
                $response = array('status' => 0, 'error' => array('date' => $this->lang->line('gmeet_datetime_cannot_be_in_the_past')));
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode($response));
            }
            //=======
            $status       = true;
            $insert_array = array(
                'title'       => $this->input->post('title'),
                'date'        => date('Y-m-d H:i:s', $this->customlib->dateTimeformat($this->input->post('date'))),
                'duration'    => $this->input->post('duration'),
                'created_id'  => $this->customlib->getStaffID(),
                'description' => $this->input->post('description'),
                'purpose'     => 'meeting',
                'timezone'    => $this->customlib->getTimeZone(),
                'url'         => $this->input->post('url'),
            );
            if ($status) {
                $staff       = $this->input->post('staff[]');
                $is_affected = $this->gmeet_model->addmeeting($insert_array, $staff);
                if ($is_affected) {
                    $staff_mail_sms_list = $this->gmeet_model->getAllStaffByArray($staff);
                    if (!empty($staff_mail_sms_list)) {
                        $sender_details = array();
                        foreach ($staff_mail_sms_list as $staff_mail_sms_list_key => $staff_mail_sms_list_value) {
                            $sender_details[] = array(
                                'title'       => $this->input->post('title'),
                                'date'        => $this->input->post('date'),
                                'duration'    => $this->input->post('duration'),
                                'employee_id' => $staff_mail_sms_list_value->employee_id,
                                'department'  => $staff_mail_sms_list_value->department,
                                'designation' => $staff_mail_sms_list_value->designation,
                                'name'        => ($staff_mail_sms_list_value->surname == "") ? $staff_mail_sms_list_value->name : $staff_mail_sms_list_value->name . " " . $staff_mail_sms_list_value->surname,
                                'contact_no'  => $staff_mail_sms_list_value->contact_no,
                                'email'       => $staff_mail_sms_list_value->email,
                            );
                        }
                        $this->gmeet_mail_sms->mailsms('gmeet_online_meeting', $sender_details);
                    }
                    $response = array('status' => 1, 'message' => $this->lang->line('success_message'));
                } else {
                    $response = array('status' => 0, 'error' => array('Something went wrong.'));
                }
            } else {
                $response = array('status' => 0, 'error' => array('Something went wrong.'));
            }
        }
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($response));
    }

    public function getcredential()
    {
        $response                    = array();
        $staff                       = $this->staff_model->get($this->customlib->getStaffID());
        $response['zoom_api_key']    = $staff['zoom_api_key'];
        $response['zoom_api_secret'] = $staff['zoom_api_secret'];
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($response));
    }

    public function chgstatus()
    {
        $response = array();

        $this->form_validation->set_rules('gmeet_id', $this->lang->line('id'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('chg_status', $this->lang->line('status'), 'required|trim|xss_clean');

        if ($this->form_validation->run() == false) {
            $data = array(
                'gmeet_id'   => form_error('gmeet_id'),
                'chg_status' => form_error('chg_status'),
            );
            $response = array('status' => 0, 'error' => $data);
        } else {
            $insert_array = array(
                'status' => $this->input->post('chg_status'),
            );
            $insert_id = $this->gmeet_model->update($this->input->post('gmeet_id'), $insert_array);
            $response  = array('status' => 1, 'message' => $this->lang->line('update_message'));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($response));
    }

    public function meeting_report()
    {
        $this->session->set_userdata('top_menu', 'gmeet');
        $this->session->set_userdata('sub_menu', 'gmeet/meeting_report');
        $data                    = array();
        $staff_id                = $this->customlib->getStaffID();
        $data['logged_staff_id'] = $staff_id;
        $data['meetingList']     = $this->gmeethistory_model->getmeeting();        
        $data['superadmin_visible'] = $this->customlib->superadmin_visible();        
        $this->load->view('layout/header');
        $this->load->view('admin/gmeet/meeting_report', $data);
        $this->load->view('layout/footer');
    }

    public function class_report()
    {
        $this->session->set_userdata('top_menu', 'gmeet');
        $this->session->set_userdata('sub_menu', 'gmeet/class_report');
        $data['title']           = 'Class Report';
        $class                   = $this->class_model->get();
        $data['classlist']       = $class;
        $staff_id                = $this->customlib->getStaffID();
        $data['logged_staff_id'] = $staff_id;
        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('section_id', $this->lang->line('section'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {

        } else {
            $class_id              = $this->input->post('class_id');
            $section_id            = $this->input->post('section_id');
            $data['class_id']      = $class_id;
            $data['section_id']    = $section_id;
            $data['liveclassList'] = $this->gmeethistory_model->getclass($class_id, $section_id);
        }
        $data['superadmin_visible'] = $this->customlib->superadmin_visible();
        $this->load->view('layout/header', $data);
        $this->load->view('admin/gmeet/class_report', $data);
        $this->load->view('layout/footer', $data);
    }

    public function add_history()
    {
        $this->form_validation->set_rules('id', $this->lang->line('id'), 'required|trim|xss_clean');
        if ($this->form_validation->run() == false) {
            $data = array(
                'id' => form_error('id'),
            );
            $array = array('status' => 'fail', 'error' => $data);
            echo json_encode($array);
        } else {
            $staff_id    = $this->customlib->getStaffID();
            $data_insert = array(
                'gmeet_id' => $this->input->post('id'),
                'staff_id' => $staff_id,
            );
            $this->gmeethistory_model->updatehistory($data_insert, 'staff');
            $array = array('status' => 1, 'error' => '');
            echo json_encode($array);
        }
    }

    public function getViewerList()
    {
        $recordid     = $this->input->post('recordid');
        $type         = $this->input->post('type');
        $data['type'] = 'staff';
        if (isset($type)) {
            $data['type']         = $type;
            $class_id             = $this->input->post('class_id');
            $section_id           = $this->input->post('section_id');
            $data['viewerDetail'] = $this->gmeethistory_model->getLiveStudent($recordid, $class_id, $section_id);
        } else {
            $data['viewerDetail'] = $this->gmeethistory_model->getMeetingStaff($recordid);
        }
        $data['sch_setting'] = $this->sch_setting_detail;
        $data['page']        = $this->load->view('admin/gmeet/_partialviewerlist', $data, true);
        echo json_encode($data);
    }
 
    public function getMeetingStaff()
    {
        $id                   = $this->input->post('id');
        $data['viewerDetail'] = $this->gmeet_model->getMeetingStaff($id);
        $data['page']         = $this->load->view('admin/gmeet/_partialmeetingstaff', $data, true);
        echo json_encode($data);
    }

    public function authenticate()
    {
        $back_url = $this->session->userdata('back_url') ? $this->session->userdata('back_url') : site_url('admin/gmeet');
        $this->session->set_flashdata('msg', '<div class="alert alert-info">' . $this->lang->line('gmeet_manual_only') . '</div>');
        redirect($back_url, 'refresh');
    }

    public function start($id, $type)
    {

        $result = $this->gmeet_model->get_urlBygmeetId($id);
        if ($type == 'meeting') {
            $staff_mail_sms_list = $this->gmeet_model->get_meetingStaff($id);
            if (!empty($staff_mail_sms_list)) {
                $sender_details = array();
                foreach ($staff_mail_sms_list as $staff_mail_sms_list_key => $staff_mail_sms_list_value) {
                    $sender_details[] = array(
                        'title'       => $result['title'],
                        'date'        => $result['date'],
                        'duration'    => $result['duration'],
                        'employee_id' => $staff_mail_sms_list_value->employee_id,
                        'department'  => $staff_mail_sms_list_value->department,
                        'designation' => $staff_mail_sms_list_value->designation,
                        'name'        => ($staff_mail_sms_list_value->surname == "") ? $staff_mail_sms_list_value->name : $staff_mail_sms_list_value->name . " " . $staff_mail_sms_list_value->surname,
                        'contact_no'  => $staff_mail_sms_list_value->contact_no,
                        'email'       => $staff_mail_sms_list_value->email,
                    );
                }
                $this->gmeet_mail_sms->mailsms('gmeet_online_meeting_start', $sender_details);
            }
        } else {
            $class_section = $this->gmeet_model->getClassSectionByGmeetID($id);
            foreach ($class_section as $key => $value) {
                $sender_details = array(
                    'class_section_id' => $value->cls_section_id,
                    'title'            => $result['title'],
                    'date'             => $result['date'],
                    'duration'         => $result['duration'],
                );

                $this->gmeet_mail_sms->mailsms('gmeet_online_classes_start', $sender_details);
            }

        }

        $url = $result['url'];

        header("Location: $url");

    }

    public function get_gmeet_for_edit()
    {
        $id = (int) $this->input->post('id');
        if ($id < 1) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('status' => 0, 'message' => $this->lang->line('something_went_wrong'))));
        }
        $row = $this->gmeet_model->getByIdSession($id);
        if (empty($row) || (int) $row->status !== 0 || (int) $row->created_id !== (int) $this->customlib->getStaffID()) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('status' => 0, 'message' => $this->lang->line('something_went_wrong'))));
        }
        $section_ids = $this->gmeet_model->getClsSectionIdsByGmeet($id);
        $class_id      = '';
        if (!empty($section_ids)) {
            $cs_row = $this->db->select('class_id')->from('class_sections')->where('id', (int) $section_ids[0])->get()->row();
            if (!empty($cs_row)) {
                $class_id = (string) $cs_row->class_id;
            }
        }
        $staff_ids = array();
        if ($row->purpose === 'meeting') {
            $staff_ids = $this->gmeet_model->getMeetingStaffIds($id);
        }
        $role_id = '';
        if (!empty((int) $row->staff_id)) {
            $sr = $this->db->select('role_id')->from('staff_roles')->where('staff_id', (int) $row->staff_id)->order_by('id', 'asc')->limit(1)->get()->row();
            if (!empty($sr) && isset($sr->role_id)) {
                $role_id = (string) $sr->role_id;
            }
        }
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status'      => 1,
                'role_id'     => $role_id,
                'record'      => array(
                    'id'          => (int) $row->id,
                    'title'       => $row->title,
                    'description' => $row->description,
                    'duration'    => $row->duration,
                    'date'        => $row->date,
                    'url'         => $row->url,
                    'purpose'     => $row->purpose,
                    'staff_id'    => $row->staff_id,
                ),
                'class_id'    => $class_id,
                'section_ids' => $section_ids,
                'staff_ids'   => $staff_ids,
            )));
    }

    public function update_meeting()
    {
        $response      = array();
        $gmeet_setting = $this->gmeetsetting_model->get();
        $this->form_validation->set_rules('gmeet_id', $this->lang->line('id'), 'required|trim|xss_clean|integer');
        $this->form_validation->set_rules('title', $this->lang->line('meeting') . ' ' . $this->lang->line('title'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('date', $this->lang->line('meeting_date_time'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('duration', $this->lang->line('meeting_duration_minutes'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('staff[]', $this->lang->line('staff'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('url', $this->lang->line('gmeet_url'), 'required|trim|xss_clean');
        if ($this->form_validation->run() == false) {
            $form_error = array(
                'gmeet_id'   => form_error('gmeet_id'),
                'title'      => form_error('title'),
                'date'       => form_error('date'),
                'staff[]'    => form_error('staff[]'),
                'duration'   => form_error('duration'),
                'url'        => form_error('url'),
            );
            $response = array('status' => 0, 'error' => $form_error);
        } else {
            if (!$this->gmeet_datetime_must_be_future($this->input->post('date'))) {
                $response = array('status' => 0, 'error' => array('date' => $this->lang->line('gmeet_datetime_cannot_be_in_the_past')));
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode($response));
            }
            $gmeet_id = (int) $this->input->post('gmeet_id');
            $row      = $this->gmeet_model->getByIdSession($gmeet_id);
            if (empty($row) || $row->purpose !== 'meeting' || (int) $row->status !== 0 || (int) $row->created_id !== (int) $this->customlib->getStaffID()) {
                $response = array('status' => 0, 'error' => array('gmeet_id' => $this->lang->line('something_went_wrong')));
            } else {
                $update = array(
                    'title'       => $this->input->post('title'),
                    'date'        => date('Y-m-d H:i:s', $this->customlib->dateTimeformat($this->input->post('date'))),
                    'duration'    => $this->input->post('duration'),
                    'description' => $this->input->post('description'),
                    'timezone'    => $this->customlib->getTimeZone(),
                    'url'         => $this->input->post('url'),
                );
                $this->gmeet_model->update($gmeet_id, $update);
                $this->gmeet_model->replaceMeetingStaff($gmeet_id, $this->input->post('staff[]'));
                $response = array('status' => 1, 'message' => $this->lang->line('update_message'));
            }
        }
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($response));
    }

    public function update_live_class()
    {
        $response      = array();
        $gmeet_setting = $this->gmeetsetting_model->get();
        $this->form_validation->set_rules('gmeet_id', $this->lang->line('id'), 'required|trim|xss_clean|integer');
        $this->form_validation->set_rules('title', $this->lang->line('class_title'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('date', $this->lang->line('class_date_time'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('section_id[]', $this->lang->line('section'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('duration', $this->lang->line('class_duration_minutes'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('url', $this->lang->line('gmeet_url'), 'required|trim|xss_clean');
        if ($this->form_validation->run() == false) {
            $form_errors = array(
                'gmeet_id'   => form_error('gmeet_id'),
                'title'      => form_error('title'),
                'date'       => form_error('date'),
                'class_id'   => form_error('class_id'),
                'section_id' => form_error('section_id[]'),
                'duration'   => form_error('duration'),
                'url'        => form_error('url'),
            );
            $response = array('status' => 0, 'error' => $form_errors);
        } else {
            if (!$this->gmeet_datetime_must_be_future($this->input->post('date'))) {
                $response = array('status' => 0, 'error' => array('date' => $this->lang->line('gmeet_datetime_cannot_be_in_the_past')));
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode($response));
            }
            $gmeet_id = (int) $this->input->post('gmeet_id');
            $row      = $this->gmeet_model->getByIdSession($gmeet_id);
            if (empty($row) || $row->purpose !== 'class' || (int) $row->status !== 0 || (int) $row->created_id !== (int) $this->customlib->getStaffID()) {
                $response = array('status' => 0, 'error' => array('gmeet_id' => $this->lang->line('something_went_wrong')));
            } else {
                $update = array(
                    'title'       => $this->input->post('title'),
                    'date'        => date('Y-m-d H:i:s', $this->customlib->dateTimeformat($this->input->post('date'))),
                    'duration'    => $this->input->post('duration'),
                    'description' => $this->input->post('description'),
                    'timezone'    => $this->customlib->getTimeZone(),
                    'url'         => $this->input->post('url'),
                );
                $this->gmeet_model->update($gmeet_id, $update);
                $this->gmeet_model->replaceSections($gmeet_id, $this->input->post('section_id[]'));
                $response = array('status' => 1, 'message' => $this->lang->line('update_message'));
            }
        }
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($response));
    }

    public function update_live_class_classteacher()
    {
        $response      = array();
        $gmeet_setting = $this->gmeetsetting_model->get();
        $this->form_validation->set_rules('gmeet_id', $this->lang->line('id'), 'required|trim|xss_clean|integer');
        $this->form_validation->set_rules('title', $this->lang->line('class_title'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('date', $this->lang->line('class_date_time'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('section_id[]', $this->lang->line('section'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('staff_id', $this->lang->line('staff'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('duration', $this->lang->line('class_duration_minutes'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('url', $this->lang->line('gmeet_url'), 'required|trim|xss_clean');
        if ($this->form_validation->run() == false) {
            $form_error = array(
                'gmeet_id'   => form_error('gmeet_id'),
                'title'      => form_error('title'),
                'date'       => form_error('date'),
                'class_id'   => form_error('class_id'),
                'section_id' => form_error('section_id[]'),
                'staff_id'   => form_error('staff_id'),
                'duration'   => form_error('duration'),
                'url'        => form_error('url'),
            );
            $response = array('status' => 0, 'error' => $form_error);
        } else {
            if (!$this->gmeet_datetime_must_be_future($this->input->post('date'))) {
                $response = array('status' => 0, 'error' => array('date' => $this->lang->line('gmeet_datetime_cannot_be_in_the_past')));
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode($response));
            }
            $gmeet_id = (int) $this->input->post('gmeet_id');
            $row      = $this->gmeet_model->getByIdSession($gmeet_id);
            if (empty($row) || $row->purpose !== 'class' || (int) $row->status !== 0 || (int) $row->created_id !== (int) $this->customlib->getStaffID()) {
                $response = array('status' => 0, 'error' => array('gmeet_id' => $this->lang->line('something_went_wrong')));
            } else {
                $update = array(
                    'staff_id'    => $this->input->post('staff_id'),
                    'title'       => $this->input->post('title'),
                    'date'        => date('Y-m-d H:i:s', $this->customlib->dateTimeformat($this->input->post('date'))),
                    'duration'    => $this->input->post('duration'),
                    'description' => $this->input->post('description'),
                    'timezone'    => $this->customlib->getTimeZone(),
                    'url'         => $this->input->post('url'),
                );
                $this->gmeet_model->update($gmeet_id, $update);
                $this->gmeet_model->replaceSections($gmeet_id, $this->input->post('section_id[]'));
                $response = array('status' => 1, 'message' => $this->lang->line('update_message'));
            }
        }
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($response));
    }

    public function update_live_class_admin()
    {
        $response      = array();
        $gmeet_setting = $this->gmeetsetting_model->get();
        $this->form_validation->set_rules('gmeet_id', $this->lang->line('id'), 'required|trim|xss_clean|integer');
        $this->form_validation->set_rules('title', $this->lang->line('class_title'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('date', $this->lang->line('class_date_time'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('section_id[]', $this->lang->line('section'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('staff_id', $this->lang->line('staff'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('role_id', $this->lang->line('role'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('duration', $this->lang->line('class_duration_minutes'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('url', $this->lang->line('gmeet_url'), 'required|trim|xss_clean');
        if ($this->form_validation->run() == false) {
            $form_error = array(
                'gmeet_id'   => form_error('gmeet_id'),
                'title'      => form_error('title'),
                'date'       => form_error('date'),
                'class_id'   => form_error('class_id'),
                'section_id' => form_error('section_id[]'),
                'staff_id'   => form_error('staff_id'),
                'role_id'    => form_error('role_id'),
                'duration'   => form_error('duration'),
                'url'        => form_error('url'),
            );
            $response = array('status' => 0, 'error' => $form_error);
        } else {
            if (!$this->gmeet_datetime_must_be_future($this->input->post('date'))) {
                $response = array('status' => 0, 'error' => array('date' => $this->lang->line('gmeet_datetime_cannot_be_in_the_past')));
                return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(200)
                    ->set_output(json_encode($response));
            }
            $gmeet_id = (int) $this->input->post('gmeet_id');
            $row      = $this->gmeet_model->getByIdSession($gmeet_id);
            if (empty($row) || $row->purpose !== 'class' || (int) $row->status !== 0 || (int) $row->created_id !== (int) $this->customlib->getStaffID()) {
                $response = array('status' => 0, 'error' => array('gmeet_id' => $this->lang->line('something_went_wrong')));
            } else {
                $update = array(
                    'staff_id'    => $this->input->post('staff_id'),
                    'title'       => $this->input->post('title'),
                    'date'        => date('Y-m-d H:i:s', $this->customlib->dateTimeformat($this->input->post('date'))),
                    'duration'    => $this->input->post('duration'),
                    'description' => $this->input->post('description'),
                    'timezone'    => $this->customlib->getTimeZone(),
                    'url'         => $this->input->post('url'),
                );
                $this->gmeet_model->update($gmeet_id, $update);
                $this->gmeet_model->replaceSections($gmeet_id, $this->input->post('section_id[]'));
                $response = array('status' => 1, 'message' => $this->lang->line('update_message'));
            }
        }
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($response));
    }

    private function gmeet_datetime_must_be_future($date_post)
    {
        $ts = $this->customlib->dateTimeformat($date_post);
        if (empty($ts)) {
            return false;
        }
        return ($ts >= time());
    }
}
