<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Gauthuser_model extends MY_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    public function get()
    {
        $this->db->select('*');
        $this->db->from('user_google_authenticate_codes');
        $this->db->order_by('user_google_authenticate_codes.id');
        $query = $this->db->get();
        return $query->row();
    }

    public function add($data)
    {
        if (isset($data['id'])) {
            $this->db->where('id', $data['id']);
            $this->db->update('user_google_authenticate_codes', $data);
        } else {
            $this->db->insert('user_google_authenticate_codes', $data);
        }
    }

    public function getByUser($type, $user)
    {
        $this->db->select('*');
        $this->db->from('user_google_authenticate_codes');
        $this->db->order_by('user_google_authenticate_codes.id');
        if ($type == "staff") {
            $this->db->where('staff_id', $user);
        } elseif ($type == "user") {
            $this->db->where('user_id', $user);
        } elseif ($type == "guest") {
            $this->db->where('guest_id', $user);
        }

        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->row();

        }
        return false;
    }

    public function check_user_exists($type, $user)
    {
        $this->db->select('*');
        $this->db->from('user_google_authenticate_codes');
        $this->db->order_by('user_google_authenticate_codes.id');
        if ($type == "staff") {
            $this->db->where('staff_id', $user);
        } elseif ($type == "user") {
            $this->db->where('user_id', $user);
        }elseif ($type == "guest") {
            $this->db->where('guest_id', $user);
        }

        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return true;

        }
        return false;
    }

    public function deleteUser($type, $user)
    {
        if ($type == "staff") {
            $this->db->where('staff_id', $user);
        } elseif ($type == "guest") {
            $this->db->where('guest_id', $user);
        } else {
            $this->db->where('user_id', $user);
        }
        $this->db->delete('user_google_authenticate_codes');
    }

}
