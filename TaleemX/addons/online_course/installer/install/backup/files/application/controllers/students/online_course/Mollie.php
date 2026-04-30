<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Mollie extends Student_Controller {

    public function __construct() {
        parent::__construct();
       $this->load->model(array('course_model','coursesection_model','courselesson_model','studentcourse_model','coursequiz_model','course_payment_model','courseofflinepayment_model','coursereport_model','currency_model'));
        $this->load->library('course_mail_sms');
        $this->setting = $this->setting_model->get();
        $this->currency_name= $this->currency_model->get($this->session->userdata('student')['currency'])->short_name;
    }
  
    public function index() {
 
        $params = $this->session->userdata('course_amount');
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/mollie/index', $data);
    } 
 
    public function pay() {
        $params = $this->session->userdata('course_amount');
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $this->form_validation->set_rules('phone', $this->lang->line('phone'), 'trim|xss_clean');
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|xss_clean');
        if ($this->form_validation->run() == false) {
            $data['api_error'] = array();
            $this->load->view('user/studentcourse/online_course/mollie/index', $data);
        } else {

            $apidetails = $this->paymentsetting_model->getActiveMethod();
            $data['name'] = $params['name'];
            $amount = number_format((float)convertBaseAmountCurrencyFormat($params['total_amount']), 2, '.', '');
            $api=' '.$apidetails->api_publishable_key;
            $order=time();
            $currency=$params['currency_name'];
            $redirectUrl=base_url()."students/online_course/mollie/success";

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://api.mollie.com/v2/payments');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "amount[currency]=".$currency."&amount[value]=".$amount."&description=#".$order."&redirectUrl=".$redirectUrl);

            $headers = array();
            $headers[] = 'Authorization: Bearer'.$api;
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
            }
            curl_close($ch);
            $json = json_decode($result, true);
            
            if ($json['status']=='open') {
                $url = $json['_links']['checkout']['href'];
                $params['mollie_payment_id']=$json['id'];
                $this->session->set_userdata("course_amount", $params);
                header("Location: $url");
            } else {
                
                $json = json_decode($result, true);
                $data['api_error'] = $json['detail'];
                $this->load->view('user/studentcourse/online_course/mollie/index', $data);
            }
        }
    }

    public function success() 
    {
        $apidetails = $this->paymentsetting_model->getActiveMethod();
        $params = $this->session->userdata('course_amount');
         $api=' '.$apidetails->api_publishable_key;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.mollie.com/v2/payments/'.$params['mollie_payment_id']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        $headers = array();
        $headers[] = 'Authorization: Bearer'.$api;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $json=json_decode($result);
 
        if ($json->status=='paid') {
            $payment_id = $json->id; 
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
                'note' => "Online course fees deposit through Mollie Txn ID: " . $payment_id,
                'payment_mode' => 'Mollie',
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
                redirect(base_url('students/online_course/course_payment/paymentfailed'));
        }
    }

    public function guest() {
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/mollie/guest_course/index', $data);
    }

    public function guestpay() {
        $params = $this->session->userdata('cart_data');
        $data['setting'] = $this->setting;
        $apidetails = $this->paymentsetting_model->getActiveMethod();
        $data['name'] = $params[0]['guest_name'];
        $amount = number_format((float)$this->input->post('total_cart_amount'), 2, '.', '');
        $api=' '.$apidetails->api_publishable_key;
        $order=time();
        $currency=$this->currency_name;
        $redirectUrl=base_url()."students/online_course/mollie/guestsuccess";

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.mollie.com/v2/payments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "amount[currency]=".$currency."&amount[value]=".$amount."&description=#".$order."&redirectUrl=".$redirectUrl);

        $headers = array();
        $headers[] = 'Authorization: Bearer'.$api;
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $json = json_decode($result, true);
        
        if ($json['status']=='open') {
            $url = $json['_links']['checkout']['href'];
            $params['mollie_payment_id']=$json['id'];
            $this->session->set_userdata("mollie_payment_id", $params);
            header("Location: $url");
        } else {
            $json = json_decode($result, true);
            $data['api_error'] = $json['detail'];
            $this->load->view('user/studentcourse/online_course/mollie/guest_course/index', $data);
        }
        
    }

    public function guestsuccess() 
    {
        $apidetails = $this->paymentsetting_model->getActiveMethod();
        $cart_data = $this->session->userdata('cart_data');
        $mollie_payment_id = $this->session->userdata('mollie_payment_id');

        $api=' '.$apidetails->api_publishable_key;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.mollie.com/v2/payments/'.$mollie_payment_id['mollie_payment_id']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');


        $headers = array();
        $headers[] = 'Authorization: Bearer'.$api;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $json=json_decode($result);

        if ($json->status=='paid') {
            $payment_id = $json->id; 

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
                    'note' => "Online course fees deposit through Mollie Txn ID: " . $payment_id,
                    'payment_mode' => 'Mollie',
                    'processing_charge_type'=>$cart_data_value['processing_charge_type'],
                    'processing_charge_amount'=>$cart_data_value['gateway_processing_charge'],
                );
                $this->course_payment_model->add($payment_data);
                
                $sender_details = array('email'=>$cart_data_value['email'], 'courseid'=> $cart_data_value['id'],'class' => null,  'class_section_id'=> null, 'section'=> null, 'title' => $cart_data_value['name'], 'price' => $cart_data_value['price'], 'discount' => $cart_data_value['discount'], 'assign_teacher' => $cart_data_value['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));

                $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
            }

            redirect(base_url("students/online_course/course_payment/paymentsuccess"));

          
        } else {
                redirect(base_url('students/online_course/course_payment/paymentfailed'));
        }
    }
}