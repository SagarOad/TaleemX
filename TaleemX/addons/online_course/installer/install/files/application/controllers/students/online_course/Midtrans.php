<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Midtrans extends Student_Controller {

    public $api_config = "";

    public function __construct() {
        parent::__construct();
		$this->load->model(array('course_model','coursesection_model','courselesson_model','studentcourse_model','coursequiz_model','course_payment_model','courseofflinepayment_model','coursereport_model','currency_model'));
        $this->api_config = $this->paymentsetting_model->getActiveMethod();
        $this->setting = $this->setting_model->get();
        $this->load->library('Midtrans_lib');
        $this->load->library('course_mail_sms');
        $this->currency_name= $this->currency_model->get($this->session->userdata('student')['currency'])->short_name;
    }

    /*
    This is used to show payment detail page
    */
    public function index() {
        $data['params'] = $this->session->userdata('course_amount');
        
        $data['setting'] = $this->setting;
        $enable_payments = array('credit_card');
        $transaction = array(
            'enabled_payments' => $enable_payments,
            'transaction_details' => array(
                'order_id' => time(),
                'gross_amount' => round(convertBaseAmountCurrencyFormat($data['params']['total_amount'])), // no decimal allowed
            ),
        );
    
if (empty($this->api_config->api_secret_key)) {
    $data['error'] = 'Server key is missing';
    $data['snap_Token'] = '';
} else {
    $snapToken = $this->midtrans_lib->getSnapToken($transaction, $this->api_config->api_secret_key);
    
    if(isset($snapToken['error'])){
        $data['error'] = $snapToken['message'];
        $data['snap_Token'] = '';
        
        // Log error for debugging
        log_message('error', 'Midtrans Error: ' . $snapToken['message']);
    } else {
        $data['snap_Token'] = $snapToken;
        $data['error'] = '';
    }
}
        $this->load->view('user/studentcourse/online_course/midtrans/index', $data);
    }

    /*
    This is for payment gateway functionality
    */
    public function midtrans_pay() {
        $response = json_decode($_POST['result_data']);
        $payment_id = $response->transaction_id;
        $params = $this->session->userdata('course_amount');
        $payment_data = array(
                'date' => date('Y-m-d'),
                'student_id' => $params['student_id'],
                'guest_id'   => $params['guest_id'],
                'online_courses_id' => $params['courseid'],
                'course_name' => $params['course_name'],
                'actual_price' => $params['actual_amount'],
                'paid_amount' => $params['total_amount'],
                'payment_type' => 'Online',
                'transaction_id' => $payment_id,
                'note' => "Online course fees deposit through midtrans Txn ID: " . $payment_id,
                'payment_mode' => 'midtrans',
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
            }
            echo 1;
    }
    
    /*
    This is used to show success page status
    */
    public function success(){

       $this->load->view('user/studentcourse/paymentsuccess');
    }

    public function guest() {
        $cartdata = $this->cart->contents();
        $cart_total = 0; 
        foreach ($cartdata as  $value) {
            
            $cart_total += $value['price'];
        }

        $data['setting'] = $this->setting;
        $enable_payments = array('credit_card');
        $transaction = array(
            'enabled_payments' => $enable_payments,
            'transaction_details' => array(
                'order_id' => time(),
                'gross_amount' => round(convertBaseAmountCurrencyFormat($cart_total)), // no decimal allowed
            ),
        );

        $snapToken = $this->midtrans_lib->getSnapToken($transaction, $this->api_config->api_secret_key);

        $data['snap_Token'] = $snapToken;
        $this->load->view('user/studentcourse/online_course/midtrans/guest_course/index', $data);
    }

    public function guestmidtranspay() {
        $response = json_decode($_POST['result_data']);
        $payment_id = $response->transaction_id;
        $cart_data = $this->session->userdata('cart_data');

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
                'note' => "Online course fees deposit through midtrans Txn ID: " . $payment_id,
                'payment_mode' => 'midtrans',
                'processing_charge_type'=>$cart_data_value['processing_charge_type'],
                    'processing_charge_amount'=>$cart_data_value['gateway_processing_charge'],
            );
            $this->course_payment_model->add($payment_data);
            
            $sender_details = array('email'=>$cart_data_value['email'], 'courseid'=> $cart_data_value['id'],'class' => null,  'class_section_id'=> null, 'section'=> null, 'title' => $cart_data_value['name'], 'price' => $cart_data_value['price'], 'discount' => $cart_data_value['discount'], 'assign_teacher' => $cart_data_value['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));

                $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
        } 

            echo 1;
    }
}
