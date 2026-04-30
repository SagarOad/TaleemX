<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Thermal_print_model extends MY_Model
{

    public function __construct()
    {
        parent::__construct();
        
    }
	
    public function get()
    {
        $this->db->select()->from('thermal_print_settings');        
        $query = $this->db->get();        
        $result = $query->row_array();
        return $result;
    } 
	
    public function add($data)
    {
        if (isset($data['id'])) {
            $this->db->where('id', $data['id']);
            $this->db->update('thermal_print_settings', $data);
        } else {
            $this->db->insert('thermal_print_settings', $data);
        }
    }

}
