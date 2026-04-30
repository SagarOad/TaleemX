<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Razorpay extends Student_Controller {

    public $api_config = "";

    function __construct() {
        parent::__construct();
		$this->load->model(array('course_model','coursesection_model','courselesson_model','studentcourse_model','coursequiz_model','course_payment_model','courseofflinepayment_model','coursereport_model','currency_model'));
        $this->api_config = $this->paymentsetting_model->getActiveMethod();
        $this->setting = $this->setting_model->get();
        $this->load->library('course_mail_sms');
        $this->currency_name= $this->currency_model->get($this->session->userdata('student')['currency'])->short_name;
    }

    /*
    This is used to show payment detail page
    */
    public function index() {
        $params = $this->session->userdata('course_amount');
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $data['merchant_order_id'] = time() . "01";
        $data['txnid'] = time() . "02";
        $data['return_url'] = site_url() . 'students/online_course/razorpay/callback';
        $data['total'] = convertBaseAmountCurrencyFormat($params['total_amount']) * 100;
        $data['key_id'] = $this->api_config->api_publishable_key;
                $ch = curl_init();
        $order_data=array('amount'=>$data['total'],'currency'=>$this->currency_name,'receipt'=>'R#'.$data['merchant_order_id']);
        curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_data));
        curl_setopt($ch, CURLOPT_USERPWD, $this->api_config->api_publishable_key . ':' . $this->api_config->api_secret_key);

        $headers = array(); 
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);die;
        }
        curl_close($ch);

     $results=(array)json_decode($result);
if(array_key_exists('error', $results)){
$order_id="";
}else{
   $order_id=json_decode($result)->id; 
}

        $data['order_id']=$order_id;
       
        $this->load->view('user/studentcourse/online_course/razorpay/index', $data);
    }

    /*
    This is used to show payment detail page
    */
    public function callback() {
        $params = $this->session->userdata('course_amount');
        $payment_id = $_POST['razorpay_payment_id'];
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
            'note' => "Online course fees deposit through Razorpay Txn ID: " . $payment_id,
            'payment_mode' => 'Razorpay',
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
    public function success() {
        $this->load->view('user/studentcourse/paymentsuccess');
    }

    public function guest() {
        $params = $this->session->userdata('cart_data');
        
        $total_amount = 0;
        foreach ($params as $key => $value) {
            $total_amount += $value['price'];
        }
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $data['merchant_order_id'] = time() . "01";
        $data['txnid'] = time() . "02";
        $data['return_url'] = site_url() . 'students/online_course/razorpay/guestcallback';
        $data['total'] = round(convertBaseAmountCurrencyFormat($this->getIndianCurrency($total_amount)));
        $data['key_id'] = $this->api_config->api_publishable_key;
        $data['currency_name']=$this->currency_name;
       
                        $ch = curl_init();
        $order_data=array('amount'=>$data['total'],'currency'=>$this->currency_name,'receipt'=>'R#'.$data['merchant_order_id']);
        curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_data));
        curl_setopt($ch, CURLOPT_USERPWD, $this->api_config->api_publishable_key . ':' . $this->api_config->api_secret_key);

        $headers = array(); 
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);die;
        }
        curl_close($ch);

        if(array_key_exists('error', json_decode($result,true))){
        $order_id="";
        }else{
           $order_id=json_decode($result)->id; 
        }

        $data['order_id']=$order_id;
        $this->load->view('user/studentcourse/online_course/razorpay/guest_course/index', $data);
    } 
function getIndianCurrency(float $number)
    {
        $no = floor($number);
        $decimal = ($number - $no) * 100;
        $decimal_part = $decimal;
        return ($no*100)+$decimal;
        
    }
    public function guestcallback() {
        $cart_data = $this->session->userdata('cart_data');
        $payment_id = $_POST['razorpay_payment_id'];

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
                'note' => "Online course fees deposit through Razorpay Txn ID: " . $payment_id,
                'payment_mode' => 'Razorpay',
                'processing_charge_type'=>$cart_data_value['processing_charge_type'],
                    'processing_charge_amount'=>$cart_data_value['gateway_processing_charge'],
            );
            $this->course_payment_model->add($payment_data);
            
            $sender_details = array('email'=>$cart_data_value['email'], 'courseid'=> $cart_data_value['id'],'class' => null,  'class_section_id'=> null, 'section'=> null, 'title' => $cart_data_value['name'], 'price' => $cart_data_value['price'], 'discount' => $cart_data_value['discount'], 'assign_teacher' => $cart_data_value['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));

                $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
        } 
        echo 1;
    }

    public function guestsuccess() {
       redirect(base_url("students/online_course/course_payment/paymentsuccess"));
    }
}