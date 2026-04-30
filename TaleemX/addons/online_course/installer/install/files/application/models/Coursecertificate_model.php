<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Coursecertificate_model extends MY_Model
{

    protected $current_session;
    public function __construct()
    {
        parent::__construct();
        $this->current_session = $this->setting_model->getCurrentSession();
    }

    public function addcertificate($data)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        if (isset($data['id']) && $data['id']>0) {
            $this->db->where('id', $data['id']);
            $this->db->update('online_course_certificate_template', $data);
            $message   = UPDATE_RECORD_CONSTANT . " On  online_course_certificate_template id " . $data['id'];
            $action    = "Update";
            $record_id = $data['id'];
            $this->log($message, $record_id, $action);
            //======================Code End==============================

            $this->db->trans_complete(); # Completing transaction
            /* Optional */

            if ($this->db->trans_status() === false) {
                # Something went wrong.
                $this->db->trans_rollback();
                return false;
            } else {
                //return $record_id;
            }
        } else {
            $this->db->insert('online_course_certificate_template', $data);
            $insert_id = $this->db->insert_id();
            $message   = INSERT_RECORD_CONSTANT . " On online_course_certificate_template id " . $insert_id;
            $action    = "Insert";
            $record_id = $insert_id;
            $this->log($message, $record_id, $action);
            //======================Code End==============================

            $this->db->trans_complete(); # Completing transaction
            /* Optional */

            if ($this->db->trans_status() === false) {
                # Something went wrong.
                $this->db->trans_rollback();
                return false;
            } else {
                //return $return_value;
            }
            return $insert_id;
        }
        return $record_id;
    }

    public function get($id=null)
    {
        if(isset($id) && $id!=""){
            $this->db->where('id', $id);
        }
        $this->db->select('*');
        $this->db->from('online_course_certificate_template');
        // $this->db->where('status = 1');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function certificateList()
    {
        $this->db->select('*');
        $this->db->from('online_course_certificate_template');
        // $this->db->where('status = 1');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function remove($id)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        $this->db->where('id', $id);
        $this->db->delete('online_course_certificate_template');
        $message   = DELETE_RECORD_CONSTANT . " On online_course_certificate_template id " . $id;
        $action    = "Delete";
        $record_id = $id;
        $this->log($message, $record_id, $action);
        //======================Code End==============================
        $this->db->trans_complete(); # Completing transaction
        /* Optional */
        if ($this->db->trans_status() === false) {
            # Something went wrong.
            $this->db->trans_rollback();
            return false;
        } else {
            //return $return_value;
        }
    }

    public function getstudentcertificate()
    {
        $this->db->select('*');
        $this->db->from('certificates');
        $this->db->where('created_for = 2');
        $query = $this->db->get();
        return $query->result();
    }

    public function certifiatebyid($id)
    {
        $this->db->select('*');
        $this->db->from('certificates');
        $this->db->where('id', $id);
        $query = $this->db->get();
        return $query->row();
    }

}
