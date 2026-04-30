<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Cashfree extends Student_Controller {
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
        $this->load->view('user/studentcourse/online_course/cashfree/index', $data);
    }

    public function pay() {

        $params = $this->session->userdata('course_amount');
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $this->form_validation->set_rules('phone', $this->lang->line('phone'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {

            $data['api_error'] = $data['api_error'] = array();
            $this->load->view('user/studentcourse/online_course/cashfree/index', $data);
        } else {
           
            $apidetails = $this->paymentsetting_model->getActiveMethod();
            $data['name'] = $params['name'];
            $amount = $params['total_amount'];
            $api=' '.$apidetails->api_publishable_key;
            $currency=$params['currency_name'];
            $customer_id="Student_id_".$params['student_id'];
            $order_id="order_".time().mt_rand(100,999);
            $redirectUrl=base_url()."students/online_course/cashfree/success?order_id={order_id}&order_token={order_token}";

            $my_array=array(
            "order_id"=> $order_id,
            "order_amount"=> convertBaseAmountCurrencyFormat($amount),
            "order_currency"=> $currency,
            "customer_details"=> array(
            "customer_id"=> $customer_id,
            "customer_name"=> $data['name'],
            "customer_email"=> $_POST['email'],
            "customer_phone"=> $_POST['phone'],
            ),
            "order_meta"=> array(
            "return_url"=> $redirectUrl,
            "notify_url"=> base_url() .'webhooks/cashfree',
            "payment_methods"=> ""
            )
        );
        $new_arrya=(object)$my_array;
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://api.cashfree.com/pg/orders');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($new_arrya));

            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'X-Api-Version: 2021-05-21';
            $headers[] = 'X-Client-Id: '.$apidetails->api_publishable_key;
            $headers[] = 'X-Client-Secret: '.$apidetails->api_secret_key;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
            }
            curl_close($ch);
            $json=json_decode($result);

            if (isset($json->order_status) && $json->order_status="ACTIVE") {
                $url = $json->payment_link;
               
                header("Location: $url");
            } else {
                $data['api_error'] = $data['api_error'] = array();
                $data['api_error'] = $json->message;
                $this->load->view('user/studentcourse/online_course/cashfree/index', $data);
            }
        }
    }

    public function success() {
        $apidetails = $this->paymentsetting_model->getActiveMethod();
        $params = $this->session->userdata('course_amount');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.cashfree.com/pg/orders/'.$_GET['order_id']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'X-Api-Version: 2021-05-21';
        $headers[] = 'X-Client-Id: '.$apidetails->api_publishable_key;
        $headers[] = 'X-Client-Secret: '.$apidetails->api_secret_key;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $payment_data=json_decode($result);

        if (isset($payment_data->order_status) && $payment_data->order_status=="PAID") {
            $payment_id = $_GET['order_id']; 
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
                'note' => "Online course fees deposit through Cashfree Txn ID: " . $payment_id,
                'payment_mode' => 'Cashfree',
                'processing_charge_type'=>$params['processing_charge_type'],
                'processing_charge_amount'=>$params['gateway_processing_charge'],
            );
            $this->course_payment_model->add($payment_data);
        if(!empty($params['courseid'])) {

                $sender_details = array('email'=>$params['email'], 'courseid'=>$params['courseid'],'class' => $params['class'],  'class_section_id'=> $params['class_sections'], 'section'=> $params['section'], 'title' => $params['course_name'], 'price' => $params['total_amount'], 'discount' => $params['discount'], 'assign_teacher' => $params['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));  

               if($params['student_id']!=""){
                    $this->course_mail_sms->purchasemail('online_course_purchase', $sender_details);
                }elseif($params['guest_id']!=""){
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
 
        $params = $this->session->userdata('cart_data');
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/cashfree/guest_course/index', $data);
    }

    public function guestpay() {
 
        $params = $this->session->userdata('cart_data');
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $this->form_validation->set_rules('phone', $this->lang->line('phone'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');
        if ($this->form_validation->run() == false) {

            $data['api_error'] = $data['api_error'] = array();
            $this->load->view('user/studentcourse/online_course/cashfree/guest_course/index', $data);
        } else {

            $apidetails = $this->paymentsetting_model->getActiveMethod();
            $data['name'] = $params[0]['guest_name'];
            $amount = $this->input->post('total_cart_amount');
            $api=' '.$apidetails->api_publishable_key;
            $currency=$this->currency_name;
            $customer_id="guest_id_".$params[0]['guest_id'];
            $order_id="order_".time().mt_rand(100,999);
            $redirectUrl=base_url()."students/online_course/cashfree/guestsuccess?order_id={order_id}&order_token={order_token}";

            $my_array=array(
            "order_id"=> $order_id,
            "order_amount"=> convertBaseAmountCurrencyFormat($amount),
            "order_currency"=> $currency,
            "customer_details"=> array(
            "customer_id"=> $customer_id,
            "customer_name"=> $data['name'],
            "customer_email"=> $_POST['email'],
            "customer_phone"=> $_POST['phone'],
            ),
            "order_meta"=> array(
            "return_url"=> $redirectUrl,
            "notify_url"=> base_url() .'webhooks/cashfree',
            "payment_methods"=> ""
            )
        );
        $new_arrya=(object)$my_array;
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://api.cashfree.com/pg/orders');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($new_arrya));

            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'X-Api-Version: 2021-05-21';
            $headers[] = 'X-Client-Id: '.$apidetails->api_publishable_key;
            $headers[] = 'X-Client-Secret: '.$apidetails->api_secret_key;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
            }
            curl_close($ch);
            $json=json_decode($result);

            if (isset($json->order_status) && $json->order_status="ACTIVE") {
                $url = $json->payment_link;
               
                header("Location: $url");
            } else {
                $data['api_error'] = $data['api_error'] = array();
                $data['api_error'] = $json->message;
                $this->load->view('user/studentcourse/online_course/cashfree/guest_course/index', $data);
            }
        }
    }

    public function guestsuccess() {
        $apidetails = $this->paymentsetting_model->getActiveMethod();
        $cart_data = $this->session->userdata('cart_data');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.cashfree.com/pg/orders/'.$_GET['order_id']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'X-Api-Version: 2021-05-21';
        $headers[] = 'X-Client-Id: '.$apidetails->api_publishable_key;
        $headers[] = 'X-Client-Secret: '.$apidetails->api_secret_key;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $payment_data=json_decode($result);

        if (isset($payment_data->order_status) && $payment_data->order_status=="PAID") {
            $payment_id = $_GET['order_id'];

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
                    'note' => "Online course fees deposit through Cashfree Txn ID: " . $payment_id,
                    'payment_mode' => 'Cashfree',
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