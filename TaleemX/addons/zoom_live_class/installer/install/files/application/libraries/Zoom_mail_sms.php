<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Zoom_mail_sms
{


    private $CI;

    /** @var mixed */
    private $config_mailsms;


    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('mailer');
        $this->config_mailsms = $this->CI->config->item('mailsms');
    }

    public function mailsms($send_for, $sender_details)
    {

        $chk_mail_sms = $this->CI->customlib->sendMailSMS($send_for);

        if (!empty($chk_mail_sms)) {

            if ($send_for == "online_classes") {

                $this->sendZoomOnlineClass($chk_mail_sms, $sender_details, $chk_mail_sms['template'], $chk_mail_sms['template_id'], $chk_mail_sms['subject']);
            } elseif ($send_for == "online_meeting") {

                $this->sendZoomMeeting($chk_mail_sms, $sender_details, $chk_mail_sms['template'], $chk_mail_sms['template_id'], $chk_mail_sms['subject']);
            } elseif ($send_for == "zoom_online_classes_start") {

                $this->sendZoomOnlineClass($chk_mail_sms, $sender_details, $chk_mail_sms['template'], $chk_mail_sms['template_id'], $chk_mail_sms['subject']);
            } elseif ($send_for == "zoom_online_meeting_start") {

                $this->sendZoomMeeting($chk_mail_sms, $sender_details, $chk_mail_sms['template'], $chk_mail_sms['template_id'], $chk_mail_sms['subject']);
            }
        }
    }

    public function sendZoomOnlineClass($chk_mail_sms, $student_details, $template, $template_id, $subject)
    {

        $student_guardian_sms_list = array();
        $student_sms_list = array();
        $student_email_list = array();
        $student_guardian_email_list = array();
        $student_notification_list = array();
        $student_guardian_notification_list = array();
        $sms_detail = $this->CI->smsconfig_model->getActiveSMS();

        if ($chk_mail_sms['mail'] or $chk_mail_sms['sms'] or $chk_mail_sms['notification'] or $chk_mail_sms['whatsapp']) {
            $title = $student_details['title'];
            $date = $student_details['date'];
            $duration = $student_details['duration'];
            $class_section_id = $student_details['class_section_id'];
            $student_list = $this->CI->conference_model->getStudentByClassSectionID($class_section_id);

            if (!empty($student_list)) {
                foreach ($student_list as $student_key => $student_value) {

                    if ($student_value['parent_app_key'] != "" && $chk_mail_sms['guardian_recipient']) {
                        $student_guardian_notification_list[] = array(
                            'app_key' => $student_value['parent_app_key'],
                            'class' => $student_value['class'],
                            'section' => $student_value['section'],
                            'title' => $title,
                            'date' => $date,
                            'duration' => $duration,
                            'admission_no' => $student_value['admission_no'],
                            'student_name' => $student_value['firstname'] . " " . $student_value['lastname'],
                        );
                    }

                    if ($student_value['app_key'] != "" && $chk_mail_sms['student_recipient']) {
                        $student_notification_list[] = array(
                            'app_key' => $student_value['app_key'],
                            'class' => $student_value['class'],
                            'section' => $student_value['section'],
                            'title' => $title,
                            'date' => $date,
                            'duration' => $duration,
                            'admission_no' => $student_value['admission_no'],
                            'student_name' => $student_value['firstname'] . " " . $student_value['lastname'],
                        );
                    }

                    if ($student_value['email'] != "" && $chk_mail_sms['student_recipient']) {
                        $student_email_list[$student_value['email']] = array(
                            'class' => $student_value['class'],
                            'section' => $student_value['section'],
                            'title' => $title,
                            'date' => $date,
                            'duration' => $duration,
                            'admission_no' => $student_value['admission_no'],
                            'student_name' => $student_value['firstname'] . " " . $student_value['lastname'],
                        );
                    }

                    if ($student_value['guardian_email'] != "" && $chk_mail_sms['guardian_recipient']) {
                        $student_guardian_email_list[$student_value['guardian_email']] = array(
                            'class' => $student_value['class'],
                            'section' => $student_value['section'],
                            'title' => $title,
                            'date' => $date,
                            'duration' => $duration,
                            'admission_no' => $student_value['admission_no'],
                            'student_name' => $student_value['firstname'] . " " . $student_value['lastname'],
                        );
                    }

                    if ($student_value['mobileno'] != "" && $chk_mail_sms['student_recipient']) {
                        $student_sms_list[$student_value['mobileno']] = array(
                            'class' => $student_value['class'],
                            'section' => $student_value['section'],
                            'title' => $title,
                            'date' => $date,
                            'duration' => $duration,
                            'admission_no' => $student_value['admission_no'],
                            'student_name' => $student_value['firstname'] . " " . $student_value['lastname'],
                        );
                    }

                    if ($student_value['guardian_phone'] != "" && $chk_mail_sms['guardian_recipient']) {
                        $student_guardian_sms_list[$student_value['guardian_phone']] = array(
                            'class' => $student_value['class'],
                            'section' => $student_value['section'],
                            'title' => $title,
                            'date' => $date,
                            'duration' => $duration,
                            'admission_no' => $student_value['admission_no'],
                            'student_name' => $student_value['firstname'] . " " . $student_value['lastname'],
                        );
                    }
                }

                if ($student_email_list) {
                    if ($chk_mail_sms['mail']) {
                        $this->sentOnlineClassStudentMail($student_email_list, $template, $subject);
                    }
                }

                if ($student_guardian_email_list) {
                    if ($chk_mail_sms['mail']) {
                        $this->sentOnlineClassStudentMail($student_guardian_email_list, $template, $subject);
                    }
                }

                if ($student_sms_list) {

                    if ($chk_mail_sms['sms'] && !empty($sms_detail)) {

                        $this->sentOnlineClassStudentSMS($student_sms_list, $template, $template_id, $subject);
                    }
                }

                if ($student_guardian_sms_list) {

                    if ($chk_mail_sms['sms'] && !empty($sms_detail)) {
                        $this->sentOnlineClassStudentSMS($student_guardian_sms_list, $template, $template_id, $subject);
                    }
                }

                if (!empty($student_notification_list)) {
                    if ($chk_mail_sms['notification']) {
                        $this->sentOnlineClassStudentNotification($student_notification_list, $template, $subject);
                    }
                }

                if (!empty($student_guardian_notification_list)) {
                    if ($chk_mail_sms['notification']) {
                        $this->sentOnlineClassStudentNotification($student_guardian_notification_list, $template, $subject);
                    }
                }

                //whatsapp//

                if ($student_sms_list && $chk_mail_sms['whatsapp'] &&  $chk_mail_sms['student_recipient']) {
                    $this->sentstudentZoomClasswhatsapp($student_sms_list, $chk_mail_sms);
                }

                if ($student_guardian_sms_list && $chk_mail_sms['whatsapp'] &&   $chk_mail_sms['guardian_recipient']) {
                    $this->sentstudentZoomClasswhatsapp($student_guardian_sms_list, $chk_mail_sms);
                }
                //whatsapp//
            }
        }
    }

    public function sentOnlineClassStudentMail($detail, $template, $subject)
    {

        if (!empty($this->CI->mail_config)) {
            foreach ($detail as $student_key => $student_value) {
                $send_to = $student_key;
                if ($send_to != "") {
                    $msg = $this->getOnlineClassStudentContent($detail[$student_key], $template);
                    $this->CI->mailer->send_mail($send_to, $subject, $msg);
                }
            }
        }
    }

    public function sentOnlineClassStudentSMS($detail, $template, $template_id, $subject)
    {

        $sms_detail = $this->CI->smsconfig_model->getActiveSMS();
        if (!empty($sms_detail)) {

            foreach ($detail as $student_key => $student_value) {
                $send_to = $student_key;
                if ($send_to != "") {
                    $msg = $this->getOnlineClassStudentContent($detail[$student_key], $template, $sms_detail->type);

                    if ($sms_detail->type == 'clickatell') {
                        $params = array(
                            'apiToken' => $sms_detail->api_id,
                        );
                        $this->CI->load->library('clickatell', $params);

                        try {
                            $result = $this->CI->clickatell->sendMessage(['to' => [$send_to], 'content' => $msg]);
                            foreach ($result['messages'] as $message) {
                            }
                            return true;
                        } catch (Exception $e) {
                            return false;
                        }
                    } else if ($sms_detail->type == 'twilio') {

                        $params = array(
                            'mode' => 'sandbox',
                            'account_sid' => $sms_detail->api_id,
                            'auth_token' => $sms_detail->password,
                            'api_version' => '2010-04-01',
                            'number' => $sms_detail->contact,
                        );

                        $this->CI->load->library('twilio', $params);

                        $from = $sms_detail->contact;
                        $to = $send_to;
                        $message = $msg;
                        $response = $this->CI->twilio->sms($from, $to, $message);

                        if ($response->IsError) {
                            return false;
                        } else {
                            return true;
                        }
                    } else if ($sms_detail->type == 'msg_nineone') {

                        $params = array(
                            'authkey' => $sms_detail->authkey,
                            'senderid' => $sms_detail->senderid,
                            'templateid' => $template_id,
                        );
                        $this->CI->load->library('msgnineone', $params);
                        $this->CI->msgnineone->sendSMS($send_to, $msg);
                    } else if ($sms_detail->type == 'smscountry') {
                        $params = array(
                            'username' => $sms_detail->username,
                            'sernderid' => $sms_detail->senderid,
                            'password' => $sms_detail->password,
                        );
                        $this->CI->load->library('smscountry', $params);
                        $this->CI->smscountry->sendSMS($send_to, $msg);
                    } else if ($sms_detail->type == 'text_local') {
                        $params = array(
                            'username' => $sms_detail->username,
                            'hash' => $sms_detail->password,
                        );
                        $this->CI->load->library('textlocalsms', $params);
                        $this->CI->textlocalsms->sendSms(array($send_to), $msg, $sms_detail->senderid);
                    } else if ($sms_detail->type == 'bulk_sms') {
                        $to      = $send_to;
                        $params = array(
                            'username' => $sms_detail->username,
                            'password' => $sms_detail->password,
                        );
                        $this->CI->load->library('bulk_sms_lib', $params);
                        $this->CI->bulk_sms_lib->sendSms(array($to), $msg);
                    } else if ($sms_detail->type == 'mobireach') {
                        $to     = $send_to;
                        $params = array(
                            'authkey'  => $sms_detail->authkey,
                            'senderid' => $sms_detail->senderid,
                            'routeid'  => $sms_detail->api_id,

                        );
                        $this->_CI->load->library('mobireach_lib', $params);
                        $this->_CI->mobireach_lib->sendSms(array($to), $msg);
                    } else if ($sms_detail->type == 'nexmo') {
                        $to     = $send_to;
                        $params = array(
                            'from'       => $sms_detail->senderid,
                            'api_key'    => $sms_detail->api_id,
                            'api_secret' => $sms_detail->authkey,

                        );
                        $this->_CI->load->library('nexmo_lib', $params);
                        $this->_CI->nexmo_lib->sendSms($to, $msg);
                    } else if ($sms_detail->type == 'africastalking') {
                        $to     = $send_to;
                        $params = array(
                            'from'         => $sms_detail->senderid,
                            'api_key'      => $sms_detail->api_id,
                            'api_username' => $sms_detail->username,

                        );
                        $this->_CI->load->library('africastalking_lib', $params);
                        $this->_CI->africastalking_lib->sendSms($to, $msg);
                    } else if ($sms_detail->type == 'smseg') {
                        $to = $send_to;
                        $this->_CI->load->library('smseg_lib');
                        $this->_CI->smseg_lib->sendSms($to, $msg);
                    } else if ($sms_detail->type == 'custom') {
                        $params = array(
                            'templateid' => $template_id,
                        );
                        $this->CI->load->library('customsms', $params);
                        $from = $sms_detail->contact;
                        $to = $send_to;
                        $message = $msg;
                        $this->CI->customsms->sendSMS($to, $message);
                    } else {
                    }
                }
            }
        }
    }

    public function sentOnlineClassStudentNotification($detail, $template, $subject)
    {
        $this->CI->load->library('pushnotification');
        foreach ($detail as $student_key => $student_value) {
            $msg = $this->getOnlineClassStudentContent($detail[$student_key], $template);

            $push_array = array(
                'title' => $subject,
                'body' => $msg,
            );

            if ($student_value['app_key'] != "") {
                $this->CI->pushnotification->send($student_value['app_key'], $push_array, "mail_sms");
            }
        }
    }

    public function sendZoomMeeting($chk_mail_sms, $staff_details, $template, $template_id, $subject)
    {

        $staff_sms_list = array();
        $staff_email_list = array();

        if ($chk_mail_sms['mail'] or $chk_mail_sms['sms'] or $chk_mail_sms['whatsapp']) {

            if (!empty($staff_details)) {
                foreach ($staff_details as $staff_key => $staff_value) {

                    if ($staff_value['email'] != "" && $chk_mail_sms['staff_recipient']) {
                        $staff_email_list[$staff_value['email']] = array(
                            'title' => $staff_value['title'],
                            'date' => $staff_value['date'],
                            'duration' => $staff_value['duration'],
                            'employee_id' => $staff_value['employee_id'],
                            'department' => $staff_value['department'],
                            'designation' => $staff_value['designation'],
                            'name' => $staff_value['name'],
                            'contact_no' => $staff_value['contact_no'],
                            'email' => $staff_value['email'],
                        );
                    }

                    if ($staff_value['contact_no'] != "" && $chk_mail_sms['staff_recipient']) {
                        $staff_sms_list[$staff_value['contact_no']] = array(
                            'title' => $staff_value['title'],
                            'date' => $staff_value['date'],
                            'duration' => $staff_value['duration'],
                            'employee_id' => $staff_value['employee_id'],
                            'department' => $staff_value['department'],
                            'designation' => $staff_value['designation'],
                            'name' => $staff_value['name'],
                            'contact_no' => $staff_value['contact_no'],
                            'email' => $staff_value['email'],
                        );
                    }
                }

                if ($staff_email_list  && $chk_mail_sms['mail']) {
                    $this->sentOnlineMeetingStaffMail($staff_email_list, $template, $subject);
                }

                if ($staff_sms_list  && $chk_mail_sms['sms']) {
                    $this->sentOnlineMeetingStaffSMS($staff_sms_list, $template, $template_id, $subject);
                }

                if ($staff_sms_list && $chk_mail_sms['template'] != "" && $chk_mail_sms['whatsapp']) {
                    $this->sentZoomMeetingStaffwhatsapp($staff_sms_list, $chk_mail_sms);
                }
            }
        }
    }

    //====================Whatsapp=======================//
    public function sentZoomMeetingStaffwhatsapp($sender_details, $chk_mail_sms)
    {
        $this->CI->load->library('whatsappgateway');
        foreach ($sender_details as $staff_key => $staff_value) {
            $send_to = $staff_key;
            $this->CI->whatsappgateway->sentZoomMeetingStaffwhatsapp($sender_details, $chk_mail_sms['template'], $send_to, $chk_mail_sms['whatsapp_template_id']);
        }
    }

    public function sentstudentZoomClasswhatsapp($sender_details, $chk_mail_sms)
    {

        $this->CI->load->library('whatsappgateway');
        foreach ($sender_details as $staff_key => $staff_value) {
            $send_to = $staff_key;
            $this->CI->whatsappgateway->sentstudentZoomClasswhatsapp($sender_details, $chk_mail_sms['template'], $send_to, $chk_mail_sms['whatsapp_template_id']);
        }
    }

    //====================Whatsapp=======================//

    public function sentOnlineMeetingStaffMail($detail, $template, $subject)
    {

        if (!empty($this->CI->mail_config)) {
            foreach ($detail as $staff_key => $staff_value) {
                $send_to = $staff_key;
                if ($send_to != "") {
                    $msg = $this->getOnlineMeetingStaffContent($detail[$staff_key], $template);
                    $this->CI->mailer->send_mail($send_to, $subject, $msg);
                }
            }
        }
    }

    public function getOnlineMeetingStaffContent($staff_detail, $template, $sms_detail_type = null)
    {
        if ($template === null) {
            $template = '';
        }

        foreach ($staff_detail as $key => $value) {

            if ($value === null) {
                $value = '';
            }

            if ($sms_detail_type === 'msg_nineone' && strlen($value) > 30) {
                $value = substr($value, 0, 29);
            }

            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        return $template;
    }

    public function getOnlineClassStudentContent($student_detail, $template, $sms_detail_type = null)
    {
        if ($template === null) {
            $template = '';
        }

        foreach ($student_detail as $key => $value) {

            if ($value === null) {
                $value = '';
            }

            if ($sms_detail_type === 'msg_nineone' && strlen($value) > 30) {
                $value = substr($value, 0, 29);
            }

            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        return $template;
    }


    public function sentOnlineMeetingStaffSMS($detail, $template, $template_id, $subject)
    {

        $sms_detail = $this->CI->smsconfig_model->getActiveSMS();
        if (!empty($sms_detail)) {

            foreach ($detail as $staff_key => $staff_value) {
                $send_to = $staff_key;
                if ($send_to != "") {
                    $msg = $this->getOnlineMeetingStaffContent($detail[$staff_key], $template, $sms_detail->type);

                    if ($sms_detail->type == 'clickatell') {
                        $params = array(
                            'apiToken' => $sms_detail->api_id,
                        );
                        $this->CI->load->library('clickatell', $params);

                        try {
                            $result = $this->CI->clickatell->sendMessage(['to' => [$send_to], 'content' => $msg]);
                            foreach ($result['messages'] as $message) {
                            }
                            return true;
                        } catch (Exception $e) {
                            return false;
                        }
                    } else if ($sms_detail->type == 'twilio') {

                        $params = array(
                            'mode' => 'sandbox',
                            'account_sid' => $sms_detail->api_id,
                            'auth_token' => $sms_detail->password,
                            'api_version' => '2010-04-01',
                            'number' => $sms_detail->contact,
                        );

                        $this->CI->load->library('twilio', $params);

                        $from = $sms_detail->contact;
                        $to = $send_to;
                        $message = $msg;
                        $response = $this->CI->twilio->sms($from, $to, $message);

                        if ($response->IsError) {
                            return false;
                        } else {
                            return true;
                        }
                    } else if ($sms_detail->type == 'msg_nineone') {

                        $params = array(
                            'authkey' => $sms_detail->authkey,
                            'senderid' => $sms_detail->senderid,
                            'templateid' => $template_id,
                        );
                        $this->CI->load->library('msgnineone', $params);
                        $this->CI->msgnineone->sendSMS($send_to, $msg);
                    } else if ($sms_detail->type == 'smscountry') {
                        $params = array(
                            'username' => $sms_detail->username,
                            'sernderid' => $sms_detail->senderid,
                            'password' => $sms_detail->password,
                        );
                        $this->CI->load->library('smscountry', $params);
                        $this->CI->smscountry->sendSMS($send_to, $msg);
                    } else if ($sms_detail->type == 'text_local') {
                        $params = array(
                            'username' => $sms_detail->username,
                            'hash' => $sms_detail->password,
                        );
                        $this->CI->load->library('textlocalsms', $params);
                        $this->CI->textlocalsms->sendSms(array($send_to), $msg, $sms_detail->senderid);
                    } else if ($sms_detail->type == 'bulk_sms') {
                        $to      = $send_to;
                        $params = array(
                            'username' => $sms_detail->username,
                            'password' => $sms_detail->password,
                        );
                        $this->CI->load->library('bulk_sms_lib', $params);
                        $this->CI->bulk_sms_lib->sendSms(array($to), $msg);
                    } else if ($sms_detail->type == 'mobireach') {
                        $to     = $send_to;
                        $params = array(
                            'authkey'  => $sms_detail->authkey,
                            'senderid' => $sms_detail->senderid,
                            'routeid'  => $sms_detail->api_id,

                        );
                        $this->_CI->load->library('mobireach_lib', $params);
                        $this->_CI->mobireach_lib->sendSms(array($to), $msg);
                    } else if ($sms_detail->type == 'nexmo') {
                        $to     = $send_to;
                        $params = array(
                            'from'       => $sms_detail->senderid,
                            'api_key'    => $sms_detail->api_id,
                            'api_secret' => $sms_detail->authkey,

                        );
                        $this->_CI->load->library('nexmo_lib', $params);
                        $this->_CI->nexmo_lib->sendSms($to, $msg);
                    } else if ($sms_detail->type == 'africastalking') {
                        $to     = $send_to;
                        $params = array(
                            'from'         => $sms_detail->senderid,
                            'api_key'      => $sms_detail->api_id,
                            'api_username' => $sms_detail->username,

                        );
                        $this->_CI->load->library('africastalking_lib', $params);
                        $this->_CI->africastalking_lib->sendSms($to, $msg);
                    } else if ($sms_detail->type == 'smseg') {
                        $to = $send_to;
                        $this->_CI->load->library('smseg_lib');
                        $this->_CI->smseg_lib->sendSms($to, $msg);
                    } else if ($sms_detail->type == 'custom') {
                        $params = array(
                            'templateid' => $template_id,
                        );
                        $this->CI->load->library('customsms', $params);
                        $from = $sms_detail->contact;
                        $to = $send_to;
                        $message = $msg;
                        $this->CI->customsms->sendSMS($to, $message);
                    } else {
                    }
                }
            }
        }
    }
}
