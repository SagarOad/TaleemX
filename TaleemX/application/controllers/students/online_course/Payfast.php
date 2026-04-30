<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Payfast extends Student_Controller {    

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
        if(isset($data['params']['guest_id']) && !empty($data['params']['guest_id'])){
            $data['fee_processing']=$this->course_payment_model->check_payment_status($data['params']['courseid'], $data['params']['student_id'],$data['params']['guest_id'],'payfast');   
        }else{
            $data['fee_processing']=$this->course_payment_model->check_payment_status($data['params']['courseid'], $data['params']['student_id'],'','payfast');
        }     
        
        $this->load->view('user/studentcourse/online_course/payfast/index', $data);
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

        if ($this->form_validation->run() == false) {
            $this->load->view('user/studentcourse/online_course/payfast/index', $data);
        } else { 
            
            $params = $this->session->userdata('course_amount');
            $amount = convertBaseAmountCurrencyFormat($params['total_amount']);
            $cartTotal = $params['total_amount'];// This amount needs to be sourced from your application
            $data = array(
            'merchant_id' => $this->api_config->api_publishable_key,
            'merchant_key' => $this->api_config->api_secret_key,
            'return_url' => base_url().'students/online_course/payfast/success',
            'cancel_url' => base_url().'students/online_course/payfast/cancel',
            'notify_url' => base_url().'gateway_ins/payfast',
            'name_first' => $params['name'],
            'name_last'  => 'name_last',
            'email_address'=> $_POST['email'],
            'm_payment_id' => time().rand(99,999), //Unique payment ID to pass through to notify_url
            'amount' => number_format( sprintf( '%.2f', $cartTotal ), 2, '.', '' ),
            'item_name' => 'fees#'.rand(99,999),
            );
           
            $signature = $this->generateSignature($data,$this->api_config->salt);
            $data['signature'] = $signature;
           
            $params['transaction_id']=$data['m_payment_id'];
            
             $ins_data=array(
            'unique_id'=>$data['m_payment_id'],
            'parameter_details'=>json_encode($data),
            'gateway_name'=>'payfast',
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
		'transaction_id' => $data['m_payment_id'],
                'note' => "Online course fees processing Payfast Payment ID: " . $data['m_payment_id'],
                'payment_mode' => 'Payfast',
            ); 
            $this->course_payment_model->add_processingpayment($payment_data);
            $this->session->set_userdata("course_amount", $params);
            // If in testing mode make use of either sandbox.payfast.co.za or www.payfast.co.za
            $testingMode = false;
            $pfHost = $testingMode ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
            $htmlForm = '<form action="https://'.$pfHost.'/eng/process" method="post" name="pay_now">';
            foreach($data as $name=> $value)
            {
            $htmlForm .= '<input name="'.$name.'" type="hidden" value=\''.$value.'\' />';
            }
            $htmlForm .= '</form>';
            $data['htmlForm']= $htmlForm;
            $this->load->view('user/studentcourse/online_course/payfast/pay', $data);
        }
    }

    public  function generateSignature($data, $passPhrase = null) {
        // Create parameter string
        $pfOutput = '';
        foreach( $data as $key => $val ) {
            if($val !== '') {
                $pfOutput .= $key .'='. urlencode( trim( $val ) ) .'&';
            }
        } 
        // Remove last ampersand
        $getString = substr( $pfOutput, 0, -1 );
        if( $passPhrase !== null ) {
            $getString .= '&passphrase='. urlencode( trim( $passPhrase ) );
        }
        return md5( $getString );
    }
    
    /*
    This is used to show success page status
    */
    public function success() {

        $params = $this->session->userdata('course_amount');
        $parameter_data=$this->gateway_ins_model->get_gateway_ins($params['transaction_id'],'payfast');

        $payment_id = $params['transaction_id'];

        if($parameter_data['payment_status']=='success'){
            
            if($params['courseid'] !='') {

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
                    'note' => "Online course fees deposit through Payfast Txn ID: " . $payment_id,
                    'payment_mode' => 'Payfast',
                    'processing_charge_type'=>$params['processing_charge_type'],
                'processing_charge_amount'=>$params['gateway_processing_charge'],
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
                $this->course_payment_model->deleteBygateway_ins_id($parameter_data['id']); 
                redirect(base_url('students/online_course/course_payment/paymentfailed'));
            }else{
                redirect(base_url("students/online_course/course_payment/paymentprocessing"));
            }
        }
    }
    
	public function cancel(){
		$params = $this->session->userdata('course_amount');

        $parameter_data=$this->gateway_ins_model->get_gateway_ins($params['transaction_id'],'payfast'); 
         
        $this->course_payment_model->deleteBygateway_ins_id($parameter_data['id']); 
        redirect(base_url('students/online_course/course_payment/paymentfailed')); 
	} 
    
    public function guest() {
        $data['params'] = $this->session->userdata('cart_data');
        $data['setting'] = $this->setting;
        $data['error'] =array();
        
        $data['fee_processing']=$this->course_payment_model->check_gestpayment_status($data['params'][0]['id'], $data['params'][0]['guest_id'],'payfast');
        $this->load->view('user/studentcourse/online_course/payfast/guest_course/index', $data);
    } 

    public function guestpay() {
        $this->form_validation->set_rules('phone', $this->lang->line('phone'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');

        $params = $this->session->userdata('cart_data');
        $data['params'] = $params;
        $data['setting'] = $this->setting;

        if ($this->form_validation->run() == false) {
            $this->load->view('user/studentcourse/online_course/payfast/guest_course/index', $data);
        } else { 
            
            $cartTotal = $this->input->post('total_cart_amount');// This amount needs to be sourced from your application
            $data = array(
            'merchant_id' => $this->api_config->api_publishable_key,
            'merchant_key' => $this->api_config->api_secret_key,
            'return_url' => base_url().'students/online_course/payfast/guestsuccess',
            'cancel_url' => base_url().'students/online_course/payfast/cancel',
            'notify_url' => base_url().'gateway_ins/payfast',
            'name_first' => $params[0]['guest_name'],
            'name_last'  => 'name_last',
            'email_address'=> $_POST['email'],
            'm_payment_id' => time().rand(99,999), //Unique payment ID to pass through to notify_url
            'amount' => number_format( sprintf( '%.2f', $cartTotal ), 2, '.', '' ),
            'item_name' => 'fees#'.rand(99,999),
            );
           
            $signature = $this->generateSignature($data,$this->api_config->salt);
            $data['signature'] = $signature;
           
            $transaction_id=$data['m_payment_id'];
            
            $ins_data=array(
            'unique_id'=>$data['m_payment_id'],
            'parameter_details'=>json_encode($data),
            'gateway_name'=>'payfast',
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
                    'note' => "Online course fees processing Payfast Payment ID: " . $transaction_id,
                    'payment_mode' => 'Payfast',
                    'gateway_ins_id' => $gateway_ins_id,
                    'processing_charge_type'=>$cart_data_value['processing_charge_type'],
                    'processing_charge_amount'=>$cart_data_value['gateway_processing_charge'],
                );
                $this->course_payment_model->add_processingpayment($payment_data);
            } 
           
            $this->session->set_userdata("course_amount", $params);
            $this->session->set_userdata("transaction_id", $transaction_id);

            // If in testing mode make use of either sandbox.payfast.co.za or www.payfast.co.za
            $testingMode = false;
            $pfHost = $testingMode ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
            $htmlForm = '<form action="https://'.$pfHost.'/eng/process" method="post" name="pay_now">';
            foreach($data as $name=> $value)
            {
            $htmlForm .= '<input name="'.$name.'" type="hidden" value=\''.$value.'\' />';
            }
            $htmlForm .= '</form>';
            $data['htmlForm']= $htmlForm;
            $this->load->view('user/studentcourse/online_course/payfast/pay', $data);
        }
    }

    public  function guestgenerateSignature($data, $passPhrase = null) 
    {
        // Create parameter string
        $pfOutput = '';
        foreach( $data as $key => $val ) {
            if($val !== '') {
                $pfOutput .= $key .'='. urlencode( trim( $val ) ) .'&';
            }
        } 
        // Remove last ampersand
        $getString = substr( $pfOutput, 0, -1 );
        if( $passPhrase !== null ) {
            $getString .= '&passphrase='. urlencode( trim( $passPhrase ) );
        }
        return md5( $getString );
    }
    
    /*
    This is used to show success page status
    */
    public function guestsuccess() {

        $cart_data = $this->session->userdata('cart_data');
        $transaction_id = $this->session->userdata('transaction_id');

        $parameter_data=$this->gateway_ins_model->get_gateway_ins($transaction_id,'payfast');
            
        if($parameter_data['payment_status']=='success'){  

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
                    'note' => "Online course fees deposit through Payfast Txn ID: " . $transaction_id,
                    'payment_mode' => 'Payfast',
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