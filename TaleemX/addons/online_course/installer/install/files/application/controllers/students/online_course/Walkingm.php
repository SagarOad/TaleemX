<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Walkingm extends Student_Controller {

    public function __construct() {
        parent::__construct();
        $this->setting = $this->setting_model->get();
       $this->load->model(array('course_model','coursesection_model','courselesson_model','studentcourse_model','coursequiz_model','course_payment_model','courseofflinepayment_model','coursereport_model','currency_model'));
        $this->load->library(array('walkingm_lib','course_mail_sms'));
          $this->currency_name= $this->currency_model->get($this->session->userdata('student')['currency'])->short_name;
    }
   
    public function index() {
 
        $params = $this->session->userdata('course_amount');        
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/walkingm/index', $data);
    }
 
    public function pay() {

        $params = $this->session->userdata('course_amount');

        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $this->form_validation->set_rules('email', $this->lang->line('walkingm_email'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('password', $this->lang->line('walkingm_password'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
         $data['api_error'] = array();
        $this->load->view('user/studentcourse/online_course/walkingm/index', $data);
        } else {
          $amount = $params['total_amount'];
          $payment_array['payer']="Walkingm"; 
          $payment_array['amount']=convertBaseAmountCurrencyFormat($amount);
          $payment_array['currency']=$params['currency_name'];
          $payment_array['successUrl']=base_url()."students/online_course/walkingm/success";
          $payment_array['cancelUrl']=base_url()."students/online_course/walkingm/cancel";
          $responce= $this->walkingm_lib->walkingm_login($_POST['email'],$_POST['password'],$payment_array);

          if($responce!=""){
            $data['api_error'] = $responce;
            $this->load->view('user/studentcourse/online_course/walkingm/index', $data);
          }
        }
    }

    public function success() {
        $responce= base64_decode($_SERVER["QUERY_STRING"]);
        $payment_responce=json_decode($responce);
        $params = $this->session->userdata('course_amount');
        if ($responce != '' && $payment_responce->status=200) {
              $payment_id = $payment_responce->transaction_id; 
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
                'note' => "Online course fees deposit through Walkingm Txn ID: " . $payment_id,
                'payment_mode' => 'Walkingm',
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

    public function cancel($responce){
      redirect(base_url('students/online_course/course_payment/paymentfailed'));
    }

    public function guest() {
    
        $params = $this->session->userdata('cart_data');
       
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/walkingm/guest_course/index', $data);
    }

    public function guestpay() {

        $params = $this->session->userdata('cart_data');

        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $this->form_validation->set_rules('email', $this->lang->line('walkingm_email'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('password', $this->lang->line('walkingm_password'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {
         $data['api_error'] = array();
        $this->load->view('user/studentcourse/online_course/walkingm/guest_course/index', $data);
        } else {

          $amount = number_format((float)$this->input->post('total_cart_amount'), 2, '.', '');
          $payment_array['payer']="Walkingm"; 
          $payment_array['amount']=$amount;
          $payment_array['currency']=$this->currency_name;
          $payment_array['successUrl']=base_url()."students/online_course/walkingm/guestsuccess";
          $payment_array['cancelUrl']=base_url()."students/online_course/walkingm/cancel";
          $responce= $this->walkingm_lib->walkingm_login($_POST['email'],$_POST['password'],$payment_array);

          if($responce!=""){
            $data['api_error'] = $responce;
            $this->load->view('user/studentcourse/online_course/walkingm/guest_course/index', $data);
          }

            }
        }

    public function guestsuccess() {
        $responce= base64_decode($_SERVER["QUERY_STRING"]);
        $payment_responce=json_decode($responce);
        $cart_data = $this->session->userdata('cart_data');
        if ($responce != '' && $payment_responce->status=200) {
            $payment_id = $payment_responce->transaction_id;

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
                  'note' => "Online course fees deposit through Walkingm Txn ID: " . $payment_id,
                  'payment_mode' => 'Walkingm',
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