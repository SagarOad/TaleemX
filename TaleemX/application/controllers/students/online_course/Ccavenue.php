<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Ccavenue extends Student_Controller {

    function __construct() {
        parent::__construct();
		$this->load->model(array('course_model','coursesection_model','courselesson_model','studentcourse_model','coursequiz_model','course_payment_model','courseofflinepayment_model','coursereport_model','currency_model'));
        $this->setting = $this->setting_model->get();
        $this->load->library('Ccavenue_crypto');
        $this->load->library('course_mail_sms');
        $this->currency_name= $this->currency_model->get($this->session->userdata('student')['currency'])->short_name;
    }

    /*
    This is used to show payment detail page
    */
    public function index() {
        $data['params'] = $this->session->userdata('course_amount');
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/ccavenue/index', $data);
    }

    /*
    This is for payment gateway functionality
    */
    public function pay()
    {
        if ($this->input->server('REQUEST_METHOD') == 'POST') {
            $session_data            = $this->session->userdata('course_amount');
            $pay_method              = $this->paymentsetting_model->getActiveMethod();
            $details['tid']          = abs(crc32(uniqid()));
            $details['merchant_id']  = $pay_method->api_secret_key;
            $details['order_id']     = abs(crc32(uniqid()));
            $details['amount']       = convertBaseAmountCurrencyFormat($session_data['total_amount']);
            $details['currency']     = $session_data['currency_name'];
            $details['redirect_url'] = base_url('students/online_course/ccavenue/success');
            $details['cancel_url']   = base_url('students/online_course/ccavenue/cancel');
            $details['language']     = "EN";
            $merchant_data = "";
            foreach ($details as $key => $value) {
                $merchant_data .= $key . '=' . $value . '&';
            }
            $data['encRequest']  = $this->ccavenue_crypto->encrypt($merchant_data, $pay_method->salt);
            $data['access_code'] = $pay_method->api_publishable_key;

            $this->load->view('user/studentcourse/online_course/ccavenue/ccavenue_pay', $data);
        } else {
            redirect(base_url("students/online_course/ccavenue"));
        }
    }

    /*
    This is for payment gateway functionality
    */
    public function success() 
    {
        if ($this->input->server('REQUEST_METHOD') == 'POST') {

            $AuthDesc = "";
            $MerchantId = "";
            $OrderId = "";
            $Amount = 0;
            $Checksum = 0;
            $veriChecksum = false;
            $pay_method = $this->paymentsetting_model->getActiveMethod();
            $Checksum = $this->input->post('Checksum');
            $MerchantId = $this->input->post('Merchant_Id');
            $OrderId = $this->input->post('Order_Id');
            $Amount = $this->input->post('Amount');
            $AuthDesc = $this->input->post('AuthDesc');
            $workingKey = $pay_method->salt;
            $rcvdString = $MerchantId . '|' . $OrderId . '|' . $Amount . '|' . $AuthDesc . '|' . $workingKey;
            $veriChecksum = $this->adler32->verifyChecksum($this->adler32->genchecksum($rcvdString), $Checksum);

                $payment_data = array(
                    'date' => date('Y-m-d'),
                    'student_id' => $params['student_id'],
                    'guest_id' => $params['guest_id'],
                    'online_courses_id' => $params['courseid'],
                    'course_name' => $params['course_name'],
                    'actual_price' => $params['actual_amount'],
                    'paid_amount' => $params['total_amount'],
                    'payment_type' => 'Online',
                    'transaction_id' => $OrderId,
                    'note' => "Online course fees deposit through CCAvenue Txn ID: " . $OrderId . ", CCAvenue Ref ID: " . $nb_order_no,
                    'payment_mode' => 'CCAvenue',
                    'processing_charge_type'=>$params['processing_charge_type'],
                    'processing_charge_amount'=>$params['gateway_processing_charge'],
                );
            $this->course_payment_model->add($payment_data);
            if(!empty($params['courseid'])) {

                $sender_details = array('email'=>$params['email'], 'courseid'=>$params['courseid'],'class' => $params['class'],  'class_section_id'=> $params['class_sections'], 'section'=> $params['section'], 'title' => $params['course_name'], 'price' => $params['total_amount'], 'discount' => $params['discount'], 'assign_teacher' => $params['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));

                $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);         
                
                if($params['student_id']!=""){
                    $this->course_mail_sms->purchasemail('online_course_purchase', $sender_details);
                }else if($params['guest_id']!=""){

                     $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
                }

            redirect(base_url("students/online_course/course_payment/paymentsuccess"));  
            }else{
                redirect(base_url('students/online_course/course_payment/paymentfailed'));
            } 

        } else if ($veriChecksum == TRUE && $AuthDesc === "B") {
            $this->session->set_flashdata('message', 'Thank you for shopping with us.We will keep you posted regarding the status of your order through e-mail');
            redirect(base_url("students/online_course/Course_payment/paymentfailed"));
        } else if ($veriChecksum == TRUE && $AuthDesc === "N") {
            $this->session->set_flashdata('message', 'Thank you for shopping with us.However,the transaction has been declined');
            redirect(base_url("students/online_course/Course_payment/paymentfailed"));
        } else {
            $this->session->set_flashdata('message', 'Security Error. Illegal access detected');
            redirect(base_url("students/online_course/course_payment/paymentfailed"));
        }
    }

    public function cancel()
    {

    }

    public function guest() {
        $data['params'] = $this->session->userdata('cart_data');
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/ccavenue/guest_course/index', $data);
    }

    public function guestpay()
    {
        if ($this->input->server('REQUEST_METHOD') == 'POST') {
            $session_data            = $this->session->userdata('cart_data');
            $pay_method              = $this->paymentsetting_model->getActiveMethod();
            $details['tid']          = abs(crc32(uniqid()));
            $details['merchant_id']  = $pay_method->api_secret_key;
            $details['order_id']     = abs(crc32(uniqid()));
            $details['amount']       = number_format((float)$this->input->post('total_cart_amount'), 2, '.', '');
            $details['currency']     = $this->currency_name;
            $details['redirect_url'] = base_url('students/online_course/ccavenue/guestsuccess');
            $details['cancel_url']   = base_url('students/online_course/ccavenue/cancel');
            $details['language']     = "EN";
            $merchant_data = "";
            foreach ($details as $key => $value) {
                $merchant_data .= $key . '=' . $value . '&';
            }
            $data['encRequest']  = $this->ccavenue_crypto->encrypt($merchant_data, $pay_method->salt);
            $data['access_code'] = $pay_method->api_publishable_key;

            $this->load->view('user/studentcourse/online_course/ccavenue/ccavenue_pay', $data);
        } else {
            redirect(base_url("students/online_course/ccavenue/guest"));
        }
    }

    /*
    This is for payment gateway functionality
    */
    public function guestsuccess() {
        if ($this->input->server('REQUEST_METHOD') == 'POST') {

            $AuthDesc = "";
            $MerchantId = "";
            $OrderId = "";
            $Amount = 0;
            $Checksum = 0;
            $veriChecksum = false;
            $pay_method = $this->paymentsetting_model->getActiveMethod();
            $Checksum = $this->input->post('Checksum');
            $MerchantId = $this->input->post('Merchant_Id');
            $OrderId = $this->input->post('Order_Id');
            $Amount = $this->input->post('Amount');
            $AuthDesc = $this->input->post('AuthDesc');
            $workingKey = $pay_method->salt;
            $rcvdString = $MerchantId . '|' . $OrderId . '|' . $Amount . '|' . $AuthDesc . '|' . $workingKey;
            $veriChecksum = $this->adler32->verifyChecksum($this->adler32->genchecksum($rcvdString), $Checksum);

            foreach ($params as $cart_data_value) {

                $payment_data = array(
                    'date' => date('Y-m-d'),
                    'guest_id' => $cart_data_value['guest_id'],
                    'online_courses_id' => $cart_data_value['id'],
                    'course_name' => $cart_data_value['name'],
                    'actual_price' => $cart_data_value['actual_amount'],
                    'paid_amount' => $cart_data_value['price'],
                    'payment_type' => 'Online',
                    'transaction_id' => $OrderId,
                    'note' => "Online course fees deposit through CCAvenue Txn ID: " . $OrderId . ", CCAvenue Ref ID: " . $nb_order_no,
                    'payment_mode' => 'CCAvenue',
                    'gateway_ins_id' => $gateway_ins_id,
                    'processing_charge_type'=>$cart_data_value['processing_charge_type'],
                    'processing_charge_amount'=>$cart_data_value['gateway_processing_charge'],
                );
                $this->course_payment_model->add_processingpayment($payment_data);
                
                $sender_details = array('email'=>$cart_data_value['email'], 'courseid'=> $cart_data_value['id'],'class' => null,  'class_section_id'=> null, 'section'=> null, 'title' => $cart_data_value['name'], 'price' => $cart_data_value['price'], 'discount' => $cart_data_value['discount'], 'assign_teacher' => $cart_data_value['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));

                $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
            } 

            //     $payment_data = array(
            //         'date' => date('Y-m-d'),
            //         'student_id' => $params['student_id'],
            //         'guest_id' => $params['guest_id'],
            //         'online_courses_id' => $params['courseid'],
            //         'course_name' => $params['course_name'],
            //         'actual_price' => $params['actual_amount'],
            //         'paid_amount' => $params['total_amount'],
            //         'payment_type' => 'Online',
            //         'transaction_id' => $OrderId,
            //         'note' => "Online course fees deposit through CCAvenue Txn ID: " . $OrderId . ", CCAvenue Ref ID: " . $nb_order_no,
            //         'payment_mode' => 'CCAvenue',
            //     );
            // $this->course_payment_model->add($payment_data);

            redirect(base_url("students/online_course/course_payment/paymentsuccess"));
            // if(!empty($params['courseid'])) {
            //     $sender_details = array('courseid'=>$params['courseid'],'class' => $params['class'],  'class_section_id'=> $params['class_sections'], 'section'=> $params['section'], 'title' => $params['course_name'], 'price' => $params['total_amount'], 'discount' => $params['discount'], 'assign_teacher' => $params['staff'], 'paid_free' => $params['paid_free'], 'purchase_date' => date('Y-m-d'));         
                
            //     if($params['student_id']!=""){
            //         $this->course_mail_sms->purchasemail('online_course_purchase', $sender_details);
            //     }else if($params['guest_id']!=""){

            //          $this->course_mail_sms->purchasemail('online_course_purchase_for_guest', $sender_details);
            //     }


            // redirect(base_url("students/online_course/course_payment/paymentsuccess"));  
            // }else{
            //     redirect(base_url('students/online_course/course_payment/paymentfailed'));
            // } 

        } else if ($veriChecksum == TRUE && $AuthDesc === "B") {
            $this->session->set_flashdata('message', 'Thank you for shopping with us.We will keep you posted regarding the status of your order through e-mail');
            redirect(base_url("students/online_course/Course_payment/paymentfailed"));
        } else if ($veriChecksum == TRUE && $AuthDesc === "N") {
            $this->session->set_flashdata('message', 'Thank you for shopping with us.However,the transaction has been declined');
            redirect(base_url("students/online_course/Course_payment/paymentfailed"));
        } else {
            $this->session->set_flashdata('message', 'Security Error. Illegal access detected');
            redirect(base_url("students/online_course/course_payment/paymentfailed"));
        }
    }

}