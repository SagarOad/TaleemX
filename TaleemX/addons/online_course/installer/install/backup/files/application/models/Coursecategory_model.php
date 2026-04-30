<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Coursecategory_model extends MY_Model {

    public function __construct() {
        parent::__construct();
    }
 
    /*
    This is used to add or edit course
    */
	function add($data) {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        if (isset($data['id']) && $data['id'] != '') {
            $this->db->where('id', $data['id']);
            $this->db->update('course_category', $data);
            $message = UPDATE_RECORD_CONSTANT . " On course category id " . $data['id'];
            $action = "Update";
            $record_id = $data['id'];
            $this->log($message, $record_id, $action);
            //======================Code End==============================
            $this->db->trans_complete(); # Completing transaction
            if ($this->db->trans_status() === false) {
                # Something went wrong.
                $this->db->trans_rollback();
                return false;
            } else {
                return $record_id;
            }
        } else {
            $this->db->insert('course_category', $data);
            $insert_id = $this->db->insert_id();
            $message = INSERT_RECORD_CONSTANT . " On course category id " . $insert_id;
            $action = "Insert";
            $record_id = $insert_id;
            $this->log($message, $record_id, $action);
            //======================Code End==============================
            $this->db->trans_complete(); # Completing transaction
          
            if ($this->db->trans_status() === false) {
                # Something went wrong.
                $this->db->trans_rollback();
                return false;
            } else {
            }
            return $insert_id;
        }
    }   

    /*
    This is used to getting lesson by section id
    */
    public function getcategory($id = null) {
        $this->db->select('*');
        $this->db->from('course_category');
        if(isset($id)){
            $this->db->where('course_category.id',$id);    
        }
        $query = $this->db->get();
        return $query->result_array();
    }  

    /*
    This is used to delete class section list
    */
    public function remove($id)
    {
        $this->db->where('id',$id);         
        $this->db->delete('course_category');
    }
    
    public function category_exists($str) {
         
        $category = $this->security->xss_clean($str);
        $res = $this->check_data_exists($category);

        if ($res) {
            $id = $this->input->post('id');
            if (isset($id)) {
                if ($res->id == $id) {
                    return true;
                }
            }
            $this->form_validation->set_message('category_exists', 'Record already exists');
            return false;
        } else {
            return true;
        }
    }
    
    public function check_data_exists($category) {
        $this->db->where('category_name', $category);
        $query = $this->db->get('course_category');
        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            return false;
        }
    }

    public function getcoursebycategory($categoryid){
        $this->db->select('online_courses.id');
        $this->db->from('online_courses');
        $this->db->where('category_id', $categoryid);
        $this->db->where("online_courses.front_side_visibility",'yes');
        $this->db->where("online_courses.status",'1');
        $result = $this->db->get();
        return $result->result_array();
    }
    
    public function getcategorywithcoursecount() {
        $this->db->select('course_category.*,count(online_courses.id) as categorycount');
        $this->db->join('online_courses','online_courses.category_id = course_category.id');        
        $this->db->from('course_category');  
        $this->db->where("online_courses.front_side_visibility",'yes');
        $this->db->where("online_courses.status",'1');
        $this->db->group_by('course_category.id');       
        $query = $this->db->get();
        return $query->result_array();
    }  

}