<?php
 if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Ipayafrica extends Student_Controller
{
    public $payment_method = array();
    public $pay_method     = array();
    public $patient_data;
    public $setting;

    public function __construct()
    {
        parent::__construct();
		$this->load->model(array('course_model','coursesection_model','courselesson_model','studentcourse_model','coursequiz_model','course_payment_model','courseofflinepayment_model','coursereport_model','currency_model'));
        $this->pay_method   = $this->paymentsetting_model->getActiveMethod();
        $this->setting        = $this->setting_model->get();
        $this->load->library('course_mail_sms');
        $this->currency_name= $this->currency_model->get($this->session->userdata('student')['currency'])->short_name;
    }

    /*
    This is used to show payment detail page
    */
    public function index(){
        $data['params'] = $this->session->userdata('course_amount');
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/ipay_africa/index', $data);
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
        $this->load->view('user/studentcourse/online_course/ipay_africa/index', $data);
    } else {
        $instadetails = $this->paymentsetting_model->getActiveMethod();
        $insta_apikey = $instadetails->api_secret_key;
        $insta_authtoken = $instadetails->api_publishable_key;

        $fields = array("live"=> "1",
            "oid"=> uniqid(),
            "inv"=> time(),
            "ttl"=> convertBaseAmountCurrencyFormat($params['total_amount']),
            "tel"=> $_POST['phone'],
            "eml"=> $_POST['email'],
            "vid"=> ($this->pay_method->api_publishable_key),
            "curr"=> $params['currency_name'],
            "p1"=> "airtel",
            "p2"=> "",
            "p3"=> "",
            "p4"=> $params['total_amount'],
            "cbk"=> base_url().'students/online_course/ipayafrica/success',
            "cst"=> "1",
            "crl"=> "2"
            );
         
        $datastring =  $fields['live'].$fields['oid'].$fields['inv'].$fields['ttl'].$fields['tel'].$fields['eml'].$fields['vid'].$fields['curr'].$fields['p1'].$fields['p2'].$fields['p3'].$fields['p4'].$fields['cbk'].$fields['cst'].$fields['crl'];

        $hashkey =($this->pay_method->api_secret_key);
        $generated_hash = hash_hmac('sha1',$datastring , $hashkey);
        $data['fields']=$fields;
        $data['generated_hash']=$generated_hash;
        $this->load->view('user/studentcourse/online_course/ipay_africa/pay', $data);
    }
}

    /*
    This is used to show success page status
    */
    public function success(){
        if(!empty($_GET['status'])){
            $params = $this->session->userdata('course_amount');
            $payment_id = $_GET['txncd'];;
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
                    'note' => "Online course fees deposit through iPayAfrica Txn ID: " . $payment_id,
                    'payment_mode' => 'iPayAfrica',
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
        }else{
            redirect(base_url("students/online_course/course_payment/paymentfailed"));
        }
    }

    public function guest(){
        $data['params'] = $this->session->userdata('cart_data');
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/ipay_africa/guest_course/index', $data);
    }

    public function guestpay() {
    $this->form_validation->set_rules('phone', $this->lang->line('phone'), 'trim|required|xss_clean');
    $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');

    $params = $this->session->userdata('cart_data');
    $data['params'] = $params;
    $data['setting'] = $this->setting;

    if ($this->form_validation->run() == false) {
        $this->load->view('user/studentcourse/online_course/ipay_africa/guest_course/index', $data);
    } else {
        $instadetails = $this->paymentsetting_model->getActiveMethod();
        $insta_apikey = $instadetails->api_secret_key;
        $insta_authtoken = $instadetails->api_publishable_key;

        $fields = array("live"=> "1",
            "oid"=> uniqid(),
            "inv"=> time(),
            "ttl"=> number_format((float)$this->input->post('total_cart_amount'), 2, '.', ''),
            "tel"=> $_POST['phone'],
            "eml"=> $_POST['email'],
            "vid"=> ($this->pay_method->api_publishable_key),
            "curr"=> $this->currency_name,
            "p1"=> "airtel",
            "p2"=> "",
            "p3"=> "",
            "p4"=> "",
            "cbk"=> base_url().'students/online_course/ipayafrica/guestsuccess',
            "cst"=> "1",
            "crl"=> "2"
            );
         
        $datastring =  $fields['live'].$fields['oid'].$fields['inv'].$fields['ttl'].$fields['tel'].$fields['eml'].$fields['vid'].$fields['curr'].$fields['p1'].$fields['p2'].$fields['p3'].$fields['p4'].$fields['cbk'].$fields['cst'].$fields['crl'];

        $hashkey =($this->pay_method->api_secret_key);
        $generated_hash = hash_hmac('sha1',$datastring , $hashkey);
        $data['fields']=$fields;
        $data['generated_hash']=$generated_hash;
        $this->load->view('user/studentcourse/online_course/ipay_africa/pay', $data);
    }
}

    /*
    This is used to show success page status
    */
    public function guestsuccess(){
        if(!empty($_GET['status'])){
            $cart_data = $this->session->userdata('cart_data');
            $payment_id = $_GET['txncd'];

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
                    'note' => "Online course fees deposit through iPayAfrica Txn ID: " . $payment_id,
                    'payment_mode' => 'iPayAfrica',
                    'processing_charge_type'=>$cart_data_value['processing_charge_type'],
                    'processing_charge_amount'=>$cart_data_value['gateway_processing_charge'],
                );
                $this->course_payment_model->add($sender_details);
                $sender_details = array('email'=>$cart_data_value['email'], 'courseid'=> $cart_data_value['id'],'class' => null,  'class_section_id'=> null, 'section'=> null, 'title' => $cart_data_value['name'], 'price' => $cart_data_value['price'], 'discount' => $cart_data_value['discount'], 'assign_teacher' => $cart_data_value['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));

                $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
            }

            redirect(base_url("students/online_course/course_payment/paymentsuccess"));

          
        }else{
            redirect(base_url("students/online_course/course_payment/paymentfailed"));
        }
    }

}