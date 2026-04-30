<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Courseexam_model extends MY_Model {

    public function __construct() {
        parent::__construct();
    }

    /*
    This is used to add or edit quiz
    */    
	public function add($data){
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well

        if (isset($data['id']) && isset($data['id'])>0) {
            $this->db->where('id', $data['id']);
            $this->db->update('online_course_exam', $data);
            $message   = UPDATE_RECORD_CONSTANT . " On  online course exam id " . $data['id'];
            $action    = "Update";
            $record_id = $id = $data['id'];
            $this->log($message, $record_id, $action);
        } else {
            $this->db->insert('online_course_exam', $data);
            $id        = $this->db->insert_id();
            $message   = INSERT_RECORD_CONSTANT . " On  online course exam id " . $id;
            $action    = "Insert";
            $record_id = $id;
            $this->log($message, $record_id, $action);            
        }
        $this->db->trans_complete(); # Completing transaction
        if ($this->db->trans_status() === false) {
            # Something went wrong.
            $this->db->trans_rollback();
            return false;
        } else {
            return $id;
        }
    }
	
	/*This is used to delete quiz*/
    public function remove($id){
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well		
		$this->db->where("exam_id", $id)->delete("online_course_exam_attempts");
		$this->db->where("online_course_exam_id", $id)->delete("online_course_exam_marks");       
        $this->db->where('id', $id)->delete('online_course_exam');		
        $message   = DELETE_RECORD_CONSTANT . " On online_course_exam id " . $id;
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
	
    //***for admin side***//
    public function getexam($id) {
        $this->db->select('*');
        $this->db->from('online_course_exam');
        $this->db->where('id',$id);
        $query = $this->db->get();
        return $query->row();
    }

    public function insertExamQuestion($insert_data){
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        $this->db->where('question_id', $insert_data['question_id']);
        $this->db->where('online_course_exam_id', $insert_data['online_course_exam_id']);
        $q = $this->db->get('online_course_exam_marks');

        if ($q->num_rows() > 0) {
            $result = $q->row();
            $this->db->where('id', $result->id);
            $this->db->delete('online_course_exam_marks');
            $message   = DELETE_RECORD_CONSTANT . " On  onlineexam questions id " . $result->id;
            $action    = "Delete";
            $record_id = $result->id;
            $this->log($message, $record_id, $action);

        } else {
            $this->db->insert('online_course_exam_marks', $insert_data);
            $id        = $this->db->insert_id();
            $message   = INSERT_RECORD_CONSTANT . " On  onlineexam questions id " . $id;
            $action    = "Insert";
            $record_id = $id;
            $this->log($message, $record_id, $action);
        }
        $this->db->trans_complete(); # Completing transaction
        if ($this->db->trans_status() === false) {
            # Something went wrong.
            $this->db->trans_rollback();
            return false;
        } else {
            
        }
    }

    public function getExamQuestions($exam_id = null, $random_type = false){
        $this->db->select('online_course_exam_question.*,online_course_tag.tag_name,
            IFNULL(online_course_exam_marks.id,0) as `exam_question_marks_id`,IFNULL(online_course_exam_marks.marks,1) as `onlineexam_question_marks`,online_course_exam_marks.neg_marks')->from('online_course_exam_marks');
        $this->db->join('online_course_exam_question', 'online_course_exam_marks.question_id= online_course_exam_question.id','left');
        $this->db->join('online_course_tag', 'online_course_tag.id = online_course_exam_question.question_tag','left');
        $this->db->where('online_course_exam_marks.online_course_exam_id',$exam_id);

        if ($random_type) {
            $this->db->order_by('rand()');
        } else {
            $this->db->order_by('online_course_exam_question.id', 'DESC');
        }
        $query = $this->db->get();
        return $query->result();
    }

    public function getExamQuestionSubjects($exam_id){
        $this->db->select('online_course_tag.tag_name,online_course_tag.id as tag_id')->from('online_course_exam_marks');
        $this->db->join('online_course_exam_question', 'online_course_exam_question.id = online_course_exam_marks.question_id','left');
        $this->db->join('online_course_tag', 'online_course_tag.id = online_course_exam_question.question_tag','left');
        $this->db->where('online_course_exam_marks.online_course_exam_id',$exam_id);
        $this->db->group_by('online_course_tag.id');
        $this->db->order_by('online_course_exam_marks.id');
        $query = $this->db->get();
        return $query->result();     
    }
 
	public function getexamdetails($exam_id,$courseid){        
        $this->db->select('online_course_exam.*,(select count(*) from online_course_exam_marks where online_course_exam_marks.online_course_exam_id=online_course_exam.id ) as `total_ques`, (select count(*) from online_course_exam_marks INNER JOIN questions on questions.id=online_course_exam_marks.question_id where online_course_exam_marks.online_course_exam_id=online_course_exam.id and questions.question_type="descriptive" ) as `total_descriptive_ques`,')->from('online_course_exam');       
        $this->db->where('online_course_exam.id', $exam_id);
        $this->db->where('online_course_exam.course_id', $courseid);       
        $query = $this->db->get();        
        return $query->row();
    }

    public function remove_question($id){
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        $this->db->where('id', $id);
        $this->db->delete('online_course_exam_marks');
        $this->db->trans_complete(); # Completing transaction
        /*Optional*/
        if ($this->db->trans_status() === false) {
            # Something went wrong.
            $this->db->trans_rollback();
            return false;
        } else {
            
        }
    }

    public function getquestion_type($exam_id){
        $this->db->select('
            count(case when question_type="descriptive" then 1 end) as total_descriptive,
            count(case when question_type="singlechoice" then 1 end) as total_singlechoice,
            count(case when question_type="true_false" then 1 end) as total_true_false,
            count(case when question_type="multichoice" then 1 end) as total_multichoice,
            count(*) as total_quetions'    
        )->from('online_course_exam_marks');
        $this->db->join('online_course_exam_question', 'online_course_exam_marks.question_id= online_course_exam_question.id','left');
        $this->db->where('online_course_exam_marks.online_course_exam_id',$exam_id);
        $this->db->order_by('online_course_exam_question.id');
        $query = $this->db->get();
        return $query->row();
    }

    public function getStudentAttemts($student_id,$exam){

        $userdata = $this->customlib->getLoggedInUserData();
        if($userdata["role"]=='student'){
            $this->db->where('student_id',$userdata["student_id"]);
        }else if($userdata["role"]=='guest'){
            $this->db->where('guest_id',$userdata["guest_id"]);  
        }
        $this->db->where('exam_id', $exam);
        $total_rows = $this->db->count_all_results('online_course_exam_attempts');
        return $total_rows;
    }

    public function addStudentAttemts($data){
        $this->db->insert('online_course_exam_attempts', $data);
        return $this->db->insert_id();
    }    

    public function addresult($data_insert){
        $this->db->trans_begin();
        if (!empty($data_insert)) {
            $this->db->insert_batch('online_course_exam_result', $data_insert);
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return true;
        }
    }

   public function getsubmitstatus($student_id,$exam_id) {

        $userdata = $this->customlib->getLoggedInUserData();
        $this->db->join('online_course_exam_marks','online_course_exam_marks.id=online_course_exam_result.question_id','left');

        if($userdata["role"]=='student'){
            $this->db->where('online_course_exam_result.student_id',$userdata["student_id"]);
        }else if($userdata["role"]=='guest'){
            $this->db->where('online_course_exam_result.guest_id',$userdata["guest_id"]);  
        }
        $this->db->where('online_course_exam_marks.online_course_exam_id', $exam_id);
        $total_rows = $this->db->count_all_results('online_course_exam_result');
        return $total_rows;
    }

    //****get question of online course exam ****//
    public function getByExamNoLimit($exam_id, $question_type){
        $this->db->select('online_course_exam_question.*,online_course_tag.tag_name,
         IFNULL(online_course_exam_marks.id,0) as `onlineexam_question_id`,
         IFNULL(online_course_exam_marks.marks,1) as `onlineexam_question_marks`')->from('online_course_exam_question');
        $this->db->join('online_course_exam_marks','online_course_exam_marks.question_id=online_course_exam_question.id','left');
        $this->db->join('online_course_tag','online_course_tag.id = online_course_exam_question.question_tag');
        $this->db->where('online_course_exam_question.question_type', $question_type);
        $this->db->where('online_course_exam_marks.online_course_exam_id', $exam_id);
        $this->db->order_by('online_course_exam_question.id');
        $query = $this->db->get();
        return $query->result();
    }
	
    public function getDescriptionRecord_old($per_page, $start, $where,$exam_id){
        $this->db->select('online_course_exam_result.*,students.admission_no,students.mobileno,students.guardian_name,students.guardian_phone,students.firstname,students.middlename,students.lastname,online_course_tag.tag_name,online_course_exam_question.question,online_course_exam_marks.marks as question_marks')->from('online_course_exam_result');
        $this->db->join('online_course_exam_marks', 'online_course_exam_marks.question_id = online_course_exam_result.question_id');
        $this->db->join('online_course_exam_question', 'online_course_exam_question.id = online_course_exam_marks.question_id');
        $this->db->join('students', 'students.id = online_course_exam_result.student_id');
        $this->db->join('online_course_tag', 'online_course_tag.id = online_course_exam_question.question_tag');
        $this->db->where('online_course_exam_question.question_type', 'descriptive');
        $this->db->where('online_course_exam_marks.online_course_exam_id', $exam_id);
        if (!empty($where)){
            if (isset($where['question_id']) && $where['question_id'] != "") {
                $this->db->where('online_course_exam_question.id', $where['question_id']);
            }
        }
        $this->db->limit($per_page, $start);
        $this->db->order_by('online_course_exam_marks.id');
        $query = $this->db->get();

        if ($query->num_rows() >= 1) {
            $result = $query->result();
            $this->db->from('online_course_exam_result');
            $this->db->join('online_course_exam_marks', 'online_course_exam_marks.question_id = online_course_exam_result.question_id');
            $this->db->join('online_course_exam_question', 'online_course_exam_question.id = online_course_exam_marks.question_id');
            $this->db->join('students', 'students.id = online_course_exam_result.student_id');
            $this->db->join('online_course_tag', 'online_course_tag.id = online_course_exam_question.question_tag');
            $this->db->where('online_course_exam_question.question_type', 'descriptive');
            $this->db->where('online_course_exam_marks.online_course_exam_id', $exam_id);
            if (!empty($where)) {
                if (isset($where['question_id']) && $where['question_id'] != "") {
                    $this->db->where('online_course_exam_question.id', $where['question_id']);
                }
            }
            $num_results = $this->db->count_all_results();
            return json_encode(array('total_row' => $num_results, 'total_result' => $result));
        } else {
            $result = $query->result();
            return json_encode(array('total_row' => 0, 'total_result' => $result));
        }
    } 

    public function getDescriptionRecord_old2($per_page, $start, $where,$exam_id){
        $this->db->select('online_course_exam_result.*,students.admission_no,students.mobileno,students.guardian_name,students.guardian_phone,students.firstname,students.middlename,students.lastname,online_course_tag.tag_name,online_course_exam_question.question,online_course_exam_marks.marks as question_marks')->from('online_course_exam_marks');
        $this->db->join('`online_course_exam_result`', '`online_course_exam_marks`.`id` = `online_course_exam_result`.`question_id`');
        $this->db->join('`online_course_exam_question`', '`online_course_exam_question`.`id` = `online_course_exam_marks`.`question_id`');
        $this->db->join('`online_course_tag`', '`online_course_tag`.`id` = `online_course_exam_question`.`question_tag`');
        $this->db->join('`students`', '`students`.`id` = `online_course_exam_result`.`student_id`');
        $this->db->where('online_course_exam_question.question_type', 'descriptive');
        $this->db->where('online_course_exam_marks.online_course_exam_id', $exam_id);
        if (!empty($where)){
            if (isset($where['question_id']) && $where['question_id'] != "") {
                $this->db->where('online_course_exam_question.id', $where['question_id']);
            }
        }
        $this->db->limit($per_page, $start);
        $this->db->order_by('online_course_exam_marks.id');
        $query = $this->db->get();

        if ($query->num_rows() >= 1) {
            $result = $query->result();
            $this->db->from('online_course_exam_marks');
            $this->db->join('`online_course_exam_result`', '`online_course_exam_marks`.`id` = `online_course_exam_result`.`question_id`');
            $this->db->join('`online_course_exam_question`', '`online_course_exam_question`.`id` = `online_course_exam_marks`.`question_id`');
            $this->db->join('`online_course_tag`', '`online_course_tag`.`id` = `online_course_exam_question`.`question_tag`');
            $this->db->join('`students`', '`students`.`id` = `online_course_exam_result`.`student_id`');
            $this->db->where('online_course_exam_question.question_type', 'descriptive');
            $this->db->where('online_course_exam_marks.online_course_exam_id', $exam_id);
            if (!empty($where)) {
                if (isset($where['question_id']) && $where['question_id'] != "") {
                    $this->db->where('online_course_exam_question.id', $where['question_id']);
                }
            }
            $num_results = $this->db->count_all_results();
            return json_encode(array('total_row' => $num_results, 'total_result' => $result));
        } else {
            $result = $query->result();
            return json_encode(array('total_row' => 0, 'total_result' => $result));
        }
    } 

    public function getDescriptionRecord($per_page, $start, $where,$exam_id){
        $this->db->select('online_course_exam_result.*,guest.created_at as guest_doj,guest.guest_unique_id,guest.email as guest_email,guest.mobileno as guest_mobile,guest.guest_name,students.admission_no,students.mobileno,students.guardian_name,students.guardian_phone,students.firstname,students.middlename,students.lastname,online_course_tag.tag_name,online_course_exam_question.question,online_course_exam_marks.marks as question_marks')->from('online_course_exam_marks');
        $this->db->join('`online_course_exam_result`', '`online_course_exam_marks`.`id` = `online_course_exam_result`.`question_id`');
        $this->db->join('`online_course_exam_question`', '`online_course_exam_question`.`id` = `online_course_exam_marks`.`question_id`');
        $this->db->join('`online_course_tag`', '`online_course_tag`.`id` = `online_course_exam_question`.`question_tag`');
        $this->db->join('`students`', '`students`.`id` = `online_course_exam_result`.`student_id`','left');
        $this->db->join('`guest`', '`guest`.`id` = `online_course_exam_result`.`guest_id`','left');
        $this->db->where('online_course_exam_question.question_type', 'descriptive');
        $this->db->where('online_course_exam_marks.online_course_exam_id', $exam_id);
        if (!empty($where)){
            if (isset($where['question_id']) && $where['question_id'] != "") {
                $this->db->where('online_course_exam_question.id', $where['question_id']);
            }
        }
        $this->db->limit($per_page, $start);
        $this->db->order_by('online_course_exam_marks.id');
        $query = $this->db->get();      

        if ($query->num_rows() >= 1) {
            $result = $query->result();
            $this->db->from('online_course_exam_marks');
            $this->db->join('`online_course_exam_result`','`online_course_exam_marks`.`id` = `online_course_exam_result`.`question_id`');
            $this->db->join('`online_course_exam_question`','`online_course_exam_question`.`id` = `online_course_exam_marks`.`question_id`');
            $this->db->join('`online_course_tag`', '`online_course_tag`.`id` = `online_course_exam_question`.`question_tag`');
            $this->db->join('`students`','`students`.`id` = `online_course_exam_result`.`student_id`','left');
            $this->db->join('`guest`','`guest`.`id` = `online_course_exam_result`.`guest_id`','left');
            $this->db->where('online_course_exam_question.question_type', 'descriptive');
            $this->db->where('online_course_exam_marks.online_course_exam_id', $exam_id);
            if (!empty($where)) {
                if (isset($where['question_id']) && $where['question_id'] != "") {
                    $this->db->where('online_course_exam_question.id', $where['question_id']);
                }
            }
            $num_results = $this->db->count_all_results();
            return json_encode(array('total_row' => $num_results, 'total_result' => $result));
        } else {
            $result = $query->result();
            return json_encode(array('total_row' => 0, 'total_result' => $result));
        }
    } 

    public function update($data_update){
        $this->db->trans_begin();
        if (!empty($data_update)) {
            $this->db->where('id', $data_update['id']);
            $this->db->update('online_course_exam_result', $data_update);
        }
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return true;
        }
    } 

    public function online_course_exam_result($studentid_or_guestid,$exam_id){   
        
        $userdata = $this->customlib->getLoggedInUserData();
        $this->db->select("online_course_exam_marks.*,online_course_exam_result.id as `online_course_student_result_id`,online_course_exam_result.marks as `score_marks`,online_course_exam_result.attachment_name,online_course_exam_result.attachment_upload_name,online_course_exam_result.remark,         
        online_course_tag.tag_name,online_course_exam_question.question,online_course_exam_question.question_type,online_course_exam_question.opt_a, 
        online_course_exam_question.opt_b,online_course_exam_question.opt_c,online_course_exam_question.opt_d,online_course_exam_question.opt_e,
        online_course_exam_question.correct,online_course_exam_result.select_option")->from('online_course_exam_marks');
 
        if($userdata["role"]=='student'){
            $this->db->join('online_course_exam_result',"online_course_exam_result.question_id=online_course_exam_marks.id and online_course_exam_result.student_id=$studentid_or_guestid","left");
        }else if($userdata["role"]=='guest'){
            $this->db->join('online_course_exam_result',"online_course_exam_result.question_id=online_course_exam_marks.id and online_course_exam_result.guest_id=$studentid_or_guestid","left");
        }

        $this->db->join('online_course_exam_question','online_course_exam_question.id=online_course_exam_marks.`question_id`',"left");
        $this->db->join('online_course_tag', 'online_course_tag.id=online_course_exam_question.question_tag',"left");
        $this->db->where('online_course_exam_marks.online_course_exam_id',$exam_id);
        $query = $this->db->get();
        return $query->result();
    }

    public function getexamstatus($id)
    {
        return $this->db->select('online_course_exam.publish_exam_notification,online_course_exam.publish_result_notification')->where('online_course_exam.id',$id)->get('online_course_exam')->row_array();
    }

    public function get_guest_details($guest_id){
        $this->db->select('guest.*');
        $this->db->from('guest');
        $this->db->where('guest.id',$guest_id);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function get_online_course_exam_result($id)
    {
        return $this->db->select('online_course_exam_result.*')->where('online_course_exam_result.id',$id)->get('online_course_exam_result')->row_array();
    }


    public function getExamAttachmentByCourseId($courseid){
        $this->db->select('online_course_exam_result.*');
        $this->db->from('online_courses');
        $this->db->join('online_course_exam', 'online_course_exam.course_id=online_courses.id');
        $this->db->join('online_course_exam_marks', 'online_course_exam_marks.online_course_exam_id=online_course_exam.id');
        $this->db->join('online_course_exam_result', 'online_course_exam_result.question_id=online_course_exam_marks.id');
        $this->db->where('online_courses.id',$courseid);
        $query = $this->db->get();
        return $query->result_array();
    }



    
}