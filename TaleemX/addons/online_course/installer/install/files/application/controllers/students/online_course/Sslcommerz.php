<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Sslcommerz extends Student_Controller {

    public $api_config = "";

    public function __construct() {
        parent::__construct();
		$this->load->model(array('course_model','coursesection_model','courselesson_model','studentcourse_model','coursequiz_model','course_payment_model','courseofflinepayment_model','coursereport_model','currency_model'));
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
        $this->load->view('user/studentcourse/online_course/sslcommerz/index', $data);
    }

    /*
    This is for payment gateway functionality
    */
    public function pay()
    {
        $params       = $this->session->userdata('course_amount');
        $sslcommerzdetails = $this->paymentsetting_model->getActiveMethod();

        $requestData        = array();
        $CURLOPT_POSTFIELDS = array(
            'store_id'         => $sslcommerzdetails->api_publishable_key,
            'store_passwd'     => $sslcommerzdetails->api_password,
            'total_amount'     => number_format((float) convertBaseAmountCurrencyFormat($params['total_amount']), 2, '.', ''),
            'currency'         => $this->setting[0]['currency'],
            'tran_id'          => abs(crc32(uniqid())),
            'success_url'      => base_url() . 'students/online_course/sslcommerz/success',
            'fail_url'         => base_url() . 'students/online_course/sslcommerz/fail',
            'cancel_url'       => base_url() . 'students/online_course/sslcommerz/cancel',
            'cus_name'         => $params['name'],
            'cus_email'        => !empty($params['email']) ? $params['email'] : "example@email.com",
            'cus_add1'         => !empty($params['address']) ? $params['address'] : "Dhaka",
            'cus_phone'        => !empty($params['contact_no']) ? $params['contact_no'] : "01711111111",
            'cus_city'         => '',
            'cus_country'      => '',
            'multi_card_name'  => 'mastercard,visacard,amexcard,internetbank,mobilebank,othercard ',
            'shipping_method'  => 'NO',
            'product_name'     => 'test',
            'product_category' => 'Electronic',
            'product_profile'  => 'general',
        );
        $string = "";
        foreach ($CURLOPT_POSTFIELDS as $key => $value) {
            $string .= $key . '=' . $value . "&";
            if ($key == 'product_profile') {
                $string .= $key . '=' . $value;
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://securepay.sslcommerz.com/gwprocess/v4/api.php');
//https://securepay.sslcommerz.com/gwprocess/v4/api.php
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "$string");

        $headers   = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $response = json_decode($result);

        header("Location: $response->GatewayPageURL");

    }

    /*
    This is used to show success page status
    */
    public function success()
    {

        if ($_POST['status'] == 'VALID') {
            $params = $this->session->userdata('course_amount');

            $payment_id = $_POST['val_id'];

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
                'note' => "Online course fees deposit through Sslcommerz Txn ID: " . $payment_id,
                'payment_mode' => 'Sslcommerz',
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

    /*
    This is used to show cancel page status
    */
    public function fail()
    {
      redirect(base_url("students/online_course/course_payment/paymentfailed"));
    }

    /*
    This is used to show cancel page status
    */
    public function cancel()
    {
      redirect(base_url("students/online_course/course_payment/paymentfailed"));
    }

    public function guest() {

        $data['params'] = $this->session->userdata('cart_data');
        $data['setting'] = $this->setting;
         $data['error'] =array();
        $this->load->view('user/studentcourse/online_course/sslcommerz/guest_course/index', $data);
    }

    public function guestpay()
    {
        $params       = $this->session->userdata('cart_data');
        $sslcommerzdetails = $this->paymentsetting_model->getActiveMethod();

        $requestData        = array();
        $CURLOPT_POSTFIELDS = array(
            'store_id'         => $sslcommerzdetails->api_publishable_key,
            'store_passwd'     => $sslcommerzdetails->api_password,
            'total_amount'     => number_format((float) ($this->input->post('total_cart_amount')), 2, '.', ''),
            'currency'         => $params[0]['currency_name'],
            'tran_id'          => abs(crc32(uniqid())),
            'success_url'      => base_url() . 'students/online_course/sslcommerz/guestsuccess',
            'fail_url'         => base_url() . 'students/online_course/sslcommerz/fail',
            'cancel_url'       => base_url() . 'students/online_course/sslcommerz/cancel',
            'cus_name'         => $params[0]['guest_name'],
            'cus_email'        => !empty($params[0]['email']) ? $params[0]['email'] : "example@email.com",
            'cus_add1'         => !empty($params[0]['address']) ? $params[0]['address'] : "Dhaka",
            'cus_phone'        => !empty($params[0]['contact_no']) ? $params[0]['contact_no'] : "01711111111",
            'cus_city'         => '',
            'cus_country'      => '',
            'multi_card_name'  => 'mastercard,visacard,amexcard,internetbank,mobilebank,othercard ',
            'shipping_method'  => 'NO',
            'product_name'     => 'test',
            'product_category' => 'Electronic',
            'product_profile'  => 'general',
        );
        $string = "";
        foreach ($CURLOPT_POSTFIELDS as $key => $value) {
            $string .= $key . '=' . $value . "&";
            if ($key == 'product_profile') {
                $string .= $key . '=' . $value;
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://securepay.sslcommerz.com/gwprocess/v4/api.php');
//https://securepay.sslcommerz.com/gwprocess/v4/api.php
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "$string");

        $headers   = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $response = json_decode($result);

        header("Location: $response->GatewayPageURL");

    }

    /*
    This is used to show success page status
    */
    public function guestsuccess()
    {
        if ($_POST['status'] == 'VALID') {
            $cart_data = $this->session->userdata('cart_data');

            $payment_id = $_POST['val_id'];

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
                    'note' => "Online course fees deposit through Sslcommerz Txn ID: " . $payment_id,
                    'payment_mode' => 'Sslcommerz',
                    'processing_charge_type'=>$cart_data_value['processing_charge_type'],
                    'processing_charge_amount'=>$cart_data_value['gateway_processing_charge'],
                );
                $this->course_payment_model->add($payment_data);
                $sender_details = array('email'=>$cart_data_value['email'], 'courseid'=> $cart_data_value['id'],'class' => null,  'class_section_id'=> null, 'section'=> null, 'title' => $cart_data_value['name'], 'price' => $cart_data_value['price'], 'discount' => $cart_data_value['discount'], 'assign_teacher' => $cart_data_value['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));

                $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
            } 

        redirect(base_url("students/online_course/course_payment/paymentsuccess"));

        } else {
          redirect(base_url("students/online_course/course_payment/paymentfailed"));
        }

    }
}