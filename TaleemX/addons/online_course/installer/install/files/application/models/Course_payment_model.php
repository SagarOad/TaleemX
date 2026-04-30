<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Course_payment_model extends MY_Model {

    public function __construct() {
        parent::__construct();
    }

    /*
    This is used to add new record for payment
    */
    public function add($data) {
		$this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        $this->db->insert('online_course_payment', $data);
		$id        = $this->db->insert_id();
        $message   = INSERT_RECORD_CONSTANT . " On online course payment id" . $id;
        $action    = "Insert";
        $this->log($message, $id, $action);
		$this->db->trans_complete(); # Completing transaction
        if ($this->db->trans_status() === false) {
            # Something went wrong.
            $this->db->trans_rollback();
            return false;
        } else {
            return $id;
        }
    }

    public function add_processingpayment($data){
       $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        $this->db->insert('online_course_processing_payment', $data);
        $id        = $this->db->insert_id();
        $message   = INSERT_RECORD_CONSTANT . " On online course payment id" . $id;
        $action    = "Insert";
        $this->log($message, $id, $action);
        $this->db->trans_complete(); # Completing transaction
        if ($this->db->trans_status() === false) {
            # Something went wrong.
            $this->db->trans_rollback();
            return false;
        } else {
            return $id;
        } 
    }

    public function check_payment_status($courseid,$studentid,$guest_id,$gateway) {
       $this->db->select('*')->from('online_course_processing_payment')->join('gateway_ins','gateway_ins.id=online_course_processing_payment.gateway_ins_id')->where('online_course_processing_payment.online_courses_id',$courseid)->where('gateway_ins.gateway_name',$gateway);
       if(!empty($guest_id) && !empty($studentid)){
        $this->db->where('(online_course_processing_payment.student_id ='.$studentid.' or online_course_processing_payment.guest_id='.$guest_id.')');
       }elseif(!empty($studentid)){
        $this->db->where('online_course_processing_payment.student_id', $studentid);
       }
       elseif(!empty($guest_id)){
        $this->db->where('online_course_processing_payment.guest_id', $guest_id);
       }
       $query= $this->db->get()->row_array();
        return $query;
    }

    public function check_gestpayment_status($courseid,$guest_id,$gateway) {
       $query= $this->db->select('*')->from('online_course_processing_payment')->join('gateway_ins','gateway_ins.id=online_course_processing_payment.gateway_ins_id')->where('online_course_processing_payment.online_courses_id',$courseid)->where('gateway_ins.gateway_name',$gateway)->where('online_course_processing_payment.guest_id',$guest_id)->get()->row_array();
        return $query;
    }

    public function get_processingpayment($id) {
       $query= $this->db->select('online_course_processing_payment.*')->from('online_course_processing_payment')->join('students','students.id=online_course_processing_payment.student_id')->join('online_courses','online_courses.id=online_course_processing_payment.online_courses_id')->where('online_course_processing_payment.id',$id)->get()->row_array();
        return $query;
    }

    public function deleteBygateway_ins_id($id){
        
        $this->db->where('gateway_ins_id', $id);
        $this->db->delete('online_course_processing_payment');
    }

    public function deleteByid($id){
        $this->db->where('id', $id);
        $this->db->delete('online_course_processing_payment');
    }
}