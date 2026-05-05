<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Enquiry extends Admin_Controller
{
    private function postScalar($key, $default = '')
    {
        $value = $this->input->post($key);
        if (is_array($value)) {
            $value = reset($value);
        }
        if ($value === null) {
            return $default;
        }
        return trim((string) $value);
    }


    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model("enquiry_model");
        $this->config->load("payroll");
        $this->enquiry_status = $this->config->item('enquiry_status');
        $this->form_validation->set_message(
            'follow_up_on_or_after_enquiry_start',
            $this->lang->line('follow_up_must_be_on_or_after_enquiry_date')
        );
    }

    /**
     * Next follow-up must be on or after the enquiry (registration) date.
     * Uses POST date for new/edit enquiry; uses stored enquiry date when enquiry_id is posted (follow-up modal).
     */
    public function follow_up_on_or_after_enquiry_start($follow_up_str)
    {
        if (is_array($follow_up_str)) {
            $follow_up_str = reset($follow_up_str);
        }
        $follow_up_str = trim((string) $follow_up_str);
        if ($follow_up_str === '') {
            return true;
        }

        $followYmd = date('Y-m-d', $this->customlib->datetostrtotime($follow_up_str));

        $enquiry_id = $this->postScalar('enquiry_id');
        if (!empty($enquiry_id)) {
            $status = $this->postScalar('enquiry_status');
            if ($status === '') {
                $status = 'active';
            }
            $row = $this->enquiry_model->getenquiry_list($enquiry_id, $status);
            if (empty($row) || empty($row['date']) || $row['date'] === '0000-00-00') {
                return true;
            }
            $enquiryDate = is_array($row['date']) ? reset($row['date']) : $row['date'];
            $enquiryYmd = date('Y-m-d', strtotime((string) $enquiryDate));

            return strtotime($followYmd) >= strtotime($enquiryYmd);
        }

        $date_post = $this->postScalar('date');
        if ($date_post === '') {
            return true;
        }
        $enquiryYmd = date('Y-m-d', $this->customlib->datetostrtotime($date_post));

        return strtotime($followYmd) >= strtotime($enquiryYmd);
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('admission_enquiry', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'front_office');
        $this->session->set_userdata('sub_menu', 'admin/enquiry');
        $data['class_list']     = $this->class_model->get();
        $data["selected_class"] = "";
        $data["source_select"]  = "";
        $data["status"]         = "active";
        $data['stff_list']      = $this->staff_model->get();
        $this->form_validation->set_rules('from_date', $this->lang->line('enquiry_from_date'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('to_date', $this->lang->line('enquiry_to_date'), 'trim|required|xss_clean');

        if ($this->form_validation->run() == true) {
            $class                  = $this->input->post("class");
            $source                 = $this->input->post("source");
            $status                 = $this->input->post("status");
            $date_from              = date("Y-m-d", $this->customlib->datetostrtotime($this->postScalar("from_date")));
            $date_to                = date("Y-m-d", $this->customlib->datetostrtotime($this->postScalar("to_date")));
            $data["source_select"]  = $source;
            $data["status"]         = $status;
            $data["selected_class"] = $class;
            $enquiry_list           = $this->enquiry_model->searchEnquiry($class, $source, $date_from, $date_to, $status);
        } else {
            $enquiry_list = $this->enquiry_model->getenquiry_list();
        }
        
        foreach ($enquiry_list as $key => $value) {
            $follow_up                          = $this->enquiry_model->getFollowByEnquiry($value["id"]);
            $enquiry_list[$key]["followupdate"] = isset($follow_up["date"]) ? $follow_up["date"] : '';
            $enquiry_list[$key]["next_date"]    = isset($follow_up["next_date"]) ? $follow_up["next_date"] : '';
            $enquiry_list[$key]["response"]     = isset($follow_up["response"]) ? $follow_up["response"] : '';
            $enquiry_list[$key]["note"]         = isset($follow_up["note"]) ? $follow_up["note"] : '';
            $enquiry_list[$key]["followup_by"]  = isset($follow_up["followup_by"]) ? $follow_up["followup_by"] : '';
        }
   
        
        $data['enquiry_list']   = $enquiry_list;
        $data['enquiry_status'] = $this->enquiry_status;
        $data['Reference']      = $this->enquiry_model->get_reference();
        $data['sourcelist']     = $this->enquiry_model->getComplaintSource();
        $this->load->view('layout/header');
        $this->load->view('admin/frontoffice/enquiryview', $data);
        $this->load->view('layout/footer');
    }

    public function add()
    {
        if (!$this->rbac->hasPrivilege('admission_enquiry', 'can_add')) {
            access_denied();
        }
        $this->form_validation->set_rules('name', $this->lang->line('name'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('contact', $this->lang->line('phone'), 'trim|required|xss_clean|saudi_phone');
        $this->form_validation->set_rules('source', $this->lang->line('source'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('date', $this->lang->line('date'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('follow_up_date', $this->lang->line('next_follow_up_date'), 'trim|required|xss_clean|callback_follow_up_on_or_after_enquiry_start');
        
        if ($this->form_validation->run() == false) {
            $msg = array(
                'name'    => form_error('name'),
                'contact' => form_error('contact'),
                'source'  => form_error('source'),
                'date'    => form_error('date'),
                'follow_up_date'    => form_error('follow_up_date'),
            );

            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $this->load->helper('saudi_phone');
            saudi_phone_normalize_post_fields(array('contact'));

            $userdata   = $this->customlib->getUserData();
            $created_by = $userdata["id"];

            $enquiry = array(
                'name'           => $this->input->post('name'),
                'contact'        => $this->input->post('contact'),
                'address'        => $this->input->post('address'),
                'reference'      => $this->input->post('reference'),
                'date'           => date('Y-m-d', $this->customlib->datetostrtotime($this->postScalar('date'))),
                'description'    => $this->input->post('description'),
                'follow_up_date' => date('Y-m-d', $this->customlib->datetostrtotime($this->postScalar('follow_up_date'))),
                'note'           => $this->input->post('note'),
                'source'         => $this->input->post('source'),
                'email'          => $this->input->post('email'),
    'assigned'       => IsNullOrEmptyString($this->input->post('assigned')) ? NULL :$this->input->post('assigned'),
                'class_id' => IsNullOrEmptyString($this->input->post('class')) ? NULL :$this->input->post('class'),
                'no_of_child'    => $this->input->post('no_of_child'),
                'status'         => 'active',
                'created_by'     => $created_by,
            );
            $this->enquiry_model->add($enquiry);
            $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
        }
        echo json_encode($array);
    }

    public function delete($id)
    {
        if (!$this->rbac->hasPrivilege('admission_enquiry', 'can_delete')) {
            access_denied();
        }
        if (!empty($id)) {
            $this->enquiry_model->enquiry_delete($id);
            $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('delete_message'));
        }
        echo json_encode($array);
    }

    public function follow_up($enquiry_id, $status, $created_by)
    {

        if (!$this->rbac->hasPrivilege('follow_up_admission_enquiry', 'can_view')) {
            access_denied();
        }
        $data['id']              = $enquiry_id;
        $data['enquiry_data']    = $this->enquiry_model->getenquiry_list($enquiry_id, $status);        
         
        if(!empty($data['enquiry_data']['assigned'])){
            $data['assigned_staff'] = $this->staff_model->get($data['enquiry_data']['assigned']);        
        }else{
            $data['assigned_staff'] = '';  
        }

        $data['next_date']       = $this->enquiry_model->next_follow_up_date($enquiry_id);
        $data['created_by']      = $this->staff_model->get($created_by);
        $data['enquiry_status']  = $this->enquiry_status;
        $userdata                = $this->customlib->getUserData();
        $data['login_staff_id']  = $userdata["id"];
        $getStaffRole            = $this->customlib->getStaffRole();
        $staffrole               = json_decode($getStaffRole);
        $data['staff_role']      = $staffrole->id;
         
        $data['superadmin_rest'] = $this->session->userdata['admin']['superadmin_restriction']; 
        $this->load->view('admin/frontoffice/follow_up_modal', $data);
    }

    public function follow_up_insert()
    {
        if (!$this->rbac->hasPrivilege('follow_up_admission_enquiry', 'can_add')) {
            access_denied();
        }

        $this->form_validation->set_rules('response', $this->lang->line('response'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('date', $this->lang->line('follow_up_date'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('follow_up_date', $this->lang->line('next_follow_up_date'), 'trim|required|xss_clean|callback_follow_up_on_or_after_enquiry_start');
        if ($this->form_validation->run() == false) {
            $msg = array(
                'response'       => form_error('response'),
                'follow_up_date' => form_error('follow_up_date'),
                'date'           => form_error('date'),
            );

            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $staff_id = $this->customlib->getStaffID();

            $follow_up = array(
                'date'        => date('Y-m-d', $this->customlib->datetostrtotime($this->postScalar('date'))),
                'next_date'   => date('Y-m-d', $this->customlib->datetostrtotime($this->postScalar('follow_up_date'))),
                'response'    => $this->input->post('response'),
                'note'        => $this->input->post('note'),
                'followup_by' => $staff_id,
                'enquiry_id'  => $this->input->post('enquiry_id'),
            );
            $this->enquiry_model->add_follow_up($follow_up);
            $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
        }

        echo json_encode($array);
    }

    public function follow_up_list($id)
    {
        $data['id']             = $id;
        $data['follow_up_list'] = $this->enquiry_model->getfollow_up_list($id);
        $this->load->view('admin/frontoffice/followuplist', $data);
    }

    public function details($id, $status)
    {
        if (!$this->rbac->hasPrivilege('admission_enquiry', 'can_view')) {
            access_denied();
        }
        $data['source']       = $this->enquiry_model->getComplaintSource();
        $data['enquiry_type'] = $this->enquiry_model->get_enquiry_type();
        $data['Reference']    = $this->enquiry_model->get_reference();        
        $data['class_list']   = $this->enquiry_model->getclasses();        
        $data['enquiry_data'] = $this->enquiry_model->getenquiry_list($id, $status);
        $data['stff_list']    = $this->staff_model->get();
        $this->load->view('admin/frontoffice/enquiryeditmodalview', $data);
    }

    public function editpost($id)
    {
        if (!$this->rbac->hasPrivilege('admission_enquiry', 'can_edit')) {
            access_denied();
        }
        $this->form_validation->set_rules('name', $this->lang->line('name'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('contact', $this->lang->line('phone'), 'trim|required|xss_clean|saudi_phone');
        $this->form_validation->set_rules('source', $this->lang->line('source'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('date', $this->lang->line('date'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('follow_up_date', $this->lang->line('next_follow_up_date'), 'trim|required|xss_clean|callback_follow_up_on_or_after_enquiry_start');
        
        if ($this->form_validation->run() == false) {
            $msg = array(
                'name'    => form_error('name'),
                'contact' => form_error('contact'),
                'source'  => form_error('source'),
                'date'    => form_error('date'),
                'follow_up_date'    => form_error('follow_up_date'),
            );

            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $this->load->helper('saudi_phone');
            saudi_phone_normalize_post_fields(array('contact'));

            $enquiry_update = array(
                'name'           => $this->input->post('name'),
                'contact'        => $this->input->post('contact'),
                'address'        => $this->input->post('address'),
                'reference'      => $this->input->post('reference'),
                'date'           => date('Y-m-d', $this->customlib->datetostrtotime($this->postScalar('date'))),
                'description'    => $this->input->post('description'),
                'follow_up_date' => date('Y-m-d', $this->customlib->datetostrtotime($this->postScalar('follow_up_date'))),
                'note'           => $this->input->post('note'),
                'source'         => $this->input->post('source'),
                'email'          => $this->input->post('email'),
                'assigned'       => empty2null($this->input->post('assigned')),
                'class_id'       => empty2null($this->input->post('class')),
                'no_of_child'    => $this->input->post('no_of_child'),
            );
            $this->enquiry_model->enquiry_update($id, $enquiry_update);
            $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('update_message'));
        }
        echo json_encode($array);
    }

    public function follow_up_delete($follow_up_id, $enquiry_id)
    {
        if (!$this->rbac->hasPrivilege('follow_up_admission_enquiry', 'can_delete')) {
            access_denied();
        }
        $this->enquiry_model->delete_follow_up($follow_up_id);
        $data['id']             = $enquiry_id;
        $data['follow_up_list'] = $this->enquiry_model->getfollow_up_list($enquiry_id);
        $this->load->view('admin/frontoffice/followuplist', $data);
    }

    public function check_default($post_string)
    {
        return $post_string == '' ? false : true;
    }

    public function change_status()
    {
        $id     = $this->input->post("id");
        $status = $this->input->post("status");
        if (!empty($id)) {
            $data = array('id' => $id, 'status' => $status);
            $this->enquiry_model->changeStatus($data);
            $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
        } else {
            $array = array('status' => 'fail', 'error' => '', 'message' => $this->lang->line('update_message'));
        }

        echo json_encode($array);
    }

    public function check_number()
    {
        $phone_number = $this->input->post("phone_number");
        $check_number = $this->enquiry_model->check_number($phone_number);
        if (!empty($check_number)) {
            $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('number_is_already_exists_and_name_is') . '  ' . $check_number['name']);
        } else {
            $array = array('status' => 'fail', 'error' => '', 'message' => '');
        }
        echo json_encode($array);
    }

}
