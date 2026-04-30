<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Coursesection_model extends MY_Model {

    public function __construct() {
        parent::__construct();
    }

    /*
    This used to add or edit section
    */
	public function add($data)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        if (isset($data['id'])) {
            $this->db->where('id', $data['id']);
            $this->db->update('online_course_section', $data);
            $message   = UPDATE_RECORD_CONSTANT . " On online course section id " . $data['id'];
            $action    = "Update";
            $record_id = $id = $data['id'];
            $this->log($message, $record_id, $action);
        } else {
            $this->db->insert('online_course_section', $data);
            $id        = $this->db->insert_id();
            $message   = INSERT_RECORD_CONSTANT . " On online course section id " . $id;
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
    This is used to get section by course
    */
    public function getsectionbycourse($id) {
        $this->db->select('online_course_section.*,online_courses.title');
        $this->db->from('online_course_section');
        $this->db->where('online_course_section.online_course_id',$id);
        $this->db->join('online_courses','online_courses.id=online_course_section.online_course_id');
        $this->db->order_by('online_course_section.order', 'asc');
        $query = $this->db->get();
        return $query->result();
    }

    /*
     This is used to get lesson by section
    */
    public function getlessonbysection($id) {
        $this->db->select('*');
        $this->db->from('online_course_lesson');
        $this->db->where('online_section_id',$id);
        $query = $this->db->get();
        return $query->result_array();
    }

    /*
    This is used to get single section
    */
    public function getsinglesection($id) {
        $this->db->select('*');
        $this->db->from('online_course_section');
        $this->db->where('id',$id);
        $query = $this->db->get();
        return $query->row();
    }
    
    /*
    This is used to delete section
    */	
	public function remove($id)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        $this->db->where('id',$id);
        $this->db->delete('online_course_section');
        $message   = DELETE_RECORD_CONSTANT . " On online course section id " . $id;
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
    This is used to get section count based on the course id
    */
    public function getsectioncount($id){
         
        $this->db->select('count(*) as total_section');
        $this->db->from('online_course_section');
        $this->db->where('online_course_id',$id);
        $this->db->join('online_courses','online_courses.id=online_course_section.online_course_id');
        $query = $this->db->get();
        $result=$query->row_array();
        return $result['total_section'];
    }

    /*
    This is used to add or edit lesson quiz order
    */    
    public function addlessonquizorder($data)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        if (isset($data['id'])) {
            $this->db->where('id', $data['id']);
            $this->db->update('course_lesson_quiz_order', $data);
            $message   = UPDATE_RECORD_CONSTANT . " On lesson quiz id " . $data['id'];
            $action    = "Update";
            $record_id = $id = $data['id'];
            $this->log($message, $record_id, $action);
        } else {
            $this->db->insert('course_lesson_quiz_order', $data);
            $id        = $this->db->insert_id();
            $message   = INSERT_RECORD_CONSTANT . " On lesson quiz id " . $id;
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
    This is used to get quiz and lesson section id
    */

 public function lessonquizbysection($sectionid){         
        $this->db->select('online_course_lesson.id as lesson_id,online_course_lesson.lesson_title,online_course_lesson.lesson_type,online_course_lesson.thumbnail,online_course_lesson.summary,online_course_lesson.attachment,online_course_lesson.video_provider,online_course_lesson.video_url,online_course_lesson.video_id,online_course_lesson.duration,online_course_quiz.id as quiz_id,online_course_quiz.quiz_title,course_lesson_quiz_order.id,course_lesson_quiz_order.type,course_lesson_quiz_order.order,online_course_assignment.assignment_title,online_course_assignment.assignment_date,online_course_assignment.submit_date,online_course_assignment.marks,online_course_assignment.description,online_course_assignment.document,online_course_assignment.course_section_id,online_course_assignment.evaluated_by,online_course_assignment.id as course_assignment_id, online_course_exam.id as course_exam_id,online_course_exam.exam as course_exam_name,online_course_exam.exam_from,online_course_exam.exam_to,online_course_exam.duration as exam_duration,online_course_exam.passing_percentage,online_course_exam.description as exam_description,online_course_exam.is_quiz,online_course_exam.is_active');
        $this->db->from('online_course_section');
        $this->db->where('online_course_section.id',$sectionid);
        $this->db->join('course_lesson_quiz_order','course_lesson_quiz_order.course_section_id=online_course_section.id','left');
        $this->db->join('online_course_quiz','online_course_quiz.id=course_lesson_quiz_order.lesson_quiz_id','left');
        $this->db->join('online_course_lesson','online_course_lesson.id=course_lesson_quiz_order.lesson_quiz_id','left');
        $this->db->join('online_course_assignment','online_course_assignment.id=course_lesson_quiz_order.lesson_quiz_id','left');
        $this->db->join('online_course_exam','online_course_exam.id=course_lesson_quiz_order.lesson_quiz_id','left');
        $this->db->order_by('course_lesson_quiz_order.order','asc');
        $query = $this->db->get();
        return $query->result_array();
    }

    /*
    This is used to delete quiz lesson
    */  
    public function deletequizlesson($id,$type)
    {
        $this->db->trans_start(); # Starting Transaction
        $this->db->trans_strict(false); # See Note 01. If you wish can remove as well
        //=======================Code Start===========================
        $this->db->where('lesson_quiz_id',$id);
        $this->db->where('type',$type);
        $this->db->delete('course_lesson_quiz_order');
        $message   = DELETE_RECORD_CONSTANT . " On online course lesson quiz id " . $id;
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
     This function is used to delete section and their lesson, quiz question, quiz 
     */
    public function delete($sectionid)
    {
        $this->db->where("course_section_id", $sectionid)->delete("course_lesson_quiz_order");
        $this->db->where("course_section_id", $sectionid)->delete("online_course_lesson");
        $this->db->where("course_section_id", $sectionid)->delete("course_progress");
        $this->db->where("course_section_id", $sectionid)->delete("online_course_exam");
        $this->db->where("course_section_id", $sectionid)->delete("online_course_assignment");
        $quiz_query  = $this->db->where("course_section_id", $sectionid)->get('online_course_quiz');
        $quiz_result = $quiz_query->result_array();
            foreach ($quiz_result as $key => $quiz_result_value) {
                $quizid = $quiz_result_value['id'];
                $this->db->where("course_quiz_id", $quizid)->delete("student_quiz_status");
                $this->db->where("course_quiz_id", $quizid)->delete("course_quiz_answer");
                $this->db->where("course_quiz_id", $quizid)->delete("course_quiz_question");
            }
        $this->db->where("course_section_id", $sectionid)->delete("online_course_quiz");
        $this->db->where("id", $sectionid)->delete("online_course_section");
    }

    //==========
    public function getLessonAttachmentListBySectionId($sectionid){
        $this->db->select('`online_course_lesson_attachment`.*,online_course_lesson.course_section_id');
        $this->db->from('online_course_section');
        $this->db->join('online_course_lesson', 'online_course_lesson.course_section_id=online_course_section.id',"left");
        $this->db->join('online_course_lesson_attachment', 'online_course_lesson_attachment.lesson_id=online_course_lesson.id',"left");
        $this->db->where('online_course_section.id',$sectionid);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function getLessonthumbnailListBySectionId($sectionid){
        $this->db->select('`online_course_lesson`.*');
        $this->db->from('online_course_section');
        $this->db->join('online_course_lesson', 'online_course_lesson.course_section_id=online_course_section.id',"left");
        $this->db->where('online_course_section.id',$sectionid);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function getAssignmentBySectionId($online_courses_id)
    {
        $this->db->select('online_course_assignment.*');
        $this->db->from('online_course_section');
        $this->db->join('online_course_assignment',"online_course_assignment.course_section_id=online_course_section.id","left");
        $this->db->where('online_course_section.id',$online_courses_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function getSubmitAssignmentBySectionId($sectionid)
    {
        $this->db->select('online_course_assignment_submit.*');
        $this->db->from('online_course_section');
        $this->db->join('online_course_assignment',"online_course_assignment.course_section_id=online_course_section.id","left");
        $this->db->join('online_course_assignment_submit',"online_course_assignment_submit.assignment_id=online_course_assignment.id","left");
        $this->db->where('online_course_section.id',$sectionid);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function getExamAttachmentBySectionId($sectionid){
        $this->db->select('online_course_exam_result.*');
        $this->db->from('online_course_section');
        $this->db->join('online_course_exam', 'online_course_exam.course_section_id=online_course_section.id');
        $this->db->join('online_course_exam_marks', 'online_course_exam_marks.online_course_exam_id=online_course_exam.id');
        $this->db->join('online_course_exam_result', 'online_course_exam_result.question_id=online_course_exam_marks.id');
        $this->db->where('online_course_section.id',$sectionid);
        $query = $this->db->get();
        return $query->result_array();
    }
    //==========






}