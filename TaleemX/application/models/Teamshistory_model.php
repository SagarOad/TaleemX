<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Teamshistory_model extends MY_Model
{
    protected $current_session;

    public function __construct()
    {
        parent::__construct();
        $this->current_session = $this->setting_model->getCurrentSession();
    }

    public function updatehistory($data, $type)
    {
        $this->db->trans_start();
        $this->db->trans_strict(false);
        $this->db->where('teams_id', $data['teams_id']);
        if ($type == "student") {
            $this->db->where('student_id', $data['student_id']);
        } elseif ($type == "staff") {
            $this->db->where('staff_id', $data['staff_id']);
        }

        $q = $this->db->get('teams_history');
        if ($q->num_rows() > 0) {
            $row               = $q->row();
            $data['total_hit'] = $row->total_hit + 1;
            $this->db->where('id', $row->id);
            $this->db->update('teams_history', $data);
            $message   = UPDATE_RECORD_CONSTANT . " On teams history id " . $row->id;
            $action    = "Update";
            $record_id = $row->id;
            $this->log($message, $record_id, $action);
        } else {
            $this->db->insert('teams_history', $data);
            $id        = $this->db->insert_id();
            $message   = INSERT_RECORD_CONSTANT . " On teams history id " . $id;
            $action    = "Insert";
            $record_id = $id;
            $this->log($message, $record_id, $action);
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }

        return true;
    }
}
