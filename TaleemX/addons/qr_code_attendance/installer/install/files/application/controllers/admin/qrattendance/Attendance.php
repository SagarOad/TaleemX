<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Attendance extends MY_Addon_QRAttendanceController
{

    function __construct()
    {
        parent::__construct();
        $this->load->library('mailsmsconf');
		$this->load->library('media_storage');
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('qr_code_attendance', 'can_view')) {
            access_denied();
        }

        $this->session->set_userdata('top_menu', 'qrattendance');
        $this->session->set_userdata('sub_menu', 'admin/qrattendance/attendance/index');
        
        $data          = array();
		
        $setting = $this->qrsetting_model->get();
        $data['setting'] = json_encode($setting);
		
		$machinetype = $this->qrsetting_model->getmachinetype();		
		$data['machine_type'] = $machine_type  = json_decode($machinetype->machine_type);		
        
        $this->load->view('layout/header');	
		 if (!empty($machine_type) && in_array("gun_machine", $machine_type) && !in_array("camera", $machine_type)) {
			// redirect instead of calling method directly
			redirect('admin/qrattendance/attendance/scannergun');
		} else {
			$this->load->view('admin/qrattendance/index', $data);
		}
		
        $this->load->view('layout/footer');
    }
	
	public function scannergun()
    {         
        $this->session->set_userdata('top_menu', 'qrattendance');
        $this->session->set_userdata('sub_menu', 'admin/qrattendance/attendance/index');
        
        $data          = array();
		
        $setting = $this->qrsetting_model->get();
        $data['setting'] = json_encode($setting);
        
		$machinetype = $this->qrsetting_model->getmachinetype();
		$data['machine_type'] = json_decode($machinetype->machine_type);		
        
        $this->load->view('layout/header');		 
		$this->load->view('admin/qrattendance/scannergun', $data);		
        $this->load->view('layout/footer');
    }	

    public function getProfileDetail()
    {        
        $data          = array();
        $setting = $this->qrsetting_model->get();
        $data['setting'] = $setting;
        $admission_no = $this->input->post('text');
        $data['sch_setting']  = $this->setting_model->getSetting();
        $date = date('Y-m-d');
        $student = $this->qrsetting_model->qrcode_attendance($admission_no, $date);

        $attendance_range = false;
        $data['student'] = $student;
        $msg = "";
        if (!$student) {
            $status = 0;
            $msg = $this->lang->line('invalid_qr_code_barcode_please_try_again_or_contact_to_admin');
        } else {

            $defalt_selected_option = 1;
            $time = date('H:i:s');
            $status =  ($student->attendance_id > 0) &&  (!IsNullOrEmptyString($student->in_time) && !IsNullOrEmptyString($student->out_time)) ? 2 : 1;

            $msg =  ($student->attendance_id > 0) &&  (!IsNullOrEmptyString($student->in_time) && !IsNullOrEmptyString($student->out_time)) ? $this->lang->line('attendance_has_been_already_submitted') : "";
 
            if ($student->table_type == "student") {

                $attendance_range_data = $this->studentAttendaceSetting_model->getAttendanceTypeByClassAndSectionTime($student->class_section_id, $time);
 
                if ($attendance_range_data) {
                    $attendance_range = true;

                    $defalt_selected_option  = $attendance_range_data->attendence_type_id;
                }
                $attendencetypes             = $this->qrsetting_model->getQRAttendanceStudentAttendanceType();
                $data['attendencetypeslist'] = $attendencetypes;
                $profile_type = 'student';
                $data['profile_type'] = $profile_type;
            } elseif ($student->table_type == "staff") {

                $attendance_range_data = $this->staffAttendaceSetting_model->getAttendanceTypeByRole($student->role_id, $time);
                if ($attendance_range_data) {
                    $attendance_range = true;
                    $defalt_selected_option  = $attendance_range_data->staff_attendence_type_id;
                }

                $attendencetypes             = $this->qrsetting_model->getQRAttendanceStaffAttendanceType();
                $data['attendencetypeslist'] = $attendencetypes;
                $profile_type = 'staff';
                $data['profile_type'] = $profile_type;
            }
            $data['student'] = $student;

            $data['defalt_selected_option'] = $defalt_selected_option;
        }
        if (!$attendance_range) {

            $msg = $this->lang->line('attendance_settings_not_configured');	
			
        }

        $data['status'] = $status;
        $data['attendance_range'] = $attendance_range;
        $page  = $this->load->view('admin/qrattendance/_getProfileDetail', $data, true);
        echo json_encode(['page' => $page, 'status' => $status, 'attendance_range' => $attendance_range, 'msg' => $msg]);
    }

    public function saveAttendance()
    {        
        $this->form_validation->set_rules('attendance_for', $this->lang->line('attendance_type'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('record_id', $this->lang->line('attendance_type'), 'required|trim|xss_clean');
        if ($this->input->post('prev_attendance_id') <= 0) {
            $this->form_validation->set_rules('attendence_type_id', $this->lang->line('attendance_type'), 'required|trim|xss_clean');
        }
        $data = array();
        $status = "";

        if ($this->form_validation->run() == false) {

            $data = array(
                'attendance_for'               => form_error('attendance_for'),
                'record_id'                    => form_error('record_id'),

            );
            if ($this->input->post('prev_attendance_id') <= 0) {
                $data['attendence_type_id'] = form_error('attendence_type_id');
            }
            $array = array('status' => 0, 'error' => $data, 'msg' => $this->lang->line('somthing_went_wrong'));
            echo json_encode($array);
        } else {

            $qr_setting = $this->qrsetting_model->get();

            $id = $this->input->post('record_id');

            if ($this->input->post('attendance_for') == "student") {

                $record_data = $this->student_model->get($id);
             
      
            } elseif ($this->input->post('attendance_for') == "staff") {
                $record_data = $this->staff_model->getAll($id);
            }

            $s = getLocation();
            // $biometric_device_data = [
            //     "uid" => '',
            //     "user_id" => '',
            //     "t" => '',
            //     "ip" => getIP(),
            //     "serial_number" => '',
            //     "country" => $s->country,
            //     "city" => $s->city,
            //     "region" => $s->region,
            //     "latitude" => $s->latitude,
            //     "longitude" => $s->longitude,
            // ];

            $biometric_device_data = [
                "uid"            => '',
                "user_id"        => '',
                "t"              => '',
                "ip"             => getIP(),
                "serial_number"  => '',
                "country"        => $s->country   ?? null,
                "city"           => $s->city      ?? null,
                "region"         => $s->region    ?? null,
                "latitude"       => $s->latitude  ?? null,
                "longitude"      => $s->longitude ?? null,
            ];

            if ($this->input->post('attendance_for') == "student") {

                $insert_record = array(
                    'date'                  => date('Y-m-d'),
                    'student_session_id'    => $record_data['student_session_id'],
                    'attendence_type_id'    => $this->input->post('attendence_type_id'),
                    'qrcode_attendance'     => 1,
                    'remark'                => '',
                    'created_at'            => date('Y-m-d H:i:s'),
                    'biometric_device_data' => json_encode($biometric_device_data),
                    'user_agent' => getAgentDetail()

                );

                $insert_result = $this->qrsetting_model->onlineStudentattendence($insert_record, $record_data['class_section_id'], $qr_setting->auto_attendance);
                $status = $insert_result;
            
            } elseif ($this->input->post('attendance_for') == "staff") {

                $insert_record = array(
                    'date'                  => date('Y-m-d'),
                    'staff_id'               => $record_data['id'],
                    'staff_attendance_type_id'    => $this->input->post('attendence_type_id'),
                    'qrcode_attendance'  => 1,
                    'remark'                => '',
                    'created_at'            => date('Y-m-d H:i:s'),
                    'biometric_device_data' => json_encode($biometric_device_data),
                    'user_agent' => getAgentDetail()

                );

                $insert_result = $this->qrsetting_model->onlineaStaffttendence($insert_record, $record_data['role_id'], $qr_setting->auto_attendance);
                $status = $insert_result;
            }

            if ($status == 1) {
                echo json_encode(array('status' => 1,  'msg' => $this->lang->line('success_message')));
            } elseif ($status == 2) {
                echo json_encode(array('status' => 1,  'msg' => $this->lang->line('attendance_settings_not_configured')));
            } else {
                echo json_encode(array('status' => 0,  'msg' => $this->lang->line('attendance_already_submitted')));
            }
        }
    }
}
