<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Courselesson extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('course_model', 'coursesection_model', 'courselesson_model', 'studentcourse_model', 'coursequiz_model', 'course_payment_model', 'courseofflinepayment_model', 'coursereport_model'));
        $this->auth->addonchk('ssoclc', site_url('onlinecourse/course/setting'));
        $this->load->model('course_model');
        $this->load->library('aws3');
        $this->load->helper('course');
        $this->load->library('media_storage');
        $this->load->model("filetype_model");
        $this->load->library('SaasValidation');
    }

    public function validateCanUploadFile($str, $params_string)
    {
        $params_array = array_map('trim', explode(',', $params_string));
        return $this->saasvalidation->validateCanUploadFile($str, $params_array);
    }

    /* This is used to add lesson */
    public function addlesson()
    {
        if (!$this->rbac->hasPrivilege('online_course_lesson', 'can_add')) {
            access_denied();
        }
        $this->form_validation->set_rules('title', $this->lang->line('title'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('lesson_type', $this->lang->line('lesson_type'), 'trim|required|xss_clean');

        $storage_array = "add_lesson_thumbnail"; // use comma for multiple files
        $this->form_validation->set_rules('validate_storage', $this->lang->line('storage'), "callback_validateCanUploadFile[$storage_array]");

        $this->form_validation->set_rules('add_lesson_thumbnail', $this->lang->line('thumbnail') . ' ' . $this->lang->line('field_is_required'), 'callback_handle_upload[add_lesson_thumbnail]');
        $lesson_type     = $this->input->post('lesson_type');
        $lesson_provider = $this->input->post('lesson_provider');

        $storage_array2 = "lesson_attachment"; // use comma for multiple files

        if ($lesson_type == 'pdf') {

            $this->form_validation->set_rules('validate_storage', $this->lang->line('storage'), "callback_validateCanUploadFile[$storage_array2]");

            $this->form_validation->set_rules('lesson_attachment', '', 'callback_pdf_handle_upload');
        } elseif ($lesson_type == 'document') {

            $this->form_validation->set_rules('validate_storage', $this->lang->line('storage'), "callback_validateCanUploadFile[$storage_array2]");

            $this->form_validation->set_rules('lesson_attachment', $this->lang->line('lesson_attachment'), 'callback_file_check');
        } elseif ($lesson_type == 'text') {

            $this->form_validation->set_rules('validate_storage', $this->lang->line('storage'), "callback_validateCanUploadFile[$storage_array2]");

            $this->form_validation->set_rules('lesson_attachment', $this->lang->line('lesson_attachment'), 'callback_text_check');
        } elseif ($lesson_type == 'video') {

            if ($lesson_provider == "s3_bucket") {
                /* File validation code goes here */
            } else {
                $this->form_validation->set_rules('lesson_url', $this->lang->line('video_url'), 'trim|required|xss_clean');
            }
            $this->form_validation->set_rules('lesson_duration', $this->lang->line('duration'), array('required', array('check_exists', array($this->courselesson_model, 'validateduration'))));
        } else {
            $this->form_validation->set_rules('lesson_url', $this->lang->line('video_url'), 'trim|required|xss_clean');
        }
        if ($this->form_validation->run() == false) {
            if ($lesson_type == 'pdf') {
                $msg = array(
                    'lesson_attachment'    => form_error('lesson_attachment'),
                    'title'                => form_error('title'),
                    'lesson_type'          => form_error('lesson_type'),
                    'add_lesson_thumbnail' => form_error('add_lesson_thumbnail'),
                    'validate_storage' => form_error('validate_storage'),
                );
            } elseif ($lesson_type == 'document') {
                $msg = array(
                    'lesson_attachment'    => form_error('lesson_attachment'),
                    'title'                => form_error('title'),
                    'lesson_type'          => form_error('lesson_type'),
                    'add_lesson_thumbnail' => form_error('add_lesson_thumbnail'),
                    'validate_storage' => form_error('validate_storage'),
                );
            } elseif ($lesson_type == 'text') {
                $msg = array(
                    'lesson_attachment'    => form_error('lesson_attachment'),
                    'title'                => form_error('title'),
                    'lesson_type'          => form_error('lesson_type'),
                    'add_lesson_thumbnail' => form_error('add_lesson_thumbnail'),
                    'validate_storage' => form_error('validate_storage'),
                );
            } elseif ($lesson_type == 'video') {
                $msg = array(
                    'title'                => form_error('title'),
                    'lesson_type'          => form_error('lesson_type'),
                    'lesson_duration'      => form_error('lesson_duration'),
                    'add_lesson_thumbnail' => form_error('add_lesson_thumbnail'),
                    'validate_storage' => form_error('validate_storage'),
                );
                if ($lesson_provider == "s3_bucket") {
                    $msg['lesson_file'] = form_error('lesson_file');
                } else {
                    $msg['lesson_url'] = form_error('lesson_url');
                }
            } else {
                $msg = array(
                    'title'                => form_error('title'),
                    'lesson_type'          => form_error('lesson_type'),
                    'add_lesson_thumbnail' => form_error('add_lesson_thumbnail'),
                    'validate_storage' => form_error('validate_storage'),
                );
            }
            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $section_id  = $this->input->post('add_lesson_section_id');
            $sectionData = array(
                'lesson_title'      => $this->input->post('title'),
                'course_section_id' => $section_id,
                'lesson_type'       => $lesson_type,
                'summary'           => $this->input->post('summary'),
                'created_date'      => date('Y-m-d h:i:s'),
            );
            // This is used to add lesson
            $insert_id = $this->courselesson_model->addlesson($sectionData);

            $orderData = array(
                'type'              => 'lesson',
                'course_section_id' => $section_id,
                'lesson_quiz_id'    => $insert_id,
            );
            $this->coursesection_model->addlessonquizorder($orderData);

            $updatecourse = array(
                'updated_date' => date("Y-m-d h:i:s"),
                'id'           => $this->input->post('lesson_course_id'),
            );
            $this->course_model->add($updatecourse);

            // This is used to create new directory
            $directory = FCPATH . '/uploads/course_content/' . $section_id . '/' . $insert_id . '/';

            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            if ($lesson_type == "text" || $lesson_type == "pdf" || $lesson_type == "document") {

                $update_student_data = [];
                $total_image_failed_size = 0;

                $update_student_data['lesson_attachment'] = null;

                if (isset($_FILES['lesson_attachment']) && !empty($_FILES['lesson_attachment']['name'])) {
                    $storage_array = ['lesson_attachment'];
                    $this->saasvalidation->updateStorageLimit('storage', $storage_array);
                    $upload_path   = 'uploads/course_content/' . $section_id . '/' . $insert_id . '/';
                    $result = $this->media_storage->fileuploadMultiple('lesson_attachment', $upload_path);

                    if (!empty($result['uploaded'])) {

                        $bulk_data = [];

                        foreach ($result['uploaded'] as $file) {

                            $bulk_data[] = [
                                'lesson_id'       => $insert_id,
                                'attachment'      => $file['saved_name'],
                                'attachment_name' => $file['name']
                            ];
                        }

                        $this->courselesson_model->add_lesson_attachment_bulk($bulk_data);
                    }

                    if ($result['total_failed_size'] > 0) {
                        $this->saasvalidation->deleteResouceQuota('storage', $result['total_failed_size']);
                    }
                }
            } else {
                $videoData = array(
                    'id'             => $insert_id,
                    'video_provider' => $this->input->post('lesson_provider'),
                    'duration'       => $this->input->post('lesson_duration'),
                );
                if ($lesson_provider == "s3_bucket") {
                    if (isset($_FILES['lesson_file'])) {
                        $file_name          = $_FILES['lesson_file']['name'];
                        $temp_file_location = $_FILES['lesson_file']['tmp_name'];
                        $url                = $this->aws3->uploadFile($file_name, $temp_file_location);
                        $getVideoUrl        = $_FILES['lesson_file']['name'];
                    }
                    $videoData['video_id'] = $getVideoUrl;
                } else {
                    if ($lesson_provider == 'youtube') {
                        $lesson_url = $this->input->post('lesson_url');
                        $video_id   = youtubeID($lesson_url);
                    } elseif ($lesson_provider == 'html5') {
                        $lesson_url = $this->input->post('lesson_url');
                    } elseif ($lesson_provider == 'vimeo') {
                        $lesson_url = $this->input->post('lesson_url');
                        $video_id   = vimeoID($lesson_url);
                    } else {
                        $lesson_url = "";
                        $video_id   = "";
                    }
                    $videoData['video_url'] = $lesson_url;
                    $videoData['video_id']  = $video_id;
                }
                // This is used to add lesson video
                $this->courselesson_model->addlesson($videoData);
            }

            $total_documents_failed_size = 0;
            $thumbnail_image = "";

            if (!empty($_FILES['add_lesson_thumbnail']['name'])) {
                try {

                    //================
                    $update_student_data = [];
                    $total_thumb_failed_size = 0;
                    $thumbnail_image = NULL;

                    if (isset($_FILES['add_lesson_thumbnail']) && !empty($_FILES['add_lesson_thumbnail']['name'])) {
                        $storage_array = ['add_lesson_thumbnail'];
                        $this->saasvalidation->updateStorageLimit('storage', $storage_array);
                        $upload_path   = 'uploads/course_content/' . $section_id . '/' . $insert_id . '/';
                        $thumbnail_image = $this->media_storage->fileupload('add_lesson_thumbnail', $upload_path);
                        if (IsNullOrEmptyString($thumbnail_image)) {
                            $total_thumb_failed_size += $this->media_storage->getTmpFileSize($field_name);
                        }
                    }

                    if ($total_thumb_failed_size > 0) {
                        $this->saasvalidation->deleteResouceQuota('storage', $total_thumb_failed_size);
                    }
                } catch (Exception $e) {

                    $thumbnail_image = "";
                    log_message('error', 'Thumbnail upload error: ' . $e->getMessage());
                    $array = array('status' => 'fail', 'error' => $msg, 'message' => $e->getMessage());
                    echo json_encode($array);
                    return;
                }
            }


            $upload_data = array('id' => $insert_id, 'thumbnail' => $thumbnail_image);
            // This is used to add lesson thumbnail
            $this->courselesson_model->addlesson($upload_data);
            $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
        }
        echo json_encode($array);
    }

    /* This is used to get single lesson list  */
    public function singlelessondetail()
    {
        $data['course_id']   = $this->input->post('courseID');
        $lessonID            = $this->input->post('lessonID');
        $getsinglelessondata = $this->courselesson_model->singlelessondetail($lessonID);
        if (!empty($getsinglelessondata)) {
            echo json_encode($getsinglelessondata);
        }
    }

    /*This is used to edit lesson */
    public function editlesson_old()
    {

        if (!$this->rbac->hasPrivilege('online_course_lesson', 'can_edit')) {
            access_denied();
        }
        $lesson_thumbnail = $_FILES['lesson_thumbnail']['name'];
        $this->form_validation->set_rules('lesson_titleID', $this->lang->line('title'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('lessons_type', $this->lang->line('lesson_type'), 'trim|required|xss_clean');
        if ($lesson_thumbnail != '') {
            $this->form_validation->set_rules('lesson_thumbnail', $this->lang->line('thumbnail') . ' ' . $this->lang->line('field_is_required'), 'callback_handle_upload[lesson_thumbnail]');
        }
        $lessons_type    = $this->input->post('lessons_type');
        $lesson_provider = $this->input->post('lesson_provider');
        if ($lessons_type == 'pdf') {
            $this->form_validation->set_rules('lesson_attachment', $this->lang->line('lesson_attachment'), 'callback_edit_handle_upload');
        } elseif ($lessons_type == 'document') {
            $this->form_validation->set_rules('lesson_attachment', $this->lang->line('lesson_attachment'), 'callback_edit_file_check');
        } elseif ($lessons_type == 'text') {
            $this->form_validation->set_rules('lesson_attachment', $this->lang->line('lesson_attachment'), 'callback_edit_text_check');
        } elseif ($lessons_type == 'video') {
            if ($lesson_provider == "s3_bucket") {
                /* File validation code goes here */
            } else {
                $this->form_validation->set_rules('lesson_url', $this->lang->line('video_url'), 'trim|required|xss_clean');
            }
            $this->form_validation->set_rules('lesson_duration', $this->lang->line('duration'), array('required', array('check_exists', array($this->courselesson_model, 'validateduration'))));
        } else {
            $this->form_validation->set_rules('lesson_url', $this->lang->line('video_url'), 'trim|required|xss_clean');
        }
        if ($this->form_validation->run() == false) {
            if ($lessons_type == 'pdf') {
                $msg = array(
                    'lesson_attachment' => form_error('lesson_attachment'),
                    'lesson_titleID'    => form_error('lesson_titleID'),
                    'lessons_type'      => form_error('lessons_type'),
                    'lesson_thumbnail'  => form_error('lesson_thumbnail'),
                );
            } elseif ($lessons_type == 'document') {
                $msg = array(
                    'lesson_attachment' => form_error('lesson_attachment'),
                    'lesson_titleID'    => form_error('lesson_titleID'),
                    'lessons_type'      => form_error('lessons_type'),
                    'lesson_thumbnail'  => form_error('lesson_thumbnail'),
                );
            } elseif ($lessons_type == 'text') {
                $msg = array(
                    'lesson_attachment' => form_error('lesson_attachment'),
                    'lesson_titleID'    => form_error('lesson_titleID'),
                    'lessons_type'      => form_error('lessons_type'),
                    'lesson_thumbnail'  => form_error('lesson_thumbnail'),
                );
            } elseif ($lessons_type == 'video') {
                $msg = array(
                    'lesson_titleID'   => form_error('lesson_titleID'),
                    'lessons_type'     => form_error('lessons_type'),
                    'lesson_duration'  => form_error('lesson_duration'),
                    'lesson_thumbnail' => form_error('lesson_thumbnail'),
                );
                if ($lesson_provider == "s3_bucket") {
                    $msg['lesson_file'] = form_error('lesson_file');
                } else {
                    $msg['lesson_url'] = form_error('lesson_url');
                }
            } else {
                $msg = array(
                    'lesson_titleID'   => form_error('lesson_titleID'),
                    'lessons_type'     => form_error('lessons_type'),
                    'lesson_thumbnail' => form_error('lesson_thumbnail'),
                );
            }
            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $lessonID    = $this->input->post('lessons_id');
            $sectionData = array(
                'id'                => $lessonID,
                'course_section_id' => $this->input->post('lesson_section_id'),
                'lesson_title'      => $this->input->post('lesson_titleID'),
                'lesson_type'       => $this->input->post('lessons_type'),
                'summary'           => $this->input->post('lessons_summary'),
            );

            $updatecourse = array(
                'updated_date' => date("Y-m-d h:i:s"),
                'id'           => $this->input->post('edit_lesson_course_id'),
            );
            $this->course_model->add($updatecourse);

            // This is used to create new directory
            $directory = FCPATH . '/uploads/course_content/' . $this->input->post('lesson_section_id') . '/' . $lessonID;

            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            if ($lessons_type == "text" || $lessons_type == "pdf" || $lessons_type == "document") {
                if (!empty($_FILES['lesson_attachment']['name'])) {
                    $this->courselesson_model->remove_lesson_attachment($lessonID); //It will remove all the old attachments
                    $dir_path     = 'uploads/course_content/' . $this->input->post('lesson_section_id') . '/' . $lessonID . '/';
                    $config['dir_path']     = $dir_path;
                    $this->load->library('imageResize', $config);
                    $responses = $this->imageresize->resize($_FILES["lesson_attachment"]);
                    foreach ($responses['images'] as $key => $value) {
                        $upload_attachment_data = array('lesson_id' => $lessonID, 'attachment' => $value['store_name'], 'attachment_name' => $value['name']);
                        $this->courselesson_model->add_lesson_attachment($upload_attachment_data);
                    }
                }
            } else {
                $videoData = array(
                    'id'             => $lessonID,
                    'video_provider' => $this->input->post('lesson_provider'),
                    'video_url'      => $this->input->post('lesson_url'),
                    'duration'       => $this->input->post('lesson_duration'),
                );
                if ($lesson_provider == "s3_bucket") {
                    if (isset($_FILES['lesson_file']) && $_FILES['lesson_file']['name'] != '') {
                        $file_name             = $_FILES['lesson_file']['name'];
                        $temp_file_location    = $_FILES['lesson_file']['tmp_name'];
                        $url                   = $this->aws3->uploadFile($file_name, $temp_file_location);
                        $getVideoUrl           = $_FILES['lesson_file']['name'];
                        $videoData['video_id'] = $getVideoUrl;
                    }
                } else {
                    if ($lesson_provider == 'youtube') {
                        $lesson_url = $this->input->post('lesson_url');
                        $video_id   = youtubeID($lesson_url);
                    } elseif ($lesson_provider == 'html5') {
                        $lesson_url = $this->input->post('lesson_url');
                    } elseif ($lesson_provider == 'vimeo') {
                        $lesson_url = $this->input->post('lesson_url');
                        $video_id   = vimeoID($lesson_url);
                    } else {
                        $lesson_url = "";
                        $video_id   = "";
                    }

                    $videoData['video_url'] = $lesson_url;
                    $videoData['video_id']  = $video_id;
                }

                // This is used to edit lesson video
                $this->courselesson_model->addlesson($videoData);
            }

            if (!empty($_FILES['lesson_thumbnail']['name'])) {
                $ext                     = pathinfo($_FILES['lesson_thumbnail']['name'], PATHINFO_EXTENSION);
                $config['upload_path']   = "uploads/course_content/" . $this->input->post('lesson_section_id') . "/" . $lessonID;
                $config['allowed_types'] = $ext;
                $file_name               = $_FILES['lesson_thumbnail']['name'];
                $config['file_name']     = $lessonID;
                $this->load->library('upload', $config);
                $this->upload->initialize($config);
                if ($this->upload->do_upload('lesson_thumbnail')) {
                    $uploadData      = $this->upload->data();
                    $thumbnail_image = $uploadData['file_name'];
                } else {
                    $thumbnail_image = $this->input->post('old_background');
                }
            } else {
                $thumbnail_image = $this->input->post('old_background');
            }
            // This is used to edit lesson
            $this->courselesson_model->addlesson($sectionData);
            $upload_data = array('id' => $lessonID, 'thumbnail' => $thumbnail_image);
            // This is used to edit lesson thumbnail
            $this->courselesson_model->addlesson($upload_data);
            $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
        }
        echo json_encode($array);
    }
    
    public function editlesson()
    {
        if (!$this->rbac->hasPrivilege('online_course_lesson', 'can_edit')) {
            access_denied();
        }
        $lesson_thumbnail = $_FILES['lesson_thumbnail']['name'];
        $this->form_validation->set_rules('lesson_titleID', $this->lang->line('title'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('lessons_type', $this->lang->line('lesson_type'), 'trim|required|xss_clean');
        //===============
        $storage_array = "add_lesson_thumbnail"; // use comma for multiple files
        $this->form_validation->set_rules('validate_storage', $this->lang->line('storage'), "callback_validateCanUploadFile[$storage_array]");
        //===============
        // if ($lesson_thumbnail != '') {
            $this->form_validation->set_rules('lesson_thumbnail', $this->lang->line('thumbnail') . ' ' . $this->lang->line('field_is_required'), 'callback_handle_upload[lesson_thumbnail]');
        // }
        $lessons_type    = $this->input->post('lessons_type');
        $lesson_provider = $this->input->post('lesson_provider');
        $storage_array2 = "lesson_attachment"; // use comma for multiple files

        if ($lessons_type == 'pdf') {
             $this->form_validation->set_rules('validate_storage', $this->lang->line('storage'), "callback_validateCanUploadFile[$storage_array2]");
            $this->form_validation->set_rules('lesson_attachment', '', 'callback_pdf_handle_upload');
            // $this->form_validation->set_rules('lesson_attachment', $this->lang->line('lesson_attachment'), 'callback_edit_handle_upload');
        } elseif ($lessons_type == 'document') {
            $this->form_validation->set_rules('validate_storage', $this->lang->line('storage'), "callback_validateCanUploadFile[$storage_array2]");
            $this->form_validation->set_rules('lesson_attachment', $this->lang->line('lesson_attachment'), 'callback_file_check');
            // $this->form_validation->set_rules('lesson_attachment', $this->lang->line('lesson_attachment'), 'callback_edit_file_check');
        } elseif ($lessons_type == 'text') {
            $this->form_validation->set_rules('validate_storage', $this->lang->line('storage'), "callback_validateCanUploadFile[$storage_array2]");
            $this->form_validation->set_rules('lesson_attachment', $this->lang->line('lesson_attachment'), 'callback_text_check');
            // $this->form_validation->set_rules('lesson_attachment', $this->lang->line('lesson_attachment'), 'callback_edit_text_check');
        } elseif ($lessons_type == 'video') {
            if ($lesson_provider == "s3_bucket") {
                /* File validation code goes here */
            } else {
                $this->form_validation->set_rules('lesson_url', $this->lang->line('video_url'), 'trim|required|xss_clean');
            }
            $this->form_validation->set_rules('lesson_duration', $this->lang->line('duration'), array('required', array('check_exists', array($this->courselesson_model, 'validateduration'))));
        } else {
            $this->form_validation->set_rules('lesson_url', $this->lang->line('video_url'), 'trim|required|xss_clean');
        }
        if ($this->form_validation->run() == false) {
            if ($lessons_type == 'pdf') {
                $msg = array(
                    'lesson_attachment' => form_error('lesson_attachment'),
                    'lesson_titleID'    => form_error('lesson_titleID'),
                    'lessons_type'      => form_error('lessons_type'),
                    'lesson_thumbnail'  => form_error('lesson_thumbnail'),
                );
            } elseif ($lessons_type == 'document') {
                $msg = array(
                    'lesson_attachment' => form_error('lesson_attachment'),
                    'lesson_titleID'    => form_error('lesson_titleID'),
                    'lessons_type'      => form_error('lessons_type'),
                    'lesson_thumbnail'  => form_error('lesson_thumbnail'),
                );
            } elseif ($lessons_type == 'text') {
                $msg = array(
                    'lesson_attachment' => form_error('lesson_attachment'),
                    'lesson_titleID'    => form_error('lesson_titleID'),
                    'lessons_type'      => form_error('lessons_type'),
                    'lesson_thumbnail'  => form_error('lesson_thumbnail'),
                );
            } elseif ($lessons_type == 'video') {
                $msg = array(
                    'lesson_titleID'   => form_error('lesson_titleID'),
                    'lessons_type'     => form_error('lessons_type'),
                    'lesson_duration'  => form_error('lesson_duration'),
                    'lesson_thumbnail' => form_error('lesson_thumbnail'),
                );
                if ($lesson_provider == "s3_bucket") {
                    $msg['lesson_file'] = form_error('lesson_file');
                } else {
                    $msg['lesson_url'] = form_error('lesson_url');
                }
            } else {
                $msg = array(
                    'lesson_titleID'   => form_error('lesson_titleID'),
                    'lessons_type'     => form_error('lessons_type'),
                    'lesson_thumbnail' => form_error('lesson_thumbnail'),
                );
            }
            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $lessonID    = $this->input->post('lessons_id');
            $sectionData = array(
                'id'                => $lessonID,
                'course_section_id' => $this->input->post('lesson_section_id'),
                'lesson_title'      => $this->input->post('lesson_titleID'),
                'lesson_type'       => $this->input->post('lessons_type'),
                'summary'           => $this->input->post('lessons_summary'),
            );

            $updatecourse = array(
                'updated_date' => date("Y-m-d h:i:s"),
                'id'           => $this->input->post('edit_lesson_course_id'),
            );
            $this->course_model->add($updatecourse);

            // This is used to create new directory
            $directory = FCPATH . '/uploads/course_content/' . $this->input->post('lesson_section_id') . '/' . $lessonID;

            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            if ($lessons_type == "text" || $lessons_type == "pdf" || $lessons_type == "document") {
               
                //=========================================
                $update_student_data = [];
                $total_image_failed_size = 0;
                $delete_file_size = 0;
                $update_student_data['lesson_attachment'] = null;

                if (isset($_FILES['lesson_attachment']) && !empty($_FILES['lesson_attachment']['name'])) {
                   
                    //=================
                    $attachments = $this->courselesson_model->get_lesson_attachment_details_lessonid($lessonID);

                    foreach ($attachments as $key => $row) {
                        if ($row['attachment'] != '') {
                            $directory = 'uploads/course_content/' . $row['course_section_id'] . '/' . $lessonID;
                            $delete_file_size += $this->media_storage->getUploadedFileSize($row['attachment'], "$directory");
                            $this->media_storage->filedelete($row['attachment'], "$directory/"); //attachments deleted from the upload folder
                        }
                    }
                    $this->saasvalidation->deleteResouceQuota('storage', $delete_file_size);
                    $this->courselesson_model->remove_lesson_attachment($lessonID); //It will remove all the old attachments from db

                    //===================

                    $dir_path  =  'uploads/course_content/' . $this->input->post('lesson_section_id') . '/' . $lessonID . '/';
                    $upload_file_sizes = $this->media_storage->getTmpMultipleFileSize("lesson_attachment");
                    $this->saasvalidation->updateResouceQuota('storage', $upload_file_sizes);

                    $result = $this->media_storage->fileuploadMultiple('lesson_attachment', $dir_path);

                    if (!empty($result['uploaded'])) {

                        $bulk_data = [];

                        foreach ($result['uploaded'] as $file) {

                            $bulk_data[] = [
                                'lesson_id'       => $lessonID,
                                'attachment'      => $file['saved_name'],
                                'attachment_name' => $file['name']
                            ];
                        }
                      $this->courselesson_model->add_lesson_attachment_bulk($bulk_data); 
                    }

                    if ($result['total_failed_size'] > 0) {
                        $this->saasvalidation->deleteResouceQuota('storage', $result['total_failed_size']);
                    }
                }
                //=========================================

            } else {
                $videoData = array(
                    'id'             => $lessonID,
                    'video_provider' => $this->input->post('lesson_provider'),
                    'video_url'      => $this->input->post('lesson_url'),
                    'duration'       => $this->input->post('lesson_duration'),
                );
                if ($lesson_provider == "s3_bucket") {
                    if (isset($_FILES['lesson_file']) && $_FILES['lesson_file']['name'] != '') {
                        $file_name             = $_FILES['lesson_file']['name'];
                        $temp_file_location    = $_FILES['lesson_file']['tmp_name'];
                        $url                   = $this->aws3->uploadFile($file_name, $temp_file_location);
                        $getVideoUrl           = $_FILES['lesson_file']['name'];
                        $videoData['video_id'] = $getVideoUrl;
                    }
                } else {
                    if ($lesson_provider == 'youtube') {
                        $lesson_url = $this->input->post('lesson_url');
                        $video_id   = youtubeID($lesson_url);
                    } elseif ($lesson_provider == 'html5') {
                        $lesson_url = $this->input->post('lesson_url');
                    } elseif ($lesson_provider == 'vimeo') {
                        $lesson_url = $this->input->post('lesson_url');
                        $video_id   = vimeoID($lesson_url);
                    } else {
                        $lesson_url = "";
                        $video_id   = "";
                    }

                    $videoData['video_url'] = $lesson_url;
                    $videoData['video_id']  = $video_id;
                }

                // This is used to edit lesson video
                $this->courselesson_model->addlesson($videoData);
            }
    
                $attachmentsthumbnail = $this->courselesson_model->singlelessondetail($lessonID);
                $prev_file_size = 0;
                $total_image_upload_size = 0;

                if (isset($_FILES["lesson_thumbnail"]) && $_FILES['lesson_thumbnail']['name'] != '' && (!empty($_FILES['lesson_thumbnail']['name']))) {
                    $directory = './uploads/course_content/' . $attachmentsthumbnail->course_section_id . '/' . $lessonID."/";

                    $prev_file_size = $this->media_storage->getUploadedFileSize($attachmentsthumbnail->thumbnail,"uploads/course_content/$attachmentsthumbnail->course_section_id/$lessonID");

                    $thumbnail_image = $this->media_storage->fileupload("lesson_thumbnail", "$directory");
                    
                    if (!IsNullOrEmptyString($thumbnail_image)) {
                        $total_image_upload_size += $this->media_storage->getTmpFileSize('lesson_thumbnail');
                    }
                } else {
                    $thumbnail_image = $attachmentsthumbnail->thumbnail;
                }

                if ($prev_file_size > $total_image_upload_size) {
                        $size_difference = $prev_file_size - $total_image_upload_size;
                        $this->saasvalidation->deleteResouceQuota('storage', $size_difference);
                } elseif ($prev_file_size < $total_image_upload_size) {
                        $size_difference = $total_image_upload_size - $prev_file_size;
                        $this->saasvalidation->updateResouceQuota('storage', $size_difference);
                } else {
                }
                if (isset($_FILES["lesson_thumbnail"]) && $_FILES['lesson_thumbnail']['name'] != '' && (!empty($_FILES['lesson_thumbnail']['name']))) {
                    $this->media_storage->filedelete($attachmentsthumbnail->thumbnail,"uploads/course_content/$attachmentsthumbnail->course_section_id/$lessonID");
                }
                
                // This is used to edit lesson
                $this->courselesson_model->addlesson($sectionData);
                $upload_data = array('id' => $lessonID, 'thumbnail' => $thumbnail_image);
                // This is used to edit lesson thumbnail
                $this->courselesson_model->addlesson($upload_data);
                $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
            }
            echo json_encode($array);
        }

    /* This is used to delete lesson */
    public function deletelesson()
    {
        if (!$this->rbac->hasPrivilege('online_course_lesson', 'can_delete')) {
            access_denied();
        }
        $lessonID = $this->input->post('lessonID');

        if (!empty($lessonID)) {
            //==============
            $attachments = $this->courselesson_model->get_lesson_attachment_details_lessonid($lessonID);
            foreach ($attachments as $key => $row) {
                if ($row['attachment'] != '') {
                    $directory = 'uploads/course_content/' . $row['course_section_id'] . '/' . $lessonID;
                    $delete_file_size = $this->media_storage->getUploadedFileSize($row['attachment'], "$directory");
                    $this->saasvalidation->deleteResouceQuota('storage', $delete_file_size);
                    $this->media_storage->filedelete($row['attachment'], "$directory/");
                }
            }

                $attachmentsthumbnail = $this->courselesson_model->singlelessondetail($lessonID);
                if ($attachmentsthumbnail->thumbnail != '') {
                    $directory = 'uploads/course_content/' . $attachmentsthumbnail->course_section_id . '/' . $lessonID;
                    $delete_file_size = $this->media_storage->getUploadedFileSize($attachmentsthumbnail->thumbnail, "$directory");
                    $this->saasvalidation->deleteResouceQuota('storage', $delete_file_size);
                    $this->media_storage->filedelete($attachmentsthumbnail->thumbnail, "$directory/");
                }
            //==============

            // This is used to delete lesson
            $this->coursesection_model->deletequizlesson($lessonID, 'lesson');
            $this->courselesson_model->remove($lessonID);
            $arrays = array('status' => 'success', 'message' => $this->lang->line('delete_message'));
            echo json_encode($arrays);
        } else {
            $arrays = array('status' => 'success', 'error' => $this->lang->line('some_thing_went_wrong'), 'message' => '');
            echo json_encode($arrays);
        }
    }

    /*
    This is used for thumbnail validation
     */
    public function handle_upload($var, $name)
    {
        $image_validate = $this->config->item('image_validate');
        $result         = $this->filetype_model->get();
        if (isset($_FILES[$name]) && !empty($_FILES[$name]["name"])) {

            $file_type = $_FILES[$name]['type'];
            $file_size = $_FILES[$name]["size"];
            $file_name = $_FILES[$name]["name"];

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
            $this->form_validation->set_message('handle_upload', $this->lang->line('the_file_field_is_required'));
            return false;
        }
    }

    /*
    This is used to add lesson pdf file thumbnail validation
     */
    public function pdf_handle_upload()
    {

        $total_attachments = count($_FILES['lesson_attachment']['name']);
        for ($i = 0; $i < $total_attachments; $i++) {
            if (isset($_FILES["lesson_attachment"]) && !empty($_FILES['lesson_attachment']['name'][$i])) {
                $allowedExts = array("pdf");
                $temp        = explode(".", $_FILES["lesson_attachment"]["name"][$i]);
                $extension   = end($temp);
                if ($_FILES["lesson_attachment"]["error"][$i] > 0) {
                    $error .= "Error opening the file<br />";
                }
                if (($_FILES["lesson_attachment"]["type"][$i] != "application/pdf")) {
                    $this->form_validation->set_message('pdf_handle_upload', $this->lang->line('file_type_not_allowed'));
                    return false;
                }
                if (!in_array($extension, $allowedExts)) {
                    $this->form_validation->set_message('pdf_handle_upload', $this->lang->line('extension_not_allowed'));
                    return false;
                }
                return true;
            } else {
                $this->form_validation->set_message('pdf_handle_upload', $this->lang->line('attachment_field_is_required'));
                return false;
            }
        }
    }

    /*
    This is used to edit lesson pdf file thumbnail validation
     */
    public function edit_handle_upload()
    {

        $total_attachments = count($_FILES['lesson_attachment']['name']);
        for ($i = 0; $i < $total_attachments; $i++) {
            if (isset($_FILES["lesson_attachment"]) && !empty($_FILES['lesson_attachment']['name'][$i])) {
                $allowedExts = array("pdf");
                $temp        = explode(".", $_FILES["lesson_attachment"]["name"][$i]);
                $extension   = end($temp);
                if ($_FILES["lesson_attachment"]["error"][$i] > 0) {
                    $error .= "Error opening the file<br />";
                }
                if (($_FILES["lesson_attachment"]["type"][$i] != "application/pdf")) {
                    $this->form_validation->set_message('edit_handle_upload', $this->lang->line('file_type_not_allowed'));
                    return false;
                }
                if (!in_array($extension, $allowedExts)) {
                    $this->form_validation->set_message('edit_handle_upload', $this->lang->line('extension_not_allowed'));
                    return false;
                }
                return true;
            }
        }
    }

    /*
    This is used to add lesson doc file thumbnail validation
     */
    public function file_check()
    {

        $total_attachments = count($_FILES['lesson_attachment']['name']);
        for ($i = 0; $i < $total_attachments; $i++) {
            if (isset($_FILES["lesson_attachment"]) && !empty($_FILES['lesson_attachment']['name'][$i])) {
                $allowedExts = array("doc", "docx", "pptx", "pptm", "ppt", "xlsx", "xlsm");
                $temp        = explode(".", $_FILES["lesson_attachment"]["name"][$i]);
                $extension   = end($temp);
                if ($_FILES["lesson_attachment"]["error"][$i] > 0) {
                    $error .= "Error opening the file<br />";
                }
                if (
                    ($_FILES["lesson_attachment"]["type"][$i] != "application/vnd.openxmlformats-officedocument.wordprocessingml.document") &&
                    ($_FILES["lesson_attachment"]["type"][$i] != "application/vnd.openxmlformats-officedocument.wordprocessingml.document") &&
                    ($_FILES["lesson_attachment"]["type"][$i] != "application/vnd.openxmlformats-officedocument.presentationml.presentation") &&
                    ($_FILES["lesson_attachment"]["type"][$i] != "application/vnd.ms-powerpoint.presentation.macroEnabled.12") &&
                    ($_FILES["lesson_attachment"]["type"][$i] != "application/vnd.ms-powerpoint") &&
                    ($_FILES["lesson_attachment"]["type"][$i] != "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet") &&
                    ($_FILES["lesson_attachment"]["type"][$i] != "application/vnd.ms-excel.sheet.macroEnabled.12")
                ) {
                    $this->form_validation->set_message('file_check', $this->lang->line('file_type_not_allowed'));
                    return false;
                }
                if (!in_array(strtolower($extension), $allowedExts)) {
                    $this->form_validation->set_message('file_check', $this->lang->line('extension_not_allowed'));
                    return false;
                }
                return true;
            } else {
                $this->form_validation->set_message('file_check', $this->lang->line('attachment_field_is_required'));
                return false;
            }
        }
    }

    /*
    This is used to edit lesson doc file thumbnail validation
     */
    public function edit_file_check()
    {
        $total_attachments = count($_FILES['lesson_attachment']['name']);
        for ($i = 0; $i < $total_attachments; $i++) {
            if (isset($_FILES["lesson_attachment"]) && !empty($_FILES['lesson_attachment']['name'][$i])) {
                $allowedExts = array("doc", "docx", "pptx", "pptm", "ppt", "xlsx", "xlsm");
                $temp        = explode(".", $_FILES["lesson_attachment"]["name"][$i]);
                $extension   = end($temp);
                if ($_FILES["lesson_attachment"]["error"][$i] > 0) {
                    $error .= "Error opening the file<br />";
                }
                if (
                    ($_FILES["lesson_attachment"]["type"][$i] != "application/vnd.openxmlformats-officedocument.wordprocessingml.document") &&
                    ($_FILES["lesson_attachment"]["type"][$i] != "application/vnd.openxmlformats-officedocument.wordprocessingml.document") &&
                    ($_FILES["lesson_attachment"]["type"][$i] != "application/vnd.openxmlformats-officedocument.presentationml.presentation") &&
                    ($_FILES["lesson_attachment"]["type"][$i] != "application/vnd.ms-powerpoint.presentation.macroEnabled.12") &&
                    ($_FILES["lesson_attachment"]["type"][$i] != "application/vnd.ms-powerpoint") &&
                    ($_FILES["lesson_attachment"]["type"][$i] != "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet") &&
                    ($_FILES["lesson_attachment"]["type"][$i] != "application/vnd.ms-excel.sheet.macroEnabled.12")
                ) {
                    $this->form_validation->set_message('edit_file_check', $this->lang->line('file_type_not_allowed'));
                    return false;
                }
                if (!in_array($extension, $allowedExts)) {
                    $this->form_validation->set_message('edit_file_check', $this->lang->line('extension_not_allowed'));
                    return false;
                }
                return true;
            }
        }
    }

    /*
    This is used to add lesson text file thumbnail validation
     */
    public function text_check()
    {
        $total_attachments = count($_FILES['lesson_attachment']['name']);
        for ($i = 0; $i < $total_attachments; $i++) {
            if (isset($_FILES["lesson_attachment"]) && !empty($_FILES['lesson_attachment']['name'][$i])) {
                $allowedExts = array('txt');
                $temp        = explode(".", $_FILES["lesson_attachment"]["name"][$i]);
                $extension   = end($temp);
                if ($_FILES["lesson_attachment"]["error"][$i] > 0) {
                    $error .= "Error opening the file<br />";
                }
                if ($_FILES["lesson_attachment"]["type"][$i] !== "text/plain") {
                    $this->form_validation->set_message('text_check', $this->lang->line('file_type_not_allowed'));
                    return false;
                }
                if (!in_array(strtolower($extension), $allowedExts)) {
                    $this->form_validation->set_message('text_check', $this->lang->line('extension_not_allowed'));
                    return false;
                }
                return true;
            } else {
                $this->form_validation->set_message('text_check', $this->lang->line('attachment_field_is_required'));
                return false;
            }
        }
    }

    /*
    This is used to edit lesson text file thumbnail validation
     */
    public function edit_text_check()
    {
        $total_attachments = count($_FILES['lesson_attachment']['name']);
        for ($i = 0; $i < $total_attachments; $i++) {
            if (isset($_FILES["lesson_attachment"]) && !empty($_FILES['lesson_attachment']['name'][$i])) {
                $allowedExts = array('txt');
                $temp        = explode(".", $_FILES["lesson_attachment"]["name"][$i]);
                $extension   = end($temp);
                if ($_FILES["lesson_attachment"]["error"][$i] > 0) {
                    $error .= "Error opening the file<br />";
                }
                if ($_FILES["lesson_attachment"]["type"][$i] !== "text/plain") {
                    $this->form_validation->set_message('edit_text_check', $this->lang->line('file_type_not_allowed'));
                    return false;
                }
                if (!in_array($extension, $allowedExts)) {
                    $this->form_validation->set_message('edit_text_check', $this->lang->line('extension_not_allowed'));
                    return false;
                }
                return true;
            }
        }
    }

    public function get_lesson_attachment()
    {
        $lesson_id      =   $_POST['lesson_id'];
        $section_id     =   $_POST['section_id'];
        $data['attachments']    =   $this->courselesson_model->get_lesson_attachment_by_lessonid($lesson_id);
        $data['lesson_id']      =   $lesson_id;
        $data['section_id']     =   $section_id;
        $page_content           =   $this->load->view('onlinecourse/course/lesson_attachment_modal', $data, true);
        echo json_encode(array('status' => 1, 'page' => $page_content));
    }

    public function download_lesson_attachments($section_id, $lesson_id, $id)
    {
        $get_attachments =  $this->courselesson_model->get_lesson_attachment_by_id($id);
        $this->media_storage->filedownload($get_attachments->attachment, "uploads/course_content/$section_id/$lesson_id");
    }
}
