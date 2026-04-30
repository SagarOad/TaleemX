<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Coursequiz_model extends MY_Model {

    public function __construct() {
        parent::__construct();
    }

    /*
    This is used to add or edit quiz
    */    
	public function add($data)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        if (isset($data['id'])) {
            $this->db->where('id', $data['id']);
            $this->db->update('online_course_quiz', $data);
            $message   = UPDATE_RECORD_CONSTANT . " On course quiz id " . $data['id'];
            $action    = "Update";
            $record_id = $id = $data['id'];
            $this->log($message, $record_id, $action);
        } else {
            $this->db->insert('online_course_quiz', $data);
            $id        = $this->db->insert_id();
            $message   = INSERT_RECORD_CONSTANT . " On course quiz id " . $id;
            $action    = "Insert";
            $record_id = $id;
            $this->log($message, $record_id, $action);           
        }
        //======================Code End==============================
        $this->db->trans_complete(); # Completing transaction

        if ($this->db->trans_status() === false) {
            # Something went wrong.
            $this->db->trans_rollback();
            return false;
        } else {
            return $id;
        }
    }

    /*
    This is used to addor edit question
    */
	public function addquizquestion($data)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        if (isset($data['id']) && $data['id'] != '') {
            $this->db->where('id', $data['id']);
            $this->db->update('course_quiz_question', $data);
            $message   = UPDATE_RECORD_CONSTANT . " On course quiz question " . $data['id'];
            $action    = "Update";
            $record_id = $id = $data['id'];
            $this->log($message, $record_id, $action);
        } else {
            $this->db->insert('course_quiz_question', $data);
            $id        = $this->db->insert_id();
            $message   = INSERT_RECORD_CONSTANT . " On course quiz question " . $id;
            $action    = "Insert";
            $record_id = $id;
            $this->log($message, $record_id, $action);           
        }
        //======================Code End==============================
        $this->db->trans_complete(); # Completing transaction
        if ($this->db->trans_status() === false) {
            # Something went wrong.
            $this->db->trans_rollback();
            return false;
        } else {
            return $id;
        }
    }

    /*
    This is used to get single quiz
    */
    public function getsinglequiz($id) {
        $this->db->select('*');
        $this->db->from('online_course_quiz');
        $this->db->where('id',$id);
        $query = $this->db->get();
        return $query->row();
    }

    /*
    This is used to get single quiz by section
    */
    public function getquizbysection($sectionId) {
        $this->db->select('*');
        $this->db->from('online_course_quiz');
        $this->db->where('course_section_id',$sectionId);
        $query = $this->db->get();
        return $query->result_array();
    }

    /*
    This is used to delete quiz
    */
	public function remove($id)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
		$this->db->where("course_quiz_id", $id)->delete("course_quiz_question");
        $this->db->where('id', $id);
        $this->db->delete('online_course_quiz');
        $message   = DELETE_RECORD_CONSTANT . " On online course quiz id " . $id;
        $action    = "Delete";
        $record_id = $id;
        $this->log($message, $record_id, $action);
        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            return false;
        } else {
            return true;
        }
    }
	
    /*
    This is used to get quiz question list by quiz
    */
    public function questionlist($quizID) {
        $this->db->select('*');
        $this->db->from('course_quiz_question');
        $this->db->where('course_quiz_id',$quizID);
        $query = $this->db->get();
        return $query->result_array();
    }

    /*
    This is used to delete quiz question by quiz id
    */    
	public function removequizquestion($id)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        $this->db->where('course_quiz_id', $id);
        $this->db->delete('course_quiz_question');
        $message   = DELETE_RECORD_CONSTANT . " On course quiz question where quiz id " . $id;
        $action    = "Delete";
        $record_id = $id;
        $this->log($message, $record_id, $action);
        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            return false;
        } else {
            return true;
        }
    }

    /*
    This is used to delete quiz question by quiz id
    */
	public function removequestion($id)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        $this->db->where('id', $id);
        $this->db->delete('course_quiz_question');
        $message   = DELETE_RECORD_CONSTANT . " On course quiz question id " . $id;
        $action    = "Delete";
        $record_id = $id;
        $this->log($message, $record_id, $action);
        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            return false;
        } else {
            return true;
        }
    }
    /*
    This is used to get option count
    */
    public function optioncount($questionID,$quizID) {
        $this->db->select('option_1,option_2,option_3,option_4,option_5');
        $this->db->from('course_quiz_question');
        $this->db->where('id',$quizID);
        $this->db->where('course_quiz_id',$questionID);
        $query = $this->db->get();
        return $query->result_array();
    }
}