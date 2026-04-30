<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Paystack extends Student_Controller {

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
        $data['params'] = $this->session->userdata('course_amount');
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/paystack/index', $data);
    }

    /*
    This is for payment gateway functionality
    */
    public function paystack_pay() {
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');

        $params = $this->session->userdata('course_amount');
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        
        if ($this->form_validation->run() == false) {
            $this->load->view('user/studentcourse/online_course/paystack/index', $data);
        } else {
            $data['total'] = $params['total_amount'] * 100;
             if (isset($data)) {
                $amount = convertBaseAmountCurrencyFormat($data['total']);
                $ref = time() . "02";
                $callback_url = base_url() . 'students/online_course/paystack/verify_payment/' . $ref;
                $postdata = array('email' => $_POST['email'],'amount' => $amount, "reference" => $ref, "callback_url" => $callback_url);
                $url = "https://api.paystack.co/transaction/initialize";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));//Post Fields
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $headers = [
                    'Authorization: Bearer ' . $this->api_config->api_secret_key,
                    'Content-Type: application/json',
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $request = curl_exec($ch);
                curl_close($ch);
                $result = json_decode($request, true);

                if ($result['status']) {
                    $redir = $result['data']['authorization_url'];
                    header("Location: " . $redir);
                } else {
                    $this->load->view('user/studentcourse/online_course/paystack/index', $data);
                }
            }
        }
    }

    /*
    This is used to show success page status
    */
    public function verify_payment($ref) {
        $result = array();
        $url = 'https://api.paystack.co/transaction/verify/' . $ref;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->api_config->api_secret_key]
        );
        $request = curl_exec($ch);
        curl_close($ch);

        if ($request) {
            $result = json_decode($request, true);
            if ($result) {
                if ($result['data']) {
                    //something came in
                    if ($result['data']['status'] == 'success') {
                        $params = $this->session->userdata('course_amount');
                        $payment_id = $ref;
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
                            'note' => "Online course fees deposit through Paystack Ref ID: " . $payment_id,
                            'payment_mode' => 'Paystack',
                            'processing_charge_type'=>$params['processing_charge_type'],
							'processing_charge_amount'=>$params['gateway_processing_charge'],
                        );
                        $this->course_payment_model->add($payment_data);
                        
                        if(!empty($params['courseid'])) {
                            
                            $sender_details = array('email'=>$params['email'], 'courseid'=>$params['courseid'], 'class' => $params['class'], 'class_section_id'=> $params['class_sections'], 'section'=> $params['section'], 'title' => $params['course_name'], 'price' => $params['total_amount'], 'discount' => $params['discount'], 'assign_teacher' => $params['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')), 'student_id' => $params['student_id'],
                            'guest_id'   => $params['guest_id']);         
                          
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
                        // the transaction was not successful, do not deliver value'
                        //uncomment this line to inspect the result, to check why it failed.
                        redirect(base_url("students/online_course/course_payment/paymentfailed"));
                    }
                } else {
                    redirect(base_url("students/online_course/course_payment/paymentfailed"));
                }
            } else {
                //die("Something went wrong while trying to convert the request variable to json. Uncomment the print_r command to see what is in the result variable.");
                redirect(base_url("students/online_course/course_payment/paymentfailed"));
            }
        } else {
            //die("Something went wrong while executing curl. Uncomment the var_dump line above this line to see what the issue is. Please check your CURL command to make sure everything is ok");
            redirect(base_url("students/online_course/course_payment/paymentfailed"));
        }
    }

    public function guest() {
        $data['params'] = $this->session->userdata('cart_data');
        $data['setting'] = $this->setting;
        $this->load->view('user/studentcourse/online_course/paystack/guest_course/index', $data);
    }

    public function guestpaystackpay() {
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');
        $params = $this->session->userdata('cart_data');
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        
        if ($this->form_validation->run() == false) {
            $this->load->view('user/studentcourse/online_course/paystack/guest_course/index', $data);
        } else {
            $data['total'] = $this->input->post('total_cart_amount') * 100;
            if (isset($data)) {
                $amount = convertBaseAmountCurrencyFormat($data['total']);
                $ref = time() . "02";
                $callback_url = base_url() . 'students/online_course/paystack/guestverifypayment/' . $ref;
                $postdata = array('email' => $_POST['email'],'amount' => $amount, "reference" => $ref, "callback_url" => $callback_url);
		$url = "https://api.paystack.co/transaction/initialize";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));//Post Fields
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $headers = [
                    'Authorization: Bearer ' . $this->api_config->api_secret_key,
                    'Content-Type: application/json',
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $request = curl_exec($ch);
                curl_close($ch);
                $result = json_decode($request, true);
                
                if ($result['status']) {
                    $redir = $result['data']['authorization_url'];

                    header("Location: " . $redir);
                } else {
                    $this->load->view('user/studentcourse/online_course/paystack/guest_course/index', $data);
                }
            }
        }
    }

    /*
    This is used to show success page status
    */
    public function guestverifypayment($ref) {
        $result = array();
        $url = 'https://api.paystack.co/transaction/verify/' . $ref;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->api_config->api_secret_key]
        );
        $request = curl_exec($ch);
        curl_close($ch);

        if ($request) {
            $result = json_decode($request, true);
            if ($result) {
                if ($result['data']) {
                    //something came in
                    if ($result['data']['status'] == 'success') {
                        $cart_data = $this->session->userdata('cart_data');
                        $payment_id = $ref;

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
                                'note' => "Online course fees deposit through Paystack Ref ID: " . $payment_id,
                                'payment_mode' => 'Paystack',
                                'processing_charge_type'=>$cart_data_value['processing_charge_type'],
                    'processing_charge_amount'=>$cart_data_value['gateway_processing_charge'],
                            );
                            $this->course_payment_model->add($payment_data);
                            
                            $sender_details = array('email'=>$cart_data_value['email'], 'courseid'=> $cart_data_value['id'],'class' => null,  'class_section_id'=> null, 'section'=> null, 'title' => $cart_data_value['name'], 'price' => $cart_data_value['price'], 'discount' => $cart_data_value['discount'], 'assign_teacher' => $cart_data_value['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));

                            $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
                        }

                        redirect(base_url("students/online_course/course_payment/paymentsuccess"));                    
                         
                    } else {
                        // the transaction was not successful, do not deliver value'
                        //uncomment this line to inspect the result, to check why it failed.
                        redirect(base_url("students/online_course/course_payment/paymentfailed"));
                    }
                } else {
                    redirect(base_url("students/online_course/course_payment/paymentfailed"));
                }
            } else {
                //die("Something went wrong while trying to convert the request variable to json. Uncomment the print_r command to see what is in the result variable.");
                redirect(base_url("students/online_course/course_payment/paymentfailed"));
            }
        } else {
            //die("Something went wrong while executing curl. Uncomment the var_dump line above this line to see what the issue is. Please check your CURL command to make sure everything is ok");
            redirect(base_url("students/online_course/course_payment/paymentfailed"));
        }
    }
}