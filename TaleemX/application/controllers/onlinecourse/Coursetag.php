<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Coursetag extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->auth->addonchk('ssoclc', site_url('onlinecourse/course/setting'));
        $this->load->model('coursetag_model');
        $this->load->model('coursecategory_model');
        $this->load->library('aws3');
        $this->load->helper('course');
    }

    public function index(){       
      	$this->form_validation->set_rules(
                'tag_name', $this->lang->line('tag_name'), array('required',array('category_exists', array($this->coursecategory_model, 'category_exists')),
            )
        );

        if ($this->form_validation->run() == false) {

        } else {
            $category = array(
                'tag_name' => $this->input->post('tag_name'),
            );

            $this->coursetag_model->add($category);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('onlinecourse/coursetag/index');
        }
        $data['result'] = $this->coursetag_model->gettags();
        $this->load->view('layout/header', $data);
        $this->load->view('onlinecourse/coursetag/index', $data);
        $this->load->view('layout/footer', $data);
    }

    public function edit_tag($id = null){      
        $this->form_validation->set_rules(
                'tag_name', $this->lang->line('tag_name'), array(
                'required',
                array('category_exists', array($this->coursetag_model, 'category_exists')),
            )
        );

        if ($this->form_validation->run() == false) {

        } else {
            $category = array(
                'tag_name'      => $this->input->post('tag_name'),
                'id'            => $this->input->post('id'),
            );

            $this->coursetag_model->add($category);
            $this->session->set_flashdata('msg', '<div class="alert alert-success text-left">' . $this->lang->line('success_message') . '</div>');
            redirect('onlinecourse/coursetag/index');
        }

        $data['result']  = $this->coursetag_model->gettags($id);
        $data['result_data'] = $this->coursetag_model->gettags();
        $this->load->view('layout/header', $data);
        $this->load->view('onlinecourse/coursetag/edit_tag', $data);
        $this->load->view('layout/footer', $data);
    }

    public function delete_tag($id){
        $result = $this->coursetag_model->remove($id);
        redirect('onlinecourse/coursetag/index');
    }

 }