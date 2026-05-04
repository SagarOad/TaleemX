<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Teams_mail_sms
{
    private $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('mailer');
    }

    public function mailsms($send_for, $sender_details)
    {
        $chk_mail_sms = $this->CI->customlib->sendMailSMS($send_for);
        if (empty($chk_mail_sms)) {
            return;
        }

        if ($send_for == "teams_online_classes" || $send_for == "teams_online_classes_start") {
            $this->sendTeamsOnlineClass($chk_mail_sms, $sender_details, $chk_mail_sms['template'], $chk_mail_sms['subject']);
        }
    }

    private function sendTeamsOnlineClass($chk_mail_sms, $student_details, $template, $subject)
    {
        if (empty($chk_mail_sms['mail'])) {
            return;
        }

        $class_section_id = $student_details['class_section_id'];
        $student_list     = $this->CI->teams_model->getStudentByClassSectionID($class_section_id);
        if (empty($student_list) || empty($this->CI->mail_config)) {
            return;
        }

        foreach ($student_list as $student) {
            if (empty($student['email'])) {
                continue;
            }
            $variables = array(
                '{{title}}'        => $student_details['title'],
                '{{date}}'         => $student_details['date'],
                '{{duration}}'     => $student_details['duration'],
                '{{class}}'        => $student['class'],
                '{{section}}'      => $student['section'],
                '{{admission_no}}' => $student['admission_no'],
                '{{student_name}}' => trim($student['firstname'] . ' ' . $student['lastname']),
            );
            $message = str_replace(array_keys($variables), array_values($variables), $template);
            $this->CI->mailer->send_mail($student['email'], $subject, $message);
        }
    }
}
