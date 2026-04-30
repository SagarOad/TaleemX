<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Momopay extends Student_Controller
{
    public $pay_method = "";
    public $setting    = "";

    public function __construct()
    {
        parent::__construct();

        $this->load->model(array(
            'course_model',
            'coursesection_model',
            'courselesson_model',
            'studentcourse_model',
            'coursequiz_model',
            'course_payment_model',
            'courseofflinepayment_model',
            'coursereport_model',
        ));

        $this->pay_method = $this->paymentsetting_model->getActiveMethod();
        $this->setting    = $this->setting_model->get();
        $this->load->library('course_mail_sms');
    }

    /**
     * Show payment detail page with phone input
     */
    public function index()
    {
        $data            = array();
        $data['params']  = $this->session->userdata('course_amount');
        $data['setting'] = $this->setting;
        $data['error']   = array();

        $this->load->view('user/studentcourse/online_course/momopay/index', $data);
    }

    /**
     * Initiate MoMo collection request and treat accepted request as success
     */
    public function pay()
    {
        $this->form_validation->set_rules('phone', 'phone', 'trim|required|xss_clean');

        $params          = $this->session->userdata('course_amount');
        $data['params']  = $params;
        $data['setting'] = $this->setting;
        $data['error']   = array();

        if ($this->form_validation->run() == false) {
            $this->load->view('user/studentcourse/online_course/momopay/index', $data);
            return;
        }

        if (empty($params) || !isset($params['total_amount'])) {
            $data['error'] = array('message' => 'Invalid or expired payment session. Please try again.');
            $this->load->view('user/studentcourse/online_course/momopay/index', $data);
            return;
        }

        $subscriptionKey = $this->pay_method->api_secret_key;
        $userId          = $this->pay_method->api_username;
        $apiKey          = $this->pay_method->api_publishable_key;

        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );

        $headers = array(
            "X-Reference-Id: " . $uuid,
            "X-Target-Environment: sandbox",
            "Ocp-Apim-Subscription-Key: " . $subscriptionKey,
            "Authorization: Bearer " . $this->momo_getAccessToken(),
            "Content-Type: application/json",
            "X-Callback-Url: " . base_url('students/online_course/momopay/handle_callback'),
        );

        $total_amount = number_format((float) $params['total_amount'], 2, '.', '');

        $payload = array(
            "amount"       => $total_amount,
            "currency"     => 'EUR',
            "externalId"   => $uuid,
            "payer"        => array(
                "partyIdType" => "MSISDN",
                "partyId"     => $this->input->post('phone'),
            ),
            "payerMessage" => "Online Course Payment",
            "payeeNote"    => "Thanks",
        );

        $ch = curl_init("https://proxy.momodeveloper.mtn.com/collection/v1_0/requesttopay");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch) || $httpCode >= 400) {
            $error_msg     = curl_errno($ch) ? curl_error($ch) : $response;
            curl_close($ch);
            $data['error'] = array('message' => $error_msg);
            $this->load->view('user/studentcourse/online_course/momopay/index', $data);
            return;
        }
        curl_close($ch);

        // For this implementation, treat successful request initiation as paid
        $this->success($uuid, $params);
    }

    /**
     * Optional callback handler (logs and redirects on failure)
     */
    public function handle_callback()
    {
        $response = json_decode(file_get_contents('php://input'), true);
        log_message('error', 'Momopay Course Callback: ' . print_r($response, true));
        // For now, main flow already records payment on accepted requesttopay
        redirect(base_url("students/online_course/course_payment/paymentsuccess"));
    }

    /**
     * Get MoMo bearer token
     *
     * @return string|null
     */
    private function momo_getAccessToken()
    {
        $subscriptionKey = $this->pay_method->api_secret_key;
        $userId          = $this->pay_method->api_username;
        $apiKey          = $this->pay_method->api_publishable_key;
        $url             = "https://proxy.momodeveloper.mtn.com/collection/token/";

        $headers = array(
            "Authorization: Basic " . base64_encode($userId . ':' . $apiKey),
            "Ocp-Apim-Subscription-Key: " . $subscriptionKey,
            'Cache-Control: no-cache',
            'Content-Length: 0',
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        if (isset($result['access_token'])) {
            return $result['access_token'];
        }

        return null;
    }

    /**
     * Persist successful payment and send notifications
     *
     * @param string $TransRef
     * @param array  $params
     */
    private function success($TransRef, $params)
    {
        $payment_data = array(
            'date'                     => date('Y-m-d'),
            'student_id'               => $params['student_id'],
            'guest_id'                 => $params['guest_id'],
            'online_courses_id'        => $params['courseid'],
            'course_name'              => $params['course_name'],
            'actual_price'             => $params['actual_amount'],
            'paid_amount'              => $params['total_amount'],
            'payment_type'             => 'Online',
            'transaction_id'           => $TransRef,
            'note'                     => "Online course fees deposit through Momopay Txn ID: " . $TransRef,
            'payment_mode'             => 'Momopay',
            'processing_charge_type'   => isset($params['processing_charge_type']) ? $params['processing_charge_type'] : null,
            'processing_charge_amount' => isset($params['gateway_processing_charge']) ? $params['gateway_processing_charge'] : 0,
        );

        $this->course_payment_model->add($payment_data);

        if (!empty($params['courseid'])) {
            $sender_details = array(
                'email'            => $params['email'],
                'courseid'         => $params['courseid'],
                'class'            => $params['class'],
                'class_section_id' => $params['class_sections'],
                'section'          => $params['section'],
                'title'            => $params['course_name'],
                'price'            => $params['total_amount'],
                'discount'         => $params['discount'],
                'assign_teacher'   => $params['staff'],
                'purchase_date'    => $this->customlib->dateformat(date('Y-m-d')),
            );

            if (!empty($params['student_id'])) {
                $this->course_mail_sms->purchasemail('online_course_purchase', $sender_details);
            } elseif (!empty($params['guest_id'])) {
                $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
            }
        }

        redirect(base_url("students/online_course/course_payment/paymentsuccess"));
    }
}


