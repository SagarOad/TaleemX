<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Course extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('course_model', 'coursesection_model', 'courselesson_model', 'studentcourse_model', 'coursequiz_model', 'course_payment_model', 'courseofflinepayment_model', 'coursereport_model', 'coursecategory_model', 'coursetag_model','courseassignment_model','courseexam_model','video_transcript_model'));

        if ($this->router->fetch_method() != "setting") {
            $this->auth->addonchk('ssoclc', site_url('onlinecourse/course/setting'));
        }
        $this->load->library('course_mail_sms');
        $this->config->load('onlinecoursedata');
        $this->config->load("ci-blog");
        $this->course_provider          = $this->config->item('courseprovider');
        $this->course_preview_providers = $this->config->item('course_preview_providers');
        $this->lesson_type              = $this->config->item('lesson_type');
        $this->front_side_visibility    = $this->config->item('front_side_visibility');
        $this->result                = $this->customlib->getUserData();
        $this->load->library("aws3");
        $this->load->library("customlib");
        $config = array(
            'field' => 'slug',
            'title' => 'title',
            'table' => 'online_courses',
            'id'    => 'id',
        );
        $this->load->library('slug', $config);
        $this->load->library('media_storage');
        $this->load->helper('course');

        $this->load->model('coursecertificate_model');
        $this->load->library('SaasValidation');
    }

    public function validateCanUploadFile($str, $params_string)
    {
        $params_array = array_map('trim', explode(',', $params_string));
        return $this->saasvalidation->validateCanUploadFile($str, $params_array);
    }

    /**
     * If the Add Course form carries a pending transcript that was extracted
     * before saving (hidden inputs populated by the subtitle preview modal),
     * persist it as a `video_transcripts` row linked to the freshly-created
     * course id. Safe to call when no pending transcript is present.
     */
    /**
     * Diagnostic helper that reports what the server actually received for the
     * subtitle extraction request. Used in error responses to help identify
     * environment issues on remote servers (e.g. post_max_size exceeded, WAF
     * stripping fields, wrong provider selected, etc.).
     */
    private function _extract_debug_info($video_provider, $api_type, $video_url)
    {
        $content_length = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
        return array(
            'received_provider' => $video_provider,
            'mapped_api_type'   => $api_type,
            'received_url'      => $video_url,
            'post_keys'         => array_keys((array) $_POST),
            'files_keys'        => array_keys((array) $_FILES),
            'content_length'    => $content_length,
            'php_post_max_size'       => ini_get('post_max_size'),
            'php_upload_max_filesize' => ini_get('upload_max_filesize'),
        );
    }

    /**
     * Map PHP $_FILES['...']['error'] codes to a human-readable message that
     * points at the underlying server config problem (typical on shared
     * hosting where `upload_max_filesize` is set lower than what the user
     * tries to upload).
     */
    private function _describe_upload_error($error_code)
    {
        $upload_max = ini_get('upload_max_filesize');
        $post_max   = ini_get('post_max_size');
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded video is larger than the server limit (upload_max_filesize = ' . $upload_max . '). Please increase upload_max_filesize and post_max_size in php.ini and restart PHP.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded video is larger than the form\'s allowed size.';
            case UPLOAD_ERR_PARTIAL:
                return 'The upload was interrupted. Please try again.';
            case UPLOAD_ERR_NO_FILE:
                return 'No video file was received by the server.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Server error: missing temporary upload folder. Contact the administrator.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Server error: cannot write the uploaded file to disk. Contact the administrator.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the upload.';
            default:
                return 'Upload failed (error code ' . (int) $error_code . '). Server limits: upload_max_filesize=' . $upload_max . ', post_max_size=' . $post_max . '.';
        }
    }

    private function _persist_pending_transcript_if_any($course_id, $video_provider, $course_url, $video_id)
    {
        $segments_json   = (string) $this->input->post('pending_transcript_segments_json');
        $full_transcript = (string) $this->input->post('pending_transcript_full');
        if ($segments_json === '' || $full_transcript === '') {
            return;
        }
        $segments = json_decode($segments_json, true);
        if (!is_array($segments) || empty($segments)) {
            return;
        }
        $this->load->model('video_transcript_model');
        $this->video_transcript_model->upsert_transcript(
            Video_transcript_model::ENTITY_COURSE,
            (int) $course_id,
            array(
                'video_provider' => (string) $video_provider,
                'video_url'      => (string) $course_url,
                'video_id'       => (string) $video_id,
            ),
            array(
                'segments'  => $segments,
                'subtitles' => $full_transcript,
                'source'    => (string) $this->input->post('pending_transcript_source'),
            )
        );
    }

    /**
     * Uploads a course-preview video file directly into the CodeIgniter project
     * root (FCPATH) so it is always reachable via base_url(), regardless of any
     * tenant-specific folder_path stored in the settings/session. This avoids
     * an environment mismatch where folder_path points to a server-only path
     * (e.g. /var/www/..) that the local web server does not serve.
     *
     * Returns the saved filename on success, or null on failure / no file.
     */
    private function _upload_course_preview_file($field_name)
    {
        if (!isset($_FILES[$field_name]) || empty($_FILES[$field_name]['name'])) {
            return null;
        }

        $file = $_FILES[$field_name];
        if (!empty($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            log_message('error', '[COURSE_PREVIEW_UPLOAD] PHP upload error code=' . $file['error']);
            return null;
        }

        $relative_path = 'uploads/course/course_preview/';
        $upload_dir    = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);

        if (!is_dir($upload_dir)) {
            if (!@mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
                log_message('error', '[COURSE_PREVIEW_UPLOAD] mkdir FAILED for ' . $upload_dir);
                return null;
            }
        }
        if (!is_writable($upload_dir)) {
            log_message('error', '[COURSE_PREVIEW_UPLOAD] dir not writable: ' . $upload_dir);
            return null;
        }

        // Generate a safe, URL-friendly filename while preserving the extension.
        $ext            = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safe_ext       = preg_replace('/[^A-Za-z0-9]/', '', (string) $ext);
        $base_name      = pathinfo($file['name'], PATHINFO_FILENAME);
        $safe_base      = preg_replace('/[^A-Za-z0-9_\-]+/', '_', (string) $base_name);
        $safe_base      = trim($safe_base, '_');
        if ($safe_base === '') {
            $safe_base = 'video';
        }
        $final_filename = time() . '_' . uniqid() . '_' . $safe_base
            . ($safe_ext !== '' ? ('.' . strtolower($safe_ext)) : '');

        $destination = $upload_dir . $final_filename;

        if (!is_uploaded_file($file['tmp_name'])) {
            log_message('error', '[COURSE_PREVIEW_UPLOAD] is_uploaded_file FALSE for ' . $file['tmp_name']);
            return null;
        }

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $err = error_get_last();
            log_message('error', '[COURSE_PREVIEW_UPLOAD] move_uploaded_file FAILED: ' . var_export($err, true));
            return null;
        }

        return $final_filename;
    }

    /*
    This is used to show course list
     */
    public function index()
    {
        if (!$this->rbac->hasPrivilege('online_course', 'can_view')) {
            access_denied();
        }
        $userid = $this->result["id"];
        $roleid = $this->result["role_id"];
        $this->session->set_userdata('top_menu', 'onlinecourse');
        $this->session->set_userdata('sub_menu', 'onlinecourse/course/index');
        $search_course                 = $this->input->get('search_course');
        $data['classlist']                 = $this->class_model->get();
        $data['allTeacherList']            = $this->course_model->allteacher();
        $data["course_provider"]           = $this->course_provider;
        $data["course_preview_providers"]  = $this->course_preview_providers;
        $data["lesson_type"]               = $this->lesson_type;
        $data["front_side_visibility"]     = $this->front_side_visibility;
        $data['search_course']             = '';
        $search                        = ($this->uri->segment(4)) ? $this->uri->segment(4) : $search_course;

        $coursecount = '';
        if ($search_course != '') {
            $coursecount           = $this->course_model->courselist($userid, $roleid, '', '', $search);
            $data['search_course'] = $search;
        } else {
            $coursecount = $this->course_model->courselist($userid, $roleid, '', '', '');
        }

        $this->load->library('pagination');

        if ($this->uri->segment(4) > 1) {
            $config['base_url'] = base_url() . "onlinecourse/course/index/" . $this->uri->segment(5) . "/$search_course";
        } else {
            $config['base_url'] = base_url() . "onlinecourse/course/index/$search_course";
        }

        $config['total_rows'] = count($coursecount);
        $config['per_page']   = 40;
        // custom paging configuration
        $config['use_page_numbers']   = true;
        $config['reuse_query_string'] = true;

        $config['full_tag_open']  = '<div class="pagination">';
        $config['full_tag_close'] = '</div>';

        $config['first_link']      = '<i class="fa fa-step-backward" aria-hidden="true"></i>';
        $config['first_tag_open']  = '<span class="firstlink">';
        $config['first_tag_close'] = '</span>';

        $config['last_link']      = '<i class="fa fa-step-forward" aria-hidden="true"></i>';
        $config['last_tag_open']  = '<span class="lastlink">';
        $config['last_tag_close'] = '</span>';

        $config['next_link']      = '<i class="fa fa-forward" aria-hidden="true"></i>';
        $config['next_tag_open']  = '<span class="nextlink">';
        $config['next_tag_close'] = '</span>';

        $config['prev_link']      = '<i class="fa fa-backward" aria-hidden="true"></i>';
        $config['prev_tag_open']  = '<span class="prevlink">';
        $config['prev_tag_close'] = '</span>';

        $config['cur_tag_open']  = '<span class="curlink">';
        $config['cur_tag_close'] = '</span>';

        $config['num_tag_open']  = '<span class="numlink">';
        $config['num_tag_close'] = '</span>';
        $page_num                = ($this->uri->segment(4)) ? $this->uri->segment(4) : 1;

        $this->pagination->initialize($config);

        $courselist = '';
        if ($search_course != '') {
            $courselist            = $this->course_model->courselist($userid, $roleid, $config['per_page'], $this->uri->segment(4), $search);
            $data['search_course'] = $search;
        } else {
            $offset     = ($page_num - 1) * $config['per_page'];
            $courselist = $this->course_model->courselist($userid, $roleid, $config['per_page'], $offset, '');
        }

        $new_courselist       = array();
        $multipalsectionarray = array();
        foreach ($courselist as $courselist_value) {
            $lesson_count                         =   $this->studentcourse_model->totallessonbycourse($courselist_value['id']);
            $quiz_count                           =   $this->studentcourse_model->totalquizbycourse($courselist_value['id']);
            $assignment_count                     =   $this->studentcourse_model->totalassignmentbycourse($courselist_value['id']);
            $exam_count                           =   $this->studentcourse_model->totalexambycourse($courselist_value['id']);

            $courselist_value['total_lesson']       =   $lesson_count[0]['total_lesson'];
            $courselist_value['total_quiz']         =   $quiz_count[0]['total_quiz'];
            $courselist_value['total_assignment']   =   $assignment_count[0]['total_assignment'];
            $courselist_value['total_exam']         =   $exam_count[0]['total_exam'];

            $courselist_value['total_hour_count']   = $this->studentcourse_model->counthours($courselist_value['id']);
            $multipalsection                        = $this->course_model->multipalsection($courselist_value['id']);
            $multipalsectionarray[]                 = $multipalsection;
            $new_courselist[]                       = $courselist_value;
        }
        $data['multipalsection']    = $multipalsectionarray;
        $data['new_courselist']     = $new_courselist;
        $data['userid']             = $userid;
        $data['roleid']             = $roleid;
        $data['category_result']    = $this->coursecategory_model->getcategory();
        $data['question_type']      = $this->config->item('question_type');
        $data['question_level']     = $this->config->item('question_level');
        $data['gettags']            = $this->coursetag_model->gettags();
        $data['certificateList'] = $this->coursecertificate_model->certificateList();


        $this->load->view('layout/header');
        $this->load->view('onlinecourse/course/courselist', $data);
        $this->load->view('layout/footer');
    }

    /* This is used to create course */
    public function create()
    {
        if (!$this->rbac->hasPrivilege('online_course', 'can_add')) {
            access_denied();
        }
        $userid = $this->result["id"];
        $roleid = $this->result["role_id"];

        $storage_array = "course_thumbnail"; // use comma for multiple files
        $this->form_validation->set_rules('validate_storage', $this->lang->line('storage'), "callback_validateCanUploadFile[$storage_array]");


        $this->form_validation->set_rules('title', $this->lang->line('title'), 'trim|required|is_unique[online_courses.title]|xss_clean', array('is_unique' => $this->lang->line('the_title_field_is_already_exist')));
        $this->form_validation->set_rules('course_thumbnail', $this->lang->line('thumbnail') . ' ' . $this->lang->line('field_is_required'), 'callback_handle_upload[course_thumbnail]');
        $this->form_validation->set_rules('description', $this->lang->line('description'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('category_id', $this->lang->line('course_category'), 'trim|required|xss_clean');
        // $this->form_validation->set_rules('certificate_template_id', $this->lang->line('certificate'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('class', $this->lang->line('class'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('section[]', $this->lang->line('section'), 'required|trim|xss_clean');
        $is_free      = $this->input->post('free_course');
        $course_price = $this->input->post('course_price');
        $discount     = $this->input->post('course_discount');

        if (empty($is_free) && empty($course_price)) {
            $this->form_validation->set_rules('course_price', $this->lang->line('price'), 'trim|required|xss_clean');
            $is_free = 0;
        }
        if (empty($is_free)) {
            $is_free = 0;
        }
        if (!empty($course_price)) {
            $this->form_validation->set_rules('course_price', $this->lang->line('price'), 'trim|regex_match[/^[0-9.]+$/]|xss_clean', array('regex_match' => $this->lang->line('the_course_price_field_is_allowed_ony_numeric_and_float_value')));
        }
        if (!empty($discount)) {
            $this->form_validation->set_rules('course_discount', $this->lang->line('discount'), 'trim|regex_match[/^[0-9.]+$/]|xss_clean', array('regex_match' => $this->lang->line('the_course_discount_field_is_allowed_ony_numeric_and_float_value')));
        }
        if ($roleid != "2") {
            $this->form_validation->set_rules('teacher', $this->lang->line('assign_teacher'), 'trim|required|xss_clean');
        }
        $sectionarray = $this->input->post('section');
        if ($this->form_validation->run() == false) {
            $msg = array(
                'title'                     => form_error('title'),
                'course_thumbnail'          => form_error('course_thumbnail'),
                'description'               => form_error('description'),
                'class'                     => form_error('class'),
                'teacher'                   => form_error('teacher'),
                'course_price'              => form_error('course_price'),
                'course_discount'           => form_error('course_discount'),
                'category_id'               => form_error('category_id'),
                'section'                   => form_error('section[]'),
                'validate_storage'           => form_error('validate_storage'),

            );
            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $course_provider = $this->input->post('course_provider');
            $getVideoUrl     = $this->input->post('course_url');

            $teacher = '';
            if ($roleid == "2") {
                $teacher = $userid;
            } else {
                $teacher = $this->input->post('teacher');
            }

            if ($course_provider == "s3_bucket") {
                if (isset($_FILES['s3_file'])) {
                    $file_name          = $_FILES['s3_file']['name'];
                    $temp_file_location = $_FILES['s3_file']['tmp_name'];
                    $url                = $this->aws3->uploadFile($file_name, $temp_file_location);
                    $getVideoUrl        = $_FILES['s3_file']['name'];
                }
            } elseif ($course_provider == "file") {
                $uploaded_name = $this->_upload_course_preview_file('s3_file');
                $getVideoUrl = $uploaded_name !== null ? $uploaded_name : '';
                // For local file uploads we store the filename in video_id; course_url is not used.
                $_POST['course_url'] = '';
            } else {

                if ($course_provider == 'youtube') {
                    $video_id   = youtubeID($getVideoUrl);
                } elseif ($course_provider == 'vimeo') {
                    $video_id   = vimeoID($getVideoUrl);
                } else {
                    $video_id   = "";
                }

                $getVideoUrl  = $video_id;
            }
            if ($course_price) {
                $new_course_price = convertCurrencyFormatToBaseAmount($course_price);
            } else {
                $new_course_price = '';
            }
            $data = array(
                'title'                 => $this->input->post('title'),
                'outcomes'              => json_encode($this->input->post('outcomes')),
                'description'           => $this->input->post('description'),
                'teacher_id'            => $teacher,
                'course_provider'       => $this->input->post('course_provider'),
                'price'                 => $new_course_price,
                'discount'              => $discount,
                'free_course'           => $is_free,
                'course_url'            => $this->input->post('course_url'),
                'video_id'              => $getVideoUrl,
                'created_by'            => $userid,
                'front_side_visibility' => $this->input->post('front_side_visibility'),
                'category_id'           => $this->input->post('category_id'),
                'certificate_template_id' => $this->input->post('certificate_template_id'),
                'created_date'          => date('Y-m-d h:i:s'),
                'updated_date'          => date('Y-m-d h:i:s'),
            );

            $data['slug'] = $this->slug->create_uri($data);
            $data['url']  = $this->config->item('ci_course_detail_url') . $data['slug'];

            // This is used to add course
            $insert_id = $this->course_model->add($data);
            if (!empty($sectionarray)) {
                foreach ($sectionarray as $sectionarray_value) {
                    $sectiondata = array(
                        'course_id'        => $insert_id,
                        'class_section_id' => $sectionarray_value,
                        'created_date'     => date('Y-m-d h:i:s'),
                    );
                    // This is used to add section by class
                    $this->course_model->addsections($sectiondata);
                }
            }

            // If the user extracted subtitles before saving the course, the
            // preview modal stashed the transcript into hidden inputs. Persist
            // it now that we have a real course id.
            $this->_persist_pending_transcript_if_any(
                (int) $insert_id,
                $course_provider,
                (string) $this->input->post('course_url'),
                (string) $getVideoUrl
            );

            $total_documents_failed_size = 0;
            $thumbnail_image = "";




            $update_student_data = [];
            $total_image_failed_size = 0;



            $update_student_data['course_thumbnail'] = null;

            if (isset($_FILES['course_thumbnail']) && !empty($_FILES['course_thumbnail']['name'])) {
                $storage_array = ['course_thumbnail'];
                $this->saasvalidation->updateStorageLimit('storage', $storage_array);

                $ext                     = pathinfo($_FILES['course_thumbnail']['name'], PATHINFO_EXTENSION);
                $config['upload_path']   = 'uploads/course/course_thumbnail/';
                $config['allowed_types'] = $ext;
                $config['file_name']     = "course_thumbnail" . $insert_id;
                $this->load->library('upload', $config);
                $this->upload->initialize($config);

                if ($this->upload->do_upload('course_thumbnail')) {
                    $uploadData      = $this->upload->data();
                    $thumbnail_image = $uploadData['file_name'];
                } else {
                    $thumbnail_image = '';
                    $total_image_failed_size += $this->media_storage->getTmpFileSize('course_thumbnail');
                }
            }



            if ($total_image_failed_size > 0) {
                $this->saasvalidation->deleteResouceQuota('storage', $total_image_failed_size);
            }



            // if (!empty($_FILES['course_thumbnail']['name'])) {
            // 	try {

            // 		$storage_array = ['course_thumbnail'];
            // 		$this->saasvalidation->updateStorageLimit('storage', $storage_array);

            // 		$ext                     = pathinfo($_FILES['course_thumbnail']['name'], PATHINFO_EXTENSION);
            // 		$config['upload_path']   = 'uploads/course/course_thumbnail/';
            // 		$config['allowed_types'] = $ext;
            // 		$file_name               = $_FILES['course_thumbnail']['name'];
            // 		$config['file_name']     = "course_thumbnail" . $insert_id;
            // 		$this->load->library('upload', $config);
            // 		$this->upload->initialize($config);
            // 		if ($this->upload->do_upload('course_thumbnail')) {
            // 			$uploadData      = $this->upload->data();
            // 			$thumbnail_image = $uploadData['file_name'];

            // 		} else {
            // 			$thumbnail_image = '';
            // 			$total_documents_failed_size = $this->media_storage->getTmpFileSize('course_thumbnail');

            // 		}				 

            // 		if ($total_documents_failed_size > 0) {
            // 			$this->saasvalidation->deleteResouceQuota('storage', $total_documents_failed_size);
            // 		}

            // 	} catch (Exception $e) {

            // 		$thumbnail_image = "";
            // 		log_message('error', 'Thumbnail upload error: ' . $e->getMessage());

            // 		$array = array('status' => 'fail', 'error' => $msg, 'message' => $e->getMessage());
            // 		echo json_encode($array);
            // 		return;
            // 	}

            // }  


            $upload_data = array('id' => $insert_id, 'course_thumbnail' => $thumbnail_image);

            $this->course_model->add($upload_data);
            $array = array('course_id' => $insert_id, 'status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
        }
        echo json_encode($array);
    }

    public function editcourse()
    {
        if (!$this->rbac->hasPrivilege('online_course', 'can_edit')) {
            access_denied();
        }
        $this->session->set_userdata('sub_menu', 'admin/coursestaff');
        $courseID                      = $this->input->post('courseID');
        $data['courseID']              = $courseID;
        $data['classlist']                 = $this->class_model->get();
        $data['allTeacherList']            = $this->course_model->allteacher();
        $data["course_provider"]           = $this->course_provider;
        $data["course_preview_providers"]  = $this->course_preview_providers;
        $coursesList                       = $this->course_model->singlecourselist($courseID);
        $data['classid']                   = $this->course_model->getclassid($courseID);
        $data['coursesList']               = $coursesList;
        $data['created_by']                = $this->staff_model->searchFullText("", 1);
        $data["front_side_visibility"]     = $this->front_side_visibility;
        $data['category_result']           = $this->coursecategory_model->getcategory();
        $data['certificateList']           = $this->coursecertificate_model->certificateList();

        $this->load->view('onlinecourse/course/courseedit', $data);
    }

    /* This is used to update course */
    public function updatecourse()
    {
        if (!$this->rbac->hasPrivilege('online_course', 'can_edit')) {
            access_denied();
        }
        $course_thumbnail = $_FILES['edit_course_thumbnail']['name'];
        $userid           = $this->result["id"];
        $roleid           = $this->result["role_id"];
        $this->form_validation->set_rules('title', $this->lang->line('title'), 'trim|required|xss_clean');


        if ($course_thumbnail != '') {

            $storage_array = "edit_course_thumbnail";
            $this->form_validation->set_rules('validate_storage', $this->lang->line('storage'), "callback_validateCanUploadFile[$storage_array]");

            $this->form_validation->set_rules('edit_course_thumbnail', $this->lang->line('thumbnail') . ' ' . $this->lang->line('field_is_required'), 'callback_handle_upload[edit_course_thumbnail]');
        }
        $this->form_validation->set_rules('description', $this->lang->line('description'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('class', $this->lang->line('class'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('category_id', $this->lang->line('course_category'), 'trim|required|xss_clean');
        // $this->form_validation->set_rules('certificate_template_id', $this->lang->line('certificate'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('section[]', $this->lang->line('section'), 'required|trim|xss_clean');
        $sectionarray = $this->input->post('section');
        $is_free      = $this->input->post('free_course');
        $course_price = $this->input->post('course_price');
        $discount     = $this->input->post('course_discount');

        if (empty($is_free) && empty($course_price)) {
            $this->form_validation->set_rules('course_price', $this->lang->line('price'), 'trim|required|xss_clean');
            $is_free = 0;
        }
        if (empty($is_free)) {
            $is_free = 0;
        }
        if (!empty($course_price)) {
            $this->form_validation->set_rules('course_price', $this->lang->line('price'), 'trim|regex_match[/^[0-9.]+$/]|xss_clean', array('regex_match' => $this->lang->line('the_course_price_field_is_allowed_ony_numeric_and_float_value')));
        }
        if (!empty($discount)) {
            $this->form_validation->set_rules('course_discount', $this->lang->line('discount'), 'trim|regex_match[/^[0-9.]+$/]|xss_clean', array('regex_match' => $this->lang->line('the_course_discount_field_is_allowed_ony_numeric_and_float_value')));
        }
        if ($roleid != "2") {
            $this->form_validation->set_rules('teacher', $this->lang->line('assign_teacher'), 'trim|required|xss_clean');
        }
        if ($this->form_validation->run() == false) {
            $msg = array(
                'title'                     => form_error('title'),
                'edit_course_thumbnail'     => form_error('edit_course_thumbnail'),
                'description'               => form_error('description'),
                'class'                     => form_error('class'),
                'teacher'                   => form_error('teacher'),
                'course_price'              => form_error('course_price'),
                'course_discount'           => form_error('course_discount'),
                'category_id'               => form_error('category_id'),
                'section'                   => form_error('section[]'),
                'validate_storage'           => form_error('validate_storage'),
            );
            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $course_provider = $this->input->post('course_provider');
            $getVideoUrl     = $this->input->post('course_url');

            $courseID = $this->input->post('edit_courseID');
            $teacher  = '';
            if ($roleid == "2") {
                $teacher = $this->result["id"];
            } else {
                $teacher = $this->input->post('teacher');
            }

            if ($course_price) {
                $new_course_price = convertCurrencyFormatToBaseAmount($course_price);
            } else {
                $new_course_price = '';
            }

            $data = array(
                'id'                        => $courseID,
                'title'                     => $this->input->post('title'),
                'outcomes'                  => json_encode($this->input->post('outcomes')),
                'description'               => $this->input->post('description'),
                'teacher_id'                => $teacher,
                'course_provider'           => $this->input->post('course_provider'),
                'price'                     => $new_course_price,
                'discount'                  => $discount,
                'free_course'               => $is_free,
                'course_url'                => $this->input->post('course_url'),
                'front_side_visibility'     => $this->input->post('front_side_visibility'),
                'category_id'               => $this->input->post('category_id'),
                'certificate_template_id'   => $this->input->post('certificate_template_id'),
                'updated_date'              => date('Y-m-d h:i:s'),
            );

            if ($course_provider == "s3_bucket") {
                if (isset($_FILES['s3_file']) && $_FILES['s3_file']['name'] != '') {
                    $file_name          = $_FILES['s3_file']['name'];
                    $temp_file_location = $_FILES['s3_file']['tmp_name'];
                    $url                = $this->aws3->uploadFile($file_name, $temp_file_location);
                    $getVideoUrl        = $_FILES['s3_file']['name'];
                    $data['video_id']   = $getVideoUrl;
                }
            } elseif ($course_provider == "file") {
                $uploaded_name = $this->_upload_course_preview_file('s3_file');
                if (!empty($uploaded_name)) {
                    $data['video_id'] = $uploaded_name;
                }
                // For local file uploads we store the filename in video_id; course_url is not used.
                $data['course_url'] = '';
            } else {

                if ($course_provider == 'youtube') {
                    $video_id   = youtubeID($getVideoUrl);
                } elseif ($course_provider == 'vimeo') {
                    $video_id   = vimeoID($getVideoUrl);
                } else {
                    $video_id   = "";
                }

                $data['video_id'] = $video_id;
            }
            if ($roleid == 7) {

                $data['created_by'] = $this->input->post('created_by');
            }

            $data['slug'] = $this->slug->create_uri($data);
            $data['url']  = $this->config->item('ci_course_detail_url') . $data['slug'];

            $this->course_model->add($data);
            $section_count = $this->course_model->sectioncount($courseID);
            if (!empty($sectionarray)) {
                $classsectionlist = $this->course_model->sectionbycourse($courseID);
                if (!empty($classsectionlist)) {
                    foreach ($classsectionlist as $classsectionlist_value) {
                        if (!(in_array($classsectionlist_value, $sectionarray))) {
                            $this->course_model->remove($classsectionlist_value, $courseID);
                        }
                    }
                    foreach ($sectionarray as $sectionarray_value) {
                        if (!(in_array($sectionarray_value, $classsectionlist))) {
                            $sectiondata = array(
                                'course_id'        => $courseID,
                                'class_section_id' => $sectionarray_value,
                                'created_date'     => date('Y-m-d h:i:s'),
                            );
                            // This is used to add section by class
                            $this->course_model->addsections($sectiondata);
                        }
                    }
                }
            }


            $total_image_upload_size = 0;
            $total_documents_failed_size = 0;
            $thumbnail_image = "";

            if (!empty($_FILES['edit_course_thumbnail']['name'])) {

                try {

                    $coursesList    = $this->course_model->singlecourselist($courseID);

                    $prev_file_size = $this->media_storage->getUploadedFileSize($coursesList['course_thumbnail'], 'uploads/course/course_thumbnail');


                    $ext                     = pathinfo($_FILES['edit_course_thumbnail']['name'], PATHINFO_EXTENSION);
                    $config['upload_path']   = 'uploads/course/course_thumbnail/';
                    $config['allowed_types'] = $ext;
                    $file_name               = $_FILES['edit_course_thumbnail']['name'];
                    $config['file_name']     = "course_thumbnail" . $courseID;
                    $this->load->library('upload', $config);
                    $this->upload->initialize($config);
                    if ($this->upload->do_upload('edit_course_thumbnail')) {
                        $uploadData      = $this->upload->data();


                        $thumbnail_image = $uploadData['file_name'];

                        $total_image_upload_size = $this->media_storage->getTmpFileSize('edit_course_thumbnail');
                    } else {
                        $thumbnail_image = $this->input->post('old_background');
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
            }


            $upload_data = array('id' => $courseID, 'course_thumbnail' => $thumbnail_image);
            // This is used to edit course thumbnail
            $this->course_model->add($upload_data);
            $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
        }
        echo json_encode($array);
    }

    /* This is for delete course */
    public function deletecourse()
    {
        if (!$this->rbac->hasPrivilege('online_course', 'can_delete')) {
            access_denied();
        }
        $courseID = $this->input->post('courseID');
        if ($courseID != '') {
            $delete_file_size=0;
            $delete_lesson_file_size=0;
            $delete_assignment_file_size=0;
            $delete_exam_file_size=0;
            $delete_docs_file_size=0;

            //=============================
            // delete course thumbnail
            $row = $this->course_model->singlecourselist($courseID);
            if ($row['course_thumbnail'] != '') {
                $delete_file_size = $this->media_storage->getUploadedFileSize($row['course_thumbnail'], 'uploads/course/course_thumbnail');
                $this->saasvalidation->deleteResouceQuota('storage', $delete_file_size);
                $this->media_storage->filedelete($row['course_thumbnail'], "uploads/course/course_thumbnail/");
            }
            //=============================
            //delete lesson attachment
            $attachments = $this->courselesson_model->getLessonAttachmentListByCourseId($courseID);
            foreach ($attachments as $key => $attachmentsrow) {
                if ($attachmentsrow['attachment'] != '') {
                    $directory = 'uploads/course_content/' . $attachmentsrow['course_section_id'] . '/' . $attachmentsrow['lesson_id'];
                    $delete_lesson_file_size = $this->media_storage->getUploadedFileSize($attachmentsrow['attachment'], "$directory");
                    $this->saasvalidation->deleteResouceQuota('storage', $delete_lesson_file_size);
                    $this->media_storage->filedelete($attachmentsrow['attachment'], "$directory/");
                }
            }
            //=============================
            //delete lesson Lesson thumbnail
            $lesson_thumbnail = $this->courselesson_model->getLessonthumbnailListByCourseId($courseID);
            foreach ($lesson_thumbnail as $key => $thumbnail) {
                if ($thumbnail['thumbnail'] != '') {
                    $directory = 'uploads/course_content/' . $thumbnail['course_section_id'] . '/' . $thumbnail['id'];
                    $delete_thumbnail_file_size = $this->media_storage->getUploadedFileSize($thumbnail['thumbnail'], "$directory");
                    $this->saasvalidation->deleteResouceQuota('storage', $delete_thumbnail_file_size);
                    $this->media_storage->filedelete($thumbnail['thumbnail'], "$directory/");
                }
            }
            //=============================
            //delete assignment attachment
            $getassignmentattachment   =   $this->courseassignment_model->getAssignmentByCourseId($courseID);     
            foreach ($getassignmentattachment as $key => $value) {
                if ($value['document'] != '') {
                    $delete_assignment_file_size = $this->media_storage->getUploadedFileSize($value['document'], "uploads/course_content/online_course_assignment");
                    $this->saasvalidation->deleteResouceQuota('storage', $delete_assignment_file_size);
                    $this->media_storage->filedelete($value['document'], "uploads/course_content/online_course_assignment/");
                }
            }
            //=============================
            //delete online course exam attachment result
            $getexamattachment   =   $this->courseexam_model->getExamAttachmentByCourseId($courseID);   
            foreach ($getexamattachment as $key => $examvalue) {
                if ($examvalue['attachment_upload_name'] != '') {
                    $delete_exam_file_size = $this->media_storage->getUploadedFileSize($examvalue['attachment_upload_name'], "uploads/course_content/online_course_exam_result");
                    $this->saasvalidation->deleteResouceQuota('storage', $delete_exam_file_size);
                    $this->media_storage->filedelete($examvalue['attachment_upload_name'], "uploads/course_content/online_course_exam_result/");
                }
            }
            //=============================
            //delete submit assignments course exam attachment
            $getsubmitassignmentattachment  =   $this->courseassignment_model->getSubmitAssignmentByCourseId($courseID);       
            foreach ($getsubmitassignmentattachment as $key => $aasignvalue) {
                if ($aasignvalue['docs'] != '') {
                    $delete_docs_file_size = $this->media_storage->getUploadedFileSize($aasignvalue['docs'], "uploads/course_content/online_course_assignment");
                    $this->saasvalidation->deleteResouceQuota('storage', $delete_docs_file_size);
                    $this->media_storage->filedelete($aasignvalue['docs'], "uploads/course_content/online_course_assignment/");
                }
            }
            //=============================
            $this->course_model->delete($courseID);
            $arrays = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('delete_message'));
        } else {
            $arrays = array('status' => 'fail', 'error' => $this->lang->line('some_thing_went_wrong'), 'message' => '');
        }
        echo json_encode($arrays);
    }

   

    /*This is for course detail view*/
    public function coursedetail()
    {
        if (!$this->rbac->hasPrivilege('online_course', 'can_view')) {
            access_denied();
        }
        $courseID                = $this->input->post('courseID');
        $data['courseID']        = $courseID;
        $data['coursesList']     = $this->course_model->singlecourselist($courseID);
        $sectionList             = $this->coursesection_model->getsectionbycourse($courseID);
        $data['sectionList']     = $sectionList;
        $data["course_provider"] = $this->course_provider;
        $data["lesson_type"]     = $this->lesson_type;
        $data['multipalsection'] = $this->course_model->multipalsection($courseID);

        $lessonquizlist_array    = array();
        $quizquestiondetail      = array();
        $lessonassignmentlist_array      = array();
        $onlineexam_array      = array();

        if (!empty($sectionList)) {
            foreach ($sectionList as $sectionList_value) {
                $lessonquizlist_array[$sectionList_value->id] = $this->coursesection_model->lessonquizbysection($sectionList_value->id);
                foreach ($lessonquizlist_array[$sectionList_value->id] as $lessonquizlist_array_value) {
                    if (!empty($lessonquizlist_array_value['quiz_id'])) {
                        $quizquestiondetail[$lessonquizlist_array_value['quiz_id']] = $this->coursequiz_model->questionlist($lessonquizlist_array_value['quiz_id']);
                    }
                }
            }

            $data['lessonquizdetail']   = $lessonquizlist_array;
            $data['quizquestiondetail'] = $quizquestiondetail;
        } else {
            $data['lessonquizdetail']   = '';
            $data['quizquestiondetail'] = '';
        }
        $data['superadmin_visible'] = $this->customlib->superadmin_visible();
        $data['role']  = json_decode($this->customlib->getStaffRole());
        $this->load->view('onlinecourse/course/coursedetail', $data);
    }

    /*This is for download course in pdf, txt,.doc format */
    public function download($doc, $section_id, $lesson_id)
    {
        $this->load->helper('download');
        $filepath = "./uploads/course_content/" . $section_id . "/" . $lesson_id . "/" . $doc;
        $data     = file_get_contents($filepath);
        $name     = $doc;
        force_download($name, $data);
    }

    /*This is used to get selected section by class*/
    public function getsection()
    {
        if (!$this->rbac->hasPrivilege('online_course_section', 'can_view')) {
            access_denied();
        }
        $classid         = $this->input->post('classid');
        $courseid        = $this->input->post('courseid');
        $multipalsection = $this->course_model->selectedsection($courseid, $classid);
        foreach ($multipalsection as $key => $value) {
            $multisection[] = $value['class_section_id'];
        }
        $data['multipalsection'] = $multisection;
        $data['sectionlist']     = $this->section_model->getClassBySection($classid);
        $this->load->view('onlinecourse/course/coursesection', $data);
    }

    /* This is used to validate image*/
    public function handle_upload($var, $name)
    {
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
            $this->form_validation->set_message('handle_upload', $this->lang->line('the_thumbnail_field_is_required'));
            return false;
        }
    }

    /*This is used to publish course */
    public function publish_unpublish()
    {
        if (!$this->rbac->hasPrivilege('course_publish', 'can_view')) {
            access_denied();
        }
        $currency_symbol = $this->customlib->getSchoolCurrencyFormat();
        $data['id']     = $courseID     = $this->input->post('courseID');
        $data['status'] = $status = $this->input->post('status');
        $this->course_model->add($data);
        $multipalsection        = $this->course_model->multipalsection($courseID);
        $coursesList            = $this->course_model->singlecourselist($courseID);
        $price                  = $coursesList['price'];
        $discount               = $coursesList['discount'];
        $free_course            = $coursesList['free_course'];
        $staff                  = $coursesList['staff_name'] . ' ' . $coursesList['staff_surname'] . ' (' . $coursesList['assign_employee_id'] . ')';
        $class_section_id       = "";
        $store_class_section_id = array();
        $section                = "";
        $store_section          = array();
        foreach ($multipalsection as $multipalsection_value) {
            if (!in_array($multipalsection_value['section'], $store_section)) {
                $store_section[] = $multipalsection_value['section'];
                $section .= $multipalsection_value['section'] . ", ";
                $store_class_section_id[] = $multipalsection_value['class_section_id'];
                $class_section_id .= $multipalsection_value['class_section_id'] . ", ";
            }
        }

        if ($free_course == 1) {
            $paid_free = "Free";
        } else {
            $paid_free = "Paid";
        }
        if (!empty($courseID) && ($status == 1)) {
            $sender_details = array(
                'courseid'         => $courseID,
                'class'            => $coursesList['class'],
                'section'          => $section,
                'class_section_id' => $class_section_id,
                'title'            => $this->input->post('title'),
                'price'            => $price,
                'discount'         => $discount,
                'paid_free'        => $paid_free,
                'assign_teacher'   => $staff,
            );
            $this->course_mail_sms->purchasemail('online_course_publish', $sender_details);
        }
    }

    /*This is used to get all course list to show in datatable */
    public function getcourselist()
    {
        if (!$this->rbac->hasPrivilege('online_course', 'can_view')) {
            access_denied();
        }
        $userid          = $this->result["id"];
        $roleid          = $this->result["role_id"];
        $currency_symbol = $this->customlib->getSchoolCurrencyFormat();
        $courselist      = $this->course_model->getcourselist($userid, $roleid, '');
        $m       = json_decode($courselist);
        $dt_data = array();
        if (!empty($m->data)) {
            foreach ($m->data as $key => $value) {

                $lessonquizcount  = $this->studentcourse_model->lessonquizcountbycourseid($value->id, '');
                $free_course      = $value->free_course;
                $discount         = $value->discount;
                $price            = $value->price;
                $discount_price   = '';
                $price            = '';
                $section_name     = "";
                $lesson_count     = $lessonquizcount['lessoncount'];
                $quiz_count       = $lessonquizcount['quizcount'];
                $assignment_count =   $lessonquizcount['assignmentcount'];
                $exam_count       =   $lessonquizcount['examcount'];
                $multipalsection  = $this->course_model->multipalsection($value->id);
                $total_hour_count = $this->studentcourse_model->counthours($value->id);
                $section_total    = $this->coursesection_model->getsectioncount($value->id);

                if ($value->discount != '0.00') {
                    $discount = $value->price - (($value->price * $value->discount) / 100);
                }

                if (($value->free_course == 1) && ($value->price == '0.00')) {
                    $price    = $this->lang->line('free');
                    $discount = $this->lang->line('free');
                } elseif (($value->free_course == 1) && ($value->price != '0.00')) {
                    if ($value->price > '0.00') {
                        $courseprice = amountFormat($value->price);
                    } else {
                        $courseprice = $this->lang->line('free');
                    }
                    $price    = $courseprice;
                    $discount = $this->lang->line('free');
                } elseif (($value->price != '0.00') && ($value->discount != '0.00')) {
                    $discount = amountFormat(($discount), 2, '.', '');
                    if ($value->price > '0.00') {
                        $courseprice = amountFormat($value->price);
                    } else {
                        $courseprice = '';
                    }
                    $price = $courseprice;
                } else {
                    $price    = amountFormat($value->price);
                    $discount = amountFormat($value->price);
                }

                $section       = "";
                $store_section = array();
                foreach ($multipalsection as $multipalsection_value) {
                    if (!in_array($multipalsection_value['section'], $store_section)) {
                        $store_section[] = $multipalsection_value['section'];
                        $section .= $multipalsection_value['section'] . ", ";
                    }
                }

                $course_detail = '';
                if ($this->rbac->hasPrivilege('online_course', 'can_view')) {
                    $course_detail = "<a href='#' onclick='loadcoursedetail(" . '"' . $value->id . '"' . "  )' class='btn btn-primary btn-xs btn-add ' data-id=" . $value->id . " data-backdrop='static' data-keyboard='false' data-placement='top' data-toggle='modal' data-target='#course_detail_modal' title=" . $this->lang->line('manage_course') . "><i class='fa fa-reorder'></i></a> &nbsp &nbsp";
                }

                $row   = array();
                $row[] = $value->title;
                $row[] = $value->class . " (" . rtrim($section, ", ") . ")";
                $row[] = $section_total;
                $row[] = $lessonquizcount['lessoncount'];

                if ($this->customlib->get_online_course_curriculam_status("online_course_quiz") == "") {
                    $row[] = $lessonquizcount['quizcount'];
                }
                if ($this->customlib->get_online_course_curriculam_status("online_course_exam") == "") {
                    $row[] = $lessonquizcount['examcount'];
                }
                if ($this->customlib->get_online_course_curriculam_status("online_course_assignment") == "") {
                    $row[] = $lessonquizcount['assignmentcount'];
                }

                if (!empty($total_hour_count) && $total_hour_count != '00:00:00') {
                    $row[] = $total_hour_count . ' ' . $this->lang->line('hrs');
                } else {
                    $row[] = '';
                }
                $row[]     = $price;
                $row[]     = $discount;
                $row[]     = date($this->customlib->getSchoolDateFormat(), strtotime($value->updated_date));
                $row[]     = $course_detail;
                $dt_data[] = $row;
            }
        }

        $json_data = array(
            "draw"            => intval($m->draw),
            "recordsTotal"    => intval($m->recordsTotal),
            "recordsFiltered" => intval($m->recordsFiltered),
            "data"            => $dt_data,
        );
        echo json_encode($json_data);
    }

    /* This is used to show data in course preview */
    public function coursepreview()
    {
        if (!$this->rbac->hasPrivilege('online_course', 'can_view')) {
            access_denied();
        }
        $courseID = $this->input->post('courseID');

        $lessonquizcount = $this->studentcourse_model->lessonquizcountbycourseid($courseID, '');
        if (!empty($lessonquizcount['lessoncount'])) {
            $data['total_lesson'] = $lessonquizcount['lessoncount'];
        } else {
            $data['total_lesson'] = '';
        }
        if (!empty($lessonquizcount['quizcount'])) {
            $data['total_quiz'] = $lessonquizcount['quizcount'];
        } else {
            $data['total_quiz'] = '';
        }
        if (!empty($lessonquizcount['examcount'])) {
            $data['total_exam'] = $lessonquizcount['examcount'];
        } else {
            $data['total_exam'] = '';
        }
        if (!empty($lessonquizcount['assignmentcount'])) {
            $data['total_assignment'] = $lessonquizcount['assignmentcount'];
        } else {
            $data['total_assignment'] = '';
        }

        $data['total_hour_count'] = $this->studentcourse_model->counthours($courseID);
        $data['coursesList']      = $this->course_model->singlecourselist($courseID);
        $sectionList              = $this->coursesection_model->getsectionbycourse($courseID);
        $data['sectionList']      = $sectionList;
        $lessonquizlist_array     = array();

        if (!empty($sectionList)) {
            foreach ($sectionList as $sectionList_value) {
                $lessonquizlist_array[$sectionList_value->id] = $this->coursesection_model->lessonquizbysection($sectionList_value->id);
            }
            $data['lessonquizdetail'] = $lessonquizlist_array;
        } else {
            $data['lessonquizdetail'] = '';
        }

        $multipalsection = $this->course_model->multipalsection($courseID);
        $section         = "";
        $store_section   = array();
        foreach ($multipalsection as $multipalsection_value) {
            if (!in_array($multipalsection_value['section'], $store_section)) {
                $store_section[] = $multipalsection_value['section'];
                $section .= $multipalsection_value['section'] . ", ";
            }
        }
        $data['section'] = $section;

        $this->load->view('onlinecourse/course/_coursepreview', $data);
    }

    /*
    This is used to change order of section
     */
    public function ordersection()
    {
        if (!$this->rbac->hasPrivilege('online_course', 'can_view')) {
            access_denied();
        }
        $courseid             = $this->input->post('courseid');
        $data['courseid']     = $courseid;
        $sectionList          = $this->coursesection_model->getsectionbycourse($courseid);
        $data['sectionlist']  = $sectionList;
        $lessonquizlist_array = array();

        if (!empty($sectionList)) {
            foreach ($sectionList as $sectionList_value) {
                $lessonquizlist_array[$sectionList_value->id] = $this->coursesection_model->lessonquizbysection($sectionList_value->id);
            }
            $data['lessonquizdetail'] = $lessonquizlist_array;
        } else {
            $data['lessonquizdetail'] = '';
        }
        $this->load->view('onlinecourse/course/_ordersection', $data);
    }

    /*
    This is used to update order of section
     */
    public function updatesectionorder()
    {
        if (!$this->rbac->hasPrivilege('online_course', 'can_edit')) {
            access_denied();
        }
        $sectionarray = $this->input->post('sectionarray');
        if (!empty($sectionarray)) {
            $sectionorder = array();
            $i            = 1;
            foreach ($sectionarray as $sectionarray_key => $sectionarray_value) {
                $sectionorder[] = $array = array('id' => $sectionarray_value, 'order' => $i);
                $i++;
            }
            $this->course_model->updatesectionorder($sectionorder);
        }
        $array = array('status' => '1', 'msg' => $this->lang->line('record_updated_successfully'));
        echo json_encode($array);
    }

    /*
    This is used to update order of lesson and quiz
     */
    public function updatelessonquizorder()
    {
        if (!$this->rbac->hasPrivilege('online_course', 'can_edit')) {
            access_denied();
        }
        $lessonquizarray = $this->input->post('lessonquizarray');
        if (!empty($lessonquizarray)) {
            $lessonquizorder = array();
            $i               = 1;
            foreach ($lessonquizarray as $lessonquizarray_key => $lessonquizarray_value) {
                $lessonquizorder[] = $array = array('id' => $lessonquizarray_value, 'order' => $i);
                $i++;
            }
            $this->course_model->updatelessonquizorder($lessonquizorder);
        }
        $array = array('status' => '1', 'msg' => $this->lang->line('record_updated_successfully'));
        echo json_encode($array);
    }

    /*
    This is used to show setting details
     */
    public function setting()
    {
        if (!$this->rbac->hasPrivilege('online_course_setting', 'can_view')) {
            access_denied();
        }
        $this->session->set_userdata('top_menu', 'onlinecourse');
        $this->session->set_userdata('sub_menu', 'onlinecourse/course/setting');
        $this->load->config('onlinecourse-config');
        $data['version'] = $this->config->item('version');
        $post_data       = $this->security->xss_clean($this->input->post());
        if (!empty($post_data)) {
            $setting_btn = $post_data['setting_btn'];
        } else {
            $setting_btn = '';
        }

        if ($setting_btn == 'aws') {
            $this->form_validation->set_rules('api_key', $this->lang->line('api_key'), 'trim|required');
            $this->form_validation->set_rules('api_secret', $this->lang->line('api_secret'), 'trim|required');
            $this->form_validation->set_rules('bucket_name', $this->lang->line('bucket_name'), 'trim|required');
            $this->form_validation->set_rules('region', $this->lang->line('region'), 'trim|required');
        } elseif ($setting_btn == 'course') {
            $this->form_validation->set_rules('guest_prefix', $this->lang->line('guest_user_prefix'), 'trim|required');
            $this->form_validation->set_rules('guest_id_start_from', $this->lang->line('guest_user_id_start_from'), 'trim|required');
            $this->form_validation->set_rules('guest_login', $this->lang->line('guest') . ' ' . $this->lang->line('guest'), 'trim|required');
        }

        if ($this->form_validation->run() == false) {
            $data['aws_setting']        = $this->course_model->getAwsS3Settings();
            $data['course_setting']     = $this->course_model->getOnlineCourseSettings();
            $this->load->view('layout/header');
            $this->load->view('onlinecourse/course/setting', $data);
            $this->load->view('layout/footer');
        } else {
            if ($setting_btn == 'aws') {
                $aws_data = array(
                    "api_key"     => $post_data['api_key'],
                    "api_secret"  => $post_data['api_secret'],
                    "bucket_name" => $post_data['bucket_name'],
                    "region"      => $post_data['region'],
                );
                $this->course_model->addAwsS3Settings($aws_data);
                $this->session->set_flashdata('msg_aws', '<div class="alert alert-success">' . $this->lang->line('update_message') . '</div>');
            } elseif ($setting_btn == 'course') {
                $course_data = array(
                    "guest_prefix"          => $post_data['guest_prefix'],
                    "guest_id_start_from"   => $post_data['guest_id_start_from'],
                    "guest_login"           => $post_data['guest_login'],
                );

                $this->course_model->addCourseSettings($course_data);
                $this->session->set_flashdata('msg_course', '<div class="alert alert-success">' . $this->lang->line('update_message') . '</div>');
            }
            redirect('onlinecourse/course/setting');
        }
    }

    public function savesetting()
    {
        $course_curriculum_settings = $this->input->post('course_curriculum_settings');
        if (empty($course_curriculum_settings)) {
            $course_curriculum_settings     =   "";
        } else {
            $course_curriculum_settings     =   json_encode($this->input->post('course_curriculum_settings'));
        }
        $data = array('course_curriculum_settings' => $course_curriculum_settings);
        $this->course_model->save_online_course_setting($data);
        $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
        echo json_encode($array);
    }

    /**
     * Call the external subtitle extraction microservice for a course preview
     * video URL and return the payload without persisting it. The admin UI
     * shows the modal preview using this response and only persists via
     * `save_extracted_subtitles()` when the user confirms.
     *
     * POST params:
     *   video_url       (required) - course preview URL to extract from
     *   course_id       (optional) - existing course id (for edit flow). When
     *                                provided the controller verifies the row
     *                                exists before touching the API.
     *   video_provider  (optional) - provider hint stored back on save
     */
    public function extract_subtitles_preview()
    {
        if (!$this->rbac->hasPrivilege('online_course', 'can_edit')
            && !$this->rbac->hasPrivilege('online_course', 'can_add')) {
            access_denied();
        }

        // Subtitle extraction on the remote AI service can take several minutes,
        // especially for uploaded video files. Raise the script time limit so
        // the PHP front controller doesn't get killed before we hear back.
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        header('Content-Type: application/json; charset=utf-8');

        $video_url      = trim((string) $this->input->post('video_url'));
        $course_id      = (int) $this->input->post('course_id');
        $video_provider = trim((string) $this->input->post('video_provider'));

        $api_type         = $this->video_transcript_model->map_provider_to_api_type($video_provider);
        $local_file_path  = null;
        $upload_file_name = null;

        if ($api_type === 'file') {
            // If the browser sent a file field but PHP rejected it (typically
            // because it exceeded upload_max_filesize / post_max_size), surface
            // a precise message instead of the misleading "please choose a
            // video file" fallback.
            if (isset($_FILES['video_file']) && !empty($_FILES['video_file']['name'])
                && $_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(array(
                    'status'  => 'fail',
                    'message' => $this->_describe_upload_error((int) $_FILES['video_file']['error']),
                    'debug'   => $this->_extract_debug_info($video_provider, $api_type, $video_url),
                ));
                return;
            }
            // Case A: A new file was picked on the client (create or re-upload
            // during edit) and sent as `video_file` via multipart/form-data.
            if (isset($_FILES['video_file']) && !empty($_FILES['video_file']['name'])
                && ($_FILES['video_file']['error'] === UPLOAD_ERR_OK)
                && is_uploaded_file($_FILES['video_file']['tmp_name'])) {
                $local_file_path = $_FILES['video_file']['tmp_name'];
                $upload_file_name = isset($_FILES['video_file']['name']) ? (string) $_FILES['video_file']['name'] : null;
                if ($video_url === '') {
                    $video_url = 'uploaded://' . $_FILES['video_file']['name'];
                }
            } elseif ($course_id > 0) {
                // Case B: Edit flow with an existing saved file on disk.
                $existing = $this->course_model->singlecourselist($course_id);
                if (!empty($existing) && !empty($existing['video_id'])) {
                    $candidate = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR
                        . 'uploads' . DIRECTORY_SEPARATOR
                        . 'course'  . DIRECTORY_SEPARATOR
                        . 'course_preview' . DIRECTORY_SEPARATOR
                        . $existing['video_id'];
                    if (is_file($candidate)) {
                        $local_file_path = $candidate;
                        if ($video_url === '') {
                            $video_url = base_url() . 'uploads/course/course_preview/' . rawurlencode($existing['video_id']);
                        }
                    }
                }
            }
            if ($local_file_path === null) {
                echo json_encode(array(
                    'status'  => 'fail',
                    'message' => 'Please choose a video file before extracting subtitles.',
                    'debug'   => $this->_extract_debug_info($video_provider, $api_type, $video_url),
                ));
                return;
            }
        } else {
            // URL-based providers (youtube / vimeo / etc.) require a valid URL.
            if ($video_url === '') {
                echo json_encode(array(
                    'status'  => 'fail',
                    'message' => 'Please enter a ' . $this->lang->line('course_preview_url') . '.',
                    'debug'   => $this->_extract_debug_info($video_provider, $api_type, $video_url),
                ));
                return;
            }
            if (!filter_var($video_url, FILTER_VALIDATE_URL)) {
                echo json_encode(array(
                    'status'  => 'fail',
                    'message' => 'Invalid video URL.',
                    'debug'   => $this->_extract_debug_info($video_provider, $api_type, $video_url),
                ));
                return;
            }
        }

        if ($course_id > 0) {
            $existing_course = $this->course_model->singlecourselist($course_id);
            if (empty($existing_course)) {
                echo json_encode(array(
                    'status'  => 'fail',
                    'message' => 'Course not found.',
                ));
                return;
            }
        }

        $result = $this->video_transcript_model->call_extract_api($video_url, $api_type, $local_file_path, $upload_file_name);
        if (!$result['ok']) {
            echo json_encode(array(
                'status'  => 'fail',
                'message' => $result['error'],
            ));
            return;
        }

        $data            = $result['data'];
        $segments        = isset($data['segments']) && is_array($data['segments']) ? $data['segments'] : array();
        $full_transcript = isset($data['subtitles']) ? (string) $data['subtitles'] : '';

        echo json_encode(array(
            'status'  => 'success',
            'message' => 'Subtitles extracted.',
            'data'    => array(
                'video_url'       => $video_url,
                'video_provider'  => $video_provider,
                'segments'        => $segments,
                'segment_count'   => count($segments),
                'full_transcript' => $full_transcript,
                'source'          => isset($data['source']) ? (string) $data['source'] : '',
            ),
        ));
    }

    /**
     * Persist a previously previewed transcript for a course preview video.
     * Called after the admin clicks Save in the subtitle preview modal.
     *
     * POST params:
     *   course_id        (required)
     *   video_url        (required) - URL that was used for extraction
     *   video_provider   (optional)
     *   video_id         (optional) - provider video id (youtube/vimeo id)
     *   segments_json    (required) - JSON string of segments array
     *   full_transcript  (required) - full subtitles text
     *   source           (optional)
     */
    public function save_extracted_subtitles()
    {
        if (!$this->rbac->hasPrivilege('online_course', 'can_edit')
            && !$this->rbac->hasPrivilege('online_course', 'can_add')) {
            access_denied();
        }

        header('Content-Type: application/json; charset=utf-8');

        $course_id       = (int) $this->input->post('course_id');
        $video_url       = trim((string) $this->input->post('video_url'));
        $video_provider  = trim((string) $this->input->post('video_provider'));
        $video_id        = trim((string) $this->input->post('video_id'));
        $segments_json   = (string) $this->input->post('segments_json');
        $full_transcript = (string) $this->input->post('full_transcript');
        $source          = trim((string) $this->input->post('source'));

        if ($course_id <= 0) {
            echo json_encode(array('status' => 'fail', 'message' => 'Course id is required.'));
            return;
        }
        if ($video_url === '') {
            echo json_encode(array('status' => 'fail', 'message' => 'Video URL is required.'));
            return;
        }
        $existing_course = $this->course_model->singlecourselist($course_id);
        if (empty($existing_course)) {
            echo json_encode(array('status' => 'fail', 'message' => 'Course not found.'));
            return;
        }

        $segments = json_decode($segments_json, true);
        if (!is_array($segments)) {
            echo json_encode(array('status' => 'fail', 'message' => 'Invalid subtitle segments payload.'));
            return;
        }
        if ($full_transcript === '') {
            echo json_encode(array('status' => 'fail', 'message' => 'Full transcript payload is missing.'));
            return;
        }

        $meta = array(
            'video_provider' => $video_provider !== '' ? $video_provider : (isset($existing_course['course_provider']) ? $existing_course['course_provider'] : ''),
            'video_url'      => $video_url,
            'video_id'       => $video_id !== '' ? $video_id : (isset($existing_course['video_id']) ? (string) $existing_course['video_id'] : ''),
        );
        $api_data = array(
            'segments'  => $segments,
            'subtitles' => $full_transcript,
            'source'    => $source,
        );

        $record_id = $this->video_transcript_model->upsert_transcript(
            Video_transcript_model::ENTITY_COURSE,
            $course_id,
            $meta,
            $api_data
        );
        if ($record_id === false) {
            echo json_encode(array('status' => 'fail', 'message' => 'Failed to save subtitles.'));
            return;
        }

        echo json_encode(array(
            'status'        => 'success',
            'message'       => $this->lang->line('success_message'),
            'transcript_id' => $record_id,
        ));
    }

    /**
     * Serve the stored transcript for a course preview video as a WebVTT file
     * so the player can attach it via a <track> element. Served as
     * text/vtt; charset=utf-8. Responds with 204 when there is no transcript
     * so browsers simply skip the track rather than erroring.
     */
    public function subtitles_vtt($course_id = 0)
    {
        $course_id = (int) $course_id;
        $vtt = ($course_id > 0)
            ? $this->video_transcript_model->build_vtt(Video_transcript_model::ENTITY_COURSE, $course_id)
            : null;
        if ($vtt === null || $vtt === '') {
            $this->output->set_status_header(204);
            return;
        }
        $this->output
            ->set_header('Content-Type: text/vtt; charset=utf-8')
            ->set_header('Cache-Control: public, max-age=60')
            ->set_output($vtt);
    }

    /**
     * Return the stored transcript segments as JSON for rendering a click-to-seek
     * transcript panel next to the player.
     */
    public function subtitles_segments($course_id = 0)
    {
        header('Content-Type: application/json; charset=utf-8');
        $course_id = (int) $course_id;
        $segments  = ($course_id > 0)
            ? $this->video_transcript_model->get_segments(Video_transcript_model::ENTITY_COURSE, $course_id)
            : array();
        echo json_encode(array(
            'status'   => 'success',
            'segments' => $segments,
        ));
    }

    /**
     * Lightweight JSON endpoint returning whether a transcript exists for the
     * given course id so the UI can render the green "subtitles extracted"
     * indicator on edit-load.
     */
    public function get_subtitle_status()
    {
        if (!$this->rbac->hasPrivilege('online_course', 'can_view')) {
            access_denied();
        }
        header('Content-Type: application/json; charset=utf-8');

        $course_id      = (int) $this->input->post('course_id');
        $video_provider = trim((string) $this->input->post('video_provider'));
        $video_url      = trim((string) $this->input->post('video_url'));
        $video_id       = trim((string) $this->input->post('video_id'));

        if ($course_id <= 0) {
            echo json_encode(array('status' => 'fail', 'has_transcript' => false));
            return;
        }

        $status = $this->video_transcript_model->get_status_for_ui(
            Video_transcript_model::ENTITY_COURSE,
            $course_id,
            $video_provider,
            $video_url,
            $video_id
        );

        echo json_encode(array(
            'status'         => 'success',
            'has_transcript' => $status !== null && $status['status'] === 'success',
            'detail'         => $status,
        ));
    }
}
