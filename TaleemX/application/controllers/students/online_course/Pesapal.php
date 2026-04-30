<?php 
if ( ! defined('BASEPATH'))
 exit('No direct script access allowed');

class Pesapal extends Student_Controller {

    public $api_config = "";
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('course_model','coursesection_model','courselesson_model','studentcourse_model','coursequiz_model','course_payment_model','courseofflinepayment_model','coursereport_model','currency_model'));
	    $api_config = $this->paymentsetting_model->getActiveMethod();
		$this->setting = $this->setting_model->get();
		$this->load->library('pesapal_lib');
		$this->load->library('course_mail_sms');
		$this->currency_name= $this->currency_model->get($this->session->userdata('student')['currency'])->short_name;
	}
	 
	/*
    This is used to show payment detail page
    */
	public function index()
	{
	    $data['params'] = $this->session->userdata('course_amount');
	    $data['setting'] = $this->setting;
	    $this->load->view('user/studentcourse/online_course/pesapal/index', $data);
	}

    /*
    This is for payment gateway functionality
    */
	public function pesapal_pay(){
		$this->form_validation->set_rules('phone', $this->lang->line('phone'), 'trim|required|xss_clean');
		$this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');
		$params = $this->session->userdata('course_amount');
		$data['params'] = $params;
		$data['setting'] = $this->setting;

		if ($this->form_validation->run()==false) {
			$this->load->view('user/studentcourse/online_course/pesapal/index', $data);
		}else{
			$pesapal_details=$this->paymentsetting_model->getActiveMethod();
			$student_id = $params['student_id'];
			$total = $params['total_amount'];
			$data['student_id'] = $student_id;
			$data['total'] = $total;
			$data['name'] = ($params['name'] != "") ? $params['name'] : "noname";
			$amount = $data['total'];
			$token = $params = NULL;
			$consumer_key = $pesapal_details->api_publishable_key;					
			$consumer_secret = $pesapal_details->api_secret_key;
			$signature_method = new OAuthSignatureMethod_HMAC_SHA1();
			$iframelink = 'https://www.pesapal.com/API/PostPesapalDirectOrderV4';     
			$amount = number_format(convertBaseAmountCurrencyFormat($amount), 2);
			$desc = "Online Course Payment";
			$type = 'MERCHANT'; 
			$reference = time();
			$first_name = $data['name']; 
			$last_name = ''; 
			$email = $_POST['email'];
			$phonenumber = $_POST['phone']; 
			$callback_url = base_url('students/online_course/pesapal/pesapal_response'); 
			$post_xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?><PesapalDirectOrderInfo xmlns:xsi=\"http://www.w3.org/2001/XMLSchemainstance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" Amount=\"".$amount."\" Description=\"".$desc."\" Type=\"".$type."\" Reference=\"".$reference."\" FirstName=\"".$first_name."\" LastName=\"".$last_name."\" Email=\"".$email."\" PhoneNumber=\"".$phonenumber."\" xmlns=\"http://www.pesapal.com\" />";
			$post_xml = htmlentities($post_xml);
			$consumer = new OAuthConsumer($consumer_key, $consumer_secret);
			$iframe_src = OAuthRequest::from_consumer_and_token($consumer, $token, "GET",
			$iframelink, $params);
			$iframe_src->set_parameter("oauth_callback", $callback_url);
			$iframe_src->set_parameter("pesapal_request_data", $post_xml);
			$iframe_src->sign_request($signature_method, $consumer, $token);
			$consumer = new OAuthConsumer($consumer_key, $consumer_secret);
			$iframe_src = OAuthRequest::from_consumer_and_token($consumer, $token, "GET",
			$iframelink, $params);
			$iframe_src->set_parameter("oauth_callback", $callback_url);
			$iframe_src->set_parameter("pesapal_request_data", $post_xml);
			$iframe_src->sign_request($signature_method, $consumer, $token);
			$data['iframe_src']=$iframe_src;
	        $this->load->view('user/studentcourse/online_course/pesapal/pay', $data);
		}
	}
	  
	 /*
    This is used to show success page status
    */
	public function pesapal_response(){

		$pesapal_details=$this->paymentsetting_model->getActiveMethod();
		$reference = null;
		$pesapal_tracking_id = null;

		if(isset($_GET['pesapal_merchant_reference'])){
		$reference = $_GET['pesapal_merchant_reference'];
		}

		if(isset($_GET['pesapal_transaction_tracking_id'])){
		$pesapal_tracking_id = $_GET['pesapal_transaction_tracking_id'];
		}

		$consumer_key = $pesapal_details->api_publishable_key;
		$consumer_secret = $pesapal_details->api_secret_key;
		$statusrequestAPI = 'https://www.pesapal.com/api/querypaymentstatus';
		$pesapalTrackingId=$_GET['pesapal_transaction_tracking_id'];
		$pesapal_merchant_reference=$_GET['pesapal_merchant_reference'];

		if($pesapalTrackingId!='')
		{
		   $token = $params = NULL;
		   $consumer = new OAuthConsumer($consumer_key, $consumer_secret);
		   $signature_method = new OAuthSignatureMethod_HMAC_SHA1();
		   $request_status = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $statusrequestAPI, $params);
		   $request_status->set_parameter("pesapal_merchant_reference", $pesapal_merchant_reference);
		   $request_status->set_parameter("pesapal_transaction_tracking_id",$pesapalTrackingId);
		   $request_status->sign_request($signature_method, $consumer, $token);
		   $ch = curl_init();
		   curl_setopt($ch, CURLOPT_URL, $request_status);
		   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		   curl_setopt($ch, CURLOPT_HEADER, 1);
		   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		   if(defined('CURL_PROXY_REQUIRED')) if (CURL_PROXY_REQUIRED == 'True')
		   {
		      $proxy_tunnel_flag = (defined('CURL_PROXY_TUNNEL_FLAG') && strtoupper(CURL_PROXY_TUNNEL_FLAG) == 'FALSE') ? false : true;
		      curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, $proxy_tunnel_flag);
		      curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		      curl_setopt ($ch, CURLOPT_PROXY, CURL_PROXY_SERVER_DETAILS);
		   }
		   
		   $response = curl_exec($ch);
		   $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		   $raw_header  = substr($response, 0, $header_size - 4);
		   $headerArray = explode("\r\n\r\n", $raw_header);
		   $header      = $headerArray[count($headerArray) - 1];
		   $elements = preg_split("/=/",substr($response, $header_size));

		   $status = $elements[1];
		   if($status=='COMPLETED'){
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
                'note' => "Online course fees deposit through Pesapal Txn ID: " . $payment_id,
                'payment_mode' => 'Pesapal',
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
		curl_close ($ch);
	}

	public function guest()
	{
	    $data['params'] = $this->session->userdata('cart_data');
	    $data['setting'] = $this->setting;
	    $this->load->view('user/studentcourse/online_course/pesapal/guest_course/index', $data);
	}

	public function guestpesapalpay(){
		$this->form_validation->set_rules('phone', $this->lang->line('phone'), 'trim|required|xss_clean');
		$this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|xss_clean');
		$params = $this->session->userdata('cart_data');
		$data['params'] = $params;
		$data['setting'] = $this->setting;

		if ($this->form_validation->run()==false) {
			$this->load->view('user/studentcourse/online_course/pesapal/guest_course/index', $data);
		}else{
			$pesapal_details=$this->paymentsetting_model->getActiveMethod();
			$guest_id = $params[0]['guest_id'];
			$total = $this->input->post('total_cart_amount');
			$data['guest_id'] = $guest_id;
			$data['total'] = $total;
			$data['name'] = ($params[0]['guest_name'] != "") ? $params[0]['guest_name'] : "noname";
			$amount = $data['total'];
			$token = $params = NULL;
			$consumer_key = $pesapal_details->api_publishable_key;					
			$consumer_secret = $pesapal_details->api_secret_key;
			$signature_method = new OAuthSignatureMethod_HMAC_SHA1();
			$iframelink = 'https://www.pesapal.com/API/PostPesapalDirectOrderV4';     
			$amount = number_format($amount, 2);
			$desc = "Online Course Payment";
			$type = 'MERCHANT'; 
			$reference = time();
			$first_name = $data['name']; 
			$last_name = ''; 
			$email = $_POST['email'];
			$phonenumber = $_POST['phone']; 
			$callback_url = base_url('students/online_course/pesapal/guestpesapalresponse'); 
			$post_xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?><PesapalDirectOrderInfo xmlns:xsi=\"http://www.w3.org/2001/XMLSchemainstance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" Amount=\"".$amount."\" Description=\"".$desc."\" Type=\"".$type."\" Reference=\"".$reference."\" FirstName=\"".$first_name."\" LastName=\"".$last_name."\" Email=\"".$email."\" PhoneNumber=\"".$phonenumber."\" xmlns=\"http://www.pesapal.com\" />";
			$post_xml = htmlentities($post_xml);
			$consumer = new OAuthConsumer($consumer_key, $consumer_secret);
			$iframe_src = OAuthRequest::from_consumer_and_token($consumer, $token, "GET",
			$iframelink, $params);
			$iframe_src->set_parameter("oauth_callback", $callback_url);
			$iframe_src->set_parameter("pesapal_request_data", $post_xml);
			$iframe_src->sign_request($signature_method, $consumer, $token);
			$consumer = new OAuthConsumer($consumer_key, $consumer_secret);
			$iframe_src = OAuthRequest::from_consumer_and_token($consumer, $token, "GET",
			$iframelink, $params);
			$iframe_src->set_parameter("oauth_callback", $callback_url);
			$iframe_src->set_parameter("pesapal_request_data", $post_xml);
			$iframe_src->sign_request($signature_method, $consumer, $token);
			$data['iframe_src']=$iframe_src;
	        $this->load->view('user/studentcourse/online_course/pesapal/pay', $data);
		}
	}
	  
	 /*
    This is used to show success page status
    */
	public function guestpesapalresponse(){

		$pesapal_details=$this->paymentsetting_model->getActiveMethod();
		$reference = null;
		$pesapal_tracking_id = null;

		if(isset($_GET['pesapal_merchant_reference'])){
		$reference = $_GET['pesapal_merchant_reference'];
		}

		if(isset($_GET['pesapal_transaction_tracking_id'])){
		$pesapal_tracking_id = $_GET['pesapal_transaction_tracking_id'];
		}

		$consumer_key = $pesapal_details->api_publishable_key;
		$consumer_secret = $pesapal_details->api_secret_key;
		$statusrequestAPI = 'https://www.pesapal.com/api/querypaymentstatus';
		$pesapalTrackingId=$_GET['pesapal_transaction_tracking_id'];
		$pesapal_merchant_reference=$_GET['pesapal_merchant_reference'];

		if($pesapalTrackingId!='')
		{
		   $token = $params = NULL;
		   $consumer = new OAuthConsumer($consumer_key, $consumer_secret);
		   $signature_method = new OAuthSignatureMethod_HMAC_SHA1();
		   $request_status = OAuthRequest::from_consumer_and_token($consumer, $token, "GET", $statusrequestAPI, $params);
		   $request_status->set_parameter("pesapal_merchant_reference", $pesapal_merchant_reference);
		   $request_status->set_parameter("pesapal_transaction_tracking_id",$pesapalTrackingId);
		   $request_status->sign_request($signature_method, $consumer, $token);
		   $ch = curl_init();
		   curl_setopt($ch, CURLOPT_URL, $request_status);
		   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		   curl_setopt($ch, CURLOPT_HEADER, 1);
		   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		   if(defined('CURL_PROXY_REQUIRED')) if (CURL_PROXY_REQUIRED == 'True')
		   {
		      $proxy_tunnel_flag = (defined('CURL_PROXY_TUNNEL_FLAG') && strtoupper(CURL_PROXY_TUNNEL_FLAG) == 'FALSE') ? false : true;
		      curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, $proxy_tunnel_flag);
		      curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		      curl_setopt ($ch, CURLOPT_PROXY, CURL_PROXY_SERVER_DETAILS);
		   }
		   
		   $response = curl_exec($ch);
		   $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		   $raw_header  = substr($response, 0, $header_size - 4);
		   $headerArray = explode("\r\n\r\n", $raw_header);
		   $header      = $headerArray[count($headerArray) - 1];
		   $elements = preg_split("/=/",substr($response, $header_size));

		   $status = $elements[1];
		   if($status=='COMPLETED'){
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
	                    'note' => "Online course fees deposit through Pesapal Txn ID: " . $payment_id,
	                    'payment_mode' => 'Pesapal',
	                    'processing_charge_type'=>$cart_data_value['processing_charge_type'],
                    'processing_charge_amount'=>$cart_data_value['gateway_processing_charge'],
	                );
	                $this->course_payment_model->add($payment_data);
	                $sender_details = array('email'=>$cart_data_value['email'], 'courseid'=> $cart_data_value['id'],'class' => null,  'class_section_id'=> null, 'section'=> null, 'title' => $cart_data_value['name'], 'price' => $cart_data_value['price'], 'discount' => $cart_data_value['discount'], 'assign_teacher' => $cart_data_value['staff'], 'purchase_date' => $this->customlib->dateformat(date('Y-m-d')));

                    $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
	            }

            redirect(base_url("students/online_course/course_payment/paymentsuccess"));

          
		    }else{
		       redirect(base_url("students/online_course/course_payment/paymentfailed"));
		    }
		}
		curl_close ($ch);
	}
}