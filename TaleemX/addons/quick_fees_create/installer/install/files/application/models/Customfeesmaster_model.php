<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Customfeesmaster_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    //**** fees master ****//
    public function getStudentfeeTypeByGroup($student_session_id){
        $this->db->select('fee_groups_feetype.*,feetype.type,feetype.code');
        $this->db->from('fee_groups_feetype');
        $this->db->join('feetype', 'feetype.id=fee_groups_feetype.feetype_id');
        $this->db->where('feetype.student_session_id', $student_session_id);
        $this->db->order_by('fee_groups_feetype.id', 'asc');
        $query = $this->db->get();
        return $query->result();
    }

    public function searchstudentfeesmaster($fee_groups_id){
        $this->db->select('fee_groups.*,fee_session_groups.id as fee_session_groups_id');
        $this->db->from('fee_groups');          
        $this->db->join('fee_session_groups', 'fee_groups.id = fee_session_groups.fee_groups_id');
        $this->db->where('fee_groups.nature', 'custom');         
        $this->db->where('fee_groups.id',$fee_groups_id);       
        $query = $this->db->get();
        $result = $query->row();        
        return $result;
    }

    public function getCustomFeesTypeByStudentSession($student_session_id){
        $this->db->select()->from('feetype');
        $this->db->where('is_system', 0);
        $this->db->where('nature', 'custom');        
        $this->db->where('student_session_id', $student_session_id);        
        $query = $this->db->get();        
        return $query->result_array();         
    }

    public function unassignfees($fee_session_groups_id, $fee_groups_id, $student_session_id){

        $this->db->where('id', $fee_groups_id);        
        $this->db->delete('fee_groups');

        $this->db->where('id', $fee_session_groups_id);        
        $this->db->delete('fee_session_groups');        
        
        $this->db->where('student_session_id', $student_session_id);        
        $this->db->delete('feetype');   
    }




}