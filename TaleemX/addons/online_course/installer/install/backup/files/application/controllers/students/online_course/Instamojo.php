<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Instamojo extends Student_Controller {

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
        $data['error'] =array();
        $this->load->view('user/studentcourse/online_course/instamojo/index', $data);
    }

    /*
    This is for payment gateway functionality
    */
    public function insta_pay() 
    {
        $this->form_validation->set_rules('phone', $this->lang->line('phone'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');

        $params = $this->session->userdata('course_amount');
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $data['error']=array();
        if ($this->form_validation->run() == false) {
            $this->load->view('user/studentcourse/online_course/instamojo/index', $data);
        } else { 
            $instadetails = $this->paymentsetting_model->getActiveMethod();
            $insta_apikey = $instadetails->api_secret_key;
            $insta_authtoken = $instadetails->api_publishable_key;
            $params = $this->session->userdata('course_amount');
            $amount = number_format((float)convertBaseAmountCurrencyFormat($params['total_amount']), 2, '.', '');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.instamojo.com/api/1.1/payment-requests/'); // for live https://www.instamojo.com/api/1.1/payment-requests/
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Api-Key:$insta_apikey",
                "X-Auth-Token:$insta_authtoken"));
            $payload = Array(
                'purpose' => 'Online Course Fess',
                'amount' => $amount,
                'phone' => $_POST['phone'],
                'buyer_name' => $params['name'],
                'redirect_url' => base_url() . 'students/online_course/instamojo/success',
                'send_email' => false,
                'webhook' => base_url() . 'webhooks/insta_webhook',
                'send_sms' => false,
                'email' => $_POST['email'],
                'allow_repeated_payments' => false
            );
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            $response = curl_exec($ch);
            curl_close($ch);
            $json = json_decode($response, true);

            if ($json['success']) {
                $url = $json['payment_request']['longurl'];
                header("Location: $url");
            } else {
                $json = json_decode($response, true);
                $data['error'] = $json['message'];
                $this->load->view('user/studentcourse/online_course/instamojo/index', $data);
            }
        }
    }
    
    /*
    This is used to show success page status
    */
    public function success() {
        if ($_GET['payment_status'] == 'Credit') {
            $params = $this->session->userdata('course_amount');
            $payment_id = $_GET['payment_id'];
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
                'note' => "Online course fees deposit through Instamojo Txn ID: " . $payment_id,
                'payment_mode' => 'Instamojo',
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
            redirect(base_url("students/online_course/course_payment/paymentfailed"));
        }
    }

    public function guest() {
        $data['params'] = $this->session->userdata('cart_data');
        $data['setting'] = $this->setting;
        $data['error'] =array();
        $this->load->view('user/studentcourse/online_course/instamojo/guest_course/index', $data);
    }

    public function guestinsta_pay() 
    {
        $this->form_validation->set_rules('phone', $this->lang->line('phone'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');

        $params = $this->session->userdata('cart_data');
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $data['error']="";
        if ($this->form_validation->run() == false) {
            $this->load->view('user/studentcourse/online_course/instamojo/guest_course/index', $data);
        } else { 
            $instadetails = $this->paymentsetting_model->getActiveMethod();
            $insta_apikey = $instadetails->api_secret_key;
            $insta_authtoken = $instadetails->api_publishable_key;
            
            $params = $this->session->userdata('cart_data');
            $data['name'] = $params[0]['guest_name'];

            $amount = number_format((float)convertBaseAmountCurrencyFormat($this->input->post('total_cart_amount')), 2, '.', '');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.instamojo.com/api/1.1/payment-requests/'); // for live https://www.instamojo.com/api/1.1/payment-requests/
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Api-Key:$insta_apikey",
                "X-Auth-Token:$insta_authtoken"));
            $payload = Array(
                'purpose' => 'Online Course Fess',
                'amount' => $amount,
                'phone' => $_POST['phone'],
                'buyer_name' => $data['name'],
                'redirect_url' => base_url() . 'students/online_course/instamojo/guestsuccess',
                'send_email' => false,
                'webhook' => base_url() . 'webhooks/insta_webhook',
                'send_sms' => false,
                'email' => $_POST['email'],
                'allow_repeated_payments' => false
            );
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            $response = curl_exec($ch);
            curl_close($ch);
            $json = json_decode($response, true);

            if ($json['success']) {
                $url = $json['payment_request']['longurl'];
                header("Location: $url");
            } else {
                $json = json_decode($response, true);
                $data['error'] = $json['message'];
                $this->load->view('user/studentcourse/online_course/instamojo/guest_course/index', $data);
            }
        }
    }

    public function guestsuccess() {
        if ($_GET['payment_status'] == 'Credit') {
            $cart_data = $this->session->userdata('cart_data');
            $payment_id = $_GET['payment_id'];

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
                    'note' => "Online course fees deposit through Instamojo Txn ID: " . $payment_id,
                    'payment_mode' => 'Instamojo',
                    'processing_charge_type'=>$cart_data_value['processing_charge_type'],
                    'processing_charge_amount'=>$cart_data_value['gateway_processing_charge'],
                );
                $this->course_payment_model->add($sender_details);
                
                $sender_details = array('email'=>$cart_data_value['email'], 'courseid'=> $cart_data_value['id'],'class' => null,  'class_section_id'=> null, 'section'=> null, 'title' => $cart_data_value['name'], 'price' => $cart_data_value['price'], 'discount' => $cart_data_value['discount'], 'assign_teacher' => $cart_data_value['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));

                $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
            }

            redirect(base_url("students/online_course/course_payment/paymentsuccess"));            
        
        } else {
            redirect(base_url("students/online_course/course_payment/paymentfailed"));
        }
    }
}