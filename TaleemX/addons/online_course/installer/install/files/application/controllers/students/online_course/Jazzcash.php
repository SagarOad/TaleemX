<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Jazzcash extends Student_Controller {

    public $api_config = "";

    function __construct() {
        parent::__construct();
		$this->load->model(array('course_model','coursesection_model','courselesson_model','studentcourse_model','coursequiz_model','course_payment_model','courseofflinepayment_model','coursereport_model','currency_model'));
        $this->api_config = $this->paymentsetting_model->getActiveMethod();
        $this->setting = $this->setting_model->get();
        $this->load->library('course_mail_sms');
		date_default_timezone_set("Asia/Karachi");
        $this->currency_name= $this->currency_model->get($this->session->userdata('student')['currency'])->short_name;
    }

    /*
    This is used to show payment detail page
    */
    public function index() {
        $params = $this->session->userdata('course_amount');
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/jazzcash/index', $data);
    }

    /*
    This is for payment gateway functionality
    */
    public function pay(){
        $params = $this->session->userdata('course_amount');
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $amount = number_format((float)convertBaseAmountCurrencyFormat($params['total_amount']), 2, '.', '');
        $data['title'] = 'Online Course Fee';
        $data['return_url'] = base_url() . 'students/online_course/jazzcash/callback';
        $data['pp_MerchantID'] = $this->api_config->api_secret_key;
        $data['pp_Password'] = $this->api_config->api_password;
        $data['currency_code'] = $this->setting[0]['currency'];
        $data['ExpiryTime'] = date('YmdHis', strtotime("+3 hours"));
        $data['TxnDateTime'] = date('YmdHis', strtotime("+0 hours"));
        $data['TxnRefNumber'] = "T". date('YmdHis');
        $input_para["pp_Version"]="2.0";
        $input_para["pp_IsRegisteredCustomer"]="Yes";
        $input_para["pp_TxnType"]="MPAY";
        $input_para["pp_TokenizedCardNumber"]="";
        $input_para["pp_CustomerID"]=time();
        $input_para["pp_CustomerEmail"]="";
        $input_para["pp_CustomerMobile"]="";
        $input_para["pp_MerchantID"]=$data['pp_MerchantID'];
        $input_para["pp_Language"]="EN";
        $input_para["pp_SubMerchantID"]="";
        $input_para["pp_Password"]=$data['pp_Password'];
        $input_para["pp_TxnRefNo"]=$data['TxnRefNumber'];
        $input_para["pp_Amount"]=$amount*100;
        $input_para["pp_DiscountedAmount"]="";
        $input_para["pp_DiscountBank"]="";
        $input_para["pp_TxnCurrency"]=$params['currency_name'];
        $input_para["pp_TxnDateTime"]=$data['TxnDateTime'];
        $input_para["pp_TxnExpiryDateTime"]=$data['ExpiryTime'];
        $input_para["pp_BillReference"]=time();
        $input_para["pp_Description"]=$data['title'];
        $input_para["pp_ReturnURL"]=$data['return_url'];
        $input_para["pp_SecureHash"]="0123456789";
        $input_para["ppmpf_1"]="1";
        $input_para["ppmpf_2"]="2";
        $input_para["ppmpf_3"]="3";
        $input_para["ppmpf_4"]="4";
        $input_para["ppmpf_5"]="5";
        $data['payment_data']=$input_para;

        $this->load->view('user/studentcourse/online_course/jazzcash/jazzcash_pay', $data);
    }

    /*
    This is used to show success page status
    */
    public function callback() {		 
		
        $params = $this->session->userdata('course_amount');
        $data = array();
        if($_POST['pp_ResponseCode']=='000'){
        	$payment_id = $_POST['pp_TxnRefNo'];
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
                'note' => "Online course fees deposit through JazzCash Txn ID: " . $payment_id,
                'payment_mode' => 'JazzCash',
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

        }elseif($_POST['pp_ResponseCode']=='112'){
        	redirect(base_url("students/online_course/course_payment/paymentfailed"));
        }else{
            $this->session->set_flashdata('msg',$_POST['pp_ResponseMessage']);
            redirect(site_url('students/online_course/jazzcash'));
        }
    }

    public function guest() {
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/jazzcash/guest_course/index', $data);
    }

    public function guestpay(){
        $params = $this->session->userdata('cart_data');
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $amount = number_format((float)($this->input->post('total_cart_amount')), 2, '.', '');
        $data['title'] = 'Online Course Fee';
        $data['return_url'] = base_url() . 'students/online_course/jazzcash/guestcallback';
        $data['pp_MerchantID'] = $this->api_config->api_secret_key;
        $data['pp_Password'] = $this->api_config->api_password;
        $data['currency_code'] = $this->setting[0]['currency'];
        $data['ExpiryTime'] = date('YmdHis', strtotime("+3 hours"));
        $data['TxnDateTime'] = date('YmdHis', strtotime("+0 hours"));
        $data['TxnRefNumber'] = "T". date('YmdHis');
        $input_para["pp_Version"]="2.0";
        $input_para["pp_IsRegisteredCustomer"]="Yes";
        $input_para["pp_TxnType"]="MPAY";
        $input_para["pp_TokenizedCardNumber"]="";
        $input_para["pp_CustomerID"]=time();
        $input_para["pp_CustomerEmail"]="";
        $input_para["pp_CustomerMobile"]="";
        $input_para["pp_MerchantID"]=$data['pp_MerchantID'];
        $input_para["pp_Language"]="EN";
        $input_para["pp_SubMerchantID"]="";
        $input_para["pp_Password"]=$data['pp_Password'];
        $input_para["pp_TxnRefNo"]=$data['TxnRefNumber'];
        $input_para["pp_Amount"]=$amount*100;
        $input_para["pp_DiscountedAmount"]="";
        $input_para["pp_DiscountBank"]="";
        $input_para["pp_TxnCurrency"]=$this->currency_name;
        $input_para["pp_TxnDateTime"]=$data['TxnDateTime'];
        $input_para["pp_TxnExpiryDateTime"]=$data['ExpiryTime'];
        $input_para["pp_BillReference"]=time();
        $input_para["pp_Description"]=$data['title'];
        $input_para["pp_ReturnURL"]=$data['return_url'];
        $input_para["pp_SecureHash"]="0123456789";
        $input_para["ppmpf_1"]="1";
        $input_para["ppmpf_2"]="2";
        $input_para["ppmpf_3"]="3";
        $input_para["ppmpf_4"]="4";
        $input_para["ppmpf_5"]="5";
        $data['payment_data']=$input_para;
        
        $this->load->view('user/studentcourse/online_course/jazzcash/jazzcash_pay', $data);
    }

    /*
    This is used to show success page status
    */
    public function guestcallback() {
        $cart_data = $this->session->userdata('cart_data');
        $data = array();
        if($_POST['pp_ResponseCode']=='000'){
            $payment_id = $_POST['pp_TxnRefNo'];

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
                    'note' => "Online course fees deposit through JazzCash Txn ID: " . $payment_id,
                    'payment_mode' => 'JazzCash',
                    'processing_charge_type'=>$cart_data_value['processing_charge_type'],
                    'processing_charge_amount'=>$cart_data_value['gateway_processing_charge'],
                );
                $this->course_payment_model->add($payment_data);
                $sender_details = array('email'=>$cart_data_value['email'], 'courseid'=> $cart_data_value['id'],'class' => null,  'class_section_id'=> null, 'section'=> null, 'title' => $cart_data_value['name'], 'price' => $cart_data_value['price'], 'discount' => $cart_data_value['discount'], 'assign_teacher' => $cart_data_value['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));

                $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
            } 

        redirect(base_url("students/online_course/course_payment/paymentsuccess"));        

        }elseif($_POST['pp_ResponseCode']=='112'){
            redirect(base_url("students/online_course/course_payment/paymentfailed"));
        }else{
            $this->session->set_flashdata('msg',$_POST['pp_ResponseMessage']);
            redirect(site_url('students/online_course/jazzcash/guest'));
        }
    }
}