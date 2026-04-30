<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Gauth_model extends MY_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    /*
    This is used to get data from google authenticator table
    */
    public function get()
    {
        $this->db->select('*');
        $this->db->from('google_authenticator');
        $this->db->order_by('google_authenticator.id');
        $query = $this->db->get();
        return $query->row();
    }

     /*
    This is used to get data from google authenticator table
    */
    public function is_enable()
    {
        $this->db->select('*');
        $this->db->from('google_authenticator');
        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            $result = $query->row();
            return $result->use_authenticator;
        }
        return false;

    }
    
    /*
    This is used to get disabled/enabled two factor authentication
    */
    public function add($data)
    {
        $this->db->trans_begin();
        $q = $this->db->get('google_authenticator');
        if ($q->num_rows() > 0) {
            $results = $q->row();
            $this->db->where('id', $results->id);
            $this->db->update('google_authenticator', $data);
        } else {
            $this->db->insert('google_authenticator', $data);
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
        } else {
            $this->db->trans_commit();
        }
    }

}
