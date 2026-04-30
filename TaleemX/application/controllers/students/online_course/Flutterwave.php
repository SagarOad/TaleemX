<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Flutterwave extends Student_Controller {

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
        $this->load->view('user/studentcourse/online_course/flutter_wave/index', $data);
    }

    /*
    This is for payment gateway functionality
    */
    public function pay() {
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');

        $params = $this->session->userdata('course_amount');
        $data['params'] = $params;
        $data['setting'] = $this->setting;

        if ($this->form_validation->run() == false) {
            $this->load->view('user/studentcourse/online_course/flutter_wave/index', $data);
        } else { 
            $details = $this->paymentsetting_model->getActiveMethod();
            $api_secret_key = $details->api_secret_key;
            $api_publishable_key = $details->api_publishable_key;
            $amount = convertBaseAmountCurrencyFormat($params['total_amount']);
            $curl = curl_init();
            $customer_email = $_POST['email'];
            $currency = $params['currency_name'];
            
            $txref = "rave" . uniqid(); // ensure you generate unique references per transaction.
            // get your public key from the dashboard.
            $PBFPubKey = $api_publishable_key; 
            $redirect_url = base_url() . 'students/online_course/flutterwave/success'; // Set your own redirect URL

            curl_setopt_array($curl, array(
              CURLOPT_URL => "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/hosted/pay",
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_POSTFIELDS => json_encode([
                'amount'=>$amount,
                'customer_email'=>$customer_email,
                'currency'=>$currency,
                'txref'=>$txref,
                'PBFPubKey'=>$PBFPubKey,
                'redirect_url'=>$redirect_url,
              ]),
              CURLOPT_HTTPHEADER => [
                "content-type: application/json",
                "cache-control: no-cache"
              ],
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            if($err){
              // there was an error contacting the rave API
              die('Curl returned error: ' . $err);
            }
            $transaction = json_decode($response);
            if(!$transaction->data && !$transaction->data->link){
              // there was an error from the API
              print_r('API returned error: ' . $transaction->message);
            }else {
           redirect(base_url("students/online_course/course_payment/paymentfailed"));
        }
            // redirect to page so User can pay
            header('Location: ' . $transaction->data->link);
        }
    }

    /*
    This is used to show success page status
    */
    public function success() {
        $details = $this->paymentsetting_model->getActiveMethod();
		$api_secret_key = $details->api_secret_key;
        $params = $this->session->userdata('course_amount');
       
       if(isset($_GET['cancelled']) && $_GET['cancelled']!=true){
            if (isset($_GET['txref'])) {
        $ref = $_GET['txref'];

        $query = array(
            "SECKEY" => $api_secret_key,
            "txref" => $ref
        );

        $data_string = json_encode($query);    
        $ch = curl_init('https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify');                                                                      
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                              
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        curl_close($ch);
        $resp = json_decode($response, true);

            $paymentStatus = $resp['data']['status'];
            $chargeResponsecode = $resp['data']['chargecode'];
            $chargeAmount = $resp['data']['amount'];
            $chargeCurrency = $resp['data']['currency'];
            $txid= $resp['data']['txref'];
            if (($chargeResponsecode == "00" || $chargeResponsecode == "0") && ($chargeAmount == $amount)  && ($chargeCurrency == $currency)) {
              // transaction was successful...
              // please check other things like whether you already gave value for this ref
              // if the email matches the customer who owns the product etc
              //Give Value and return to Success page
                $payment_id = $txid;
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
                    'note' => "Online course fees deposit through Flutterwave Txn ID: " . $payment_id,
                    'payment_mode' => 'Flutterwave',
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
       }else{
        redirect(base_url("students/online_course/course_payment/paymentfailed"));
       }
    }

    public function guest() 
    {
        $data['params'] = $this->session->userdata('cart_data');
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/flutter_wave/guest_course/index', $data);
    }

    public function guestpay() {
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');

        $params = $this->session->userdata('cart_data');
        $data['params'] = $params;
        $data['setting'] = $this->setting;

        if ($this->form_validation->run() == false) {
            $this->load->view('user/studentcourse/online_course/flutter_wave/guest_course/index', $data);
        } else { 
            $details = $this->paymentsetting_model->getActiveMethod();
            $api_secret_key = $details->api_secret_key;
            $api_publishable_key = $details->api_publishable_key;
            $amount = number_format((float)$this->input->post('total_cart_amount'), 2, '.', '');
            $curl = curl_init();
            $customer_email = $_POST['email'];
           $currency=$this->currency_name;
            
            $txref = "rave" . uniqid(); // ensure you generate unique references per transaction.
            // get your public key from the dashboard.
            $PBFPubKey = $api_publishable_key; 
            $redirect_url = base_url() . 'students/online_course/flutterwave/guestsuccess'; // Set your own redirect URL

            curl_setopt_array($curl, array(
              CURLOPT_URL => "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/hosted/pay",
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_POSTFIELDS => json_encode([
                'amount'=>$amount,
                'customer_email'=>$customer_email,
                'currency'=>$currency,
                'txref'=>$txref,
                'PBFPubKey'=>$PBFPubKey,
                'redirect_url'=>$redirect_url,
              ]),
              CURLOPT_HTTPHEADER => [
                "content-type: application/json",
                "cache-control: no-cache"
              ],
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            if($err){
              // there was an error contacting the rave API
              die('Curl returned error: ' . $err);
            }
            $transaction = json_decode($response);
            if(!$transaction->data && !$transaction->data->link){
              // there was an error from the API
              print_r('API returned error: ' . $transaction->message);
            }else {
           redirect(base_url("students/online_course/course_payment/paymentfailed"));
        }
            // redirect to page so User can pay
            header('Location: ' . $transaction->data->link);
        }
    }

    /*
    This is used to show success page status
    */
    public function guestsuccess() {
        $details = $this->paymentsetting_model->getActiveMethod();
        $api_secret_key = $details->api_secret_key;
        $cart_data = $this->session->userdata('cart_data');
       
       if(isset($_GET['cancelled']) && $_GET['cancelled']!=true){
            if (isset($_GET['txref'])) {
        $ref = $_GET['txref'];

        $query = array(
            "SECKEY" => $api_secret_key,
            "txref" => $ref
        );

        $data_string = json_encode($query);    
        $ch = curl_init('https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify');                                                                      
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                              
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        curl_close($ch);
        $resp = json_decode($response, true);

            $paymentStatus = $resp['data']['status'];
            $chargeResponsecode = $resp['data']['chargecode'];
            $chargeAmount = $resp['data']['amount'];
            $chargeCurrency = $resp['data']['currency'];
            $txid= $resp['data']['txref'];
            if (($chargeResponsecode == "00" || $chargeResponsecode == "0") && ($chargeAmount == $amount)  && ($chargeCurrency == $currency)) {
              // transaction was successful...
              // please check other things like whether you already gave value for this ref
              // if the email matches the customer who owns the product etc
              //Give Value and return to Success page
                $payment_id = $txid;

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
                        'note' => "Online course fees deposit through Flutterwave Txn ID: " . $payment_id,
                        'payment_mode' => 'Flutterwave',
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
       }else{
        redirect(base_url("students/online_course/course_payment/paymentfailed"));
       }
    }
}