<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Twocheckout extends Student_Controller {

    //public $api_config = "";

    public function __construct() {
        parent::__construct();
		$this->load->model(array('course_model','coursesection_model','courselesson_model','studentcourse_model','coursequiz_model','course_payment_model','courseofflinepayment_model','coursereport_model','gateway_ins_model','currency_model'));
        $this->api_config = $this->paymentsetting_model->getActiveMethod();
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
        $data['error'] =array();
        $this->load->view('user/studentcourse/online_course/twocheckout/index', $data);
    }
  
    /*
    This is for payment gateway functionality
    */
    public function pay() {
        $this->form_validation->set_rules('phone', $this->lang->line('phone'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');

        $params = $this->session->userdata('course_amount');
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $data['api_error'] = $data['api_error'] = array();
        $result=array();
        if ($this->form_validation->run() == false) {
            $params = $this->session->userdata('course_amount');
            $data['params'] = $params;
            $this->load->view('user/studentcourse/online_course/twocheckout/index', $data);
        } else { 
            
            $params = $this->session->userdata('course_amount');

            $data['amount'] = convertBaseAmountCurrencyFormat($params['total_amount']);
            $data['currency']=$params['currency_name'];
            $data['api_config']=$this->api_config;
           
            $this->load->view('user/studentcourse/online_course/twocheckout/pay', $data);
        }
    }

    
    /*
    This is used to show success page status
    */
    public function success() {

        $params = $this->session->userdata('course_amount');
        $parameter_data=$this->gateway_ins_model->get_gateway_ins($params['transaction_id'],'toyyibpay');

        $payment_id = $params['transaction_id'];
        
            if($parameter_data['payment_status']=='success'){
            
                if(!empty($params['courseid'])) {

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
                    'note' => "Online course fees deposit through Twocheckout Txn ID: " . $payment_id,
                    'payment_mode' => 'Twocheckout',
                );
                $this->course_payment_model->add($payment_data);
                
                $sender_details = array('email'=>$params['email'], 'courseid'=>$params['courseid'],'class' => $params['class'],  'class_section_id'=> $params['class_sections'], 'section'=> $params['section'], 'title' => $params['course_name'], 'price' => $params['total_amount'], 'discount' => $params['discount'], 'assign_teacher' => $params['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));
 
                if($params['student_id']!=""){
                   $this->course_mail_sms->purchasemail('online_course_purchase', $sender_details);
                }else if($params['guest_id']!=""){
                     $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
                }  
                  redirect(base_url("students/online_course/course_payment/paymentsuccess")); 
            }elseif($parameter_data['payment_status']=='CANCELLED'){
                $this->gateway_ins_model->deleteBygateway_ins_id($parameter_data['id']); 
                redirect(base_url('students/online_course/course_payment/paymentfailed'));
            }else{
                //redirect(base_url("user/gateway/payment/paymentprocessing"));
            }
        
    }
}

    public function guest() {
        $data['params'] = $this->session->userdata('cart_data');
        $data['setting'] = $this->setting;
        $data['error'] =array();
        $this->load->view('user/studentcourse/online_course/twocheckout/guest_course/index', $data);
    }

    public function guestpay() {
        $this->form_validation->set_rules('phone', $this->lang->line('phone'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');

        $params = $this->session->userdata('cart_data');
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $data['api_error'] = $data['api_error'] = array();
        $result=array();
        if ($this->form_validation->run() == false) {
            $this->load->view('user/studentcourse/online_course/twocheckout/guest_course/index', $data);
        } else { 
            
            $params = $this->session->userdata('cart_data');

            $data['amount'] = $this->input->post('total_cart_amount');
            $data['currency']=$this->currency_name;
            $data['api_config']=$this->api_config;
           
            $this->load->view('user/studentcourse/online_course/twocheckout/guest_course/index', $data);
        }
    }
 
    
    /*
    This is used to show success page status
    */
    public function guestsuccess() {

        $cart_data = $this->session->userdata('cart_data');
        $parameter_data=$this->gateway_ins_model->get_gateway_ins($params['transaction_id'],'toyyibpay');
        
            
            if($parameter_data['payment_status']=='success'){
            
           // if(!empty($params['courseid'])) {

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
                        'note' => "Online course fees deposit through Twocheckout Txn ID: " . $payment_id,
                        'payment_mode' => 'Twocheckout',
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

}