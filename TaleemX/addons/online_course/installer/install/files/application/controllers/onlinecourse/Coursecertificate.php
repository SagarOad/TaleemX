<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Coursecertificate extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('coursecertificate_model');
        $this->load->helper('course');
        $this->load->library('Customlib');
        $this->load->library('media_storage');
        $this->load->library('SaasValidation');

    }

    public function validateCanUploadFile($str, $params_string)
    {
        $params_array = array_map('trim', explode(',', $params_string));
        return $this->saasvalidation->validateCanUploadFile($str, $params_array);
    }

    public function templatelist(){   
        $data['id'] = "";    
        $data['certificateList'] = $this->coursecertificate_model->certificateList();
        $this->load->view('layout/header', $data);
        $this->load->view('onlinecourse/coursecertificate/templatelist', $data);
        $this->load->view('layout/footer', $data);
    }

    public function getcertificate(){       
        $id= $_POST['id'];
        $data['get_data'] = $this->coursecertificate_model->get($id);
        echo json_encode($data);
    }

    public function certificateposition_by_model(){       
        $data['id'] = $id= $_POST['certificate_edit_id'];
        $data['get_data'] = $this->coursecertificate_model->get($id);
        $this->load->view('layout/header', $data);
        $this->load->view('onlinecourse/coursecertificate/certificateposition', $data);
        $this->load->view('layout/footer', $data);
    }

    public function certificateposition($id){       
        $data['id'] = $id;
        $data['get_data'] = $this->coursecertificate_model->get($id);
        $this->load->view('layout/header', $data);
        $this->load->view('onlinecourse/coursecertificate/certificateposition', $data);
        $this->load->view('layout/footer', $data);
    }


    public function save_certificate()
    {

        $id=$this->input->post('id');
        $this->form_validation->set_rules('certificate_name', $this->lang->line('certificate_name'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('certificate_text', $this->lang->line('body_text'), 'trim|required|xss_clean');

        //==========added==========
        $storage_array = "background_image"; // use comma for multiple files
        // $this->form_validation->set_rules('background_image', $this->lang->line('background_image'), "required|callback_validateCanUploadFile[$storage_array]");  
        if($this->input->post('id')==0){
            $this->form_validation->set_rules('background_image',$this->lang->line('background_image'),"callback_handle_upload[background_image]|callback_validateCanUploadFile[$storage_array]");
        }
        //==========added==========

        if ($this->form_validation->run() == false) {
           
            $msg = array(
                'certificate_name'      => form_error('certificate_name'),
                'certificate_text'      => form_error('certificate_text'),
                'background_image'      => form_error('background_image'),
            );
            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {

            $data = array(
                'certificate_name'       => $this->input->post('certificate_name'),
                'certificate_text'       => $this->input->post('certificate_text'),
            );

            //===============
            $total_image_upload_size = 0;
            $total_documents_failed_size = 0;
            $background_image = "";

            if(isset($id) && $id!="" && $id!=0){ //on edit 
                $get_data = $this->coursecertificate_model->get($id);
                $is_background_image_exist=$get_data[0]['background_image'];

                if(!empty($_FILES['background_image']['name'])){
                    try {
                        $prev_file_size = $this->media_storage->getUploadedFileSize($is_background_image_exist, 'uploads/course_content/online_course_certificate');   
                        $ext                     =  pathinfo($_FILES['background_image']['name'], PATHINFO_EXTENSION);
                        $config['upload_path']   =  'uploads/course_content/online_course_certificate/';
                        $config['allowed_types'] =  $ext;
                        $file_name               =  $_FILES['background_image']['name'];
                        $original_name           =  $_FILES['background_image']['name'];
                        $file_name               =  time() . "-" . uniqid(rand()) . "!" . basename($original_name);
                        $config['file_name']     =  $file_name;

                        $this->load->library('upload', $config);
                        $this->upload->initialize($config);
                        if ($this->upload->do_upload('background_image')) {
                            $uploadData      = $this->upload->data();
                            $data['background_image']= $uploadData['file_name'];
                            $total_image_upload_size = $this->media_storage->getTmpFileSize('background_image');                   
                        } else {
                           $data['background_image']= "default_template.jpg";   
                        }
                        if ($prev_file_size > $total_image_upload_size) {                        
                            $size_difference = $prev_file_size - $total_image_upload_size;
                            $this->saasvalidation->deleteResouceQuota('storage', $size_difference);
                        } elseif ($prev_file_size < $total_image_upload_size) {
                            $size_difference = $total_image_upload_size - $prev_file_size;
                            $this->saasvalidation->updateResouceQuota('storage', $size_difference);
                        }
                    } catch (Exception $e) {
                        $thumbnail_image = $this->input->post('old_background');
                        log_message('error', 'Thumbnail upload error: ' . $e->getMessage());
                        $array = array('status' => 'fail', 'error' => $e->getMessage(), 'message' => '');
                        echo json_encode($array);
                        return;
                    }
                }else if($is_background_image_exist==null){
                    $data['background_image']= "default_template.jpg";
                }    
 
            }else{ // on add

                if(!empty($_FILES['background_image']['name'])) {
                    try {
                        $storage_array = ['background_image'];
                        $this->saasvalidation->updateStorageLimit('storage', $storage_array);
                        $ext                     = pathinfo($_FILES['background_image']['name'], PATHINFO_EXTENSION);
                        $config['upload_path']   = 'uploads/course_content/online_course_certificate/';
                        $config['allowed_types'] = $ext;
                        $original_name       = $_FILES['background_image']['name'];
                        $file_name           = time() . "-" . uniqid(rand()) . "!" . basename($original_name);
                        $config['file_name'] = $file_name;
                   
                        $this->load->library('upload', $config);

                        $this->upload->initialize($config);

                        if ($this->upload->do_upload('background_image')) {
                            $uploadData      = $this->upload->data();
                            $data['background_image']= $uploadData['file_name'];;
                        } else {
                            $data['background_image']="";
                            $total_documents_failed_size = $this->media_storage->getTmpFileSize('background_image');
                        }                
                        if ($total_documents_failed_size > 0) {
                            $this->saasvalidation->deleteResouceQuota('storage', $total_documents_failed_size);
                        }
                    }catch (Exception $e) {

                        $data['background_image']="";
                        log_message('error', 'background image upload error: ' . $e->getMessage());
                        $array = array('status' => 'fail', 'error' => $msg, 'message' => $e->getMessage());
                        echo json_encode($array);
                        return;
                    }
                }else{

                    $data['background_image']= "default_template.jpg";
                }
            }
            //===============
            
            $data['id']  = $this->input->post('id');
            $record_id=$this->coursecertificate_model->addcertificate($data);
            $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'),"record_id"=>$record_id);
        }
            echo json_encode($array);

    }

    //new function
    public function savetemplateposition_old(){
        $data = array(
            'id'     => $this->input->post('editid'),
            'certificate_template'     => $this->input->post('text_positions'),
            'fontsizeselectblock'     => $this->input->post('fontsizeselectblock'),
            'fontsizeselectall'     => $this->input->post('fontsizeselectall'),
        );
        $this->coursecertificate_model->addcertificate($data);
    }
    //new function

	public function savetemplateposition(){

		$html = $this->input->post('text_positions');
	
		$html = preg_replace_callback(
			'/<img[^>]+src="([^"]+)"/i',
			function ($matches) {
				$src = $matches[1];
				$parts = explode('/', $src);
				$filename = end($parts); // sirf image name
				return str_replace($src, $filename, $matches[0]);
			},
			$html
		);

		$data = array(
			'id'                     => $this->input->post('editid'),
			'certificate_template'   => $html,
			'fontsizeselectblock'    => $this->input->post('fontsizeselectblock'),
			'fontsizeselectall'      => $this->input->post('fontsizeselectall'),
		);

		$this->coursecertificate_model->addcertificate($data);
	}


    public function edit_certificate_position(){
		
		$html = $this->input->post('text_positions');
		 
		$html = preg_replace_callback(
			'/<img[^>]+src="([^"]+)"/i',
			function ($matches) {
				$src = $matches[1];
				$parts = explode('/', $src);
				$filename = end($parts); // sirf image name
				return str_replace($src, $filename, $matches[0]);
			},
			$html
		);
		
        $data = array(
            'id'     => $this->input->post('id'),
            'certificate_template'   => $html,
            'fontsizeselectblock'     => $this->input->post('fontsizeselectblock'),
            'fontsizeselectall'     => $this->input->post('fontsizeselectall'),
        );
        $this->coursecertificate_model->addcertificate($data);
    }
    
    public function delete_record($id){
        $get_data = $this->coursecertificate_model->get($id);
        if ($get_data[0]['background_image'] != '' && $get_data[0]['background_image']!='default_template.jpg') {
            $delete_file_size = $this->media_storage->getUploadedFileSize($get_data[0]['background_image'], 'uploads/course_content/online_course_certificate');
            $this->saasvalidation->deleteResouceQuota('storage', $delete_file_size);
            $this->media_storage->filedelete($get_data[0]['background_image'], "uploads/course_content/online_course_certificate/");
        }
        $result = $this->coursecertificate_model->remove($id);
        redirect('onlinecourse/coursecertificate/templatelist');
    }


     /* This is used to validate image*/
    public function handle_upload($var, $name){
        $image_validate = $this->config->item('image_validate');
        $result         = $this->filetype_model->get();
        if (!empty($_FILES[$name]["name"])) {
            $file_type         = $_FILES[$name]['type'];
            $file_size         = $_FILES[$name]["size"];
            $file_name         = $_FILES[$name]["name"];
            $allowed_extension = array_map('trim', array_map('strtolower', explode(',', $result->image_extension)));
            $allowed_mime_type = array_map('trim', array_map('strtolower', explode(',', $result->image_mime)));
            $ext               = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if ($files = @getimagesize($_FILES[$name]['tmp_name'])) {
                if (!in_array($files['mime'], $allowed_mime_type)) {
                    $this->form_validation->set_message('handle_upload', $this->lang->line('file_type_not_allowed'));
                    return false;
                }
                if (!in_array($ext, $allowed_extension) || !in_array($file_type, $allowed_mime_type)) {
                    $this->form_validation->set_message('handle_upload', $this->lang->line('extension_not_allowed'));
                    return false;
                }
                if ($file_size > $result->image_size) {
                    $this->form_validation->set_message('handle_upload', $this->lang->line('file_size_shoud_be_less_than') . number_format($image_validate['upload_size'] / 1048576, 2) . " MB");
                    return false;
                }
            } else {
                $this->form_validation->set_message('handle_upload', $this->lang->line('file_type_extension_error_uploading_image'));
                return false;
            }
            return true;
        } else {
            $this->form_validation->set_message('handle_upload', $this->lang->line('the_background_field_is_required'));
            return false;
        }
    }

    public function addcertificate($id=null){
        $data['id'] = $id;  
        $data['certificateList'] = $this->coursecertificate_model->certificateList();
        $this->load->view('layout/header', $data);
        $this->load->view('onlinecourse/coursecertificate/addcertificate', $data);
        $this->load->view('layout/footer', $data);

    }


















    

 }