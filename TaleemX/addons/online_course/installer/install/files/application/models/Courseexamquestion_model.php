<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Courseexamquestion_model extends MY_Model { 

    public function __construct() {
        parent::__construct();
    }

    public function add($data){
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        if (isset($data['id'])) {
            $this->db->where('id', $data['id']);
            $this->db->update('online_course_exam_question', $data);
            $message   = UPDATE_RECORD_CONSTANT . " On  online_course_exam_question id " . $data['id'];
            $action    = "Update";
            $record_id = $data['id'];
            $this->log($message, $record_id, $action);
            //======================Code End==============================
            $this->db->trans_complete(); # Completing transaction
            if ($this->db->trans_status() === false) {
                # Something went wrong.
                $this->db->trans_rollback();
                return false;
            } else {
            }
        } else {
            $this->db->insert('online_course_exam_question', $data);
            $id        = $this->db->insert_id();
            $message   = INSERT_RECORD_CONSTANT . " On  online_course_exam_question id " . $id;
            $action    = "Insert";
            $record_id = $id;
            $this->log($message, $record_id, $action);
            //======================Code End==============================
            $this->db->trans_complete(); # Completing transaction
            if ($this->db->trans_status() === false) {
                # Something went wrong.
                $this->db->trans_rollback();
                return false;
            } else {
            }
            return $id;
        }
    }

    public function getAllRecord($question_type,$question_level,$created_by,$question_tag){

        if(!empty($question_type)){
            $this->datatables->where('questions.question_type', $question_type);
        }
        if(!empty($question_level)){
            $this->datatables->where('questions.level', $question_level);
        }
        if(!empty($created_by)){
            $this->datatables->where('questions.staff_id', $created_by);
        }
        if(!empty($question_tag)){
            $this->datatables->where('questions.question_tag', $question_tag);
        }

        $this->datatables->select('questions.*,staff.name as staff_name,staff.surname as staff_surname,staff.employee_id,staff_roles.role_id as created_role,online_course_tag.tag_name');
        $this->datatables->join('staff', 'staff.id = questions.staff_id', 'left');
        $this->datatables->join('staff_roles', 'staff_roles.staff_id = staff.id', 'left');
        $this->datatables->join('online_course_tag', 'online_course_tag.id = questions.question_tag', 'left');
        $this->datatables->searchable('questions.id,online_course_tag.tag_name,questions.question_type,questions.level,questions.question,staff.surname,staff.employee_id');
        $this->datatables->orderable('questions.id,online_course_tag.tag_name,questions.question_type,questions.level,questions.question,staff.surname,staff.employee_id');
        $this->datatables->from('online_course_exam_question as  questions');
        $this->datatables->sort('questions.id','desc');
        return $this->datatables->generate('json');
    }

    public function get($id = null){
        
        $userdata = $this->customlib->getUserData();
        $role_id  = $userdata["role_id"];
        $this->db->select('questions.*')->from('online_course_exam_question as questions');   
        if ($id != null) {
            $this->db->where('questions.id', $id);
        } else {
            $this->db->order_by('questions.id');
        }
        $query = $this->db->get();
        if ($id != null) {
            return $query->row();
        } else {
            return $query->result();
        }
    }

    public function remove($id){

        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================	
		$this->db->where('question_id ', $id)->delete("online_course_exam_result");		
		$this->db->where('id', $id)->delete("online_course_exam_question");
        $message   = DELETE_RECORD_CONSTANT . " On online_course_exam_question id " . $id;
        $action    = "Delete";
        $record_id = $id;
        $this->log($message, $record_id, $action);
        //======================Code End==============================
        $this->db->trans_complete(); # Completing transaction
        if ($this->db->trans_status() === false) {
            # Something went wrong.
            $this->db->trans_rollback();
            return false;
        } else {
        }
    }

    public function bulkdelete($question_array){
        $this->db->where_in('id', $question_array);
        $this->db->delete('online_course_exam_question');
    }    

    public function getquestioncreatedstaff()
    {     
        $this->db->select('staff.id,staff.name,staff.surname,staff.employee_id');
        $this->db->from('online_course_exam_question as questions');        
        $this->db->join('staff', 'staff.id = questions.staff_id', 'left'); 
        $this->db->join("staff_roles", "staff_roles.staff_id = staff.id", "left");
        $this->db->join("roles", "staff_roles.role_id = roles.id", "left");            
        $this->db->order_by('staff.id');
        $this->db->group_by('staff.name');
        $query = $this->db->get();
        return $query->result();
    }

	public function getByExamID($limit, $start, $where_search,$exam_id){

        $this->db->select('online_course_exam_question.*, IFNULL(online_course_exam_marks.id,0) as `onlineexam_question_id`,IFNULL(online_course_exam_marks.marks,1.00) as `onlineexam_question_marks`,IFNULL(online_course_exam_marks.neg_marks,"0.25") as `onlineexam_question_neg_marks`')->from('online_course_exam_question');
        $this->db->join('online_course_exam_marks', '(online_course_exam_marks.question_id = online_course_exam_question.id AND online_course_exam_marks.online_course_exam_id=' . $this->db->escape($exam_id) . ')', 'LEFT');
        
        if (!empty($where_search)) {
            if (isset($where_search['keyword']) && $where_search['keyword'] != "") {
                $this->db->like('question', $where_search['keyword']);
            }
            if (isset($where_search['question_level']) && $where_search['question_level'] != "") {
                $this->db->where('level', $where_search['question_level']);
            }
            if (isset($where_search['question_type']) && $where_search['question_type'] != "") {
                $this->db->where('question_type', $where_search['question_type']);
            }
            if (isset($where_search['question_tag']) && $where_search['question_tag'] != "") {
                $this->db->where('question_tag', $where_search['question_tag']);
            }
            if ($where_search['is_quiz'] == 1) {
                $this->db->where('online_course_exam_question.question_type !=','descriptive');
            }
        }else{
        $userdata = $this->customlib->getUserData();
        $role_id = $userdata["role_id"];

        if(isset($role_id) && ($userdata["role_id"] == 2) && ($userdata["class_teacher"] == "yes")){
            $carray = array();
            $class_list=array();
       
            if ($userdata["class_teacher"] == 'yes') {
                $carray = $this->teacher_model->get_teacherrestricted_mode($userdata["id"]);
            }

        foreach ($carray as $key => $value) {
          $class_list[]=$value['id'];
        } 
        if(!empty($class_list)){
             $this->db->where_in('online_course_exam_question.class_id',$class_list);
        }
            }
        }
        $this->db->order_by('online_course_exam_question.id');
        $this->db->limit($limit, $start);
        $query = $this->db->get();
        return $query->result();
    }
	
	public function getCountByExamID($where_search){
        $this->db->select('online_course_exam_question.*')->from('online_course_exam_question');
        if (!empty($where_search)) {           
            if (isset($where_search['keyword']) && $where_search['keyword'] != "") {
                $this->db->like('question', $where_search['keyword']);
            }
            if (isset($where_search['question_level']) && $where_search['question_level'] != "") {
                $this->db->where('level', $where_search['question_level']);
            }
            if (isset($where_search['question_type']) && $where_search['question_type'] != "") {
                $this->db->where('question_type', $where_search['question_type']);
            }            
            if ($where_search['is_quiz'] == 1) {
                $this->db->where('online_course_exam_question.question_type !=','descriptive');
            }
        }
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function check_tag_exist($tag_id,$recored_id){    
        $this->db->select('online_course_exam_question.*');
        $this->db->from('online_course_exam_question');   
        $this->db->where('online_course_exam_question.question_tag',$tag_id);
        $this->db->where('online_course_exam_question.id',$recored_id);
        $query = $this->db->get();
        return $query->result();
    }

    public function add_question_bulk($data)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        $this->db->insert_batch('online_course_exam_question', $data);
        $message   = 'Questions ' . IMPORT_RECORD_CONSTANT . " (" . count($data) . ")";
        $action    = "Import";
        $record_id = null;
        $this->log($message, $record_id, $action);
        //======================Code End==============================
        $this->db->trans_complete(); # Completing transaction
        if ($this->db->trans_status() === false) {
            # Something went wrong.
            $this->db->trans_rollback();
            return false;
        } else {
        }

    }  

}