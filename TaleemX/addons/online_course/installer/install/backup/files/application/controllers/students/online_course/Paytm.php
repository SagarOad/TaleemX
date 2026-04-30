<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Paytm extends Student_Controller {

    public $setting = "";

    public function __construct() {

        parent::__construct();
		$this->load->model(array('course_model','coursesection_model','courselesson_model','studentcourse_model','coursequiz_model','course_payment_model','courseofflinepayment_model','coursereport_model','currency_model'));
        $this->api_config = $this->paymentsetting_model->getActiveMethod();
        $this->setting = $this->setting_model->get();
        $this->load->library('Paytm_lib');
        $this->load->library('course_mail_sms');
         $this->currency_name= $this->currency_model->get($this->session->userdata('student')['currency'])->short_name;
    }

    /*
    This is used to show payment detail page and payment gateway functionality
    */
    public function index() {
        $params = $this->session->userdata('course_amount');
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        
        $paytmParams = array();
        $ORDER_ID = time();
        $CUST_ID = time();
        $paytmParams = array(
            "MID" => $this->api_config->api_publishable_key,
            "WEBSITE" => $this->api_config->paytm_website,
            "INDUSTRY_TYPE_ID" => $this->api_config->paytm_industrytype,
            "CHANNEL_ID" => "WEB",
            "ORDER_ID" => $ORDER_ID,
            "CUST_ID" => $params['student_id'],
            "TXN_AMOUNT" => convertBaseAmountCurrencyFormat($params['total_amount']),
            "CALLBACK_URL" => base_url() . "students/online_course/paytm/paytm_response",
        ); 

        $paytmChecksum = $this->paytm_lib->getChecksumFromArray($paytmParams, $this->api_config->api_secret_key);
 
        $paytmParams["CHECKSUMHASH"] = $paytmChecksum;
       //$transactionURL = 'https://securegw-stage.paytm.in/order/process';//sandbox
        $transactionURL = 'https://securegw.paytm.in/order/process';//live
        $data['paytmParams'] = $paytmParams;
        $data['transactionURL'] = $transactionURL;
        $this->load->view('user/studentcourse/online_course/paytm/index', $data);
    }

    /*
    This is used to show success page status
    */
    public function paytm_response() 
    {    
       if ($_POST["STATUS"] == "TXN_SUCCESS") {
        $paytmChecksum = "";
        $paramList = array();
        $isValidChecksum = "FALSE";
        $paramList = $_POST;
        $paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : "";

        $isValidChecksum = $this->paytm_lib->verifychecksum_e($paramList, $this->api_config->api_secret_key, $paytmChecksum);

        if ($isValidChecksum == "TRUE") {

                $params = $this->session->userdata('course_amount');
                $payment_id = $_POST['TXNID'];
                $payment_data = array(
                    'date' => date('Y-m-d'),
                    'student_id' => $params['student_id'],
                    'guest_id' => $params['guest_id'],
                    'online_courses_id' => $params['courseid'],
                    'course_name' => $params['course_name'],
                    'actual_price' => $params['actual_amount'],
                    'paid_amount' => $params['total_amount'],
                    'payment_type' => 'Online',
                    'transaction_id' => $payment_id,
                    'note' => "Online course fees deposit through Paytm Txn ID: " . $payment_id,
                    'payment_mode' => 'Paytm',
                    'processing_charge_type'=>$params['processing_charge_type'],
                    'processing_charge_amount'=>$params['gateway_processing_charge'],
                );
                $this->course_payment_model->add($payment_data);
                if(!empty($params['courseid'])) {
                    
                    $sender_details = array('email'=>$params['email'], 'courseid'=>$params['courseid'],'class' => $params['class'],  'class_section_id'=> $params['class_sections'], 'section'=> $params['section'], 'title' => $params['course_name'], 'price' => $params['total_amount'], 'discount' => $params['discount'], 'assign_teacher' => $params['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));         
                   
                     if($params['student_id']!=""){
                        $this->course_mail_sms->purchasemail('online_course_purchase', $sender_details);
                    }else if($params['guest_id']!=""){
                         $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
                    }              
                redirect(base_url("students/online_course/course_payment/paymentsuccess"));        
            }else{
                redirect(base_url('students/online_course/course_payment/paymentfailed'));
            } 
            
        } else {
            redirect(base_url("students/online_course/course_payment/paymentfailed"));
        }
        } else {
              redirect(base_url("students/online_course/course_payment/paymentfailed"));
            }
    }

    public function guest() {
        $params = $this->session->userdata('cart_data');
	
        $cart_total = 0;
        foreach ($params as $key => $params_value) {
            $cart_total += $params_value['price'];
        }
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        
        $paytmParams = array();
        $ORDER_ID = time();
        $CUST_ID = time();
        $paytmParams = array(
            "MID" => $this->api_config->api_publishable_key,
            "WEBSITE" => $this->api_config->paytm_website,
            "INDUSTRY_TYPE_ID" => $this->api_config->paytm_industrytype,
            "CHANNEL_ID" => "WEB",
            "ORDER_ID" => $ORDER_ID,
            "CUST_ID" => $params[0]['guest_id'],
            "TXN_AMOUNT" => convertBaseAmountCurrencyFormat($cart_total),
            "CALLBACK_URL" => base_url() . "students/online_course/paytm/guestpaytmresponse",
        ); 

        $paytmChecksum = $this->paytm_lib->getChecksumFromArray($paytmParams, $this->api_config->api_secret_key);
 
        $paytmParams["CHECKSUMHASH"] = $paytmChecksum;
        //$transactionURL = 'https://securegw-stage.paytm.in/order/process';//sandbox
        $transactionURL = 'https://securegw.paytm.in/order/process';//live
        $data['paytmParams'] = $paytmParams;
        $data['transactionURL'] = $transactionURL;
        $this->load->view('user/studentcourse/online_course/paytm/guest_course/index', $data);
    }

    /*
    This is used to show success page status
    */
    public function guestpaytmresponse() {
      
        $paytmChecksum = "";
        $paramList = array();
        $isValidChecksum = "FALSE";
        $paramList = $_POST;
        $paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : "";

        $isValidChecksum = $this->paytm_lib->verifychecksum_e($paramList, $this->api_config->api_secret_key, $paytmChecksum);

        if ($isValidChecksum == "TRUE") {

            if ($_POST["STATUS"] == "TXN_SUCCESS") {

                $cart_data = $this->session->userdata('cart_data');
                $payment_id = $_POST['TXNID'];

                foreach ($cart_data as $cart_data_value) {

                    $payment_data = array(
                        'date' => date('Y-m-d'),
                        'guest_id' => $cart_data_value['guest_id'],
                        'online_courses_id' => $cart_data_value['id'],
                        'course_name' => $cart_data_value['name'],
                        'actual_price' => $cart_data_value['actual_amount'],
                        'paid_amount' => $cart_data_value['price'],
                        'payment_type' => 'Online',
                        'transaction_id' =>  $payment_id,
                        'note' => "Online course fees deposit through Paytm Txn ID: " . $payment_id,
                        'payment_mode' => 'Paytm',
                        'processing_charge_type'=>$cart_data_value['processing_charge_type'],
                    'processing_charge_amount'=>$cart_data_value['gateway_processing_charge'],
                    );
                    $this->course_payment_model->add($payment_data);
                    
                    $sender_details = array('email'=>$cart_data_value['email'], 'courseid'=> $cart_data_value['id'],'class' => null,  'class_section_id'=> null, 'section'=> null, 'title' => $cart_data_value['name'], 'price' => $cart_data_value['price'], 'discount' => $cart_data_value['discount'], 'assign_teacher' => $cart_data_value['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));

                $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
                }

                redirect(base_url("students/online_course/course_payment/paymentsuccess"));
          
            } else {
              redirect(base_url("students/online_course/course_payment/paymentfailed"));
            }
        } else {
            redirect(base_url("students/online_course/course_payment/paymentfailed"));
        }
    }
}
