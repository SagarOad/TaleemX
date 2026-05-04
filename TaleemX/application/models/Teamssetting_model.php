<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Teamssetting_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get()
    {
        $this->db->select('*');
        $this->db->from('teams_settings');
        $this->db->order_by('teams_settings.id');
        $query = $this->db->get();
        return $query->row();
    }

    public function add($data)
    {
        $this->db->trans_begin();
        $q = $this->db->get('teams_settings');
        if ($q->num_rows() > 0) {
            $result = $q->row();
            $this->db->where('id', $result->id);
            $this->db->update('teams_settings', $data);
            $message   = UPDATE_RECORD_CONSTANT . " On teams settings id " . $result->id;
            $action    = "Update";
            $record_id = $result->id;
            $this->log($message, $record_id, $action);
        } else {
            $this->db->insert('teams_settings', $data);
            $id        = $this->db->insert_id();
            $message   = INSERT_RECORD_CONSTANT . " On teams settings id " . $id;
            $action    = "Insert";
            $record_id = $id;
            $this->log($message, $record_id, $action);
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
        } else {
            $this->db->trans_commit();
        }
    }
}
