<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Coursetag_model extends MY_Model {

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
            $this->db->update('online_course_tag', $data);
            $message = UPDATE_RECORD_CONSTANT . " On online_course_tag id " . $data['id'];
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
            $this->db->insert('online_course_tag', $data);

            $insert_id = $this->db->insert_id();
            $message = INSERT_RECORD_CONSTANT . " On online_course_tag id " . $insert_id;
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
    public function gettags($id = null) {
        $this->db->select('*');
        $this->db->from('online_course_tag');
        if(isset($id)){
            $this->db->where('online_course_tag.id',$id);    
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
        $this->db->delete('online_course_tag');
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
        $this->db->where('tag_name', $category);
        $query = $this->db->get('online_course_tag');
        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            return false;
        }
    }

}