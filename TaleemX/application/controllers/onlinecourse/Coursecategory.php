<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Coursecategory extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('course_model', 'coursesection_model', 'courselesson_model', 'studentcourse_model', 'coursequiz_model', 'course_payment_model', 'courseofflinepayment_model', 'coursereport_model', 'coursecategory_model'));
        $this->auth->addonchk('ssoclc', site_url('onlinecourse/course/setting'));
        $this->load->model('course_model');
        $this->load->library('aws3');
        $this->load->helper('course');
    }

    public function categoryadd()
    {
        if (!$this->rbac->hasPrivilege('course_category', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'onlinecourse');
        $this->session->set_userdata('sub_menu', 'onlinecourse/coursecategory/categoryadd');

        $this->form_validation->set_rules(
            'category_name', $this->lang->line('category_name'), array(
                'required',
                array('category_exists', array($this->coursecategory_model, 'category_exists')),
            )
        );

        if ($this->form_validation->run() == false) {

        } else {
            $category = array(
                'category_name' => $this->input->post('category_name'),
            );
            $this->coursecategory_model->add($category);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('onlinecourse/coursecategory/categoryadd');
        }

        $data['category_result'] = $this->coursecategory_model->getcategory();

        $this->load->view('layout/header', $data);
        $this->load->view('onlinecourse/coursecategory/categoryadd', $data);
        $this->load->view('layout/footer', $data);
    }

    public function categoryedit($id = null)
    {
        if (!$this->rbac->hasPrivilege('course_category', 'can_edit')) {
            access_denied();
        }

        $this->session->set_userdata('top_menu', 'onlinecourse');
        $this->session->set_userdata('sub_menu', 'onlinecourse/coursecategory/categoryadd');

        $this->form_validation->set_rules(
            'category_name', $this->lang->line('category_name'), array(
                'required',
                array('category_exists', array($this->coursecategory_model, 'category_exists')),
            )
        );

        if ($this->form_validation->run() == false) {

        } else {

            $category = array(
                'category_name' => $this->input->post('category_name'),
                'id'            => $this->input->post('id'),
            );

            $this->coursecategory_model->add($category);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('onlinecourse/coursecategory/categoryadd');
        }

        $data['result']          = $this->coursecategory_model->getcategory($id);
        $data['category_result'] = $this->coursecategory_model->getcategory();

        $this->load->view('layout/header', $data);
        $this->load->view('onlinecourse/coursecategory/categoryedit', $data);
        $this->load->view('layout/footer', $data);
    }

    public function categorydelete($id)
    {
        if (!$this->rbac->hasPrivilege('course_category', 'can_delete')) {
            access_denied();
        }
        $category_result = $this->coursecategory_model->remove($id);
        redirect('onlinecourse/coursecategory/categoryadd');
    }

}
