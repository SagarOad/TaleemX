<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Payhere extends Student_Controller {  

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
        $this->load->view('user/studentcourse/online_course/payhere/index', $data);
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
            $this->load->view('user/studentcourse/online_course/payhere/index', $data);
        } else { 
            
            $params = $this->session->userdata('course_amount');
            $amount = $params['total_amount'];            
             
            $htmlform=array(
                'merchant_id'=>$this->api_config->api_publishable_key,
                'return_url'=>base_url().'students/online_course/payhere/success',
                'cancel_url'=>base_url().'students/online_course/course_payment/paymentfailed',
                'notify_url'=>base_url().'gateway_ins/payhere',
                'order_id'=>time().rand(99,999),
                'items'=>'Student Fees',
                'currency'=>$params['currency_name'],
                'amount'=>convertBaseAmountCurrencyFormat($amount),
                'first_name'=>$params['name'],
                'last_name'=>'',
                'email'=>$_POST['email'],
                'phone'=>$_POST['phone'],
                'address'=>'',
                'city'=>'',
                'country'=>''
            );
             
            $data['htmlform']=$htmlform;
            $params['transaction_id']=$htmlform['order_id'];
            
            $ins_data=array(
                'unique_id'=>$htmlform['order_id'],
                'parameter_details'=>json_encode($htmlform),
                'gateway_name'=>'payhere',
                'module_type'=>'online_course',
                'payment_status'=>'processing',
            );

            $gateway_ins_id=$this->gateway_ins_model->add_gateway_ins($ins_data);
           
            $payment_data = array(
                'date' => date('Y-m-d'),
                'student_id' => $params['student_id'],
                'guest_id' => $params['guest_id'],
                'online_courses_id' => $params['courseid'],
                'course_name' => $params['course_name'],
                'actual_price' => $params['actual_amount'],
                'paid_amount' => $params['total_amount'],
                'payment_type' => 'Online',
                'gateway_ins_id' => $gateway_ins_id,
		'transaction_id' => $params['transaction_id'],
                'note' => "Online course fees processing Payhere Payment ID: " . $params['transaction_id'],
                'payment_mode' => 'Payhere',
            ); 
            $this->course_payment_model->add_processingpayment($payment_data);
            $this->session->set_userdata("course_amount", $params);              
            $this->load->view('user/studentcourse/online_course/payhere/pay', $data);
        }
    }
    
    /*
    This is used to show success page status
    */
    public function success() {

        $params = $this->session->userdata('course_amount');
        $parameter_data=$this->gateway_ins_model->get_gateway_ins($params['transaction_id'],'payhere');
        $payment_id = $params['transaction_id'];
        if($parameter_data['payment_status']=='2'){        
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
                    'note' => "Online course fees deposit through Payhere Txn ID: " . $payment_id,
                    'payment_mode' => 'Payhere',
                );
                $this->course_payment_model->add($payment_data);
                
                $sender_details = array('email'=>$params['email'], 'courseid'=>$params['courseid'],'class' => $params['class'],  'class_section_id'=> $params['class_sections'], 'section'=> $params['section'], 'title' => $params['course_name'], 'price' => $params['total_amount'], 'discount' => $params['discount'], 'assign_teacher' => $params['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));

                if($params['student_id']!=""){
                       $this->course_mail_sms->purchasemail('online_course_purchase', $sender_details);
                }else if($params['guest_id']!=""){
                     $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
                }   
                redirect(base_url("students/online_course/course_payment/paymentsuccess")); 
            }elseif($parameter_data['payment_status']=='-2'){
                $this->gateway_ins_model->deleteBygateway_ins_id($parameter_data['id']); 
                redirect(base_url('students/online_course/course_payment/paymentfailed'));
            }else{
                redirect(base_url("user/gateway/payment/paymentprocessing"));
            }
        
    }
}

    public function guest() {
        $data['params'] = $this->session->userdata('cart_data');
        $data['setting'] = $this->setting;
        $data['error'] =array();
        $this->load->view('user/studentcourse/online_course/payhere/guest_course/index', $data);
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
            $this->load->view('user/studentcourse/online_course/payhere/guest_course/index', $data);
        } else { 
            
            $params = $this->session->userdata('cart_data');
            $amount = number_format((float)$this->input->post('total_cart_amount'), 2, '.', '');             
            $htmlform=array(
                'merchant_id'=>$this->api_config->api_publishable_key,
                'return_url'=>base_url().'students/online_course/payhere/guestsuccess',
                'cancel_url'=>base_url().'students/online_course/course_payment/paymentfailed',
                'notify_url'=>base_url().'gateway_ins/payhere',
                'order_id'=>time().rand(99,999),
                'items'=>'Online Course Fees',
                'currency'=>$this->currency_name,
                'amount'=>$amount,
                'first_name'=>$params[0]['guest_name'],
                'last_name'=>'',
                'email'=>$_POST['email'],
                'phone'=>$_POST['phone'],
                'address'=>'',
                'city'=>'',
                'country'=>''
            );
             
            $data['htmlform']=$htmlform;
            $transaction_id=$htmlform['order_id'];
            
            $ins_data=array(
            'unique_id'=>$htmlform['order_id'],
            'parameter_details'=>json_encode($htmlform),
            'gateway_name'=>'payhere',
            'module_type'=>'online_course',
            'payment_status'=>'processing',
            );

            $gateway_ins_id=$this->gateway_ins_model->add_gateway_ins($ins_data);
          
            foreach ($params as $cart_data_value) {

                $payment_data = array(
                    'date' => date('Y-m-d'),
                    'guest_id' => $cart_data_value['guest_id'],
                    'online_courses_id' => $cart_data_value['id'],
                    'course_name' => $cart_data_value['name'],
                    'actual_price' => $cart_data_value['actual_amount'],
                    'paid_amount' => $cart_data_value['price'],
                    'payment_type' => 'Online',
                    'transaction_id' =>  $transaction_id,
                    'note' => "Online course fees processing Payhere Payment ID: " . $transaction_id,
                    'payment_mode' => 'Payhere',
                    'gateway_ins_id' => $gateway_ins_id,
                    'processing_charge_type'=>$cart_data_value['processing_charge_type'],
                    'processing_charge_amount'=>$cart_data_value['gateway_processing_charge'],
                );
                $this->course_payment_model->add_processingpayment($payment_data);
            }

            $this->session->set_userdata("transaction_id", $transaction_id);
              
            $this->load->view('user/studentcourse/online_course/payhere/pay', $data);
        }
    }

    
    /*
    This is used to show success page status
    */
    public function guestsuccess() {

        $cart_data = $this->session->userdata('cart_data');
        $transaction_id = $this->session->set_userdata("transaction_id", $transaction_id);

        $parameter_data=$this->gateway_ins_model->get_gateway_ins($transaction_id,'payhere');
        if($parameter_data['payment_status']=='2'){      

            foreach ($cart_data as $cart_data_value) {

                $payment_data = array(
                    'date' => date('Y-m-d'),
                    'guest_id' => $cart_data_value['guest_id'],
                    'online_courses_id' => $cart_data_value['id'],
                    'course_name' => $cart_data_value['name'],
                    'actual_price' => $cart_data_value['actual_amount'],
                    'paid_amount' => $cart_data_value['price'],
                    'payment_type' => 'Online',
                    'transaction_id' =>  $transaction_id,
                    'note' => "Online course fees deposit through Payhere Txn ID: " . $transaction_id,
                    'payment_mode' => 'Payhere',
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