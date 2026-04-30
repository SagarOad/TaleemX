<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Payu extends Student_Controller {

    function __construct() {
        parent::__construct();
		$this->load->model(array('course_model','coursesection_model','courselesson_model','studentcourse_model','coursequiz_model','course_payment_model','courseofflinepayment_model','coursereport_model','currency_model'));
        $this->setting = $this->setting_model->get();
        $this->load->library('course_mail_sms');
         $this->currency_name= $this->currency_model->get($this->session->userdata('student')['currency'])->short_name;
    }
 
    /*
    This is used to show payment detail page and payment gateway functionality
    */
    public function index() {
        $pre_session_data = $this->session->userdata('course_amount');
        $txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
        $pre_session_data['txn_id'] = $txnid;
        $this->session->set_userdata("course_amount", $pre_session_data);
        $session_data = $this->session->userdata('course_amount');
        $session_data['name'] = ($session_data['name'] != "") ? $session_data['name'] : "noname";
        $session_data['email'] = ($session_data['email'] != "") ? $session_data['email'] : "noemail@gmail.com";
        $session_data['contact_no'] = ($session_data['contact_no'] != "") ? $session_data['contact_no'] : "0000000000";
        $pay_method = $this->paymentsetting_model->getActiveMethod();

        $session_data['total_amount']           = $session_data['total_amount'];
        
        $amount=convertBaseAmountCurrencyFormat($session_data['total_amount']);
        $customer_name = $session_data['name'];
        $customer_emial = $session_data['email'];
        //echo $customer_emial;die;
        $product_info = 'online course';
        $MERCHANT_KEY = $pay_method->api_secret_key;
        $SALT = $pay_method->salt;

        //optional udf values 
        $udf1 = '';
        $udf2 = '';
        $udf3 = '';
        $udf4 = '';
        $udf5 = '';

        $hashstring = $MERCHANT_KEY . '|' . $txnid . '|' . $amount . '|' . $product_info . '|' . $customer_name . '|' . $customer_emial . '|' . $udf1 . '|' . $udf2 . '|' . $udf3 . '|' . $udf4 . '|' . $udf5 . '||||||' . $SALT;
        
        $hash = strtolower(hash('sha512', $hashstring));

        $success = base_url('students/online_course/payu/success');
        $fail = base_url('students/online_course/payu/success');
        $cancel = base_url('students/online_course/payu/success');
        $data = array(
            'mkey' => $MERCHANT_KEY,
            'tid' => $txnid,
            'hash' => $hash,
            'amount' => $amount,
            'name' => $customer_name,
            'productinfo' => $product_info,
            'mailid' => $customer_emial,
            'action' => "https://secure.payu.in", //for live change action  https://secure.payu.in
            'sucess' => $success,
            'failure' => $fail,
            'cancel' => $cancel
        );
        $data['session_data'] = $session_data;
        $data['setting'] = $this->setting;
      
        $this->load->view('user/studentcourse/online_course/payu/index', $data);
    }
 
    /*
    This is used to validate user and payment information
    */
    function checkout() {

        $this->form_validation->set_rules('firstname', 'Customer Name', 'required|trim|xss_clean');
        //$this->form_validation->set_rules('phone', 'Mobile No', 'required|trim|xss_clean');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim|xss_clean');
        $this->form_validation->set_rules('amount', 'Amount', 'required|trim|xss_clean');
        if ($this->form_validation->run() == false) {
            $data = array(
                'amount' => form_error('amount'),
                'email' => form_error('email'),
            );
            $array = array('status' => 'fail', 'error' => $data);
            echo json_encode($array);
       } else {
            $array = array('status' => 'success', 'error' => '');
            echo json_encode($array);
        }
    }

    /*
    This is used to show success page status
    */
    public function success() {
        
        if ($this->input->server('REQUEST_METHOD') == 'POST') {

            if ($this->input->post('status') == "success") {
                $params = $this->session->userdata('course_amount');
                $mihpayid = $this->input->post('mihpayid');
                $transactionid = $this->input->post('txnid');
                    $payment_data = array(
                        'date' => date('Y-m-d'),
                        'student_id' => $params['student_id'],
                        'guest_id' => $params['guest_id'],
                        'online_courses_id' => $params['courseid'],
                        'course_name' => $params['course_name'],
                        'actual_price' => $params['actual_amount'],
                        'paid_amount' => $params['total_amount'],
                        'payment_type' => 'Online',
                        'transaction_id' => $transactionid,
                        'note' => "Online course fees deposit through PayU Txn ID: " . $transactionid . ", PayU Ref ID: " . $mihpayid,
                        'payment_mode' => 'PayU',
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
    }

    public function guest()
    {

        $pre_session_data           = $this->session->userdata('cart_data');
        $txnid                      = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
        $pre_session_data['txn_id'] = $txnid;
        $this->session->set_userdata("params", $pre_session_data);
        $session_data                   = $this->session->userdata('cart_data');
       
        $session_data['name']           = ($session_data[0]['guest_name'] != "") ? $session_data[0]['guest_name'] : "noname";
        $session_data['email']          = ($session_data[0]['email'] != "") ? $session_data[0]['email'] : "noemail@gmail.com";
        $session_data['contact_no'] = ($session_data[0]['contact_no'] != "") ? $session_data[0]['contact_no'] : "0000000000";
        //$session_data['address']        = ($session_data[0]['address'] != "") ? $session_data[0]['address'] : "noaddress";
        $pay_method                     = $this->paymentsetting_model->getActiveMethod();
        //payumoney details
         $cartdata = $this->cart->contents();
            $cart_total = 0;
            foreach ($cartdata as  $value) {
              $cart_total += $value['price'];
          }
        $amount           = amountFormat($cart_total);
        $customer_name    = $session_data['name'];
        $customer_emial   = $session_data['email'];
        $customer_mobile  = $session_data['contact_no'];
        $customer_address = 'noaddress';

        $product_info = 'Online course Payment';
        $MERCHANT_KEY = $pay_method->api_secret_key;
        $SALT         = $pay_method->salt;

        //optional udf values
        $udf1 = '';
        $udf2 = '';
        $udf3 = '';
        $udf4 = '';
        $udf5 = '';
 
        $hashstring = $MERCHANT_KEY . '|' . $txnid . '|' . $amount . '|' . $product_info . '|' . $customer_name . '|' . $customer_emial . '|' . $udf1 . '|' . $udf2 . '|' . $udf3 . '|' . $udf4 . '|' . $udf5 . '||||||' . $SALT;
        $hash       = strtolower(hash('sha512', $hashstring));

        $success = base_url('students/online_course/payu/guestsuccess');
        $fail    = base_url('students/online_course/payu/guestsuccess');
        $cancel  = base_url('students/online_course/payu/guestsuccess');
        $data    = array(
            'mkey'                      => $MERCHANT_KEY,
            'tid'                       => $txnid,
            'hash'                      => $hash,
            'amount'                    => $amount,
            'name'                      => $customer_name,
            'productinfo'               => $product_info,
            'mailid'                    => $customer_emial,
            'phoneno'                   => $customer_mobile,
            'address'                   => $customer_address,
            'action'                    => "https://secure.payu.in", //for live change action  https://secure.payu.in
            'sucess'                    => $success,
            'failure'                   => $fail,
            'cancel'                    => $cancel,
        );
        $data['session_data'] = $session_data;
        $data['setting']      = $this->setting;
        
        $this->load->view('user/studentcourse/online_course/payu/guest_course/index', $data);
    }

    public function guestcheckout()
    {
        $this->form_validation->set_rules('firstname', $this->lang->line('customer_name'), 'required|trim|xss_clean');
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'required|valid_email|trim|xss_clean');
        $this->form_validation->set_rules('amount', $this->lang->line('amount'), 'required|trim|xss_clean');

        if ($this->form_validation->run() == false) {
            $data = array(
                'firstname' => form_error('firstname'),
                'email'     => form_error('email'),
                'amount'    => form_error('amount'),
            );
            $array = array('status' => 'fail', 'error' => $data);
            echo json_encode($array);
        } else {

            $array = array('status' => 'success', 'error' => '');
            echo json_encode($array);
        }
    }

    public function guestsuccess()
    {
        if ($this->input->server('REQUEST_METHOD') == 'POST') {
            $session_data = $this->session->userdata('params');

            if ($this->input->post('status') == "success") {
                $mihpayid      = $this->input->post('mihpayid');
                $transactionid = $this->input->post('txnid');
                $txn_id        = $session_data['txn_id'];

                if ($txn_id == $transactionid) {
                    $bulk_fees = array();
                    $params    = $this->session->userdata('params');

                    foreach ($params['student_fees_master_array'] as $fee_key => $fee_value) {

                        $json_array = array(
                            'amount'          => $fee_value['amount_balance'],
                            'date'            => date('Y-m-d'),
                            'amount_discount' => 0,
                            'amount_fine'     => $fee_value['fine_balance'],
                            'description'     => "Online fees deposit through PayU TXN ID: " . $txn_id . " PayU Ref ID: " . $mihpayid,
                            'received_by'     => '',
                            'payment_mode'    => 'PayU',
                        );

                        $insert_fee_data = array(
                            'fee_category'=>$fee_value['fee_category'],
                            'student_transport_fee_id'=>$fee_value['student_transport_fee_id'],
                            'student_fees_master_id' => $fee_value['student_fees_master_id'],
                            'fee_groups_feetype_id'  => $fee_value['fee_groups_feetype_id'],
                            'amount_detail'          => $json_array,
                        );
                        $bulk_fees[] = $insert_fee_data;
                        //========
                    }
                    $send_to     = $params['guardian_phone'];
                    $inserted_id = $this->studentfeemaster_model->fee_deposit_bulk($bulk_fees, $send_to);
                    if ($inserted_id) {
                        redirect(base_url("students/online_course/course_payment/paymentsuccess"));
                    } else {
                       redirect(base_url("students/online_course/course_payment/paymentfailed"));
                    }

                } else {
                    redirect(base_url("students/online_course/course_payment/paymentfailed"));
                }
            } else {

                redirect(base_url("students/online_course/course_payment/paymentfailed"));
            }
        }
    }

    // public function guest() {
    //     $pre_session_data = $this->session->userdata('cart_data');
    //     $cartdata = $this->cart->contents();
    //     $txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
    //     $pre_session_data['txn_id'] = $txnid;
    //     $this->session->set_userdata("params", $pre_session_data);
    //     $session_data = $this->session->userdata('cart_data');
    //     $session_data['name'] = ($session_data[0]['guest_name'] != "") ? $session_data[0]['guest_name'] : "noname";
    //     $session_data['email'] = ($session_data[0]['email'] != "") ? $session_data[0]['email'] : "noemail@gmail.com";
    //     $session_data['contact_no'] = ($session_data[0]['contact_no'] != "") ? $session_data[0]['contact_no'] : "0000000000";
    //     $pay_method = $this->paymentsetting_model->getActiveMethod();
    
    //     //payumoney details
    //     $amount = number_format((float)$this->input->post('amount'), 2, '.', '');;
    //     $customer_name = $session_data['name'];
    //     $customer_emial = $session_data['email'];
    //     //echo $customer_emial;die;
    //     $product_info = 'online course';
    //     $MERCHANT_KEY = $pay_method->api_secret_key;
    //     $SALT = $pay_method->salt;

    //     //optional udf values 
    //     $udf1 = '';
    //     $udf2 = '';
    //     $udf3 = '';
    //     $udf4 = '';
    //     $udf5 = '';

    //     $hashstring = $MERCHANT_KEY . '|' . $txnid . '|' . $amount . '|' . $product_info . '|' . $customer_name . '|' . $customer_emial . '|' . $udf1 . '|' . $udf2 . '|' . $udf3 . '|' . $udf4 . '|' . $udf5 . '||||||' . $SALT;
        
    //     $hash = strtolower(hash('sha512', $hashstring));

    //     $success = base_url('students/online_course/payu/guestsuccess');
    //     $fail = base_url('students/online_course/payu/guestsuccess');
    //     $cancel = base_url('students/online_course/payu/guestsuccess');
    //     $data = array(
    //         'mkey' => $MERCHANT_KEY,
    //         'tid' => $txnid,
    //         'hash' => $hash,
    //         'amount' => $amount,
    //         'name' => $customer_name,
    //         'productinfo' => $product_info,
    //         'mailid' => $customer_emial,
    //         'action' => "https://secure.payu.in", //for live change action  https://secure.payu.in
    //         'sucess' => $success,
    //         'failure' => $fail,
    //         'cancel' => $cancel
    //     );
    //     $data['session_data'] = $session_data;
    //     $data['setting'] = $this->setting;
    //     $this->load->view('user/studentcourse/online_course/payu/guest_course/index', $data);
    // }

    // public function guestsuccess() {
    //     if ($this->input->server('REQUEST_METHOD') == 'POST') {

    //         if ($this->input->post('status') == "success") {
    //             $params = $this->session->userdata('cart_data');
    //             $mihpayid = $this->input->post('mihpayid');
    //             $transactionid = $this->input->post('txnid');

    //             foreach ($cart_data as $cart_data_value) {

    //                 $sender_details = array(
    //                     'date' => date('Y-m-d'),
    //                     'guest_id' => $cart_data_value['guest_id'],
    //                     'online_courses_id' => $cart_data_value['id'],
    //                     'course_name' => $cart_data_value['name'],
    //                     'actual_price' => $cart_data_value['actual_amount'],
    //                     'paid_amount' => $cart_data_value['price'],
    //                     'payment_type' => 'Online',
    //                     'transaction_id' =>  $transactionid,
    //                     'note' => "Online course fees deposit through PayU Txn ID: " . $transactionid . ", PayU Ref ID: " . $mihpayid,
    //                     'payment_mode' => 'PayU',
    //                 );
    //                 $this->course_payment_model->add($sender_details);
    //             }

    //             redirect(base_url("students/online_course/course_payment/paymentsuccess"));


    //         //         if(!empty($params['courseid'])) {
    //         //            $sender_details = array('courseid'=>$params['courseid'],'class' => $params['class'],  'class_section_id'=> $params['class_sections'], 'section'=> $params['section'], 'title' => $params['course_name'], 'price' => $params['total_amount'], 'discount' => $params['discount'], 'assign_teacher' => $params['staff'], 'paid_free' => $params['paid_free'], 'purchase_date' => date('Y-m-d'));
                       
    //         //         if($params['student_id']!=""){
    //         //            $this->course_mail_sms->purchasemail('online_course_purchase', $sender_details);
    //         //         }else if($params['guest_id']!=""){
    //         //              $this->course_mail_sms->purchasemail('online_course_purchase_for_guest', $sender_details);
    //         //         }      
    //         //           redirect(base_url("students/online_course/course_payment/paymentsuccess"));     
    //         // }else{
    //         //     redirect(base_url('students/online_course/course_payment/paymentfailed'));
    //         // } 
    //         } else {
    //             redirect(base_url("students/online_course/course_payment/paymentfailed"));
    //         }
    //     }
    // }
}