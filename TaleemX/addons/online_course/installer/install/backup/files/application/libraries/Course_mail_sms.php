<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Course_mail_sms
{
    /** @var CI_Controller */
    private $CI;

    /** @var mixed */
    private $config_mailsms;

    /** @var mixed */
    private $sch_setting;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('mailer');
        $this->config_mailsms = $this->CI->config->item('mailsms');
        $this->sch_setting = $this->CI->setting_model->getSetting();
        $this->CI->load->library('customlib');
    }

    public function purchasemail($send_for, $sender_details)
    {

        $chk_mail_sms = $this->CI->customlib->sendMailSMS($send_for);
        $subject = $chk_mail_sms['subject'];
        $sms_detail = $this->CI->smsconfig_model->getActiveSMS();
        if (!empty($chk_mail_sms)) {
            if ($send_for == "online_course_purchase") {

                if ($chk_mail_sms['mail'] && $chk_mail_sms['template'] != "") {
                    $this->purchasesentmail($sender_details, $chk_mail_sms['template'], $subject, $chk_mail_sms);
                }

                if ($chk_mail_sms['sms'] && $chk_mail_sms['template'] != "" && !empty($sms_detail)) {
                    $this->purchasesendsms($sender_details, $chk_mail_sms['template_id'], $chk_mail_sms, $chk_mail_sms['template']);
                }

                if ($chk_mail_sms['notification'] && $chk_mail_sms['template'] != "") {
                    $this->purchasesentnotification($sender_details, $chk_mail_sms['subject'], $chk_mail_sms, $chk_mail_sms['template']);
                }
                //====whatsapp====//
                if ($chk_mail_sms['whatsapp'] && $chk_mail_sms['whatsapp_template_id'] != "") {
                    $this->purchasesendWhatsapp($sender_details, $chk_mail_sms);
                }
                //====whatsapp====//
            }

            if ($send_for == "online_course_purchase_for_guest_user") {
                if ($chk_mail_sms['mail'] && $chk_mail_sms['template'] != "") {
                    $this->purchasesentmailforguest($sender_details, $chk_mail_sms['template'], $subject, $chk_mail_sms);
                }
                if ($chk_mail_sms['notification'] && $chk_mail_sms['template'] != "") {
                    $this->purchasesentnotificationforguest($sender_details, $chk_mail_sms['subject'], $chk_mail_sms['template']);
                }
            }

            if ($send_for == "online_course_guest_user_sign_up") {
                if ($chk_mail_sms['mail'] && $chk_mail_sms['template'] != "") {
                    $this->guestusersignup($sender_details, $chk_mail_sms['template'], $subject, $chk_mail_sms);
                }
            }

            if ($send_for == "online_course_publish") {

                if ($chk_mail_sms['mail'] && $chk_mail_sms['template'] != "") {
                    $this->publishsentmail($sender_details, $chk_mail_sms['template'], $subject, $chk_mail_sms);
                }

                if ($chk_mail_sms['sms'] && $chk_mail_sms['template'] != "" && !empty($sms_detail)) {
                    $this->publishsendsms($sender_details, $chk_mail_sms['template_id'], $chk_mail_sms, $chk_mail_sms['template']);
                }

                if ($chk_mail_sms['notification'] && $chk_mail_sms['template'] != "") {
                    $this->publishsentnotification($sender_details, $chk_mail_sms['subject'], $chk_mail_sms, $chk_mail_sms['template']);
                }
                //====whatsapp====//
                if ($chk_mail_sms['whatsapp'] && $chk_mail_sms['whatsapp_template_id'] != "") {
                    $this->publishsendWhatsapp($sender_details, $chk_mail_sms);
                }
                //====whatsapp====//
            }
        }
    }

    //=====================================whatsapp=============================
    public function publishsendWhatsapp($sender_details, $chk_mail_sms)
    {
        $this->CI->load->library('whatsappgateway');

        $class_section = explode(',', $sender_details['class_section_id']);

        foreach ($class_section as $class_section_id) {
            $student_list = $this->CI->course_model->getStudentByClassSectionID($class_section_id);

            if (!empty($student_list)) {
                foreach ($student_list as $student_key) {
                    if ($chk_mail_sms['student_recipient']) {
                        $send_to = $student_key['mobileno'];
                        if ($send_to != "") {
                            $this->CI->whatsappgateway->publishsendWhatsapp($sender_details, $chk_mail_sms['template'], $send_to, $chk_mail_sms['whatsapp_template_id']);
                        }
                    }
                    if ($chk_mail_sms['guardian_recipient']) {
                        $send_to = $student_key['guardian_phone'];
                        if ($send_to != "") {
                            $this->CI->whatsappgateway->publishsendWhatsapp($sender_details, $chk_mail_sms['template'], $send_to, $chk_mail_sms['whatsapp_template_id']);
                        }
                    }
                }
            }
        }
    }

    public function purchasesendWhatsapp($sender_details, $chk_mail_sms)
    {

        $this->CI->load->library('whatsappgateway');

        $student_list = $this->CI->student_model->getRecentRecord($sender_details['student_id']);

        if (!empty($sender_details)) {

            if ($chk_mail_sms['student_recipient']) {
                $send_to = $student_list['mobileno'];
                if ($send_to != "") {
                    $this->CI->whatsappgateway->purchasesendWhatsapp($sender_details, $chk_mail_sms['template'], $send_to, $chk_mail_sms['whatsapp_template_id']);
                }
            }

            if ($chk_mail_sms['guardian_recipient']) {
                $send_to = $student_list['guardian_phone'];
                if ($send_to != "") {
                    $this->CI->whatsappgateway->purchasesendWhatsapp($sender_details, $chk_mail_sms['template'], $send_to, $chk_mail_sms['whatsapp_template_id']);
                }
            }
        }
    }
    //=====================================whatsapp=============================

    public function publishsentmail($sender_details, $template, $subject, $chk_mail_sms)
    {

        $currency_symbol      = $this->CI->customlib->getSchoolCurrencyFormat();
        $sender_details['price'] = $currency_symbol . amountFormat($sender_details['price']);
        $msg = $this->getpublishcontent($sender_details, $template);
        $class_section = explode(',', $sender_details['class_section_id']);

        foreach ($class_section as $class_section_id) {

            $student_list = $this->CI->course_model->getStudentByClassSectionID($class_section_id);
            if (!empty($student_list)) {
                foreach ($student_list as $student_key) {

                    if ($chk_mail_sms['student_recipient']) {
                        $send_to = $student_key['email'];
                        if (!empty($this->CI->mail_config) && $send_to != "") {
                            $this->CI->mailer->send_mail($send_to, $subject, $msg);
                        }
                    }

                    if ($chk_mail_sms['guardian_recipient']) {
                        $send_to = $student_key['guardian_email'];
                        if (!empty($this->CI->mail_config) && $send_to != "") {
                            $this->CI->mailer->send_mail($send_to, $subject, $msg);
                        }
                    }
                }
            }
        }
    }

    public function getpublishcontent($sender_details, $template)
    {

        foreach ($sender_details as $key => $value) {
            if (!empty($value)) {
                $template = str_replace('{{' . $key . '}}', $value, $template);
            }
        }
        return $template;
    }

    public function publishsendsms($sender_details, $template_id, $chk_mail_sms, $template = '')
    {

        $currency_symbol      = $this->CI->customlib->getSchoolCurrencyFormat();
        $sender_details['price'] = $currency_symbol . amountFormat($sender_details['price']);
        $sms_detail = $this->CI->smsconfig_model->getActiveSMS();

        if ($template != "") {
            $msg = $this->getpublishcontent($sender_details, $template);
        } else {
            $msg = $template;
        }

        $class_section = explode(',', $sender_details['class_section_id']);

        foreach ($class_section as $class_section_id) {
            $student_list = $this->CI->course_model->getStudentByClassSectionID($class_section_id);

            if (!empty($student_list)) {
                foreach ($student_list as $student_key) {

                    if ($chk_mail_sms['student_recipient']) {
                        $send_to = "";
                        $send_to = $student_key['mobileno'];

                        if ($send_to != "") {

                            if (!empty($sms_detail)) {

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
                                        return true;
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
                                        return true;
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
                                        'authkey'   => $sms_detail->authkey,
                                        'api_id'    => $sms_detail->api_id,
                                    );
                                    $this->CI->load->library('smscountry', $params);
                                    $this->CI->smscountry->sendSMS($send_to, $msg);
                                } else if ($sms_detail->type == 'text_local') {
                                    $to = $send_to;
                                    $params = array(
                                        'username' => $sms_detail->username,
                                        'hash' => $sms_detail->password,
                                    );
                                    $this->CI->load->library('textlocalsms', $params);
                                    $this->CI->textlocalsms->sendSms(array($to), $msg, $sms_detail->senderid);
                                } else if ($sms_detail->type == 'bulk_sms') {
                                    $to = $send_to;
                                    $params = array(
                                        'username' => $sms_detail->username,
                                        'password' => $sms_detail->password,
                                    );
                                    $this->CI->load->library('bulk_sms_lib', $params);
                                    $this->CI->bulk_sms_lib->sendSms(array($to), $msg);
                                } else if ($sms_detail->type == 'mobireach') {
                                    $to = $send_to;
                                    $params = array(
                                        'authkey' => $sms_detail->authkey,
                                        'senderid' => $sms_detail->senderid,
                                        'routeid' => $sms_detail->api_id,

                                    );
                                    $this->CI->load->library('mobireach_lib', $params);
                                    $this->CI->mobireach_lib->sendSms(array($to), $msg);
                                } else if ($sms_detail->type == 'smseg') {
                                    $to     = $send_to;
                                    $this->_CI->load->library('smseg_lib');
                                    $this->_CI->smseg_lib->sendSms($to, $msg);
                                } else if ($sms_detail->type == 'custom') {
                                    $this->CI->load->library('customsms');
                                    $from = $sms_detail->contact;
                                    $to = $send_to;
                                    $message = $msg;
                                    $this->CI->customsms->sendSMS($to, $message);
                                } else {
                                }
                            }
                        }
                    }

                    if ($chk_mail_sms['guardian_recipient']) {
                        $send_to = "";
                        $send_to = $student_key['guardian_phone'];

                        if ($send_to != "") {

                            if (!empty($sms_detail)) {

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
                                        return true;
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
                                        return true;
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
                                        'authkey'   => $sms_detail->authkey,
                                        'api_id'    => $sms_detail->api_id,
                                    );
                                    $this->CI->load->library('smscountry', $params);
                                    $this->CI->smscountry->sendSMS($send_to, $msg);
                                } else if ($sms_detail->type == 'text_local') {
                                    $to = $send_to;
                                    $params = array(
                                        'username' => $sms_detail->username,
                                        'hash' => $sms_detail->password,
                                    );
                                    $this->CI->load->library('textlocalsms', $params);
                                    $this->CI->textlocalsms->sendSms(array($to), $msg, $sms_detail->senderid);
                                } else if ($sms_detail->type == 'bulk_sms') {
                                    $to = $send_to;
                                    $params = array(
                                        'username' => $sms_detail->username,
                                        'password' => $sms_detail->password,
                                    );
                                    $this->CI->load->library('bulk_sms_lib', $params);
                                    $this->CI->bulk_sms_lib->sendSms(array($to), $msg);
                                } else if ($sms_detail->type == 'mobireach') {
                                    $to = $send_to;
                                    $params = array(
                                        'authkey' => $sms_detail->authkey,
                                        'senderid' => $sms_detail->senderid,
                                        'routeid' => $sms_detail->api_id,

                                    );
                                    $this->CI->load->library('mobireach_lib', $params);
                                    $this->CI->mobireach_lib->sendSms(array($to), $msg);
                                } else if ($sms_detail->type == 'smseg') {
                                    $to     = $send_to;
                                    $this->_CI->load->library('smseg_lib');
                                    $this->_CI->smseg_lib->sendSms($to, $msg);
                                } else if ($sms_detail->type == 'custom') {
                                    $this->CI->load->library('customsms');
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
        }
        return true;
    }

    public function publishsentnotification($sender_details, $subject, $chk_mail_sms, $template = '')
    {
        $currency_symbol      = $this->CI->customlib->getSchoolCurrencyFormat();
        $sender_details['price'] = $currency_symbol . amountFormat($sender_details['price']);
        $class_section = explode(',', $sender_details['class_section_id']);

        foreach ($class_section as $class_section_id) {
            $student_list = $this->CI->course_model->getStudentByClassSectionID($class_section_id);

            if (!empty($student_list)) {
                foreach ($student_list as $student_key) {
                    $send_to_student = $student_key['app_key'];
                    $send_to_parent = $student_key['parent_app_key'];

                    if ($send_to_student != "" && $chk_mail_sms['student_recipient']) {
                        $this->CI->load->library('pushnotification');
                        $msg        = $this->getpublishcontent($sender_details, $template);

                        $push_array = array(
                            'title' => $subject,
                            'body'  => $msg,
                        );
                        $this->CI->pushnotification->send($send_to_student, $push_array, "mail_sms");
                    }
                    if ($send_to_parent != "" && $chk_mail_sms['guardian_recipient']) {
                        $this->CI->load->library('pushnotification');
                        $msg        = $this->getpublishcontent($sender_details, $template);

                        $push_array = array(
                            'title' => $subject,
                            'body'  => $msg,
                        );
                        $this->CI->pushnotification->send($send_to_parent, $push_array, "mail_sms");
                    }
                }
            }
        }
    }

    public function purchasesentmail($sender_details, $template, $subject, $chk_mail_sms)
    {
        $currency_symbol      = $this->CI->customlib->getSchoolCurrencyFormat();
        $msg = $this->getpublishcontent($sender_details, $template);
        $sender_details['price'] = $currency_symbol . amountFormat($sender_details['price']);
        $class_section = explode(',', $sender_details['class_section_id']);

        foreach ($class_section as $class_section_id) {
            $student_list = $this->CI->course_model->getStudentByClassSectionID($class_section_id);

            if (!empty($student_list)) {
                foreach ($student_list as $student_key) {

                    if ($chk_mail_sms['student_recipient']) {
                        $send_to = "";
                        $send_to = $student_key['email'];

                        if (!empty($this->CI->mail_config) && $send_to != "") {
                            $this->CI->mailer->send_mail($send_to, $subject, $msg);
                        }
                    }

                    if ($chk_mail_sms['guardian_recipient']) {
                        $send_to = "";
                        $send_to = $student_key['guardian_email'];

                        if (!empty($this->CI->mail_config) && $send_to != "") {
                            $this->CI->mailer->send_mail($send_to, $subject, $msg);
                        }
                    }
                }
            }
        }
    }

    public function purchasesendsms($sender_details, $template_id, $chk_mail_sms, $template = '')
    {

        $currency_symbol      = $this->CI->customlib->getSchoolCurrencyFormat();
        $sender_details['price'] = $currency_symbol . amountFormat($sender_details['price']);

        $sms_detail = $this->CI->smsconfig_model->getActiveSMS();

        if ($template != "") {
            $msg = $this->getpublishcontent($sender_details, $template);
        } else {
            $msg = $template;
        }

        $class_section = explode(',', $sender_details['class_section_id']);

        foreach ($class_section as $class_section_id) {
            $student_list = $this->CI->course_model->getStudentByClassSectionID($class_section_id);

            if (!empty($student_list)) {
                foreach ($student_list as $student_key) {
                    if ($chk_mail_sms['student_recipient']) {
                        $send_to = "";
                        $send_to = $student_key['mobileno'];

                        if ($send_to != "") {

                            if (!empty($sms_detail)) {

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
                                        return true;
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
                                        return true;
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
                                        'authkey'   => $sms_detail->authkey,
                                        'api_id'    => $sms_detail->api_id,
                                    );
                                    $this->CI->load->library('smscountry', $params);
                                    $this->CI->smscountry->sendSMS($send_to, $msg);
                                } else if ($sms_detail->type == 'text_local') {
                                    $to = $send_to;
                                    $params = array(
                                        'username' => $sms_detail->username,
                                        'hash' => $sms_detail->password,
                                    );
                                    $this->CI->load->library('textlocalsms', $params);
                                    $this->CI->textlocalsms->sendSms(array($to), $msg, $sms_detail->senderid);
                                } else if ($sms_detail->type == 'bulk_sms') {
                                    $to = $send_to;
                                    $params = array(
                                        'username' => $sms_detail->username,
                                        'password' => $sms_detail->password,
                                    );
                                    $this->CI->load->library('bulk_sms_lib', $params);
                                    $this->CI->bulk_sms_lib->sendSms(array($to), $msg);
                                } else if ($sms_detail->type == 'mobireach') {
                                    $to = $send_to;
                                    $params = array(
                                        'authkey' => $sms_detail->authkey,
                                        'senderid' => $sms_detail->senderid,
                                        'routeid' => $sms_detail->api_id,

                                    );
                                    $this->CI->load->library('mobireach_lib', $params);
                                    $this->CI->mobireach_lib->sendSms(array($to), $msg);
                                } else if ($sms_detail->type == 'smseg') {
                                    $to     = $send_to;
                                    $this->_CI->load->library('smseg_lib');
                                    $this->_CI->smseg_lib->sendSms($to, $msg);
                                } else if ($sms_detail->type == 'custom') {
                                    $this->CI->load->library('customsms');
                                    $from = $sms_detail->contact;
                                    $to = $send_to;
                                    $message = $msg;
                                    $this->CI->customsms->sendSMS($to, $message);
                                } else {
                                }
                            }
                        }
                    }

                    if ($chk_mail_sms['guardian_recipient']) {
                        $send_to = "";
                        $send_to = $student_key['guardian_phone'];

                        if ($send_to != "") {

                            if (!empty($sms_detail)) {

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
                                        return true;
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
                                        return true;
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
                                        'authkey'   => $sms_detail->authkey,
                                        'api_id'    => $sms_detail->api_id,
                                    );
                                    $this->CI->load->library('smscountry', $params);
                                    $this->CI->smscountry->sendSMS($send_to, $msg);
                                } else if ($sms_detail->type == 'text_local') {
                                    $to = $send_to;
                                    $params = array(
                                        'username' => $sms_detail->username,
                                        'hash' => $sms_detail->password,
                                    );
                                    $this->CI->load->library('textlocalsms', $params);
                                    $this->CI->textlocalsms->sendSms(array($to), $msg, $sms_detail->senderid);
                                } else if ($sms_detail->type == 'bulk_sms') {
                                    $to = $send_to;
                                    $params = array(
                                        'username' => $sms_detail->username,
                                        'password' => $sms_detail->password,
                                    );
                                    $this->CI->load->library('bulk_sms_lib', $params);
                                    $this->CI->bulk_sms_lib->sendSms(array($to), $msg);
                                } else if ($sms_detail->type == 'mobireach') {
                                    $to = $send_to;
                                    $params = array(
                                        'authkey' => $sms_detail->authkey,
                                        'senderid' => $sms_detail->senderid,
                                        'routeid' => $sms_detail->api_id,

                                    );
                                    $this->CI->load->library('mobireach_lib', $params);
                                    $this->CI->mobireach_lib->sendSms(array($to), $msg);
                                } else if ($sms_detail->type == 'smseg') {
                                    $to     = $send_to;
                                    $this->_CI->load->library('smseg_lib');
                                    $this->_CI->smseg_lib->sendSms($to, $msg);
                                } else if ($sms_detail->type == 'custom') {
                                    $this->CI->load->library('customsms');
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
        }

        return true;
    }

    public function purchasesentnotification($sender_details, $subject, $chk_mail_sms, $template = '')
    {
        $currency_symbol      = $this->CI->customlib->getSchoolCurrencyFormat();
        $sender_details['price'] = $currency_symbol . amountFormat($sender_details['price']);

        $class_section = explode(',', $sender_details['class_section_id']);

        foreach ($class_section as $class_section_id) {
            $student_list = $this->CI->course_model->getStudentByClassSectionID($class_section_id);

            if (!empty($student_list)) {
                foreach ($student_list as $student_key) {
                    $send_to = $student_key['app_key'];
                    if ($send_to != "" && $chk_mail_sms['student_recipient']) {
                        $this->CI->load->library('pushnotification');
                        $msg        = $this->getpublishcontent($sender_details, $template);
                        $push_array = array(
                            'title' => $subject,
                            'body'  => $msg,
                        );
                        $this->CI->pushnotification->send($send_to, $push_array, "mail_sms");
                    }

                    $send_to_parent = $student_key['parent_app_key'];
                    if ($send_to_parent != "" && $chk_mail_sms['guardian_recipient']) {
                        $this->CI->load->library('pushnotification');
                        $msg        = $this->getpublishcontent($sender_details, $template);
                        $push_array = array(
                            'title' => $subject,
                            'body'  => $msg,
                        );
                        $this->CI->pushnotification->send($send_to_parent, $push_array, "mail_sms");
                    }
                }
            }
        }
    }

    public function purchasesentmailforguest($sender_details, $template, $subject, $chk_mail_sms)
    {

        $currency_symbol      = $this->CI->customlib->getSchoolCurrencyFormat();
        $sender_details['price'] = $currency_symbol . amountFormat($sender_details['price']);

        $msg = $this->getpublishcontent($sender_details, $template);

        if ($chk_mail_sms['student_recipient']) {
            $send_to = "";
            $send_to = $sender_details['email'];

            if (!empty($this->CI->mail_config) && $send_to != "") {
                $this->CI->mailer->send_mail($send_to, $subject, $msg);
            }
        }
    }

    public function purchasesentnotificationforguest($sender_details, $subject, $template = '')
    {
        $currency_symbol      = $this->CI->customlib->getSchoolCurrencyFormat();
        $sender_details['price'] = $currency_symbol . amountFormat($sender_details['price']);
        $class_section = explode(',', $sender_details['class_section_id']);

        foreach ($class_section as $class_section_id) {
            $student_list = $this->CI->course_model->getStudentByClassSectionID($class_section_id);

            if (!empty($student_list)) {
                foreach ($student_list as $student_key) {
                    $send_to = $student_key['app_key'];
                    if ($send_to != "") {
                        $this->CI->load->library('pushnotification');
                        $msg        = $this->getpublishcontent($sender_details, $template);
                        $push_array = array(
                            'title' => $subject,
                            'body'  => $msg,
                        );
                        $this->CI->pushnotification->send($send_to, $push_array, "mail_sms");
                    }
                }
            }
        }
    }

    public function guestusersignup($sender_details, $template, $subject, $chk_mail_sms)
    {
        $msg = $this->getpublishcontent($sender_details, $template);

        if ($chk_mail_sms['student_recipient']) {
            $send_to = "";
            $send_to = $sender_details['email'];

            if (!empty($this->CI->mail_config) && $send_to != "") {
                $this->CI->mailer->send_mail($send_to, $subject, $msg);
            }
        }
    }
}
