<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Paypal extends Student_Controller {

    public $setting = "";

    function __construct() {
        parent::__construct();
		$this->load->model(array('course_model','coursesection_model','courselesson_model','studentcourse_model','coursequiz_model','course_payment_model','courseofflinepayment_model','coursereport_model','currency_model'));
        $this->load->helper('file');
 
        $this->load->library('auth'); 
        $this->load->library('course_paypal_payment');
        $this->load->library('course_mail_sms');
        $this->setting = $this->setting_model->get();
          $this->currency_name= $this->currency_model->get($this->session->userdata('student')['currency'])->short_name;
    }
 
    public function index() {
        $data = array();
        $data['params'] = $this->session->userdata('course_amount');
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/paypal/index', $data);
    }
    
    public function complete() {

        if ($this->input->server('REQUEST_METHOD') == 'POST') {
            $params=$this->session->userdata('course_amount');
            $payment = array(
            'cancelUrl' => site_url('students/online_course/paypal/getsuccesspayment'),
            'returnUrl' => site_url('students/online_course/paypal/getsuccesspayment'),
            'course_name' => $params['course_name'],
            'name' => $params['name'],
            'description' => 'Online Course Fess',
            'amount' => convertBaseAmountCurrencyFormat($params['total_amount']),
            'currency' => $params['currency_name'],
            );
        
            $response = $this->course_paypal_payment->payment($payment);
            if ($response->isSuccessful()) {
                
            } elseif ($response->isRedirect()) {
                $response->redirect();
            } else {
                echo $response->getMessage();
            }
        }
    } 

    //paypal successpayment
    public function getsuccesspayment() {
            $params=$this->session->userdata('course_amount');
            $payment_success = array(
            'cancelUrl' => site_url('students/online_course/paypal/getsuccesspayment'),
            'returnUrl' => site_url('students/online_course/paypal/getsuccesspayment'),
            'course_name' => $params['course_name'],
            'name' => $params['name'],
            'description' => 'Online Course Fess',
            'amount' => $params['total_amount'],
            'currency' => $params['currency_name'],
            );
        $response = $this->course_paypal_payment->success($payment_success);

        $paypalResponse = $response->getData();
        if ($response->isSuccessful()) {
            $purchaseId = $_GET['PayerID'];

            if (isset($paypalResponse['PAYMENTINFO_0_ACK']) && $paypalResponse['PAYMENTINFO_0_ACK'] === 'Success') {
                if ($purchaseId) {
                    
                    $ref_id = $paypalResponse['PAYMENTINFO_0_TRANSACTIONID'];
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
                'transaction_id' => $ref_id,
                'note' => "Online course fees deposit through Paypal Ref. ID: " . $ref_id,
                'payment_mode' => 'Paypal',
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
            
                }
            }
        } elseif ($response->isRedirect()) {
            $response->redirect();
        } else {
            redirect(base_url("students/online_course/course_payment/paymentfailed"));
        }
    }

    public function guest() {
        $data = array();
        $data['params'] = $this->session->userdata('cart_data');
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/paypal/guest_course/index', $data);
    }

    public function guestcomplete() {

        if ($this->input->server('REQUEST_METHOD') == 'POST') {
            $params=$this->session->userdata('cart_data');
            $payment = array(
            'cancelUrl' => site_url('students/online_course/paypal/guestsuccesspayment'),
            'returnUrl' => site_url('students/online_course/paypal/guestsuccesspayment'),
            'course_name' => $params[0]['name'],
            'name' => $params[0]['guest_name'],
            'description' => 'Online Course Fess',
            'amount' => number_format((float)($this->input->post('total_cart_amount')), 2, '.', ''),
            'currency' =>$this->currency_name,
            );
        
            $response = $this->course_paypal_payment->payment($payment);
            if ($response->isSuccessful()) {
                
            } elseif ($response->isRedirect()) {
                $response->redirect();
            } else {
                echo $response->getMessage();
            }
        }
    } 

    //paypal successpayment
    public function guestsuccesspayment() {
            $params=$this->session->userdata('cart_data');
            $payment_success = array(
            'cancelUrl' => site_url('students/online_course/paypal/guestsuccesspayment'),
            'returnUrl' => site_url('students/online_course/paypal/guestsuccesspayment'),
            'course_name' => $params['course_name'],
            'name' => $params[0]['guest_name'],
            'description' => 'Online Course Fess',
            'amount' => $params['total_amount'],
            'currency' => $this->currency_name,
            );
        $response = $this->course_paypal_payment->success($payment_success);

        $paypalResponse = $response->getData();
        if ($response->isSuccessful()) {
            $purchaseId = $_GET['PayerID'];

            if (isset($paypalResponse['PAYMENTINFO_0_ACK']) && $paypalResponse['PAYMENTINFO_0_ACK'] === 'Success') {
                if ($purchaseId) {
                    
                    $ref_id = $paypalResponse['PAYMENTINFO_0_TRANSACTIONID'];
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
                        'transaction_id' =>  $ref_id,
                        'note' => "Online course fees deposit through Paypal Ref. ID: " . $ref_id,
                        'payment_mode' => 'Paypal',
                        'processing_charge_type'=>$cart_data_value['processing_charge_type'],
                    'processing_charge_amount'=>$cart_data_value['gateway_processing_charge'],
                    );
                    $this->course_payment_model->add($payment_data);
                    
                    $sender_details = array('email'=>$cart_data_value['email'], 'courseid'=> $cart_data_value['id'],'class' => null,  'class_section_id'=> null, 'section'=> null, 'title' => $cart_data_value['name'], 'price' => $cart_data_value['price'], 'discount' => $cart_data_value['discount'], 'assign_teacher' => $cart_data_value['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));

                $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
                }

                redirect(base_url("students/online_course/course_payment/paymentsuccess"));         
            
                }
            }
        } elseif ($response->isRedirect()) {
            $response->redirect();
        } else {
            redirect(base_url("students/online_course/course_payment/paymentfailed"));
        }
    }
}