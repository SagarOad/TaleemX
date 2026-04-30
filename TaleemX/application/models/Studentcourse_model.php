<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Studentcourse_model extends MY_Model {

    protected $current_session;
    protected $role;
    public function __construct() {
        parent::__construct();
        $this->role = '';
        if(!empty($this->session->userdata('student'))){
            $this->role = $this->session->userdata('student')['role'];
        }
        $this->current_session = $this->setting_model->getCurrentSession();
    }

    /*
    This is used to show course list by class and section
    */
    public function courselist($classID=null,$sectionID=null) {

        $role           = $this->result["role"];

        if($role=='guest'){
             $this->db->where('online_courses.front_side_visibility','yes');
        }

        $query_string="" ;
        if($classID !="" && $sectionID!=""){

            $query_string = ",sections.section,classes.class" ;
            $this->db->join('online_course_class_sections', 'online_course_class_sections.course_id = online_courses.id','left');
            $this->db->join('class_sections', 'class_sections.id =  online_course_class_sections.class_section_id','left');
            $this->db->join('classes', 'classes.id = class_sections.class_id','left');
            $this->db->join('sections', 'sections.id = class_sections.class_id','left');
            $this->db->group_by('online_course_class_sections.course_id');
            $this->db->where('class_sections.class_id',$classID);
            $this->db->where('class_sections.section_id',$sectionID);
        }
        
        $this->db->select('online_courses.*,online_course_certificate_template.id as online_course_certificate_template_id,staff.name,staff.surname,staff.image,staff.gender,staff.employee_id'.$query_string)->from('online_courses');
        $this->db->join('staff', 'staff.id = online_courses.teacher_id');
        $this->db->join('online_course_certificate_template', 'online_course_certificate_template.id = online_courses.certificate_template_id','left');

        $this->db->where('online_courses.status','1');       
        
        $this->db->order_by('online_courses.id', 'desc');
        $query = $this->db->get();
        return $query->result_array();
    }

        /*
    This is used to show course list by class and section
    */
    public function getcourselist($classID=null,$sectionID=null) {

        $join_string="";$select_string="";          
          
        $join_string = " join online_course_class_sections on online_course_class_sections.course_id = online_courses.id join class_sections on class_sections.id =  online_course_class_sections.class_section_id join classes on classes.id = class_sections.class_id join sections on sections.id = class_sections.section_id" ;
        
        $select_string ="classes.class,sections.section,";                 
         
        $query   =   "select online_courses.*, ".$select_string." staff.name,staff.surname,staff.image from online_courses join staff on staff.id = online_courses.teacher_id ".$join_string."  where 1=1 and class_sections.class_id = $classID and class_sections.section_id = $sectionID and online_courses.status = '1'   " ;            
            
        $this->datatables->query($query)
        
        ->searchable('online_courses.title,null,null,null,null,null,null,null,online_courses.updated_date')
        ->orderable('online_courses.title,null,null,null,null,null,null,null,online_courses.updated_date') 
        ->query_where_enable(TRUE)
        ->sort('online_courses.id', 'desc') ;
        return $this->datatables->generate('json');
    }
    
    public function getguestcourselist() {

        $condition="";$join_string="";$select_string="";          
             
        $condition .=" and online_courses.front_side_visibility='yes'  " ;             
          
        $join_string = " join online_course_class_sections on online_course_class_sections.course_id = online_courses.id 
        join class_sections on class_sections.id =  online_course_class_sections.class_section_id
        join classes on classes.id = class_sections.class_id 
        join sections on sections.id = class_sections.section_id" ;
            $select_string ="classes.class,sections.section,";       
         
        $query   =   "select online_courses.*, ".$select_string." staff.name,staff.surname,staff.image from online_courses join staff on staff.id = online_courses.teacher_id ".$join_string."  where 1=1 ".$condition." and online_courses.status = '1' group by online_course_class_sections.course_id " ;          
 
        $this->datatables->query($query)
        
        ->searchable('online_courses.title,null,null,null,null,null,null,null,online_courses.updated_date')
        ->orderable('online_courses.title,null,null,null,null,null,null,null,online_courses.updated_date') 
        ->query_where_enable(TRUE)
        ->sort('online_courses.id', 'desc') ;
        return $this->datatables->generate('json');
    } 
    
    /*
    This is used to get total lesson count by course
    */
    public function totallessonbycourse($courseID) {
        $this->db->select('count(online_course_lesson.id) as total_lesson')->from('online_course_section');
        $this->db->join('online_course_lesson', 'online_course_lesson.course_section_id = online_course_section.id');
        $this->db->where('online_course_section.online_course_id',$courseID);
        $query = $this->db->get();
        return $query->result_array();
    }
	
	/*
    This is used to get total lesson count by course
    */
    public function totalquizbycourse($courseID) {
        $this->db->select('count(online_course_quiz.id) as total_quiz')->from('online_course_quiz');
        $this->db->join('online_course_section', 'online_course_section.id = online_course_quiz.course_section_id');
        $this->db->join('online_courses', 'online_courses.id = online_course_section.online_course_id');
        $this->db->where('online_courses.id',$courseID);
        $query = $this->db->get();
        return $query->result_array();
    }

    /*
    This is used to get total assignment count by course
    */    
    public function totalassignmentbycourse($courseID) {
        $this->db->select('count(online_course_assignment.id) as total_assignment')->from('online_course_assignment');
        $this->db->join('online_course_section', 'online_course_section.id = online_course_assignment.course_section_id');
        $this->db->join('online_courses', 'online_courses.id = online_course_section.online_course_id');
        $this->db->where('online_courses.id',$courseID);
        $query = $this->db->get();
        return $query->result_array();
    }

    /*
    This is used to get total exam count by course
    */
    public function totalexambycourse($courseID) {
        $this->db->select('count(online_course_exam.id) as total_exam')->from('online_course_exam');
        $this->db->join('online_course_section', 'online_course_section.id = online_course_exam.course_section_id');
        $this->db->join('online_courses', 'online_courses.id = online_course_section.online_course_id');
        $this->db->where('online_courses.id',$courseID);
        $this->db->where('online_course_exam.is_active',1);
        $query = $this->db->get();
        return $query->result_array();
    }


    /*
    This is used to get all quiz by course
    */
    public function quizbycourse($courseID) {
        $this->db->select('online_course_quiz.id,online_course_quiz.quiz_title')->from('online_course_quiz');
        $this->db->join('online_course_section', 'online_course_section.id = online_course_quiz.course_section_id');
        $this->db->join('online_courses', 'online_courses.id = online_course_section.online_course_id');
        $this->db->where('online_courses.id',$courseID);
        $this->db->order_by('online_course_quiz.id','asc');
		$query = $this->db->get();
        return $query->result();
    }

    public function quizstatusbycourseid($courseID, $studentid, $user_type) {

        if($user_type == 'guest'){
            $this->db->where('student_quiz_status.guest_id',$studentid);
        }else{
            $this->db->where('student_quiz_status.student_id',$studentid);
        }

        $this->db->select('student_quiz_status.*,online_course_quiz.quiz_title')->from('student_quiz_status');
        $this->db->join('online_course_quiz', 'online_course_quiz.id = student_quiz_status.course_quiz_id');
        $this->db->join('online_course_section', 'online_course_section.id = online_course_quiz.course_section_id');
        $this->db->join('online_courses', 'online_courses.id = online_course_section.online_course_id');
        $this->db->where('online_courses.id',$courseID);       
        $this->db->group_by('student_quiz_status.course_quiz_id');
        $this->db->order_by('online_course_quiz.id','asc');
        $query = $this->db->get();
        return $query->result_array();
    }

    /*
    This is used to get all attempt quiz by course
    */
    public function attemptquizbycourse($courseID) {
        $this->db->select('online_course_quiz.id,online_course_quiz.quiz_title')->from('online_course_quiz');
        $this->db->join('online_course_section', 'online_course_section.id = online_course_quiz.course_section_id');
        $this->db->join('online_courses', 'online_courses.id = online_course_section.online_course_id');
        $this->db->join('student_quiz_status', 'student_quiz_status.course_quiz_id = online_course_quiz.id');
        $this->db->where('online_courses.id',$courseID);
        $this->db->where('student_quiz_status.status','1');
        $this->db->order_by('online_course_quiz.id','asc');
		$this->db->group_by('online_course_quiz.id');	
        $query = $this->db->get();
        return $query->result_array();
    }

    /*
    This is used to get total lesson count by section
    */
    public function totallessonbysection($sectionID) {
        $this->db->select('count(online_course_lesson.id) as total_lesson')->from('online_course_section');
        $this->db->join('online_course_lesson', 'online_course_lesson.course_section_id = online_course_section.id');
        $this->db->where('online_course_lesson.course_section_id',$sectionID);
        $query = $this->db->get();
        return $query->result_array();
    }

    /*
    This is used to calculate total hours count
    */
    public function counthours($id)
    {
        $this->db->select('online_course_lesson.duration,online_course_lesson.lesson_type,')
            ->join("online_course_section", "online_course_section.online_course_id = online_courses.id")
            ->join("online_course_lesson", "online_course_lesson.course_section_id = online_course_section.id")
            ->where("online_course_lesson.lesson_type", 'video')
            ->where("online_courses.id", $id);
        $query     = $this->db->get('online_courses');
        $result    = $query->result_array();
        $totaltime = 0;
        $hours     = 0;
        $min       = 0;
        $sec       = 0;
        $total       = 0;
        $hh = 0;
        $mm = 0;
        $ss = 0;
        foreach ($result as $rs) {
            if ($rs['lesson_type'] == 'video') {
                $str_arr = explode(":", $rs['duration']);
				if(!empty($str_arr[0])){
                $hh      = $str_arr[0] * 3600;
				}
                if(!empty($str_arr[1])){
                $mm      = $str_arr[1] * 60;
                }
                if(!empty($str_arr[2])){
                $ss      = $str_arr[2];
                }
                $total   = $hh + $mm + $ss;
            }
            $totaltime += $total;
        }
        $hours = intval($totaltime / 3600);
        $min1  = $totaltime - ($hours * 3600);
        $min   = intval($min1 / 60);
        $sec   = $totaltime - (($min * 60) + ($hours * 3600));
        if($hours < 10){$hours = "0".$hours;}
        if($min < 10){$min = "0".$min;}
        if($sec < 10){$sec = "0".$sec;}
        
        return $hours . ':' . $min . ':' . $sec;
    }

    /*
    This is used to get lesson video for student section
    */
    public function singlevideo($lessonID) {
        $this->db->select('*')->from('online_course_lesson');
        $this->db->where('online_course_lesson.id',$lessonID);
        $query = $this->db->get();
        return $query->row_array();
    }

    /*
    This is used to get single quiz for student section
    */
    public function getsinglequiz($quizID) {
        $this->db->select('*')->from('online_course_quiz');
        $this->db->where('online_course_quiz.id',$quizID);
        $query = $this->db->get();
        return $query->row_array();
    }

    /*
    This is for getting total number of question present in single quiz  for student section
    */
    public function getquestioncount($quizID) {
        $this->db->select('count(id) as question_count')->from('course_quiz_question');
        $this->db->where('course_quiz_question.course_quiz_id',$quizID);
        $query = $this->db->get();
        return $query->row_array();
    }

    /*
    This is for get all question by quiz for student section
    */
    public function getallquestion($quizID) {
        $this->db->select('*')->from('course_quiz_question');
        $this->db->where('course_quiz_id',$quizID);
        $query = $this->db->get();
        return $query->result_array();
    }
    
    /*
    This is used to get first question by quiz and question id
    */
    public function firstquestion($quizID,$quizquestionID) {
        $this->db->select('*')->from('course_quiz_question');
        $this->db->where('course_quiz_id',$quizID);
        $this->db->where('id',$quizquestionID);
        $this->db->limit(1);
        $query = $this->db->get();
        return $query->row_array();
    }

    /*
    This is used to get single question for next question
    */
    public function getsinglequestion($quizID,$quizquestionID) {
        $this->db->select('*')->from('course_quiz_question');
        $this->db->where('course_quiz_id',$quizID);
        $this->db->where('id >',$quizquestionID);
        $this->db->limit(1);
        $query = $this->db->get();
        return $query->row_array();
    }

    /*
    This is used to get single question for previous question
    */
    public function previousquestion($quizID,$quizquestionID) {
        $this->db->select('*')->from('course_quiz_question');
        $this->db->where('course_quiz_id',$quizID);
        $this->db->where('id <',$quizquestionID);
        $this->db->order_by('id','desc');
        $this->db->limit(1);
        $query = $this->db->get();
        return $query->row_array();
    }
    
    public function checkLogin($data)
    {
        $resultdata    = $this->setting_model->getSetting();
        $student_login = json_decode($resultdata->student_login);
        $parent_login  = json_decode($resultdata->parent_login);

        // Guest login
        $this->db->select("guest.id as id,password,guest.is_active as is_active,'guest' as type,guest_unique_id as unique_id");
        $this->db->distinct();
        $this->db->from("guest");
        $this->db->where("email", $data['username']);
        $query1 = $this->db->get_compiled_select(); // It resets the query just like a get()
        
        
        
        // if ($resultdata->student_panel_login) {
             
      
            // Student login
            // $this->db->select("users.id as id,password,users.is_active as is_active,'student' as type,username as unique_id");
            // $this->db->distinct();
            // $this->db->from("users");
            // $this->db->join('students', 'students.id = users.user_id'); 
            // $this->db->where("password", $data['password']);
        
            // $this->db->group_start();
            // $this->db->where("username", $data['username']);
            
            // if (!empty($student_login) && in_array("admission_no", $student_login)) {
                // $this->db->or_where('students.admission_no', $data['username']);
            // }
            // if (!empty($student_login) && in_array("mobile_number", $student_login)) {
                // $this->db->or_where('students.mobileno', $data['username']);
            // }
            // if (!empty($student_login) && in_array("email", $student_login)) {
                // $this->db->or_where('students.email', $data['username']);
            // }
            // $this->db->group_end();
            // $this->db->where("role", 'student');
            // $query2 = $this->db->get_compiled_select();
        
            // Parent login
            // $this->db->select("users.id as id,password,users.is_active as is_active,'parent' as type,username as unique_id");
            // $this->db->distinct();
            // $this->db->from("users");
            // $this->db->join('students', 'students.parent_id = users.id');
            // $this->db->where("password", $data['password']);
            // $this->db->group_start();
            // $this->db->where("username", $data['username']);
            
            // if (!empty($parent_login) && in_array("mobile_number", $parent_login)) {
                // $this->db->or_where('students.guardian_phone', $data['username']);
            // }
            // if (!empty($parent_login) && in_array("email", $parent_login)) {
                // $this->db->or_where('students.guardian_email', $data['username']);
            // }
            // $this->db->group_end();
            // $this->db->where("role", 'parent');
            // $query3 = $this->db->get_compiled_select();
    
            // $query = $this->db->query($query1 . " UNION " . $query2 . " UNION " . $query3);
        // }else{
            $query = $this->db->query($query1);
        // }
        
        if ($query->num_rows() == 1) {
            $record = $query->row();
            if ($record->type == "guest") {

                $pass_verify = $this->enc_lib->passHashDyc($data['password'], $record->password);
                if ($pass_verify) {
                    return $query->row();
                }
            } 
            // elseif ($record->type == "student" || $record->type == "parent" ) {
                // return $query->row();
            // }

            return false;

        } else {
            return false;
        }
    }

    /*
    This is used to add quiz answer
    */
	public function addanswer($data)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        if (isset($data['id'])) {
            $this->db->where('id', $data['id']);
            $this->db->update('course_quiz_answer', $data);
            $message   = UPDATE_RECORD_CONSTANT . " On  course quiz answer id " . $data['id'];
            $action    = "Update";
            $record_id = $id = $data['id'];
            $this->log($message, $record_id, $action);
        } else {
            $this->db->insert('course_quiz_answer', $data);
            $id        = $this->db->insert_id();
            $message   = INSERT_RECORD_CONSTANT . " On course quiz answer id " . $id;
            $action    = "Insert";
            $record_id = $id;
            $this->log($message, $record_id, $action);           
        }
        //======================Code End==============================

        $this->db->trans_complete(); # Completing transaction
        /* Optional */

        if ($this->db->trans_status() === false) {
            # Something went wrong.
            $this->db->trans_rollback();
            return false;
        } else {
            return $id;
        }
    }

    /*
    This is used to get previous answer detail
    */
    public function getpreviousanswer($id) {
        $this->db->select('course_quiz_answer.*,course_quiz_question.id as question_id,course_quiz_question.question,course_quiz_question.option_1,course_quiz_question.option_2,course_quiz_question.option_3,course_quiz_question.option_4,course_quiz_question.option_5,course_quiz_question.course_quiz_id')->from('course_quiz_answer');
        $this->db->join('course_quiz_question', 'course_quiz_question.id = course_quiz_answer.course_quiz_question_id','left');
        $this->db->where('course_quiz_answer.id',$id);
        $query = $this->db->get();
        return $query->row_array();
    }

    /*
    This is used to get previous question detail
    */
    public function getpreviousquestiondetail($questionID,$quizID,$student_id) {
        $this->db->select('course_quiz_answer.*,course_quiz_question.id as question_id,course_quiz_question.question,course_quiz_question.option_1,course_quiz_question.option_2,course_quiz_question.option_3,course_quiz_question.option_4,course_quiz_question.option_5,course_quiz_question.course_quiz_id')->from('course_quiz_answer');
        $this->db->join('course_quiz_question', 'course_quiz_question.id = course_quiz_answer.course_quiz_question_id','left');
        $this->db->where('course_quiz_answer.course_quiz_question_id',$questionID);
        $this->db->where('course_quiz_answer.course_quiz_id',$quizID);      
        
        if($this->role=='guest'){ 
            $this->db->where('course_quiz_answer.guest_id',$student_id);
        }else if($this->role=='student'){
            $this->db->where('course_quiz_answer.student_id',$student_id);
        }       
        
        $query = $this->db->get();
        return $query->row_array();
    }

    /*
    This is used to get all answer detail
    */
    public function getallanswer($quizID,$studentid) {      
        
        $this->db->select('course_quiz_question_id,answer')->from('course_quiz_answer');
        $this->db->where('course_quiz_id',$quizID);
        if($this->role=='guest'){
            $this->db->where('guest_id',$studentid);
        }else if($this->role=='student'){
            $this->db->where('student_id',$studentid);
        }
        $query = $this->db->get();
        return $query->result_array();
    }

    /*
    This is used to add student result status
    */
	public function addresult($data)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        if (isset($data['id'])) {
            $this->db->where('id', $data['id']);
            $this->db->update('student_quiz_status', $data);
            $message   = UPDATE_RECORD_CONSTANT . " On student quiz status id " . $data['id'];
            $action    = "Update";
            $record_id = $id = $data['id'];
            $this->log($message, $record_id, $action);
        } else {
            $this->db->insert('student_quiz_status', $data);

            $id        = $this->db->insert_id();
            $message   = INSERT_RECORD_CONSTANT . " On student quiz status id" . $id;
            $action    = "Insert";
            $record_id = $id;
            $this->log($message, $record_id, $action);           
        }
        //======================Code End==============================

        $this->db->trans_complete(); # Completing transaction
        /* Optional */

        if ($this->db->trans_status() === false) {
            # Something went wrong.
            $this->db->trans_rollback();
            return false;
        } else {
            return $id;
        }
    }

    /*
    This is used to get single student result
    */
    public function getresult($quizID,$studentid) {

        if($this->role=='guest'){
               $this->db->where('course_quiz_answer.guest_id',$studentid);
        }else if($this->role=='student'){
             $this->db->where('course_quiz_answer.student_id',$studentid);
        }

        $this->db->select('course_quiz_answer.answer,course_quiz_question.id,course_quiz_question.question,course_quiz_question.option_1,course_quiz_question.option_2,course_quiz_question.option_3,course_quiz_question.option_4,course_quiz_question.option_5,course_quiz_question.correct_answer')->from('course_quiz_question');        
        $this->db->join('course_quiz_answer','course_quiz_question.id = course_quiz_answer.course_quiz_question_id','left');   
        $this->db->where('course_quiz_question.course_quiz_id',$quizID);
        $this->db->where('course_quiz_answer.course_quiz_id',$quizID);      
		$this->db->group_by('course_quiz_question.id');		
        $query = $this->db->get();
        return $query->result_array();
    }

    /*
    This is used to get all given answer of attempt quiz 
    */
    public function getanswer($quizid,$questionid,$studentid) {

        if($this->role=='guest'){
             $this->db->where('course_quiz_answer.guest_id',$studentid);
        }else if($this->role=='student'){
            $this->db->where('course_quiz_answer.student_id',$studentid);
        }

        $this->db->select('course_quiz_answer.answer')->from('course_quiz_question');        
        $this->db->join('course_quiz_answer','course_quiz_question.id = course_quiz_answer.course_quiz_question_id','left');  
        $this->db->where('course_quiz_question.course_quiz_id',$quizid);
        $this->db->where('course_quiz_answer.course_quiz_question_id',$questionid);        
        $this->db->group_by('course_quiz_question.id');     
        $query = $this->db->get();
        return $query->row_array();
    }

    /*
    This is used to get data to show student performance
    */
    public function graphdata($quizID,$studentid) {

        if($this->role=='guest'){
             $this->db->where('student_quiz_status.guest_id',$studentid);
        }else if($this->role=='student'){
            $this->db->where('student_quiz_status.student_id',$studentid);
        }

        $this->db->select('student_quiz_status.*,course_quiz_answer.answer,course_quiz_question.id,course_quiz_question.question,course_quiz_question.option_1,course_quiz_question.option_2,course_quiz_question.option_3,course_quiz_question.option_4,course_quiz_question.option_5,course_quiz_question.correct_answer')->from('student_quiz_status');
        $this->db->join('course_quiz_answer','course_quiz_answer.course_quiz_id = student_quiz_status.course_quiz_id');
        $this->db->join('course_quiz_question','course_quiz_question.id = course_quiz_answer.question_id');
        $this->db->where('student_quiz_status.course_quiz_id',$quizID);
        $this->db->group_by('course_quiz_question.id');
        $query = $this->db->get();
        return $query->result_array();
    }

    /*
    This is used to get data to show quiz performance
    */
    public function quizgraph($quizID,$studentid) {

        if($this->role=='guest'){
             $this->db->where('student_quiz_status.guest_id',$studentid);
        }else if($this->role=='student'){
            $this->db->where('student_quiz_status.student_id',$studentid);
        }

        $this->db->select('student_quiz_status.*, student_quiz_status.correct_answer as right_answer,course_quiz_answer.answer,course_quiz_question.id,course_quiz_question.question,course_quiz_question.option_1,course_quiz_question.option_2,course_quiz_question.option_3,course_quiz_question.option_4,course_quiz_question.option_5,course_quiz_question.correct_answer,student_quiz_status.total_question')->from('student_quiz_status');
        $this->db->join('course_quiz_answer','course_quiz_answer.course_quiz_id = student_quiz_status.course_quiz_id');
        $this->db->join('course_quiz_question','course_quiz_question.id = course_quiz_answer.course_quiz_question_id');
        $this->db->where('student_quiz_status.course_quiz_id',$quizID);        
        $this->db->group_by('student_quiz_status.course_quiz_id');
        $this->db->order_by('course_quiz_question.course_quiz_id','asc');
        $query = $this->db->get();
        return $query->row_array();
    }

    /*
    This is used to get data to show quiz performance
    */
    public function quizgraphadmin($quizID,$studentid,$user_type) {

        if($user_type=='guest'){
             $this->db->where('student_quiz_status.guest_id',$studentid);
        }else if($user_type=='student'){
            $this->db->where('student_quiz_status.student_id',$studentid);
        }

        $this->db->select('student_quiz_status.*, student_quiz_status.correct_answer as right_answer,course_quiz_answer.answer,course_quiz_question.id,course_quiz_question.question,course_quiz_question.option_1,course_quiz_question.option_2,course_quiz_question.option_3,course_quiz_question.option_4,course_quiz_question.option_5,course_quiz_question.correct_answer,student_quiz_status.total_question')->from('student_quiz_status');
        $this->db->join('course_quiz_answer','course_quiz_answer.course_quiz_id = student_quiz_status.course_quiz_id');
        $this->db->join('course_quiz_question','course_quiz_question.id = course_quiz_answer.course_quiz_question_id');
        $this->db->where('student_quiz_status.course_quiz_id',$quizID);        
        $this->db->group_by('student_quiz_status.course_quiz_id');
        $this->db->order_by('course_quiz_question.course_quiz_id','asc');
        $query = $this->db->get();
        return $query->row_array();
    }

    /*
    This is used to get total question count for result
    */
    public function questioncount($quizID,$studentid) {

        $this->db->select('count(id) as total_count')->from('course_quiz_answer');
        $this->db->where('course_quiz_id',$quizID);
        $query = $this->db->get();
        return $query->row_array();
    }

    /*
    This is for check result is complet or incomplete when student click on quiz 
    */
    public function checkstatus($quizID,$studentid) {

        if($this->role=='guest'){
             $this->db->where('guest_id',$studentid);
        }else if($this->role=='student'){
            $this->db->where('student_id',$studentid);
        }

        $this->db->select('*')->from('student_quiz_status');
        $this->db->where('course_quiz_id',$quizID);        
        $query = $this->db->get();
        return $query->row_array();
    }

    /*
    This is for delete result status record when student click on reset button 
    */
    public function remove($quizID,$studentid) {

        if($this->role=='guest'){
             $this->db->where('guest_id',$studentid);
        }else if($this->role=='student'){
            $this->db->where('student_id',$studentid);
        }

        $this->db->where('course_quiz_id',$quizID);
        $this->db->delete('student_quiz_status');
    }

    /*
    This is for delete all answer when result status is 1 
    */
    public function removeanswer($quizID,$roleID) {

        if($this->role=='guest'){
             $this->db->where('guest_id',$roleID);
        }else if($this->role=='student'){
            $this->db->where('student_id',$roleID);
        }

        $this->db->where('course_quiz_id',$quizID);
        $this->db->delete('course_quiz_answer');
    }
	
	/**
     * This function is used to mark/unmark lesson complete
     */
    public function markascomplete($lesson_data, $mark)
    {
        if ($mark == 0) {
            $this->db->where(array(
				"course_id" => $lesson_data["course_id"], 
				"course_section_id" => $lesson_data["course_section_id"], 
				"student_id" => $lesson_data["student_id"],
				"lesson_quiz_type" => $lesson_data["lesson_quiz_type"],
				"lesson_quiz_id" => $lesson_data["lesson_quiz_id"]));				
            $this->db->delete("course_progress");
        } elseif ($mark == 1) {
            $this->db->insert("course_progress", $lesson_data);
        }
    }
	
	/**
     * This function is used to get course progress
     */
    public function getcourseprogress($courseid, $student_id, $section_id, $lesson_quiz_type, $lesson_quiz_id)
    {
        if($this->role=='guest'){
            $this->db->where('guest_id',$student_id );
        }else if($this->role=='student'){
            $this->db->where('student_id',$student_id);
        }

        $result = $this->db->select("id")
            ->where(array("course_id" => $courseid, "course_section_id" => $section_id, "lesson_quiz_type" => $lesson_quiz_type, "lesson_quiz_id" => $lesson_quiz_id))
            ->get("course_progress")
            ->result_array();
        return $result;
    }

    public function courseprogresscount($courseid, $student_id, $user_type) // here $student id represents student id or guest id
    {
        if($user_type == 'guest'){
            $this->db->where('guest_id',$student_id);
        }else{
            $this->db->where('student_id',$student_id);
        }

        $result = $this->db->select("id,lesson_quiz_type")
            ->where("course_id",$courseid)
            ->get("course_progress")
            ->result_array();
        return $result;
    }


    /*
    This is used to get section count based on the course id
    */
    public function getsectioncount($id){         
        $this->db->select('count(*) as total_section');
        $this->db->from('online_course_section');
        $this->db->where('online_course_id',$id);
        $this->db->join('online_courses','online_courses.id=online_course_section.online_course_id');
        $query = $this->db->get();
        $result=$query->row_array();
        return $result['total_section'] ;
    }

    /*
    This is used to get completed lesson, quiz count by course
    */

    public function completelessonquizbycourse($courseid,$userid, $user_type) {

        if($user_type=='guest'){
            $this->db->where('course_progress.guest_id',$userid);
        }else{
           $this->db->where('course_progress.student_id',$userid);
        }

        $this->db->select('course_progress.*')->from('course_progress');
        $this->db->where('course_progress.course_id',$courseid);

        $query = $this->db->get();
        $result = $query->result();
        $lesscount = 0 ;
        $quizcount = 0 ;  
        $assignmentcount = 0 ;        
        $examcount = 0 ;        
        foreach ($result as $key => $sectionList_value) {            
            if($sectionList_value->lesson_quiz_type == 1){
               $lesscount++ ; 
            }
            if($sectionList_value->lesson_quiz_type == 2){
               $quizcount++ ; 
            }
            if($sectionList_value->lesson_quiz_type == 3){
               $assignmentcount++ ; 
            }
            if($sectionList_value->lesson_quiz_type == 4){
               $examcount++ ; 
            }

            $result['lesson'] = $lesscount;                  
            $result['quiz']   = $quizcount;  
            $result['assignmentcount']   = $assignmentcount;                  
            $result['examcount']         = $examcount;  
        }                           
        return $result;
    }


    /*
    This is used to get lesson quiz count by course and student
    */
    public function lessonquizcountbycourseid($course_id,$studentid, $user_type = null) {

        $this->db->select('online_course_section.*,online_courses.title');
        $this->db->from('online_course_section');        
        $this->db->join('online_courses','online_courses.id=online_course_section.online_course_id');
        $this->db->where('online_course_section.online_course_id',$course_id);
        $query = $this->db->get();
        $result = $query->result();
        $result['lessoncount'] ='';
        $result['quizcount'] ='';
        $result['assignmentcount']='';
        $result['examcount']='';

        foreach ($result as $key => $sectionList_value) {

            $lesson_count = $this->totallessonbycourse($course_id);
            $result['lessoncount'] = $lesson_count[0]['total_lesson'];             
            $quiz_count = $this->totalquizbycourse($course_id);
            $result['quizcount'] = $quiz_count[0]['total_quiz'];
            $assignment_count = $this->totalassignmentbycourse($course_id);          
            $result['assignmentcount'] = $assignment_count[0]['total_assignment'];
            $exam_count = $this->totalexambycourse($course_id);          
            $result['examcount'] = $exam_count[0]['total_exam'];
            if($studentid != ''){
                $result['courseprogresscount'] = $this->courseprogresscount($course_id, $studentid, $user_type);  
            }
						
        }                           
        return $result;
    }

    /*
    This is used to add and update course rating
    */
    public function rating($data) {
        if (isset($data['id'])) {
            $this->db->where('id', $data['id']);
            $this->db->update('course_rating', $data);
        } else {
            $this->db->insert('course_rating', $data);
            return $this->db->insert_id();
        }
    }

    /*
    This is used to get course rating by course
    */
    public function getcourserating($courseid){     

        $this->db->select("course_rating.*,guest.guest_name as rating_provider_name,'null' as middlename, 'null' as lastname,guest_image as image, guest.guest_unique_id as admission_no, guest.gender as gender");
        $this->db->from("course_rating");
        $this->db->join('guest','guest.id=course_rating.guest_id');
        $this->db->where('course_rating.course_id',$courseid);
        $query1 = $this->db->get_compiled_select(); // It resets the query just like a get()
        $this->db->select("course_rating.*,students.firstname as rating_provider_name,students.middlename as middlename,students.lastname as lastname,students.image as image, students.admission_no as admission_no, students.gender as gender");
        $this->db->from("course_rating");
         $this->db->join('students','students.id=course_rating.student_id');
        $this->db->where('course_rating.course_id',$courseid);
        $query2 = $this->db->get_compiled_select();
        
        $query = $this->db->query($query1 . " UNION " . $query2);
        
        return $query->result_array();
    }
    
    public function getcourseratingcount($courseid){     
        
        $userdata = $this->customlib->getUserData();
        
        $this->db->select("course_rating.*,guest.guest_name as rating_provider_name,'null' as middlename, 'null' as lastname,guest_image as image, guest.guest_unique_id as admission_no, guest.gender as gender");
        $this->db->from("course_rating");
        $this->db->join('guest','guest.id=course_rating.guest_id');
        $this->db->where('course_rating.course_id',$courseid);
        $query1 = $this->db->get_compiled_select(); // It resets the query just like a get()
        $this->db->select("course_rating.*,students.firstname as rating_provider_name,students.middlename as middlename,students.lastname as lastname,students.image as image, students.admission_no as admission_no, students.gender as gender");
        $this->db->from("course_rating");
         $this->db->join('students','students.id=course_rating.student_id');
        $this->db->where('course_rating.course_id',$courseid);
        $query2 = $this->db->get_compiled_select();
        
        if (($userdata["role_id"] == 2) && ($userdata["class_teacher"] == "yes")) {
            $query = $this->db->query($query2);
        }else{        
            $query = $this->db->query($query1 . " UNION " . $query2);
        }
        return $query->result_array();
    }

    /*
    This is used to check rating is already available for individual student, and also get rating detail by course and student
    */
    public function checkratingstatus($courseid,$studentid,$guest_id){ 

        if($this->role=='guest'){
            $this->db->where('guest_id',$guest_id );
        }else if($this->role=='student'){
            $this->db->where('student_id',$studentid);
        }    
        $this->db->select('count(id) as count,id,student_id,course_id,rating,review');
        $this->db->from('course_rating');
        $this->db->where('course_id',$courseid);        
        $query = $this->db->get();
        return $query->row_array();
    } 

    public function getPurchasedCourseId($userid, $course_id)
    {     
        if($this->role=='guest'){
            $this->db->where("online_course_payment.guest_id", $userid);
        }else if($this->role=='student'){
            $this->db->where("online_course_payment.student_id", $userid);
        }

        $query = $this->db->select('online_course_payment.*')
            ->where("online_course_payment.online_courses_id", $course_id)
            ->get('online_course_payment');
        return $query->result_array();
    }

    public function addRating($data)
    {
        if (isset($data["id"])) {
            $this->db->where("id", $data["id"])->update("course_rating", $data);
        } else {
            $this->db->insert("course_rating", $data);
        }
    }
    
    /*
    This is used to show course list by class and section
    */
    public function purchasecourselist() {

        $id     = $this->result["guest_id"];       
        $this->db->select('online_courses.*,staff.name,staff.surname,staff.image,staff.gender')->from('online_courses');
        $this->db->join('staff', 'staff.id = online_courses.teacher_id',"left");
         $this->db->join('online_course_payment', 'online_course_payment.online_courses_id = online_courses.id');
        $this->db->where('online_courses.status','1');
        $this->db->where('online_course_payment.guest_id',$id);
        $this->db->order_by('online_courses.id', 'desc');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function valid_guest_email_id($str)
    {
        $email   = $this->input->post('email');
        $id      = $this->input->post('employee_id');
        $stud_id = $this->input->post('staff_id');
        if (!isset($id)) {
            $id = 0;
        }
        if (!isset($stud_id)) {
            $stud_id = 0;
        }
        if ($str == "") {
            $this->form_validation->set_message('check_exists', $this->lang->line('the_email_field_is_required'));
            return false;
        }
        if (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str)) {
            $this->form_validation->set_message('check_exists', $this->lang->line('invalid_email'));
            return false;
        } else {
            if ($this->check_email_exists($email, $id, $stud_id)) {
                $this->form_validation->set_message('check_exists', $this->lang->line('email_already_exists'));
                return false;
            } else {
                return true;
            }
        }
    }
    
    public function check_email_exists($email, $id, $stud_id)
    {
        if ($stud_id != 0) {
            $data  = array('id != ' => $stud_id, 'email' => $email);
            $query = $this->db->where($data)->get('students');
            $guest_query = $this->db->where($data)->get('guest');
            if (($query->num_rows() > 0) || ($guest_query->num_rows() > 0) ) {
                return true;
            } else {
                return false;
            }
        } else {
            $this->db->where('email', $email);
            $query = $this->db->get('students');            
           
            $this->db->where('email', $email);
            $guest_query = $this->db->get('guest');
            
            if (($query->num_rows() > 0) || ($guest_query->num_rows() > 0) ) {               
                return true;
            } else {                
                return false;
            }
        }
    }

    public function lastRecord()
    {
        $last_row = $this->db->select('*')->order_by('id', "desc")->limit(1)->get('guest')->row();
        return $last_row;
    }

    public function addguest($data)
    {
        if (isset($data["id"])) {
            $query = $this->db->where("id", $data["id"])->update("guest", $data);
            if ($query) {
                return true;
            } else {
                return false;
            }
        } else {
            $this->db->insert("guest", $data);
            return $this->db->insert_id();
        }
    }

    public function read_user_information($users_id)
    {
        $this->db->select('guest.*,languages.language,IFNULL(currencies.name,0) as currency_name,IFNULL(currencies.symbol,0) as symbol,IFNULL(currencies.base_price,0) as base_price,IFNULL(currencies.id,0) as `currency`');
        $this->db->from('guest');
        $this->db->join('languages','languages.id=guest.lang_id');  
        $this->db->join('currencies', 'currencies.id=guest.currency_id', 'left');
        $this->db->where('guest.id', $users_id);         
        $this->db->limit(1);
        $query = $this->db->get();
        if ($query->num_rows() == 1) {
            return $query->result();
        } else {
            return false;
        }
    }

    public function checkOldPass($data) {
        
        $record = $this->getGuestByEmail($data['email']);        
        $pass_verify = $this->enc_lib->passHashDyc($data['current_pass'], $record->password);
        
        if($pass_verify){            
            $this->db->where('id', $data['user_id']);
            $this->db->where('email', $data['email']);        
            $query = $this->db->get('guest');            
            return TRUE;            
        }else{            
            return FALSE;                
        }
    }
    
    public function getGuestByEmail($email) 
    {
        $this->db->select("id,password");
        $this->db->distinct();
        $this->db->from("guest");
        $this->db->where("email", $email);
        
        $query = $this->db->get();
        if ($query->num_rows() == 1) {
            return $query->row();
        } else {
            return false;
        }
    }

    public function guestpurchasehistory($guest_id) 
    {         
        $sql="select online_courses.title, online_courses.course_provider, online_course_payment.*,online_course_payment.payment_type from online_courses inner join online_course_payment on online_course_payment.online_courses_id=online_courses.id where online_course_payment.guest_id = '".$guest_id."' and online_courses.status = '1' " ;
             $this->datatables->query($sql) 
              ->searchable('date,online_courses.title,online_courses.course_provider,payment_type,payment_mode,paid_amount')
              ->orderable('date,online_courses.title,online_courses.course_provider,payment_type,payment_mode,paid_amount')
              ->sort('online_course_payment.date','desc')
              ->query_where_enable(TRUE);
        return $this->datatables->generate('json');
    }     
    
    public function deleteguest($id) {
		
		$this->db->where("guest_id", $id)->delete("course_progress");
		$this->db->where("guest_id", $id)->delete("course_quiz_answer");
		$this->db->where("guest_id", $id)->delete("course_rating");
		$this->db->where("guest_id", $id)->delete("online_course_assignment_evaluation");
		$this->db->where("guest_id", $id)->delete("online_course_assignment_submit");
		$this->db->where("guest_id", $id)->delete("online_course_exam_attempts");
		$this->db->where("guest_id", $id)->delete("online_course_exam_result");
		$this->db->where("guest_id", $id)->delete("online_course_processing_payment");       
        $this->db->where('id',$id)->delete('guest');
		
    }

    public function guestprogresslist() 
    {      
        $this->datatables
            ->select('course_progress.guest_id as id,guest.guest_name,guest.guest_unique_id')
            ->searchable('guest.guest_name,guest.guest_unique_id,""')
              ->orderable('guest.guest_name,guest.guest_unique_id,""')
            ->group_by('course_progress.guest_id')            
            ->join('online_courses', 'online_courses.id = course_progress.course_id') 
            ->join('guest', 'guest.id = course_progress.guest_id') 
            ->from('course_progress')
            ->where(array('online_courses.front_side_visibility' => 'yes','guest.is_active'=> 'yes'));
            return $this->datatables->generate('json');
    }

    public function updateverifactioncode($table, $data)
    {
        $this->db->where('id', $data['id']);
        $query = $this->db->update($table, $data);
        if ($query) {
            return true;
        } else {
            return false;
        }
    }

    public function forgotPassword($email)
    {       
        $result = $this->getGuestByEmail($email);
        return $result;
    }

    public function getstudentbyemail($table, $role, $email)
    {
        $this->db->select($table . '.*,users.id as `user_tbl_id`,users.username,users.password as `user_tbl_password`');
        $this->db->from($table);
        $this->db->join('users', 'users.user_id = ' . $table . '.id', 'left');
        $this->db->where('users.role', $role);
        $this->db->where($table . '.email', $email);
        if ($role == 'parent') {
            $this->db->where($table . '.guardian_email', $email);
        } else {
            $this->db->where($table . '.email', $email);
        }
        
        $query = $this->db->get();
        if ($email != null) {
            return $query->row();
        } else {
            return false;
        }
    }

    public function getparentbyemail($table, $role, $email)
    {
        $this->db->select($table . '.*,users.id as `user_tbl_id`,users.username,users.password as `user_tbl_password`');
        $this->db->from($table);
        $this->db->join('users', 'users.id = ' . $table . '.parent_id', 'left');
        $this->db->where('users.role', $role);
        if ($role == 'parent') {
            $this->db->where($table . '.guardian_email', $email);
        } else {
            $this->db->where($table . '.email', $email);
        }
        $query = $this->db->get();
        if ($email != null) {
            return $query->row();
        } else {
            return false;
        }
    }

    public function getusercodebyrole($usertype, $code)
    {
        $result = false;

        if ($usertype == 'student') {
            $table  = "students";
            $role   = "student";
            $result = $this->getuservalidcode($table, $role, $code);
        }elseif ($usertype == 'parent') {
            $table  = "students";
            $role   = "parent";
            $result = $this->getparentvalidcode($table, $role, $code);
        }elseif ($usertype == 'guest') {
            $table  = "guest";
            $role   = "guest";
            $result = $this->getguestvalidcode($table, $role, $code);
        }
        return $result;
    }

    public function getparentvalidcode($table, $role, $code)
    {
        $this->db->select($table . '.*,users.id as `user_tbl_id`,users.username,users.password as `user_tbl_password`');
        $this->db->from($table);
        $this->db->join('users', 'users.id = ' . $table . '.parent_id', 'left');
        $this->db->where('users.role', $role);
        $this->db->where('users.verification_code', $code);

        $query = $this->db->get();
        if ($code != null) {
            return $query->row();
        } else {
            return false;
        }
    }

    public function getuservalidcode($table, $role, $code)
    {
        $this->db->select($table . '.*,users.id as `user_tbl_id`,users.username,users.password as `user_tbl_password`');
        $this->db->from($table);
        $this->db->join('users', 'users.user_id = ' . $table . '.id', 'left');
        $this->db->where('users.role', $role);
        $this->db->where('users.verification_code', $code);
        $query = $this->db->get();
        if ($code != null) {
            return $query->row();
        } else {
            return false;
        }
    }

    public function getguestvalidcode($table, $role, $code)
    {
        $this->db->select($table . '.*');
        $this->db->from($table);
        $this->db->where($table.'.verification_code', $code);
        $query = $this->db->get();
        if ($code != null) {
            return $query->row();
        } else {
            return false;
        }
    }

    public function savenewpassword($data)
    {
        $this->db->where('id', $data['id']);
        $query = $this->db->update('guest', $data);
        if ($query) {
            return true;
        } else {
            return false;
        }
    }

    public function get_lesson_attachments_by_lessonid($lesson_id){
        $this->db->select('online_course_lesson_attachment.*');
        $this->db->from('online_course_lesson_attachment');
        $this->db->where('online_course_lesson_attachment.lesson_id', $lesson_id);
        $query = $this->db->get();
        return $query->result_array();        
    }

    public function get_lesson_attachments_byid($id){
        $this->db->select('online_course_lesson_attachment.*');
        $this->db->from('online_course_lesson_attachment');
        $this->db->where('online_course_lesson_attachment.id', $id);
        $query = $this->db->get();
        return $query->row();        
    }
   
//*******disable all checkboxes until the first lesson is complete logic section code and queries*******//
    public function get_lesson_complete_status($section_id,$lesson_quiz_id){

        $query = $this->db->query("SELECT * FROM `course_lesson_quiz_order` LEFT JOIN `course_progress` ON `course_progress`.`course_section_id`=`course_lesson_quiz_order`.`course_section_id` and `course_lesson_quiz_order`.`lesson_quiz_id`=`course_progress`.`lesson_quiz_id` WHERE `course_progress`.`course_section_id` = $section_id AND `course_progress`.`lesson_quiz_id` = $lesson_quiz_id");
        return $query->num_rows();        
    }

    public function get_next_lesson_quiz_start_status($section_id,$lesson_quiz_id,$order_id,$course_id){
		
		$this->db->select('course_lesson_quiz_order.order,course_lesson_quiz_order.id,course_section_id');
        $this->db->from('course_lesson_quiz_order');
        $this->db->where('course_lesson_quiz_order.course_section_id', $section_id);
        $this->db->where('course_lesson_quiz_order.id', $order_id);
        $query = $this->db->get();
		$result = $query->row_array();
		 
        if($result['order']){

			$nextorder = $result['order'] + 1 ;
			$this->db->select('course_lesson_quiz_order.lesson_quiz_id');
			$this->db->from('course_lesson_quiz_order');
			$this->db->where('course_lesson_quiz_order.course_section_id', $section_id);			 
			$this->db->where('course_lesson_quiz_order.order', $nextorder);
			$query = $this->db->get();
			$result2 = $query->row_array(); 
			
			if($query->num_rows()==0){	

				$this->db->select('course_lesson_quiz_order.lesson_quiz_id');
				$this->db->from('course_lesson_quiz_order');
				$this->db->where('course_lesson_quiz_order.course_section_id >', $result['course_section_id']);
				$query = $this->db->get();
				return $query->row_array(); 
			}else{
				return $result2;	
			} 
		}else{
			$this->db->select('course_lesson_quiz_order.lesson_quiz_id');
			$this->db->from('course_lesson_quiz_order');			
			$this->db->where('course_lesson_quiz_order.course_section_id', $result['course_section_id']);			
			$this->db->where('course_lesson_quiz_order.id >', $result['id']);
			$query = $this->db->get();
			$result1 = $query->row_array(); 
			
			if($query->num_rows()==0){		
				$this->db->select('course_lesson_quiz_order.lesson_quiz_id');
				$this->db->from('course_lesson_quiz_order');
				$this->db->where('course_lesson_quiz_order.course_section_id >', $result['course_section_id']);
				$query = $this->db->get();
				return $query->row_array(); 
				
			}else{
				return $result1;	
			}
		return false;
		}  
    }

    public function delete_all_course_progress($course_id) {
        $this->db->where('course_id',$course_id);
        $this->db->delete('course_progress');
    }

    public function savecoursestartdate($data)
    {
        $this->db->select('online_course_start_date.*');
        $this->db->from('online_course_start_date');
        
        if($data['student_id'] != 0) {
            $this->db->where('online_course_start_date.student_id', $data['student_id']);
        }
        if($data['guest_id'] != 0) {
            $this->db->where('online_course_start_date.guest_id', $data['guest_id']);
        }
        if($data['course_id'] != 0) {
            $this->db->where('online_course_start_date.course_id', $data['course_id']);
        }
        $query = $this->db->get();
        
        // Check if the result is empty (no rows returned)
        if ($query->num_rows() == 0) {
            // If there's no existing record, insert new data
            $this->db->insert('online_course_start_date', $data);
        } 
    }

    public function getcoursecompletiondate($course_id,$student_guest_id,$role=null) {
       
        $this->db->select('*');
        $this->db->from('course_progress');

        if($role==null || $role==""){
            $role = $this->result["role"];
            if ($role == 'student' || $role == 'parent') {
                $student_id=$this->result["student_id"];
                $this->db->where('student_id',$student_id);
            }elseif ($role == 'guest') {
                $guest_id = $this->result["guest_id"];
                $this->db->where('guest_id',$guest_id);
            }
        }else{
            if ($role == 'student' || $role == 'parent') {
                $student_id=$student_guest_id;
                $this->db->where('student_id',$student_id);
            }elseif ($role == 'guest') {
                $guest_id = $student_guest_id;
                $this->db->where('guest_id',$guest_id);
            }
        }
       
        $this->db->where('course_id',$course_id);
        $this->db->order_by("completion_date", 'desc');
        $this->db->limit(1);
        $query = $this->db->get();
        return $query->row_array();
    }
    public function getcoursestartdate($course_id,$student_guest_id,$role=null) {
       
        $this->db->select('*');
        $this->db->from('online_course_start_date');
        if($role==null || $role==""){
            $role = $this->result["role"];
            if ($role == 'student' || $role == 'parent') {
                $student_id=$this->result["student_id"];
                $this->db->where('student_id',$student_id);
            }elseif ($role == 'guest') {
                $guest_id = $this->result["guest_id"];
                $this->db->where('guest_id',$guest_id);
            }
        }else{
            if ($role == 'student' || $role == 'parent') {
                $student_id=$student_guest_id;
                $this->db->where('student_id',$student_id);
            }elseif ($role == 'guest') {
                $guest_id = $student_guest_id;
                $this->db->where('guest_id',$guest_id);
            }
        }

        $this->db->where('course_id',$course_id);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function get_guest_details($guest_id){
        $this->db->select('guest.*');
        $this->db->from('guest');
        $this->db->where('guest.id', $guest_id);
        $query = $this->db->get();
        return $query->row_array();        
    }


    
   

   






}