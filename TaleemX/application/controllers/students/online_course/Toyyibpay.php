<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Toyyibpay extends Student_Controller {   

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
         $data['fee_processing']=$this->course_payment_model->check_payment_status($data['params']['courseid'], $data['params']['student_id'],$data['params']['guest_id'],'toyyibpay');   
        }else{
            $data['fee_processing']=$this->course_payment_model->check_payment_status($data['params']['courseid'], $data['params']['student_id'],'','toyyibpay');
        } 
        $this->load->view('user/studentcourse/online_course/toyyibpay/index', $data);
    }
 
    /*
    This is for payment gateway functionality
    */
    public function pay() {
        $this->form_validation->set_rules('phone', $this->lang->line('phone'), 'trim|required|xss_clean|saudi_phone');
        $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');

        $params = $this->session->userdata('course_amount');
        $data['params'] = $params;
        $data['setting'] = $this->setting;
        $data['api_error'] = $data['api_error'] = array();
        $result=array();
        if ($this->form_validation->run() == false) {
            $this->load->view('user/studentcourse/online_course/toyyibpay/index', $data);
        } else { 
            
            $params = $this->session->userdata('course_amount');

            $amount = $params['total_amount'];
            
            $payment_data = array(
                'userSecretKey'=>$this->api_config->api_secret_key,
                'categoryCode'=>$this->api_config->api_signature,
                'billName'=>'Fees',
                'billDescription'=>'Student Fees',
                'billPriceSetting'=>1,
                'billPayorInfo'=>1,
                'billAmount'=>convertBaseAmountCurrencyFormat($amount),
                'billReturnUrl'=>base_url().'students/online_course/toyyibpay/success',
                'billCallbackUrl'=>base_url().'gateway_ins/toyyibpay',
                'billExternalReferenceNo' => time().rand(99,999),
                'billTo'=>$params['name'],
                'billEmail'=>$_POST['email'],
                'billPhone'=>$_POST['phone'],
                'billSplitPayment'=>0,
                'billSplitPaymentArgs'=>'',
                'billPaymentChannel'=>'0',
                'billContentEmail'=>'Thank you for fees submission!',
                'billChargeToCustomer'=>1
              );  

              $curl = curl_init();
              curl_setopt($curl, CURLOPT_POST, 1);
              curl_setopt($curl, CURLOPT_URL, 'https://toyyibpay.com/index.php/api/createBill');  
              curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
              curl_setopt($curl, CURLOPT_POSTFIELDS, $payment_data);

              $result = curl_exec($curl);
              $info = curl_getinfo($curl);  
              curl_close($curl);
              $obj = json_decode($result);
           
            $params['transaction_id']=$payment_data['billExternalReferenceNo'];
            
             $ins_data=array(
            'unique_id'=>$payment_data['billExternalReferenceNo'],
            'parameter_details'=>json_encode($payment_data),
            'gateway_name'=>'toyyibpay',
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
		'transaction_id' => $params['transaction_id'],
                'note' => "Online course fees processing Toyyibpay Payment ID: " . $params['transaction_id'],
                'payment_mode' => 'Toyyibpay',
            ); 
            $this->course_payment_model->add_processingpayment($payment_data);
            $this->session->set_userdata("course_amount", $params);
              if((isset($obj->status) && $obj->status=='error')){
                    $result=$obj->msg;  
                    
                }else{                  
				  $url = "https://toyyibpay.com/".$obj[0]->BillCode;
                    header("Location: $url");
                }
                $data['api_error'] = $result;
                $this->load->view('user/studentcourse/online_course/toyyibpay/index', $data);
        }
    }
    
    /*
    This is used to show success page status
    */
    public function success() {

        $params = $this->session->userdata('course_amount');
        $parameter_data=$this->gateway_ins_model->get_gateway_ins($params['transaction_id'],'toyyibpay');
        $payment_id = $params['transaction_id'];

            if($parameter_data['payment_status']=='success'){
            
            if(!empty($params['courseid'])) {

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
                    'note' => "Online course fees deposit through Toyyibpay Txn ID: " . $payment_id,
                    'payment_mode' => 'Toyyibpay',
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

    public function guest()
    {
        
         $cartdata = $this->cart->contents();
            $cart_total = 0;
            foreach ($cartdata as  $value) {
              $cart_total += $value['price'];
          }
        $tamount           = convertBaseAmountCurrencyFormat($cart_total);

        $setting             = $this->setting;
        $data                = array();
        $data['setting'] = $setting;
        $total_amount = number_format((float)$this->input->post('total_cart_amount'), 2, '.', '');
        $data['amount'] = $total_amount;
        $total                       = 0;
        $amount                      = $total_amount;
        $data['total']               = $amount;
        $cart_data = $this->session->userdata('cart_data');

        $customer_email = $cart_data[0]['email'];
        
        if($cart_data[0]['contact_no']!=''){
            $customer_phone = $cart_data[0]['contact_no'];
        }else{
            $customer_phone = '9999999999';
        } 
       
            $payment_data = array(
                'userSecretKey'=>$this->api_config->api_secret_key,
                'categoryCode'=>$this->api_config->api_signature,
                'billName'=>'Online Course',
                'billDescription'=>'Online Course Fees',
                'billPriceSetting'=>1,
                'billPayorInfo'=>1,
                'billAmount'=>($tamount),
                'billReturnUrl'=>base_url().'students/online_course/toyyibpay/guestsuccess',
                'billCallbackUrl'=>base_url().'gateway_ins/toyyibpay',
                'billExternalReferenceNo' => time().rand(99,999),
                'billTo'=>$cart_data[0]['guest_name'],
                'billEmail'=>$customer_email,
                'billPhone'=>$customer_phone,
                'billSplitPayment'=>0,
                'billSplitPaymentArgs'=>'',
                'billPaymentChannel'=>'0',
                'billContentEmail'=>'Thank you for fees submission!',
                'billChargeToCustomer'=>1
              );  

              $curl = curl_init();
              curl_setopt($curl, CURLOPT_POST, 1);
              curl_setopt($curl, CURLOPT_URL, 'https://toyyibpay.com/index.php/api/createBill');  
              curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
              curl_setopt($curl, CURLOPT_POSTFIELDS, $payment_data);

              $result = curl_exec($curl);
              $info = curl_getinfo($curl);  
              curl_close($curl);
              $obj = json_decode($result);   

            if (!empty($obj)) { 
                $transaction_id = $payment_data['billExternalReferenceNo'];
                $this->session->set_userdata("billExternalReferenceNo",$transaction_id);
                $ins_data=array(
                    'unique_id'=>$payment_data['billExternalReferenceNo'],
                    'parameter_details'=>json_encode($payment_data),
                    'gateway_name'=>'toyyibpay',
                    'module_type'=>'online_course',
                    'payment_status'=>'processing',
                    );

                $gateway_ins_id=$this->gateway_ins_model->add_gateway_ins($ins_data);

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
                        'note' => "Online course fees processing Toyyibpay Payment ID: " . $transaction_id,
                        'payment_mode' => 'Payfast',
                        'gateway_ins_id' => $gateway_ins_id,
                        'processing_charge_type'=>$cart_data_value['processing_charge_type'],
                    'processing_charge_amount'=>$cart_data_value['gateway_processing_charge'],
                    );
                    $this->course_payment_model->add_processingpayment($payment_data);
                } 
            
                $data['url']=$data['error']="";
               
                if(isset($obj->status) && $obj->status=='error'){
                 $data['error']=$obj->msg;   
                }else{
                    $url = "https://dev.toyyibpay.com/".$obj[0]->BillCode;
                    header("Location: $url");
                }
             
                }else{
                    $data['error']=$result;
                }
            $this->load->view('user/studentcourse/online_course/toyyibpay/guest_course/index', $data);
    }

    public function guestsuccess() {
        $cart_data  = $this->session->userdata('cart_data');
        $billExternalReferenceNo  = $this->session->userdata('billExternalReferenceNo');
        $parameter_data=$this->gateway_ins_model->get_gateway_ins($billExternalReferenceNo,'toyyibpay');
        if($parameter_data['payment_status']!='3'){
            if($parameter_data['payment_status']=='1'){
                $gateway_response['paid_status']= 1;
            }else{
                $gateway_response['paid_status']= 2;
            }
            $transactionid                      = $_GET['transaction_id'];

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
                    'note' => "Online course fees deposit through Toyyibpay Txn ID: " . $transaction_id,
                    'payment_mode' => 'Toyyibpay',
                    'processing_charge_type'=>$cart_data_value['processing_charge_type'],
                    'processing_charge_amount'=>$cart_data_value['gateway_processing_charge'],
                );
                $this->course_payment_model->add($payment_data);
                
                $sender_details = array('email'=>$cart_data_value['email'], 'courseid'=> $cart_data_value['id'],'class' => null,  'class_section_id'=> null, 'section'=> null, 'title' => $cart_data_value['name'], 'price' => $cart_data_value['price'], 'discount' => $cart_data_value['discount'], 'assign_teacher' => $cart_data_value['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));

                $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
            }

            redirect(base_url("students/online_course/course_payment/paymentsuccess"));        
           
        }else{
           
        }

    }

}