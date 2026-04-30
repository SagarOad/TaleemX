<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Coursereport_model extends MY_Model {

    protected $current_session;
    public function __construct() {
        parent::__construct();
    }  

    public function get_teacherstudents()
    {
        $userdata            = $this->customlib->getUserData();
        if (($userdata["role_id"] == 2) && ($userdata["class_teacher"] == "yes") && (empty($class_section_array))) {
            $class_section_array = $this->customlib->get_myClassSection();
        }
        
        $class_section_array = $this->customlib->get_myClassSection();
          if (!empty($class_section_array)) {
            $this->db->select('student_session.student_id')->from('student_session');
            $this->db->group_start();
            foreach ($class_section_array as $class_sectionkey => $class_sectionvalue) {
                foreach ($class_sectionvalue as $class_sectionvaluekey => $class_sectionvaluevalue) {
                    $this->db->or_group_start();
                    $this->db->where('student_session.class_id', $class_sectionkey);
                    $this->db->where('student_session.section_id', $class_sectionvaluevalue);
                    $this->db->group_end();
                }
            }
            $this->db->group_end();
            $query  = $this->db->get();
        $result = $query->result_array();
        $student_ids=array();
        foreach($result as $key=>$value){
            $student_ids[]=$value['student_id'];
        }
        return $student_ids;
        }else{
            return false;
        }    
    }
    
    public function coursereport($payment_type, $start_date, $end_date, $users_type)
    {
        $condition="" ;
        if($payment_type != 'all'){
            $condition.= "and online_course_payment.payment_type='".$payment_type."' " ;
        }
        if($users_type !='all'){

            if($_POST['users_type']=='student'){
                $condition.= "and online_course_payment.student_id !='Null'" ;
            }else{
                $condition.= "and online_course_payment.guest_id !='Null'" ;
            }
        }
        if($this->get_teacherstudents()){
            $student_ids=implode(",",$this->get_teacherstudents());
            $condition.= "and online_course_payment.student_id in(".$student_ids.") " ;
        }
        $sql="select online_courses.title, online_courses.course_provider, online_course_payment.*,online_course_payment.payment_type from online_courses inner join online_course_payment on online_course_payment.online_courses_id=online_courses.id where date_format(online_course_payment.date,'%Y-%m-%d') >='". $start_date."'  and date_format(online_course_payment.date,'%Y-%m-%d') <= '".$end_date."' and online_courses.status = '1' ".$condition ;
             $this->datatables->query($sql) 
              ->searchable('date,online_courses.title,online_courses.course_provider,payment_type,payment_mode,paid_amount')
              ->orderable('date,online_courses.title,online_courses.course_provider,payment_type,payment_mode,paid_amount')
              ->sort('date_format(online_course_payment.date, "%m/%e/%Y")','desc')
              ->query_where_enable(TRUE);
        return $this->datatables->generate('json');
    } 

    public function course_processingreport($users_type,$payment_type, $start_date, $end_date)
    {       
        $condition="" ;
        if($payment_type != 'all'){
            $condition.= "and online_course_processing_payment.payment_type='".$payment_type."' " ;
        }	
	
	   if($users_type=='guest'){
            $condition.= "and (online_course_processing_payment.guest_id IS NOT NULL and online_course_processing_payment.student_id IS NULL)" ;
	   }elseif($users_type=='student'){
            if($this->get_teacherstudents())
            {
                $student_ids=implode(",",$this->get_teacherstudents());
                $condition.= "and online_course_processing_payment.student_id in(".$student_ids.") " ;	
            }
            $condition.= "and online_course_processing_payment.guest_id IS NULL" ;
	   }      
       
        $sql="select online_courses.title, online_courses.course_provider, online_course_processing_payment.*,online_course_processing_payment.payment_type from online_courses inner join online_course_processing_payment on online_course_processing_payment.online_courses_id=online_courses.id  where date_format(online_course_processing_payment.date,'%Y-%m-%d') >='". $start_date."'  and date_format(online_course_processing_payment.date,'%Y-%m-%d') <= '".$end_date."' and online_courses.status = '1'  ".$condition ;
            $this->datatables->query($sql) 
            ->searchable('date,online_courses.title,online_courses.course_provider,payment_type,payment_mode,paid_amount')
            ->orderable('date,online_courses.title,online_courses.course_provider,payment_type,payment_mode,paid_amount')              
            ->sort('date_format(online_course_processing_payment.date, "%m/%e/%Y")','desc')
            ->query_where_enable(TRUE);
            return $this->datatables->generate('json');
    } 

    /*This is used to get data for seller report*/
    public function sellreport()
    {      
        $userdata            = $this->customlib->getUserData();
        if (($userdata["role_id"] == 2) && ($userdata["class_teacher"] == "yes") && (empty($class_section_array))) {
            $class_section_array = $this->customlib->get_myClassSection();
        }
        
        $class_section_array = $this->customlib->get_myClassSection();
        $this->datatables
            ->select('count(online_courses.id) as sell_count, online_courses.title, online_courses.created_by, staff.name, staff.surname, staff.employee_id,  online_course_payment.date,online_course_payment.online_courses_id,classes.class,s.name as assign_name,s.surname as assign_surname,s.employee_id as assign_employee_id,staff_roles.role_id')
            ->searchable('online_courses.title, online_courses.created_by, staff.name, staff.surname,  online_course_payment.date,online_course_payment.online_courses_id,classes.class')
             ->orderable('online_courses.title,classes.class," ",sell_count,assign_name, name')
            ->group_by('online_course_payment.online_courses_id')
            ->join('online_courses','online_courses.id = online_course_payment.online_courses_id')
            ->join('staff','staff.id=online_courses.created_by')			
			->join('staff as s', 's.id = online_courses.teacher_id')
            ->join('staff_roles','staff_roles.staff_id=staff.id')
			->join('online_course_class_sections', 'online_course_class_sections.course_id = online_courses.id')
			->join('class_sections', 'class_sections.id =  online_course_class_sections.class_section_id')
			->join('classes', 'classes.id = class_sections.class_id');	
            if (!empty($class_section_array)) {
            $this->datatables->group_start();

            foreach ($class_section_array as $class_sectionkey => $class_sectionvalue) {
                foreach ($class_sectionvalue as $class_sectionvaluekey => $class_sectionvaluevalue) {
                    $this->datatables->or_group_start();
                    $this->datatables->where('class_sections.class_id', $class_sectionkey);
                    $this->datatables->where('class_sections.section_id', $class_sectionvaluevalue);
                    $this->datatables->group_end();
                }
            }
            $this->datatables->group_end();
        }	
            $this->datatables->sort('sell_count','desc');
            $this->datatables->from('online_course_payment');
            $this->datatables->where(array('online_courses.status' =>'1'));
            return $this->datatables->generate('json');
    }

    /*This is used to show student list by purchasing course*/
    public function studentdata($courseid) {
        $this->datatables
            ->select('online_courses.title,online_course_payment.date,online_course_payment.online_courses_id,online_course_payment.paid_amount,online_course_payment.student_id,online_course_payment.guest_id')
            ->searchable('online_course_payment.guest_id,online_course_payment.student_id,online_course_payment.date')
            ->orderable('online_course_payment.guest_id,online_course_payment.student_id,online_course_payment.date')
            ->join('online_courses','online_courses.id = online_course_payment.online_courses_id')             
            ->where(array('online_course_payment.online_courses_id'=> $courseid, 'online_courses.status' =>'1'));
            $this->datatables->from('online_course_payment');
            return $this->datatables->generate('json');
    }

    /*This is used to get top trending course list */
    public function trendingreport() {
        
        $userdata = $this->customlib->getUserData();
		
        if (($userdata["role_id"] == 2) && ($userdata["class_teacher"] == "yes") && (empty($class_section_array))) {
            $class_section_array = $this->customlib->get_myClassSection();
        }
        
        $this->datatables
            ->select('online_courses.*,staff.name,staff.surname,staff.employee_id,s.name as assign_name,s.surname as assign_surname,s.employee_id as assign_employee_id,classes.class,staff_roles.role_id')
            ->searchable('online_courses.title, online_courses.created_by, staff.name, staff.surname,classes.class,view_count')
            ->orderable('online_courses.title,classes.class," ",view_count, online_courses.created_by, staff.name, staff.surname')
            ->join('staff','staff.id=online_courses.created_by')
			->join('staff as s', 's.id = online_courses.teacher_id')
            ->join('staff_roles','staff_roles.staff_id=staff.id')
			->join('online_course_class_sections', 'online_course_class_sections.course_id = online_courses.id')
			->join('class_sections', 'class_sections.id =  online_course_class_sections.class_section_id')
			->join('classes', 'classes.id = class_sections.class_id')
            ->sort('online_courses.view_count','desc');
            
            if (!empty($class_section_array)) {
                $this->datatables->group_start();
                foreach ($class_section_array as $class_sectionkey => $class_sectionvalue) {
                    foreach ($class_sectionvalue as $class_sectionvaluekey => $class_sectionvaluevalue) {
                        $this->datatables->or_group_start();
                        $this->datatables->where('class_sections.class_id', $class_sectionkey);
                        $this->datatables->where('class_sections.section_id', $class_sectionvaluevalue);
                        $this->datatables->group_end();

                    }
                }
                $this->datatables->group_end();
            }
            
			$this->datatables->group_by('online_courses.id')
            ->from('online_courses')
            ->where(array('online_courses.status' =>'1'));
            return $this->datatables->generate('json');
    }

    public function courselist($class_section_id, $users_type = null)
    {
        if($users_type == 'guest'){
            $this->db->where('online_courses.front_side_visibility','yes');
        }else{
            $this->db->join('online_course_class_sections','online_course_class_sections.course_id=online_courses.id');
            $this->db->where('online_course_class_sections.class_section_id',$class_section_id);
        }
        $this->db->select('online_courses.id,online_courses.title');
        $this->db->from('online_courses');
        $this->db->where('online_courses.status','1');
        $query = $this->db->get();
        return $query->result_array();
    }

    /*This is used to get student list by class_section_id and course id*/
    public function coursecompletereport($class_section_id)
    {
        $this->datatables
            ->select('students.id,students.firstname,students.lastname,students.middlename,students.admission_no')
            ->searchable('students.firstname, students.lastname,students.admission_no')
            ->orderable('students.firstname, students.admission_no," "')
            ->group_by('students.id')            
            ->join('student_session', 'student_session.class_id = class_sections.class_id and student_session.section_id = class_sections.section_id')
            ->join('students', 'student_session.student_id = students.id')           
            ->from('class_sections')
            ->where(array('class_sections.id' => $class_section_id));
            return $this->datatables->generate('json');
    }

    public function get_online_course_exam_list($course_id)
    {
        if($course_id!=""){
            $this->db->where('online_course_exam.course_id',$course_id); 
        }
        $this->db->select('online_course_exam.*');
        $this->db->from('online_course_exam');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_online_course_list()
    {
        $this->db->select('online_courses.*');
        $this->db->from('online_courses');
        $this->db->where('online_courses.status','1');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_online_course_students($exam_id,$course_id)
    {
       $this->current_session = $this->setting_model->getCurrentSession();
       $query=" SELECT count(case when online_courses.free_course=1 then 1 end) as total_free_course, count(case when online_courses.free_course=0 and (select online_course_payment.student_id from online_course_payment where online_course_payment.student_id=c.student_id and online_course_payment.online_courses_id=`b`.`course_id`) then 1 end) as total_paid_course FROM `class_sections` `a` JOIN `online_course_class_sections` `b` ON `b`.`class_section_id`=`a`.`id` JOIN `student_session` `c` ON `c`.`class_id`=`a`.`class_id` and `c`.`section_id`=`a`.`section_id` left join online_courses on online_courses.id=b.course_id WHERE `b`.`course_id` = $course_id AND `c`.`session_id` = $this->current_session";
        $data=$this->db->query($query);
        return $data->result_array();
    }
  
    public function get_exam_report_data($course_id=null)
    { 
        $class_section_array="";
        $condition="";
        if($course_id!="" || $course_id!=null){
          $condition=" and online_course_exam.course_id=$course_id"  ;
        }
        $query = "SELECT online_course_exam.*,
        (select count(*) from online_course_exam_marks where online_course_exam_marks.online_course_exam_id=online_course_exam.id) as questions,online_courses.free_course,online_courses.title
         FROM `online_course_exam` 
         left join online_courses on online_courses.id=online_course_exam.course_id
         where 0=0 " . $condition .$class_section_array. " ";
        $this->datatables->query($query)
        ->searchable('online_course_exam.exam,online_course_exam.attempt,online_course_exam.exam_from,online_course_exam.exam_to,online_course_exam.duration')
        ->orderable('online_course_exam.exam,online_course_exam.attempt,online_course_exam.exam_from,online_course_exam.exam_to,online_course_exam.duration,
            null,null,null,null,null,null') 
        ->query_where_enable(TRUE)
        ->sort('online_course_exam.id','asc') ;
        return $this->datatables->generate('json');
    }

    public function get_total_attempts($course_id=null)
    { 
        $condition="";
        if($course_id!="" || $course_id!=null){
          $condition=" and online_course_exam.course_id=$course_id"  ;
        }

        $query = "select student_session.session_id,students.id as studentid,guest.id as guestid,students.admission_no,students.firstname,students.middlename,students.lastname,guest.guest_name,guest.guest_unique_id,GROUP_CONCAT(DISTINCT
        online_course_exam.id,'@',online_course_exam.exam,'@',online_course_exam.exam_from,'@',online_course_exam.exam_to,'@',online_course_exam.duration,'@',
        online_course_exam.passing_percentage,'@',online_course_exam.is_active,'@',online_course_exam.publish_result,'@',online_courses.title,'@',online_course_exam.is_quiz) as exams,online_courses.title from online_course_exam_attempts 
        left join online_course_exam on online_course_exam.id=online_course_exam_attempts.exam_id 
        left join online_courses on online_courses.id=online_course_exam.course_id
        left join students on students.id=online_course_exam_attempts.student_id 
        left join student_session on student_session.student_id=students.id  
        left join guest on guest.id=online_course_exam_attempts.guest_id 
        where 0=0  $condition 
        group by online_courses.id,online_course_exam_attempts.student_id,online_course_exam_attempts.guest_id";   

        $this->datatables->query($query)
        ->searchable('online_course_exam.exam,online_course_exam.attempt,online_course_exam.exam_from,online_course_exam.exam_to,online_course_exam.duration')
        ->orderable('online_course_exam.exam,online_course_exam.attempt,online_course_exam.exam_from,online_course_exam.exam_to,online_course_exam.duration,null,null,null') 
        ->query_where_enable(TRUE)
        ->sort('online_course_exam.id','asc') ;
        return $this->datatables->generate('json');
    }

    public function get_online_course_result($exam_id)
    {
         $this->datatables
            ->select('(select count(*) from online_course_exam_attempts where online_course_exam_attempts.student_id=`online_course_exam_result`.`student_id` and online_course_exam_attempts.guest_id=`online_course_exam_result`.`guest_id`) as total_used_attempmts,`online_course_exam_result`.`student_id`, `online_course_exam_result`.`guest_id`,students.firstname,students.middlename,students.lastname,students.admission_no,guest.guest_unique_id, 
            guest.guest_name,online_course_exam.attempt,online_course_exam_result.created_at,online_course_exam_marks.online_course_exam_id as exam_id')
            ->searchable('students.firstname,students.admission_no,online_course_exam_result.created_at')
            ->orderable('students.firstname,students.admission_no,online_course_exam.attempt,total_used_attempmts,online_course_exam_result.created_at')
            ->group_by('online_course_exam_result.student_id')
            ->group_by('online_course_exam_result.guest_id')
            ->join("students","students.id=online_course_exam_result.student_id","left")
            ->join("guest","guest.id=online_course_exam_result.guest_id","left")
            ->join('online_course_exam_marks','online_course_exam_marks.id=online_course_exam_result.question_id','left')
            ->join('online_course_exam','online_course_exam.id=online_course_exam_marks.online_course_exam_id','left')   
            ->from('online_course_exam_result')
            ->where("online_course_exam_marks.online_course_exam_id",$exam_id);
            return $this->datatables->generate('json');
    }

    public function online_course_exam_result($type,$studentid_or_guestid,$exam_id)
    {  
        $this->db->select("online_course_exam_marks.*,online_course_exam_result.id as `online_course_student_result_id`,online_course_exam_result.marks as `score_marks`,online_course_exam_result.attachment_name,online_course_exam_result.attachment_upload_name,online_course_exam_result.remark,         
        online_course_tag.tag_name,online_course_exam_question.question,online_course_exam_question.question_type,online_course_exam_question.opt_a, 
        online_course_exam_question.opt_b,online_course_exam_question.opt_c,online_course_exam_question.opt_d,online_course_exam_question.opt_e,
        online_course_exam_question.correct,online_course_exam_result.select_option")->from('online_course_exam_marks');
        if($type=='student'){
            $this->db->join('online_course_exam_result',"online_course_exam_result.question_id=online_course_exam_marks.id and online_course_exam_result.student_id=$studentid_or_guestid","left");
        }else if($type=='guest'){
            $this->db->join('online_course_exam_result',"online_course_exam_result.question_id=online_course_exam_marks.id and online_course_exam_result.guest_id=$studentid_or_guestid","left");
        }
        $this->db->join('online_course_exam_question','online_course_exam_question.id=online_course_exam_marks.`question_id`',"left");
        $this->db->join('online_course_tag', 'online_course_tag.id=online_course_exam_question.question_tag',"left");
        $this->db->where('online_course_exam_marks.online_course_exam_id',$exam_id);
        $query = $this->db->get();
        return $query->result();
    }


}