<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Coursesection extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('course_model', 'coursesection_model', 'courselesson_model', 'studentcourse_model', 'coursequiz_model', 'course_payment_model', 'courseofflinepayment_model', 'coursereport_model'));
        $this->auth->addonchk('ssoclc', site_url('onlinecourse/course/setting'));

        $this->load->library("media_storage");
        $this->load->library('SaasValidation');

    }

    /*
    This is used to add section
     */
    public function addsection()
    {
        if (!$this->rbac->hasPrivilege('online_course_section', 'can_add')) {
            access_denied();
        }
        $this->form_validation->set_rules('title', $this->lang->line('title'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $msg = array(
                'title' => form_error('title'),
            );
            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $sectionData = array(
                'section_title'    => $this->input->post('title'),
                'online_course_id' => $this->input->post('add_course_id'),
            );
            // This is used to add course section
            $this->coursesection_model->add($sectionData);
            $updatecourse = array(
                'updated_date' => date("Y-m-d h:i:s"),
                'id'           => $this->input->post('add_course_id'),
            );
            $this->course_model->add($updatecourse);

            $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
        }
        echo json_encode($array);
    }

    /*
    This is for getting single section
     */
    public function getsinglesection()
    {
        $sectionID        = $this->input->post('sectionID');
        $getsinglesection = $this->coursesection_model->getsinglesection($sectionID);
        if (!empty($getsinglesection)) {
            echo json_encode($getsinglesection);
        }
    }

    /*
    This is for edit section
     */
    public function editsection()
    {
        if (!$this->rbac->hasPrivilege('online_course_section', 'can_edit')) {
            access_denied();
        }
        $this->form_validation->set_rules('edit_title', $this->lang->line('title'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
            $msg = array(
                'edit_title' => form_error('edit_title'),
            );
            $array = array('status' => 'fail', 'error' => $msg, 'message' => '');
        } else {
            $sectionData = array(
                'id'            => $this->input->post('section_id'),
                'section_title' => $this->input->post('edit_title'),
            );
            // This is used to edit course section
            $this->coursesection_model->add($sectionData);
            $updatecourse = array(
                'updated_date' => date("Y-m-d h:i:s"),
                'id'           => $this->input->post('online_course_id'),
            );
            $this->course_model->add($updatecourse);
            $array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
        }
        echo json_encode($array);
    }

    /*
    This is for delete course
     */
    public function deletesection()
    {
        if (!$this->rbac->hasPrivilege('online_course_section', 'can_delete')) {
            access_denied();
        }
        $sectionID = $this->input->post('sectionID');
        if ($sectionID != '') {
            //=============================
            //delete lesson attachment
            $attachments = $this->coursesection_model->getLessonAttachmentListBySectionId($sectionID);
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
            $lesson_thumbnail = $this->coursesection_model->getLessonthumbnailListBySectionId($sectionID);
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
            $getassignmentattachment   =   $this->coursesection_model->getAssignmentBySectionId($sectionID); 
            foreach ($getassignmentattachment as $key => $value) {
                if ($value['document'] != '') {
                    $delete_assignment_file_size = $this->media_storage->getUploadedFileSize($value['document'], "uploads/course_content/online_course_assignment");
                    $this->saasvalidation->deleteResouceQuota('storage', $delete_assignment_file_size);
                    $this->media_storage->filedelete($value['document'], "uploads/course_content/online_course_assignment/");
                }
            }
            //=============================
            //delete submit assignments course exam attachment
            $getsubmitassignmentattachment  =   $this->coursesection_model->getSubmitAssignmentBySectionId($sectionID);  
            foreach ($getsubmitassignmentattachment as $key => $aasignvalue) {
                if ($aasignvalue['docs'] != '') {
                    $delete_docs_file_size = $this->media_storage->getUploadedFileSize($aasignvalue['docs'], "uploads/course_content/online_course_assignment");
                    $this->saasvalidation->deleteResouceQuota('storage', $delete_docs_file_size);
                    $this->media_storage->filedelete($aasignvalue['docs'], "uploads/course_content/online_course_assignment/");
                }
            }
            //=============================
            //delete online course exam attachment result
            $getexamattachment   =   $this->coursesection_model->getExamAttachmentBySectionId($sectionID);   
            foreach ($getexamattachment as $key => $examvalue) {
                if ($examvalue['attachment_upload_name'] != '') {
                    $delete_exam_file_size = $this->media_storage->getUploadedFileSize($examvalue['attachment_upload_name'], "uploads/course_content/online_course_exam_result");
                    $this->saasvalidation->deleteResouceQuota('storage', $delete_exam_file_size);
                    $this->media_storage->filedelete($examvalue['attachment_upload_name'], "uploads/course_content/online_course_exam_result/");
                }
            }
            //=============================
            $this->coursesection_model->delete($sectionID);
            $arrays = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('delete_message'));
        } else {
            $arrays = array('status' => 'fail', 'error' => $this->lang->line('some_thing_went_wrong'), 'message' => '');
        }
        echo json_encode($arrays);
    }
}
