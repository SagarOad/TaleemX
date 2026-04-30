<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Courseassignment_model extends MY_Model {

    protected $current_session;
    public function __construct() {
        parent::__construct();
    }

    public function add_course_assignment($data)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        if (isset($data["id"]) && $data["id"] > 0) {
            $this->db->where("id", $data["id"])->update("online_course_assignment", $data);
            $message   = UPDATE_RECORD_CONSTANT . " On online_course_assignment id " . $data['id'];
            $action    = "Update";
            $record_id = $insert_id = $data['id'];
            $this->log($message, $record_id, $action);
        } else {
            $this->db->insert("online_course_assignment", $data);
            $insert_id = $this->db->insert_id();
            $message   = INSERT_RECORD_CONSTANT . " On online_course_assignment id " . $insert_id;
            $action    = "Insert";
            $record_id = $insert_id;
            $this->log($message, $record_id, $action);
        }
        //======================Code End==============================
        $this->db->trans_complete(); # Completing transaction
        if ($this->db->trans_status() === false) {
            # Something went wrong.
            $this->db->trans_rollback();
            return false;
        } else {
            return $insert_id;
        } 
    }

    public function getassignment($id)
    {
        $this->db->select('online_course_assignment.*,a.role_id as created_by_role,b.role_id as evaluated_by_role,concat(create_by.name," ",create_by.surname," (",create_by.employee_id,")") as assignemnt_created_by,concat(evaluate_by.name," ",evaluate_by.surname," (",evaluate_by.employee_id,")") as assignemnt_evaluated_by');
        $this->db->from('online_course_assignment');
        $this->db->join('staff as create_by',"create_by.id=online_course_assignment.created_by","left");
        $this->db->join('staff as evaluate_by',"evaluate_by.id=online_course_assignment.evaluated_by","left");
        $this->db->join('staff_roles as a','a.staff_id=create_by.id',"left");
        $this->db->join('staff_roles as b','b.staff_id=evaluate_by.id',"left");
        $this->db->where('online_course_assignment.id',$id);
        $query = $this->db->get();
        return $query->row();
    }

    public function getAssignmentByCourseId($online_courses_id)
    {
        $this->db->select('online_course_assignment.*');
        $this->db->from('online_courses');
        $this->db->join('online_course_section',"online_courses.id=online_course_section.online_course_id","left");
        $this->db->join('online_course_assignment',"online_course_assignment.course_section_id=online_course_section.id","left");
        $this->db->where('online_courses.id',$online_courses_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function getSubmitAssignmentByCourseId($online_courses_id)
    {
        $this->db->select('online_course_assignment_submit.*');
        $this->db->from('online_courses');
        $this->db->join('online_course_section',"online_courses.id=online_course_section.online_course_id","left");
        $this->db->join('online_course_assignment',"online_course_assignment.course_section_id=online_course_section.id","left");
        $this->db->join('online_course_assignment_submit',"online_course_assignment_submit.assignment_id=online_course_assignment.id","left");
        $this->db->where('online_courses.id',$online_courses_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function remove($id)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        $this->db->where('id', $id);
        $this->db->delete('online_course_assignment');
        $message   = DELETE_RECORD_CONSTANT . " On online_course_assignment id " . $id;
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
  
    public function check_assignment($assignment_id, $studentid_or_guestid)
    {
        $userdata = $this->customlib->getLoggedInUserData();
        if($userdata["role"]=="student"){
            $this->db->where('online_course_assignment_submit.student_id',$studentid_or_guestid);
        }else if($userdata["role"]=="guest"){
            $this->db->where('online_course_assignment_submit.guest_id',$studentid_or_guestid);
        }
        $this->db->select('*');
        $this->db->from('online_course_assignment_submit');
        $this->db->where(array('assignment_id' => $assignment_id));
        $status =  $this->db->get();
        return $status->row_array();
    }
    
    public function upload_docs($data)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        if (isset($data["id"]) && $data["id"] > 0) {
            $this->db->where("id", $data["id"])->update("online_course_assignment_submit", $data);
            $message   = UPDATE_RECORD_CONSTANT . " On online_course_assignment_submit id " . $data['id'];
            $action    = "Update";
            $record_id = $insert_id = $data['id'];
            $this->log($message, $record_id, $action);
        } else {
            $this->db->insert("online_course_assignment_submit", $data);
            $insert_id = $this->db->insert_id();
            $message   = INSERT_RECORD_CONSTANT . " On online_course_assignment_submit id " . $insert_id;
            $action    = "Insert";
            $record_id = $insert_id;
            $this->log($message, $record_id, $action);
        }
        //======================Code End==============================
        $this->db->trans_complete(); # Completing transaction
        if ($this->db->trans_status() === false) {
            # Something went wrong.
            $this->db->trans_rollback();
            return false;
        } else {
            return $insert_id;
        } 
    }

    public function get_student_assignment_status($assignment_id,$studentid_or_guestid)
    {
        $userdata = $this->customlib->getLoggedInUserData();
        if($userdata["role"]=="student"){
            $this->db->where('online_course_assignment_submit.student_id',$studentid_or_guestid);
        }else if($userdata["role"]=="guest"){
            $this->db->where('online_course_assignment_submit.guest_id',$studentid_or_guestid);
        }
        $this->db->select('*,(select `online_course_assignment_evaluation`.`date` from online_course_assignment_evaluation where 
            online_course_assignment_evaluation.assignment_id = online_course_assignment_submit.assignment_id and 
            online_course_assignment_evaluation.guest_id=online_course_assignment_submit.guest_id and 
            online_course_assignment_evaluation.student_id=online_course_assignment_submit.student_id) as evaluated_date,(select `online_course_assignment_evaluation`.`note` from online_course_assignment_evaluation where 
            online_course_assignment_evaluation.assignment_id = online_course_assignment_submit.assignment_id and 
            online_course_assignment_evaluation.guest_id=online_course_assignment_submit.guest_id and 
            online_course_assignment_evaluation.student_id=online_course_assignment_submit.student_id) as evaluated_note');
        $this->db->from('online_course_assignment_submit');
        $this->db->where('online_course_assignment_submit.assignment_id', $assignment_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_student_assignment($assignment_id)
    {
        $this->db->select('online_course_assignment_submit.id,guest.guest_unique_id,guest.id as guest_id,guest.guest_name,students.firstname,students.middlename,students.lastname,students.admission_no,IFNULL(online_course_assignment_evaluation.id,0) as assignment_evaluation_id,
            online_course_assignment_evaluation.note,online_course_assignment_evaluation.marks as evaluate_marks,
            students.id as student_id,online_course_assignment_submit.assignment_id,online_course_assignment_submit.message,online_course_assignment_submit.docs');
        $this->db->from('online_course_assignment_submit');
        $this->db->join('online_course_assignment_evaluation', 'online_course_assignment_evaluation.assignment_id=online_course_assignment_submit.assignment_id 
                        and online_course_assignment_evaluation.student_id=online_course_assignment_submit.student_id 
                        and online_course_assignment_evaluation.guest_id=online_course_assignment_submit.guest_id','left');
        $this->db->join('students', 'students.id=online_course_assignment_submit.student_id','left');
        $this->db->join('guest', 'guest.id=online_course_assignment_submit.guest_id','left');
        $this->db->where('online_course_assignment_submit.assignment_id', $assignment_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function add_evaluation($insert_array,$update_array,$delete_array,$evaluation_date,$evaluated_by,$assignment_id)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        $assignment_data = array('evaluation_date' => $evaluation_date, 'evaluated_by' => $evaluated_by);
        $this->db->where("id", $assignment_id)->update("online_course_assignment", $assignment_data);

        if(!empty($insert_array)){
            foreach ($insert_array as $insert_student_key => $insert_student_value) {
                $insert_student = array(
                    'assignment_id'      => $assignment_id,
                    'note'               => $insert_student_value['note'],
                    'marks'              => $insert_student_value['marks'],
                    'student_id'         => $insert_student_value['student_id'],
                    'guest_id'           => $insert_student_value['guest_id'],
                    'date'               => $evaluation_date,
                );
                $this->db->insert("online_course_assignment_evaluation", $insert_student);
                $action    = "Insert";
                $record_id = $this->db->insert_id();
                $message   = INSERT_RECORD_CONSTANT . " On online_course_assignment_evaluation id " . $record_id;
                $this->log($message, $record_id, $action);
            }
        }

        if (!empty($update_array)) {
            foreach ($update_array as $parameter_key => $row) {
                $update_student = array(
                    'note'  => $row['note'],
                    'marks' => $row['marks'],
                );
                $this->db->where("id", $row['id'])->update("online_course_assignment_evaluation", $update_student);
                $action    = "Update";
                $record_id = $row['id'];
                $message   = UPDATE_RECORD_CONSTANT . " On online_course_assignment_evaluation id " .  $record_id;
                $this->log($message, $record_id, $action);
            }
        }

        if (!empty($delete_array)) {
            foreach ($delete_array as $key => $val) {
                $this->db->where('assignment_id', $assignment_id);
                $this->db->where('id',$val['id']);
                $this->db->delete('online_course_assignment_evaluation');
                $action    = "Delete";
                $record_id = $val['id'];
                $message   = DELETE_RECORD_CONSTANT . " On online_course_assignment_evaluation id " .  $record_id;
                $this->log($message, $record_id, $action);
            }
        }
        //======================Code End==============================
        $this->db->trans_complete(); # Completing transaction
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        } else {
            return $record_id;
        } 
    }

    public function get_course_assignment_report()
    {       
        $this->datatables
            ->select('online_course_assignment.*,
                concat(a.name," ",a.surname," (",a.employee_id,")") as assignemnt_created_by,
                concat(b.name," ",b.surname," (",b.employee_id,")") as assignemnt_evaluated_by,
                a_role.role_id as created_by_role_id,
                b_role.role_id as evaluated_by_role_id,
                online_course_section.section_title,online_courses.title,online_courses.id as courseid,online_courses.free_course')  
            ->join('online_course_section','online_course_section.id=online_course_assignment.course_section_id','left')    
            ->join('online_courses','online_courses.id=online_course_section.online_course_id','left')
            ->join('staff as a','a.id=online_course_assignment.created_by','left') 
            ->join('staff as b','b.id=online_course_assignment.evaluated_by','left') 
            ->join('staff_roles as a_role','a_role.staff_id=a.id','left') 
            ->join('staff_roles as b_role','b_role.staff_id=b.id','left') 
            ->searchable('online_course_assignment.description,online_course_assignment.assignment_date,online_course_assignment.submit_date,
                 online_course_assignment.marks,online_course_section.section_title,online_courses.title,"","","","","","",""')
            ->orderable('online_course_assignment.description,online_course_assignment.assignment_date,online_course_assignment.submit_date,
                 online_course_assignment.marks,online_course_section.section_title,online_courses.title,"","","","","","",""')   
            ->from('online_course_assignment');
        return $this->datatables->generate('json');        
    } 

    public function get_course_students($online_course_id)
    {
		if($online_course_id > 0){
			$query=" SELECT (count(case when online_courses.free_course=1 then 1 end) + count(case when online_courses.free_course=0 and (select online_course_payment.student_id from online_course_payment where online_course_payment.student_id=c.student_id and online_course_payment.online_courses_id=`b`.`course_id`) then 1 end) ) as total_students
			FROM `class_sections` `a` 
			JOIN `online_course_class_sections` `b` ON `b`.`class_section_id`=`a`.`id` 
			JOIN `student_session` `c` ON `c`.`class_id`=`a`.`class_id` and `c`.`section_id`=`a`.`section_id` 
			left join online_courses on online_courses.id=b.course_id 
			WHERE `b`.`course_id` = $online_course_id
			and c.student_id in (select course_progress.student_id from course_progress where course_progress.course_id=$online_course_id)";
			$data=$this->db->query($query);
			return $data->result_array();
		}else{
			return array();
		}
    }

    public function get_total_guests($online_course_id,$free_course)
    {
		if($online_course_id > 0){
        if($free_course==1){
            $query="select count(*) as total_guest from guest where is_active='yes' and guest.id in (select course_progress.guest_id from course_progress where  course_progress.course_id=$online_course_id)";
            $data=$this->db->query($query);
            return $data->result_array();
        }else if($free_course==0){
            $query="select count(*) as total_guest from guest left join online_course_payment on online_course_payment.guest_id=guest.id 
            where is_active='yes' and online_course_payment.online_courses_id=$online_course_id";
            $data=$this->db->query($query);
            return $data->result_array();
        }}else{
			 return array();
		}
    }
 
    public function get_course_student_list($courseid,$assignment_id,$free_course)
    {
        
         if($free_course==1){
            $this->current_session = $this->setting_model->getCurrentSession();
            $query=" SELECT 
            classes.class as class_name,students.firstname,students.middlename,students.lastname,students.admission_no,
            (select `online_course_assignment_submit`.`created_at`  from online_course_assignment_submit where online_course_assignment_submit.assignment_id=$assignment_id and online_course_assignment_submit.student_id=students.id) as submit_date,
            (select `online_course_assignment_evaluation`.`id`  from online_course_assignment_evaluation where online_course_assignment_evaluation.assignment_id=$assignment_id and online_course_assignment_evaluation.student_id=students.id) as is_evaluated,
            (select `online_course_assignment_evaluation`.`date` from online_course_assignment_evaluation where online_course_assignment_evaluation.student_id=students.id and online_course_assignment_evaluation.assignment_id=$assignment_id) as evaluate_date      
            FROM `class_sections` `a` 
            JOIN `online_course_class_sections` `b` ON `b`.`class_section_id`=`a`.`id` 
            JOIN `student_session` `c` ON `c`.`class_id`=`a`.`class_id` and `c`.`section_id`=`a`.`section_id` 
            left join classes on a.class_id=classes.id
            join students on students.id = c.student_id
            left join online_courses on online_courses.id=b.course_id 
            WHERE `b`.`course_id` = $courseid  and online_courses.free_course=$free_course  
            and students.id in (select course_progress.student_id from course_progress where course_progress.course_id=$courseid)";
            $data=$this->db->query($query);
            return $data->result_array();
        }else if($free_course==0){
            $this->current_session = $this->setting_model->getCurrentSession();
            $query=" SELECT classes.class as class_name,students.firstname,students.middlename,students.lastname,students.admission_no, 
            (select `online_course_assignment_submit`.`created_at` from online_course_assignment_submit where online_course_assignment_submit.assignment_id=$assignment_id and online_course_assignment_submit.student_id=students.id) as submit_date, 
            (select `online_course_assignment_evaluation`.`id` from online_course_assignment_evaluation where online_course_assignment_evaluation.assignment_id=$assignment_id and online_course_assignment_evaluation.student_id=students.id) as is_evaluated, 
            (select `online_course_assignment_evaluation`.`date` from online_course_assignment_evaluation where online_course_assignment_evaluation.student_id=students.id and online_course_assignment_evaluation.assignment_id=$assignment_id) as evaluate_date FROM `class_sections` `a` 
            JOIN `online_course_class_sections` `b` ON `b`.`class_section_id`=`a`.`id` 
            JOIN `student_session` `c` ON `c`.`class_id`=`a`.`class_id` and `c`.`section_id`=`a`.`section_id` 
            left join classes on a.class_id=classes.id 
            join students on students.id = c.student_id 
            left join online_courses on online_courses.id=b.course_id 
            WHERE `b`.`course_id` = $courseid  and online_courses.free_course=$free_course 
            and students.id in ( select online_course_payment.student_id from online_course_payment where  `online_course_payment`.`online_courses_id`=$courseid )
            and students.id in (select course_progress.student_id from course_progress where course_progress.course_id=$courseid)";
            $data=$this->db->query($query);
            return $data->result_array();
        }
    }

    public function get_course_guest_list($courseid,$assignment_id,$free_course)
    {
        if($free_course==1){
            $query="select *,
            (select `online_course_assignment_submit`.`created_at`  from online_course_assignment_submit where online_course_assignment_submit.guest_id=guest.id and online_course_assignment_submit.assignment_id=$assignment_id) as submit_date,
            (select `online_course_assignment_evaluation`.`id` from online_course_assignment_evaluation where online_course_assignment_evaluation.guest_id=guest.id and online_course_assignment_evaluation.assignment_id=$assignment_id) as is_evaluated,
            (select `online_course_assignment_evaluation`.`date` from online_course_assignment_evaluation where online_course_assignment_evaluation.guest_id=guest.id and online_course_assignment_evaluation.assignment_id=$assignment_id) as evaluate_date            
            from guest where is_active='yes'  and guest.id in (select course_progress.guest_id from course_progress where  course_progress.course_id=$courseid)";
            $data=$this->db->query($query);
            return $data->result_array();
        }else if($free_course==0){
            $query="select *,
            (select `online_course_assignment_submit`.`created_at` from online_course_assignment_submit where online_course_assignment_submit.guest_id=guest.id and online_course_assignment_submit.assignment_id=$assignment_id) as submit_date,
            (select `online_course_assignment_evaluation`.`id` from online_course_assignment_evaluation where online_course_assignment_evaluation.guest_id=guest.id and online_course_assignment_evaluation.assignment_id=$assignment_id) as is_evaluated,
            (select `online_course_assignment_evaluation`.`date` from online_course_assignment_evaluation where online_course_assignment_evaluation.guest_id=guest.id and online_course_assignment_evaluation.assignment_id=$assignment_id) as evaluate_date
             from guest 
            left join online_course_payment on online_course_payment.guest_id=guest.id 
            where is_active='yes' and online_course_payment.online_courses_id=$courseid";
            $data=$this->db->query($query);
            return $data->result_array();
        }
    }

    public function get_assignment_submitted($assignment_id,$courseid)
    {
		if($assignment_id > 0 && $courseid >0){
			$this->current_session = $this->setting_model->getCurrentSession();
			$this->db->select("
			count(case when online_course_assignment_submit.student_id!=0 AND `student_session`.`session_id` = $this->current_session then 1 end) as total_student_submitted,
			count(case when online_course_assignment_submit.guest_id!=0 then 1 end) as total_guest_submitted,
			(select count(*) from online_course_assignment_evaluation  
			where online_course_assignment_evaluation.student_id!=0
			and online_course_assignment_evaluation.assignment_id=$assignment_id) as total_student_eval,
			(select count(*) from online_course_assignment_evaluation 
			where online_course_assignment_evaluation.guest_id!=0
			and online_course_assignment_evaluation.assignment_id=$assignment_id) as total_guest_eval");
			$this->db->from('online_course_assignment_submit');
			$this->db->join('students', 'students.id=online_course_assignment_submit.student_id', 'left');
			$this->db->join('guest', "guest.id=online_course_assignment_submit.guest_id and guest.id in (select course_progress.guest_id from course_progress where course_progress.course_id=$courseid)", 'left');
			$this->db->join("student_session", "student_session.student_id = online_course_assignment_submit.student_id", 'left');
			$this->db->join("classes", "classes.id = student_session.class_id", 'left');
			$this->db->join("sections", "sections.id = student_session.section_id", 'left');
			$this->db->where("online_course_assignment_submit.assignment_id=$assignment_id ");
			$query = $this->db->get();
			return $query->result_array();
		}else{
			return array();
		}
    }

    public function check_valid_marks($str)
    {
        $this->form_validation->set_message('check_valid_marks', $this->lang->line('enter_valid_marks'));
        return false;
    }

    public function download_submit_assignment($id)
    {
        $this->db->select('*');
        $this->db->from('online_course_assignment_submit');
        $this->db->where(array('id' => $id));
        $query =  $this->db->get();
        return $query->row();
    }

}