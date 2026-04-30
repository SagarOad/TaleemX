<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Onepay extends Student_Controller {

    public $api_config = "";

    public function __construct() {
        parent::__construct();
		$this->load->model(array('course_model','coursesection_model','courselesson_model','studentcourse_model','coursequiz_model','course_payment_model','courseofflinepayment_model','coursereport_model','currency_model'));
        $api_config = $this->paymentsetting_model->getActiveMethod();
        $this->setting = $this->setting_model->get();
        $this->load->library('course_mail_sms');
        $this->currency_name= $this->currency_model->get($this->session->userdata('student')['currency'])->short_name;
    }

    /*
    This is used to show payment detail page
    */
    public function index() {
        
        $data['params'] = $this->session->userdata('course_amount');
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/onepay/index', $data);
    }

    /*
    This is for payment gateway functionality
    */
    public function pay() {
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');

        $params = $this->session->userdata('course_amount');

        $data['params'] = $params;
        $data['setting'] = $this->setting;

        if ($this->form_validation->run() == false) {
            $this->load->view('user/studentcourse/online_course/onepay/index', $data);
        } else { 
            $details = $this->paymentsetting_model->getActiveMethod();
            $api_secret_key = $details->api_secret_key;
            $api_publishable_key = $details->api_publishable_key;
            $amount = round(convertBaseAmountCurrencyFormat($params['total_amount']));
               $data = array();
            $data['name'] = $params['name'];
           
           
        $student_data = $this->student_model->get($params['student_id']);
     
        $appendAmp = 0;

        $SECURE_SECRET =$details->api_signature;
        $payment_data=array(
        'AVS_City' => '',
        'AVS_Country' =>'',
        'AVS_PostCode' => '',
        'AVS_StateProv' => '',
        'AVS_Street01' => '',
        'AgainLink' => '',
        'Title' => '',
        'display' => '',
        'vpc_AccessCode' => $details->salt,
        'vpc_Amount' => $amount*100,
        'vpc_Command' => 'pay',
        'vpc_Customer_Email' => '',
        'vpc_Customer_Id' => '',
        'vpc_Customer_Phone' => '',
        'vpc_Locale' => 'en',
        'vpc_MerchTxnRef' => date('YmdHis') . rand(),
        'vpc_Merchant' => $details->api_publishable_key,
        'vpc_OrderInfo' => 'JSECURETEST01',
        'vpc_ReturnURL' => base_url() . 'students/online_course/onepay/success',
        'vpc_SHIP_City' => '',
        'vpc_SHIP_Country' => '',
        'vpc_SHIP_Provice' => '',
        'vpc_SHIP_Street01' => '',
        'vpc_TicketNo' => $_SERVER['REMOTE_ADDR'],
        'vpc_Version' => '2');
        $vpcURL="https://mtf.onepay.vn/paygate/vpcpay.op?";
        $md5HashData="";
        foreach($payment_data as $key => $value) {
            if (strlen($value) > 0) {
                if ($appendAmp == 0) {
                    $vpcURL .= urlencode($key) . '=' . urlencode($value);
                    $appendAmp = 1;
                } else {
                    $vpcURL .= '&' . urlencode($key) . "=" . urlencode($value);
                }

                if ((strlen($value) > 0) && ((substr($key, 0,4)=="vpc_") || (substr($key,0,5) =="user_"))) {
                    $md5HashData .= $key . "=" . $value . "&";
                }
            }
        }

        $md5HashData = rtrim($md5HashData, "&");

        if (strlen($SECURE_SECRET) > 0) {

            $vpcURL .= "&vpc_SecureHash=" . strtoupper(hash_hmac('SHA256', $md5HashData, pack('H*',$SECURE_SECRET)));
        }


        header("Location: ".$vpcURL);
        }
    }

    /*
    This is used to show success page status
    */

     public function success() {
        $details = $this->paymentsetting_model->getActiveMethod();
        $api_secret_key = $details->api_secret_key;
        $params = $this->session->userdata('course_amount');
        
       
      
     $SECURE_SECRET =$details->api_signature;
$vpc_Txn_Secure_Hash = $_GET["vpc_SecureHash"];
$vpc_MerchTxnRef = $_GET["vpc_MerchTxnRef"];
$vpc_AcqResponseCode = $_GET["vpc_AcqResponseCode"];
unset($_GET["vpc_SecureHash"]);
$errorExists = false;
if (strlen($SECURE_SECRET) > 0 && $_GET["vpc_TxnResponseCode"] != "7" && $_GET["vpc_TxnResponseCode"] != "No Value Returned") {
    ksort($_GET);
    $md5HashData = "";
    foreach ($_GET as $key => $value) {
        if ($key != "vpc_SecureHash" && (strlen($value) > 0) && ((substr($key, 0,4)=="vpc_") || (substr($key,0,5) =="user_"))) {
            $md5HashData .= $key . "=" . $value . "&";
        }
    }

    $md5HashData = rtrim($md5HashData, "&");
    if (strtoupper ( $vpc_Txn_Secure_Hash ) == strtoupper(hash_hmac('SHA256', $md5HashData, pack('H*',$SECURE_SECRET)))) {
        $hashValidated = "CORRECT";
    } else {
        $hashValidated = "INVALID HASH";
    }
} else {

    $hashValidated = "INVALID HASH";
}

$txnResponseCode = $this->null2unknown($_GET["vpc_TxnResponseCode"]);

$verType = array_key_exists("vpc_VerType", $_GET) ? $_GET["vpc_VerType"] : "No Value Returned";
$verStatus = array_key_exists("vpc_VerStatus", $_GET) ? $_GET["vpc_VerStatus"] : "No Value Returned";
$token = array_key_exists("vpc_VerToken", $_GET) ? $_GET["vpc_VerToken"] : "No Value Returned";
$verSecurLevel = array_key_exists("vpc_VerSecurityLevel", $_GET) ? $_GET["vpc_VerSecurityLevel"] : "No Value Returned";
$enrolled = array_key_exists("vpc_3DSenrolled", $_GET) ? $_GET["vpc_3DSenrolled"] : "No Value Returned";
$xid = array_key_exists("vpc_3DSXID", $_GET) ? $_GET["vpc_3DSXID"] : "No Value Returned";
$acqECI = array_key_exists("vpc_3DSECI", $_GET) ? $_GET["vpc_3DSECI"] : "No Value Returned";
$authStatus = array_key_exists("vpc_3DSstatus", $_GET) ? $_GET["vpc_3DSstatus"] : "No Value Returned";

$errorTxt = "";

if ($txnResponseCode == "7" || $txnResponseCode == "No Value Returned" || $errorExists) {
    $errorTxt = "Error ";
}

$transStatus = "";
if($hashValidated=="CORRECT" && $txnResponseCode=="0"){
    $transStatus = "success";
    $params = $this->session->userdata('params');

            $payment_id = $_GET["vpc_MerchTxnRef"];
              
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
                    'note' => "Online course fees deposit through Onepay Txn ID: " . $payment_id,
                    'payment_mode' => 'onepay',
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
               
                 
            }elseif ($hashValidated=="INVALID HASH" && $txnResponseCode=="0"){
                redirect(base_url('students/online_course/course_payment/paymentfailed'));
            }else {
                $transStatus = "fail";
              redirect(base_url('students/online_course/course_payment/paymentfailed'));
            }
     

         
        
    
    }


   
    public function guest() 
    {
        $data['params'] = $this->session->userdata('cart_data');
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/onepay/guest_course/index', $data);
    }

    public function guestpay() {
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');

        $params = $this->session->userdata('cart_data');
        $data['params'] = $params;
        $data['setting'] = $this->setting;

        if ($this->form_validation->run() == false) {
            $this->load->view('user/studentcourse/online_course/onepay/guest_course/index', $data);
        } else { 
            $details = $this->paymentsetting_model->getActiveMethod();
            $api_secret_key = $details->api_secret_key;
            $api_publishable_key = $details->api_publishable_key;
            $amount = round($this->input->post('total_cart_amount'));
            
            $customer_email = $_POST['email'];
           $currency=$this->currency_name;
            
            $txref = "rave" . uniqid(); // ensure you generate unique references per transaction.
            // get your public key from the dashboard.
            $PBFPubKey = $api_publishable_key; 
           
            $appendAmp = 0;
        $SECURE_SECRET =$details->api_signature;
        $payment_data=array(
        'AVS_City' => '',
        'AVS_Country' =>'',
        'AVS_PostCode' => '',
        'AVS_StateProv' => '',
        'AVS_Street01' => '',
        'AgainLink' =>'',
        'Title' => '',
        'display' => '',
        'vpc_AccessCode' => $details->salt,
        'vpc_Amount' => $amount*100,
        'vpc_Command' => 'pay',
        'vpc_Customer_Email' => '',
        'vpc_Customer_Id' => '',
        'vpc_Customer_Phone' => '',
        'vpc_Locale' => 'en',
        'vpc_MerchTxnRef' => date('YmdHis') . rand(),
        'vpc_Merchant' => $details->api_publishable_key,
        'vpc_OrderInfo' => 'JSECURETEST01',
        'vpc_ReturnURL' => base_url() . 'students/online_course/onepay/success',
        'vpc_SHIP_City' => '',
        'vpc_SHIP_Country' => '',
        'vpc_SHIP_Provice' => '',
        'vpc_SHIP_Street01' => '',
        'vpc_TicketNo' => $_SERVER ['REMOTE_ADDR'],
        'vpc_Version' => '2');
        $vpcURL="https://mtf.onepay.vn/paygate/vpcpay.op?";
        foreach($payment_data as $key => $value) {
            if (strlen($value) > 0) {
                if ($appendAmp == 0) {
                    $vpcURL .= urlencode($key) . '=' . urlencode($value);
                    $appendAmp = 1;
                } else {
                    $vpcURL .= '&' . urlencode($key) . "=" . urlencode($value);
                }

                if ((strlen($value) > 0) && ((substr($key, 0,4)=="vpc_") || (substr($key,0,5) =="user_"))) {
                    $md5HashData .= $key . "=" . $value . "&";
                }
            }
        }

        $md5HashData = rtrim($md5HashData, "&");

        if (strlen($SECURE_SECRET) > 0) {

            $vpcURL .= "&vpc_SecureHash=" . strtoupper(hash_hmac('SHA256', $md5HashData, pack('H*',$SECURE_SECRET)));
        }


        header("Location: ".$vpcURL);
        }
    }

    /*
    This is used to show success page status
    */
    public function guestsuccess() {
        $details = $this->paymentsetting_model->getActiveMethod();
        $api_secret_key = $details->api_secret_key;
        $cart_data = $this->session->userdata('cart_data');
       
      
     $SECURE_SECRET =$details->api_signature;
$vpc_Txn_Secure_Hash = $_GET["vpc_SecureHash"];
$vpc_MerchTxnRef = $_GET["vpc_MerchTxnRef"];
$vpc_AcqResponseCode = $_GET["vpc_AcqResponseCode"];
unset($_GET["vpc_SecureHash"]);
$errorExists = false;
if (strlen($SECURE_SECRET) > 0 && $_GET["vpc_TxnResponseCode"] != "7" && $_GET["vpc_TxnResponseCode"] != "No Value Returned") {
    ksort($_GET);
    $md5HashData = "";
    foreach ($_GET as $key => $value) {
        if ($key != "vpc_SecureHash" && (strlen($value) > 0) && ((substr($key, 0,4)=="vpc_") || (substr($key,0,5) =="user_"))) {
            $md5HashData .= $key . "=" . $value . "&";
        }
    }

    $md5HashData = rtrim($md5HashData, "&");
    if (strtoupper ( $vpc_Txn_Secure_Hash ) == strtoupper(hash_hmac('SHA256', $md5HashData, pack('H*',$SECURE_SECRET)))) {
        $hashValidated = "CORRECT";
    } else {
        $hashValidated = "INVALID HASH";
    }
} else {

    $hashValidated = "INVALID HASH";
}

$txnResponseCode = $this->null2unknown($_GET["vpc_TxnResponseCode"]);

$verType = array_key_exists("vpc_VerType", $_GET) ? $_GET["vpc_VerType"] : "No Value Returned";
$verStatus = array_key_exists("vpc_VerStatus", $_GET) ? $_GET["vpc_VerStatus"] : "No Value Returned";
$token = array_key_exists("vpc_VerToken", $_GET) ? $_GET["vpc_VerToken"] : "No Value Returned";
$verSecurLevel = array_key_exists("vpc_VerSecurityLevel", $_GET) ? $_GET["vpc_VerSecurityLevel"] : "No Value Returned";
$enrolled = array_key_exists("vpc_3DSenrolled", $_GET) ? $_GET["vpc_3DSenrolled"] : "No Value Returned";
$xid = array_key_exists("vpc_3DSXID", $_GET) ? $_GET["vpc_3DSXID"] : "No Value Returned";
$acqECI = array_key_exists("vpc_3DSECI", $_GET) ? $_GET["vpc_3DSECI"] : "No Value Returned";
$authStatus = array_key_exists("vpc_3DSstatus", $_GET) ? $_GET["vpc_3DSstatus"] : "No Value Returned";

$errorTxt = "";

if ($txnResponseCode == "7" || $txnResponseCode == "No Value Returned" || $errorExists) {
    $errorTxt = "Error ";
}

$transStatus = "";
if($hashValidated=="CORRECT" && $txnResponseCode=="0"){
    $transStatus = "success";
    $params = $this->session->userdata('params');

            $payment_id = $_GET["vpc_MerchTxnRef"];
            
                foreach ($cart_data as $cart_data_value) {

                    $sender_details = array(
                        'date' => date('Y-m-d'),
                        'guest_id' => $cart_data_value['guest_id'],
                        'online_courses_id' => $cart_data_value['id'],
                        'course_name' => $cart_data_value['name'],
                        'actual_price' => $cart_data_value['actual_amount'],
                        'paid_amount' => $cart_data_value['price'],
                        'payment_type' => 'Online',
                        'transaction_id' =>  $payment_id,
                        'note' => "Online course fees deposit through Onepay Txn ID: " . $payment_id,
                        'payment_mode' => 'Onepay',
                        'processing_charge_type'=>$cart_data_value['processing_charge_type'],
                    'processing_charge_amount'=>$cart_data_value['gateway_processing_charge'],
                    );
                    $this->course_payment_model->add($sender_details);
                    $sender_details = array('email'=>$cart_data_value['email'], 'courseid'=> $cart_data_value['id'],'class' => null,  'class_section_id'=> null, 'section'=> null, 'title' => $cart_data_value['name'], 'price' => $cart_data_value['price'], 'discount' => $cart_data_value['discount'], 'assign_teacher' => $cart_data_value['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));

                    $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
                }
                   
                     
                    if ($response) { 
                          redirect(base_url("students/online_course/course_payment/paymentsuccess"));                     
                    } else {
                      redirect(base_url('students/online_course/course_payment/paymentfailed'));
                    }
            }elseif ($hashValidated=="INVALID HASH" && $txnResponseCode=="0"){
                $transStatus = "pending";
            }else {
                $transStatus = "fail";
                $this->fail();
            }
     

         
        
    
    }

        public function fail()
    {

        redirect(base_url('students/online_course/course_payment/paymentfailed'));

    }
    
    public function cancel()
    {

       redirect(base_url('students/online_course/course_payment/paymentfailed'));

    }


// If input is null, returns string "No Value Returned", else returns input
public function null2unknown($data)
{
    if ($data == "") {
        return "No Value Returned";
    } else {
        return $data;
    }
}
}