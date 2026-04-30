<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Qrsetting_model extends MY_Model
{


    private $default_time_range = 300; //in seconds
    protected $current_session;
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('studentAttendaceSetting_model', 'staffAttendaceSetting_model'));
        $this->current_session = $this->setting_model->getCurrentSession();
    }



    public function qrcode_attendance($admission_no, $date)
    {
        $sql    = "SELECT staff.id,staff.employee_id,staff.name,null as `middlename`,staff.surname,'staff' as `table_type`,Null as class_id,Null as class ,Null as section_id, null as section, null as roll_no, null as admission_date, staff.image as student_image, contact_no as  `mobile_no`, email,  null as admission_no , father_name, mother_name, null as guardian_name,null as dob, `staff_designation`.`designation`, `department`.`department_name` as `department`, `roles`.`id` as `role_id`, `roles`.`name` as `role`,staff.gender,IFNULL(staff_attendance.id, 0 ) as `attendance_id`,staff.dob,IFNULL(staff_attendance_type.long_lang_name, 0 ) as `long_lang_name`,staff_attendance.in_time,staff_attendance.out_time,null as `class_section_id` FROM `staff` LEFT JOIN `staff_designation` ON `staff_designation`.`id` = `staff`.`designation` LEFT JOIN `staff_roles` ON `staff_roles`.`staff_id` = `staff`.`id` LEFT JOIN `roles` ON `roles`.`id` = `staff_roles`.`role_id` LEFT JOIN `department` ON `department`.`id` = `staff`.`department` LEFT JOIN `staff_attendance` ON `staff_attendance`.`staff_id` = `staff`.`id` and staff_attendance.date='" . $date . "' LEFT JOIN `staff_attendance_type` ON `staff_attendance_type`.`id` = `staff_attendance`.`staff_attendance_type_id`  WHERE employee_id=" . $this->db->escape($admission_no) . " UNION SELECT students.id, student_session.id as `student_session_id`, students.firstname, students.middlename, students.lastname, 'student' as `table_type`, classes.id as `class_id`, classes.class, sections.id as `section_id`, sections.section, students.roll_no, students.admission_date, students.image,students.mobileno,students.email,students.admission_no,father_name,mother_name,guardian_name,dob, null as designation,  null as  department,  null as role_id, null as role,students.gender,IFNULL(student_attendences.id,0) as `attendance_id`,students.dob,IFNULL(attendence_type.long_lang_name, 0 ) as `long_lang_name`,student_attendences.in_time,student_attendences.out_time,class_sections.id as `class_section_id` FROM `students` JOIN `student_session` ON `student_session`.`student_id` = `students`.`id` JOIN `classes` ON `student_session`.`class_id` = `classes`.`id` JOIN `sections` ON `sections`.`id` = `student_session`.`section_id` inner join class_sections on class_sections.class_id=classes.id and class_sections.section_id=sections.id LEFT JOIN `hostel_rooms` ON `hostel_rooms`.`id` = `students`.`hostel_room_id` LEFT JOIN `hostel` ON `hostel`.`id` = `hostel_rooms`.`hostel_id` LEFT JOIN `room_types` ON `room_types`.`id` = `hostel_rooms`.`room_type_id` LEFT JOIN `vehicle_routes` ON `vehicle_routes`.`id` = `student_session`.`vehroute_id` LEFT JOIN `route_pickup_point` ON `route_pickup_point`.`id` = `student_session`.`route_pickup_point_id` LEFT JOIN `pickup_point` ON `route_pickup_point`.`pickup_point_id` = `pickup_point`.`id` LEFT JOIN `transport_route` ON `vehicle_routes`.`route_id` = `transport_route`.`id` LEFT JOIN `vehicles` ON `vehicles`.`id` = `vehicle_routes`.`vehicle_id` LEFT JOIN `school_houses` ON `school_houses`.`id` = `students`.`school_house_id` LEFT JOIN `users` ON `users`.`user_id` = `students`.`id` LEFT JOIN `student_attendences` ON `student_attendences`.`student_session_id` = `student_session`.`id` and student_attendences.date='" . $date . "' LEFT JOIN `attendence_type` ON `attendence_type`.`id` = `student_attendences`.`attendence_type_id`  WHERE `student_session`.`session_id` = '" . $this->current_session . "' AND `users`.`role` = 'student' AND `students`.`is_active` = 'yes' AND `students`.`admission_no` = " . $this->db->escape($admission_no);


        $query  = $this->db->query($sql);
        if ($query->num_rows() > 0) {
            $result = $query->row();
            return $result;
        } else {
            return false;
        }
    }

    public function getQRAttendanceStudentAttendanceType($id = null)
    {
        $this->db->select('attendence_type.*')->from('attendence_type');

        $this->db->where('for_qr_attendance', 1);
        $this->db->order_by('id');

        $query = $this->db->get();
        if ($id != null) {
            return $query->row_array();
        } else {
            return $query->result_array();
        }
    }


    public function getQRAttendanceStaffAttendanceType($id = null)
    {
        $this->db->select('staff_attendance_type.*')->from('staff_attendance_type');

        $this->db->where('for_qr_attendance', 1);
        $this->db->order_by('id');

        $query = $this->db->get();
        if ($id != null) {
            return $query->row_array();
        } else {
            return $query->result_array();
        }
    }




    public function get()
    {
        $this->db->select('QR_code_settings.id,QR_code_settings.camera_type,QR_code_settings.auto_attendance,QR_code_settings.created_at');
        $this->db->from('QR_code_settings');
        $this->db->order_by('QR_code_settings.id');
        $query = $this->db->get();
        return $query->row();
    }

    public function getmachinetype()
    {
        $this->db->select('QR_code_settings.machine_type');
        $this->db->from('QR_code_settings');
        $this->db->order_by('QR_code_settings.id');
        $query = $this->db->get();
        return $query->row();
    }

    public function add($data)
    {

        $this->db->trans_begin();

        $q = $this->db->get('QR_code_settings');

        if ($q->num_rows() > 0) {
            $results = $q->row();
            $this->db->where('id', $results->id);
            $this->db->update('QR_code_settings', $data);
            $message = UPDATE_RECORD_CONSTANT . " On QR Attendance settings id " . $results->id;
            $action = "Update";
            $record_id = $results->id;
            $this->log($message, $record_id, $action);
        } else {

            $this->db->insert('QR_code_settings', $data);
            $id = $this->db->insert_id();
            $message = INSERT_RECORD_CONSTANT . " On QR Attendance settings id " . $id;
            $action = "Insert";
            $record_id = $id;
            $this->log($message, $record_id, $action);
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
        } else {
            $this->db->trans_commit();
        }
    }


    public function onlineaStaffttendence($data, $role_id, $is_auto_attendance)
    {

        $this->db->where('staff_id', $data['staff_id']);
        $this->db->where('date', $data['date']);
        $q = $this->db->get('staff_attendance');
        $time = date('H:i:s');
        if ($q->num_rows() == 0) {
            if ($is_auto_attendance) { //if attendance is auto selected

                $attendance_range = $this->staffAttendaceSetting_model->getAttendanceTypeByRole($role_id, $time);
                if ($attendance_range) {
                    $data['staff_attendance_type_id'] = $attendance_range->staff_attendence_type_id;
                    $data['in_time'] = $time;
					
					$present_student_list['staff_id'][$data['staff_id']] = ($data['staff_id']);
                    $present_student_list['in_time'][$data['staff_id']] =$time;
                    $this->mailsmsconf->mailsms('staff_present_attendence', $present_student_list, $data['date']);
					
                    $this->db->insert('staff_attendance', $data);
                    $status = 1; 
                } else {
                    $status = 2; 
                }
            }else{
                $data['in_time'] = $time;
				
				$present_student_list['staff_id'][$data['staff_id']] = ($data['staff_id']);
                $present_student_list['in_time'][$data['staff_id']] =$time;
                $this->mailsmsconf->mailsms('staff_present_attendence', $present_student_list, $data['date']);
					
                $this->db->insert('staff_attendance', $data);
                $status = 1; //for successfully saving
            }

        } else {

            $return_result = $q->row();
            if (!IsNullOrEmptyString($return_result->in_time)  && !IsNullOrEmptyString($return_result->out_time)) {

                $status = 0;
            } else {
                $updateArr = ['out_time' => $time];
                $return_attendance_type =  $this->staff_schedule_hours($return_result->staff_id, $return_result->in_time);
                if ($return_attendance_type) {
                    $updateArr['staff_attendance_type_id'] = $return_attendance_type;
                }


                $this->db->where('id', $return_result->id);
                $this->db->update('staff_attendance', $updateArr);
                $status = 1;
            }
        }
        return $status;
    }

    public function staff_schedule_hours($staff_id, $in_time)
    {

        $date = date('Y-m-d');
        $sql    = "SELECT staff_roles.role_id,staff_attendence_schedules.staff_attendence_type_id as staff_attendence_schedule_staff_attendence_type_id,staff_attendence_schedules.entry_time_from,staff_attendence_schedules.entry_time_to,staff_attendence_schedules.total_institute_hour FROM `staff` INNER JOIN staff_roles on staff_roles.staff_id=staff.id INNER join staff_attendence_schedules on staff_attendence_schedules.role_id = staff_roles.role_id WHERE staff.id=" . $this->db->escape($staff_id);
        // $current_time = date('H:i:s', strtotime('12:00:00'));

        $current_time = date('H:i:s');
        $query  = $this->db->query($sql);
        if ($query->num_rows() > 0) {

            $return_attedance_type = false;
            $time_entry_seconds = strtotime("1970-01-01 $in_time UTC");
            $time_current_seconds = strtotime("1970-01-01 $current_time UTC");
            $total_spend_time = $time_current_seconds - $time_entry_seconds;

            $result = $query->result();
            $find_array = array();

            foreach ($result as $result_key => $result_value) {

                $entry_time_from_seconds = strtotime("1970-01-01 $result_value->entry_time_from UTC");
                $entry_time_to_seconds = strtotime("1970-01-01 $result_value->entry_time_to UTC");

                if ($entry_time_from_seconds  <= $time_entry_seconds && $entry_time_to_seconds >= $time_entry_seconds) {

                    $find_array[] = array(
                        'staff_attendence_type_id' => $result_value->staff_attendence_schedule_staff_attendence_type_id,
                        'time_schedule_seconds' => strtotime("1970-01-01 $result_value->total_institute_hour UTC")
                    );
                }
            }
            if (count($find_array) > 1) {

                if ($total_spend_time < $find_array[0]['time_schedule_seconds'] && $total_spend_time > $find_array[1]['time_schedule_seconds']) {
                    $return_attedance_type = $find_array[1]['staff_attendence_type_id'];
                }
            }

            return $return_attedance_type;
        } else {
            return false;
        }
    }

    public function student_schedule_hours($class_section_id, $in_time)
    {

        $date = date('Y-m-d');
        $sql    = "SELECT * FROM `student_attendence_schedules`WHERE class_section_id=" . $this->db->escape($class_section_id);

        $current_time = date('H:i:s');
        $query  = $this->db->query($sql);
        if ($query->num_rows() > 0) {

            $return_attedance_type = false;
            $time_entry_seconds = strtotime("1970-01-01 $in_time UTC");
            $time_current_seconds = strtotime("1970-01-01 $current_time UTC");
            $total_spend_time = $time_current_seconds - $time_entry_seconds;


            $result = $query->result();
            $find_array = array();

            foreach ($result as $result_key => $result_value) {

                $entry_time_from_seconds = strtotime("1970-01-01 $result_value->entry_time_from UTC");
                $entry_time_to_seconds = strtotime("1970-01-01 $result_value->entry_time_to UTC");

                if ($entry_time_from_seconds  <= $time_entry_seconds && $entry_time_to_seconds >= $time_entry_seconds) {

                    $find_array[] = array(
                        'attendence_type_id' => $result_value->attendence_type_id,
                        'time_schedule_seconds' => strtotime("1970-01-01 $result_value->total_institute_hour UTC")
                    );
                }
            }

            if (count($find_array) > 1) {

                if ($total_spend_time < $find_array[0]['time_schedule_seconds'] && $total_spend_time > $find_array[1]['time_schedule_seconds']) {
                    $return_attedance_type = $find_array[1]['attendence_type_id'];
                }
            }

            return $return_attedance_type;
        } else {
            return false;
        }
    }
    public function onlineStudentattendence($data, $class_section_id, $is_auto_attendance)
    {
        $status = 0;
        $this->db->where('student_session_id', $data['student_session_id']);
        $this->db->where('date', $data['date']);
        $q = $this->db->get('student_attendences');
        $time = date('H:i:s');

        if ($q->num_rows() == 0) {
            $present_student_list = array();
            if ($is_auto_attendance) { //if attendance is auto selected
                $attendance_range = $this->studentAttendaceSetting_model->getAttendanceTypeByClassAndSectionTime($class_section_id, $time);
                if ($attendance_range) {
                    $data['attendence_type_id'] = $attendance_range->attendence_type_id;
                    $data['in_time'] = $time;

                    $this->db->insert('student_attendences', $data);
                    $status = 1; //for successfully saving
                    $present_student_list['student_sessions_id'][$data['student_session_id']] = ($data['student_session_id']);
                    $present_student_list['in_time'][$data['student_session_id']] =$time;
                    $this->mailsmsconf->mailsms('student_present_attendence', $present_student_list, $data['date']);

                } else {
                    $status = 2; //for range not exist to save

                }
            } else {

                $data['in_time'] = $time;

                $this->db->insert('student_attendences', $data);
                $present_student_list['student_sessions_id'][$data['student_session_id']] = ($data['student_session_id']);
                $present_student_list['in_time'][$data['student_session_id']] =$time;
                $this->mailsmsconf->mailsms('student_present_attendence', $present_student_list, $data['date']);
                $status = 1; //for successfully saving
            }
        } else {
            $return_result = $q->row();
            if (!IsNullOrEmptyString($return_result->in_time)  && !IsNullOrEmptyString($return_result->out_time)) {

                $status = 0;
            } else {


                $updateArr = ['out_time' => $time];
                $return_attendance_type =  $this->student_schedule_hours($class_section_id, $return_result->in_time);
                if ($return_attendance_type) {
                    $updateArr['attendence_type_id'] = $return_attendance_type;
                }

                $this->db->where('id', $return_result->id);
                $this->db->update('student_attendences', $updateArr);
                $status = 1;
            }
        }
        return $status;
    }
}
