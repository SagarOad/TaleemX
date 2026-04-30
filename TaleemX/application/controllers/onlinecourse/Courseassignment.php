<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Courseassignment extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('course_model', 'coursesection_model', 'courselesson_model', 'studentcourse_model', 'coursequiz_model', 'course_payment_model', 'courseofflinepayment_model', 'coursereport_model','courseassignment_model'));
        $this->auth->addonchk('ssoclc', site_url('onlinecourse/course/setting'));
        $this->load->model('coursequiz_model');
        $this->load->model('course_model');
        $this->load->library('media_storage');
        $config = array(
            'field' => 'slug',
            'title' => 'title',
            'table' => 'online_courses',
            'id'    => 'id',
        );
        $this->load->library('slug', $config);
        $this->sch_setting_detail = $this->setting_model->getSetting();
		$this->load->library('SaasValidation');
    }
	
	public function validateCanUploadFile($str, $params_string)
    {
        $params_array = array_map('trim', explode(',', $params_string));
        return $this->saasvalidation->validateCanUploadFile($str, $params_array);
    }

    public function add_course_assignment(){
        $this->form_validation->set_rules('assignment_title',$this->lang->line("assignment_title"), 'trim|required|xss_clean');
        $this->form_validation->set_rules('assignment_date',$this->lang->line("assignment_date"), 'trim|required|xss_clean');
        $this->form_validation->set_rules('description',$this->lang->line("description"), 'trim|required|xss_clean');
		
		$storage_array = "userfile"; // use comma for multiple files	 

        $this->form_validation->set_rules('validate_storage', $this->lang->line('storage'), "callback_validateCanUploadFile[$storage_array]");
		
        $this->form_validation->set_rules('userfile', $this->lang->line("document"), 'callback_handle_upload');

        if ($this->form_validation->run() == false) {
            $msg = array(
                'assignment_title'    => form_error('assignment_title'),
                'assignment_date'     => form_error('assignment_date'),
                'description'         => form_error('description'),
                'userfile'            => form_error('userfile'),
                'validate_storage'            => form_error('validate_storage'),
            );
            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
			
			try {
			
                $userdata             =  $this->customlib->getUserData();
                $edit_assignment_id   =  $this->input->post('edit_assignment_id');
                if ($this->input->post("marks")) {
                    $marks = $this->input->post("marks");
                } else {
                    $marks =  null;
                }
				
				if ($this->input->post('submit_date')) {
					$submit_date = date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('submit_date')));
				} else {
					$submit_date = null;
				}
				
				$data = array(
					'id'                   =>   $edit_assignment_id,
					'assignment_title'     =>   $this->input->post('assignment_title'),
					'assignment_date'      =>   date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('assignment_date'))),
					'submit_date'          =>   $submit_date,
					'marks'                =>   $marks,
					'description'          =>   $this->input->post("description"),
					'course_section_id'    =>   $this->input->post("assignment_section_id"),
					'created_at'           =>   date("Y-m-d"),
					'created_by'           =>   $userdata["id"],
				);

				if ($edit_assignment_id > 0) {

                    $prev_file_size = 0;
                    $total_image_upload_size = 0;
                    $assigment_list = $this->courseassignment_model->getassignment($edit_assignment_id);
                    if (isset($_FILES["userfile"]) && $_FILES['userfile']['name'] != '' && (!empty($_FILES['userfile']['name']))) {
                        $prev_file_size = $this->media_storage->getUploadedFileSize($assigment_list->document, 'uploads/course_content/online_course_assignment');
                        $img_name = $this->media_storage->fileupload("userfile", "./uploads/course_content/online_course_assignment/");
                        if (!IsNullOrEmptyString($img_name)) {
                            $total_image_upload_size += $this->media_storage->getTmpFileSize('userfile');
                        }
                    } else {
                        $img_name = $assigment_list->document;
                    }
    
                    $data['document'] = $assigment_list->document;
    
                    if (isset($_FILES["userfile"]) && $_FILES['userfile']['name'] != '' && (!empty($_FILES['userfile']['name']))) {
                        $this->media_storage->filedelete($assigment_list->document, "uploads/course_content/online_course_assignment");
                    }

                    if ($prev_file_size > $total_image_upload_size) {
                        // Previous file was larger 
                        $size_difference = $prev_file_size - $total_image_upload_size;
                        $this->saasvalidation->deleteResouceQuota('storage', $size_difference);
                    } elseif ($prev_file_size < $total_image_upload_size) {
                        // New file is larger 
    
                        $size_difference = $total_image_upload_size - $prev_file_size;
                        $this->saasvalidation->updateResouceQuota('storage', $size_difference);
                    } else {
                        // File size unchanged → no quota adjustment needed 
                    }
					
				} else {
					$total_documents_failed_size = 0;
					$storage_array = ['userfile'];
					$this->saasvalidation->updateStorageLimit('storage', $storage_array); // update resource quota initially 			 
					
					$img_name         = $this->media_storage->fileupload("userfile", "./uploads/course_content/online_course_assignment/");;
					$data['document'] = $img_name;
					
					if (IsNullOrEmptyString($img_name)) {  // check upload image has not uploaded successfully
						$total_documents_failed_size += $this->media_storage->getTmpFileSize('userfile');  // get temp size of image because of image not uploaded 
					}

					if ($total_documents_failed_size > 0) {
						$this->saasvalidation->deleteResouceQuota('storage', $total_documents_failed_size);
					}
						
				}
				
				$lastid = $this->courseassignment_model->add_course_assignment($data);           

				//***assignment order***//            
				if($edit_assignment_id==0 || $edit_assignment_id==""){
				$orderData = array(
					'type'              => 'assignment',
					'course_section_id' => $this->input->post('assignment_section_id'),
					'lesson_quiz_id'    => $lastid,
				);
				$this->coursesection_model->addlessonquizorder($orderData);
				}          
				//***assignment order***// 

				$msg   = $this->lang->line('success_message');
				$array = array('status' => 'success', 'error' => '', 'message' => $msg);
			
			 } catch (Exception $e) {
               
                $array = array('status' => 'fail', 'error' =>  array('exception' => $e->getMessage()), 'message' => '');
            }	
			
			
        }
        echo json_encode($array);
    }


	public function handle_upload()
    {
        $image_validate = $this->config->item('file_validate');
        $result         = $this->filetype_model->get();
        if (isset($_FILES["userfile"]) && !empty($_FILES['userfile']['name'])) {
            $file_type = $_FILES["userfile"]['type'];
            $file_size = $_FILES["userfile"]["size"];
            $file_name = $_FILES["userfile"]["name"];

            $allowed_extension = array_map('trim', array_map('strtolower', explode(',', $result->file_extension)));
            $allowed_mime_type = array_map('trim', array_map('strtolower', explode(',', $result->file_mime)));
            $ext               = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if ($files = filesize($_FILES['userfile']['tmp_name'])) {

                if (!in_array($file_type, $allowed_mime_type)) {
                    $this->form_validation->set_message('handle_upload', $this->lang->line('file_type_not_allowed'));
                    return false;
                }
                if (!in_array($ext, $allowed_extension) || !in_array($file_type, $allowed_mime_type)) {
                    $this->form_validation->set_message('handle_upload', $this->lang->line('file_type_not_allowed'));
                    return false;
                }
                if ($file_size > $result->file_size) {
                    $this->form_validation->set_message('handle_upload', $this->lang->line('file_size_shoud_be_less_than') . number_format($result->file_size / 1048576, 2) . " MB");
                    return false;
                }
            } else {
                $this->form_validation->set_message('handle_upload', $this->lang->line('file_type_not_allowed'));
                return false;
            }

            return true;
        }
        return true;
    }
	
	public function download_assignment($id){
        $getsinglesection =  $this->courseassignment_model->getassignment($id);
        $this->media_storage->filedownload($getsinglesection->document, "uploads/course_content/online_course_assignment");
    }
	
    public function getassignment(){
        $assigment_id       =   $this->input->post('assigment_id');
        $getsinglesection   =   $this->courseassignment_model->getassignment($assigment_id);
        $data['from_date']  =   (isset($getsinglesection->assignment_date)) ? date($this->customlib->getSchoolDateFormat(),strtotime($getsinglesection->assignment_date)) : "";
        $data['to_date']    =   (isset($getsinglesection->submit_date)) ? date($this->customlib->getSchoolDateFormat(),strtotime($getsinglesection->submit_date)) : "";
        $data['getsinglesection'] =  $getsinglesection;
        if (!empty($data)) {
            echo json_encode($data);
        }
    }

     /*This is used to delete*/
    public function delete(){
        $assigment_id = $this->input->post('assigment_id');
        if (!empty($assigment_id)) {
            // This is used to delete quiz	   
			
			
			$getsinglesection   =   $this->courseassignment_model->getassignment($assigment_id);			
			
			if ($getsinglesection->document != '') {
				$delete_file_size = $this->media_storage->getUploadedFileSize($getsinglesection->document, 'uploads/course_content/online_course_assignment');
				$this->saasvalidation->deleteResouceQuota('storage', $delete_file_size);						 
				$this->media_storage->filedelete($getsinglesection->document, "uploads/course_content/online_course_assignment");
			}
			
			$this->coursesection_model->deletequizlesson($assigment_id, 'assignment');
            $this->courseassignment_model->remove($assigment_id);
			
            $arrays = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('delete_message'));
            echo json_encode($arrays);
        } else {
            $arrays = array('status' => 'fail', 'error' => 'some thing went wrong', 'message' => $this->lang->line('delete_message'));
            echo json_encode($arrays);
        }
    }

    public function evaluation($id){
        $data['studentlist']    = $this->courseassignment_model->get_student_assignment($id);
        $getassignment          = $this->courseassignment_model->getassignment($id);
        $data['getassignment']  = $getassignment;
        $data['sch_setting']    = $this->setting_model->getSetting();
        $superadmin_visible = $this->customlib->superadmin_visible();
        $role  = json_decode($this->customlib->getStaffRole());

        if($role->id == 7){
            $data["created_by"]   =  $getassignment->assignemnt_created_by;  
        }else{
            if($superadmin_visible == 'disabled' && $getassignment->created_by_role == 7 && isset($getassignment->created_by_role)){
                $data["created_by"]   = "";          
            }else{
                 $data["created_by"]  =  $getassignment->assignemnt_created_by;  
            }
        }
        if($role->id == 7){
            $data["evaluated_by"]   =  $getassignment->assignemnt_evaluated_by; 
        }else{
            if($superadmin_visible == 'disabled' && $getassignment->evaluated_by_role == 7  && isset($getassignment->evaluated_by_role)){
                $data["evaluated_by"]  = '';               
            }else{
                $data["evaluated_by"]  =  $getassignment->assignemnt_evaluated_by; 
            }
        }

        $this->load->view("onlinecourse/assignment/evaluation_modal",$data);
    }

    public function download_submit_assignment($id){
        $get_attachments =  $this->courseassignment_model->download_submit_assignment($id);
        $this->media_storage->filedownload($get_attachments->docs,"uploads/course_content/online_course_assignment");
    }

    public function add_evaluation(){

        $this->form_validation->set_rules('evaluation_date', $this->lang->line('evaluation_date'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('student_id[]', $this->lang->line('student_name'), 'trim|required|xss_clean');
        $student_id     =   $this->input->post("student_id[]");
        $marks          = $this->input->post("marks");
        $getassignment  =   $this->courseassignment_model->getassignment($this->input->post("assignment_id"));

         if (!empty($student_id) && $getassignment->marks!=0) {
            foreach ($student_id as $std_key => $std_value) {
                $marks1 = $marks[$std_key];
                if ($getassignment->marks < $marks1) {
                   $this->form_validation->set_rules("marks",$this->lang->line('marks'),array('valid_marks',array('check_valid_marks',array($this->courseassignment_model,'check_valid_marks'))));
                }
            }
        }

        if ($this->form_validation->run() == false) { 
            $msg = array(
                'evaluation_date'    => form_error('evaluation_date'),
                'student_id[]'       => form_error('student_id[]'),
                'marks'           => form_error('marks'),
            );

            $array = array('status'=>'fail','error'=>$msg,'message'=>"");
        } else {
            
            $insert_prev            =    array();
            $insert_array           =    array();
            $update_array           =    array();
            $delete_array           =    array();
            $assignment_id          =    $this->input->post("assignment_id");
            $student_list           =    $this->input->post("student_list");
            $student_id             =    $this->input->post("student_id");
            $marks                  =    $this->input->post("marks");
            $note                   =    $this->input->post("note");
            $evaluation_id          =    $this->input->post("evaluation_id");
            $evaluation_date        =    date('Y-m-d', $this->customlib->datetostrtotime($this->input->post('evaluation_date')));
            $user_type              =    $this->input->post("user_type[]");
            $assign_marks           =    null;
            
            foreach($student_list as $std_key=>$value){

                if($getassignment->marks!=0){
                    if($marks[$std_key]){
                        $assign_marks  =   $marks[$std_key];
                    }else{
                        $assign_marks  =   null;
                    }
                }

                if(isset($student_id[$std_key]) && isset($evaluation_id[$std_key]) && $evaluation_id[$std_key]!=0){  // update recored
                     
                    $update_array[] = array(
                        'id'                 => $evaluation_id[$std_key],
                        'note'               => $note[$std_key],
                        'marks'              => $assign_marks,
                     );
               
               }else if(isset($student_id[$std_key]) && $evaluation_id[$std_key]==0){  //insert data
                 
                    if($user_type[$std_key]=="student"){
                        $studentid  =    $student_id[$std_key];
                        $guestid    =    0;
                    }else if($user_type[$std_key]=="guest"){
                        $studentid  =    0; 
                        $guestid    =    $student_id[$std_key]; 
                    }

                    $insert_array[] = array(
                        'note'               => $note[$std_key],
                        'marks'              => $assign_marks,
                        'student_id'         => $studentid,
                        'guest_id'           => $guestid,
                    );

               }else{
                    $delete_array[] = array(
                        'id' => $evaluation_id[$std_key],
                    );
                }
            }

            $evaluation_date= $this->customlib->dateFormatToYYYYMMDD($this->input->post('evaluation_date'));
            $evaluated_by   = $this->customlib->getStaffID();
            $lastid         = $this->courseassignment_model->add_evaluation($insert_array,$update_array,$delete_array,$evaluation_date,$evaluated_by,$assignment_id);
            $msg            = $this->lang->line("assignment_evaluated_successfully");
            $array          = array('status' => 'success', 'error' => '', 'message' => "$msg");

        }
        echo json_encode($array);
    }
}