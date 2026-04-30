<?php 
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
	
class Course_payment extends Student_Controller {

    public $pay_method;
    public $school_name;
    public $school_setting;
    public $setting;

    function __construct() {
        parent::__construct();

        $this->load->model(array('course_model','coursesection_model','courselesson_model','studentcourse_model','coursequiz_model','course_payment_model','courseofflinepayment_model','coursereport_model'));       
        $this->pay_method = $this->paymentsetting_model->getActiveMethod();
        $this->school_name = $this->customlib->getAppName();
        $this->school_setting = $this->setting_model->get();
        $this->setting = $this->setting_model->get();
        $this->result = $this->customlib->getLoggedInUserData();
        $this->load->library('cart');
    }

    /*
    This is used to call all payment gateway and also store payment data in session
    */
    public function payment()
    {         
        $this->session->unset_userdata("course_amount");
        
        $courseid = $this->uri->segment(5);
        $role = $this->result["role"];
       
        if($role=='guest'){
            $userid = $this->result["guest_id"];
            $logged_user_userdata  =   $this->studentcourse_model->get_guest_details($userid);
        }else if(($role=='student') || ($role=='parent')){
            $userid = $this->result["student_id"];
            $logged_user_userdata = $this->student_model->get($userid);
        }
        $currency_symbol=$this->session->userdata('student')['currency_symbol'];
        $contact_no = $logged_user_userdata["mobileno"];
        $email = $logged_user_userdata["email"];
        $name = $this->result["username"];
        $courseslist = $this->course_model->singlecourselist($courseid);
        $multipalsection   =   $this->course_model->multipalsection($courseid);
        $staff = $courseslist["staff_name"].' '.$courseslist["staff_surname"]." (".$courseslist["assign_employee_id"].")";  
        $discount = '';
        $price = '';
        if (!empty($courseslist['discount'])) {
            $discount = $courseslist['price'] - (($courseslist['price'] * $courseslist['discount']) / 100);
        }
        if (($courseslist["free_course"] == 1) && (empty($courseslist["price"]))) {
            $price      = 'Free';           
        } elseif (($courseslist["free_course"] == 1) && (!empty($courseslist["price"]))) {
            if($courseslist['price'] > '0.00'){
                $courseprice = $courseslist['price'];
            }else{
                $courseprice = '';
            }
            $price      = $courseprice;           
        } elseif (!empty($courseslist["price"]) && (!empty($courseslist["discount"]))) {
            $discount   = ($discount);
            if($courseslist['price'] > '0.00'){
                $courseprice = $courseslist['price'];
            }else{
                $courseprice = '';
            }
            $price      = $discount ;
        } else {
            $price      = $courseslist['price']  ; 
        }

        $section = "";
        $store_section = array();
        foreach ($multipalsection as $multipalsection_value) {
            if (!in_array($multipalsection_value['section'], $store_section)) {
                $store_section[] = $multipalsection_value['section'];
                $section .= $multipalsection_value['section'] . ", ";
            }
        }
        $gateway_processing_charge=0;
                    if($this->pay_method->charge_type=='percentage'){
                    $gateway_processing_charge=(($price * $this->pay_method->charge_value)/100);
                    }elseif($this->pay_method->charge_type=='fix'){
                        $gateway_processing_charge=$this->pay_method->charge_value;
                    }else{
                     $gateway_processing_charge=0;   
                    } 
        if(($role=='student') || ($role=='parent')){

                $paymentdata = array(
                'actual_amount' => $courseslist['price'],
                'discount' => $courseslist['discount'],
                'total_amount' => $price+$gateway_processing_charge,
                'courseid' => $courseid,
                'course_name' => $courseslist['title'],
                'description' => $courseslist['description'],
                'course_thumbnail' => $courseslist['course_thumbnail'],
                'paid_free' => $courseslist['free_course'],
                'student_id' => $userid,
                'guest_id' => null,
                'contact_no' => $contact_no,
                'email' => $email,
                'name' => $name,
                'section' => $section,
                'class' => $courseslist['class'],
                'class_sections' => $courseslist['class_sections'],
                'staff' => $staff,
                'address' => '',
                'processing_charge_type'=>$this->pay_method->charge_type,
                'gateway_processing_charge'=>$gateway_processing_charge
                
            );
	
        }else if($role=='guest'){

            $guest_data = $this->studentcourse_model->read_user_information($userid);

                $paymentdata = array(
                'actual_amount' => $courseslist['price'],
                'discount' => $courseslist['discount'],
                'total_amount' => $price+$gateway_processing_charge,
                'courseid' => $courseid,
                'course_name' => $courseslist['title'],
                'description' => $courseslist['description'],
                'course_thumbnail' => $courseslist['course_thumbnail'],
                'paid_free' => $courseslist['free_course'],
                'student_id' => null,
                'guest_id' => $userid,
                'contact_no' => $guest_data[0]->mobileno,
                'email' => $guest_data[0]->email,
                'name' => $guest_data[0]->guest_name,
                'section' => $section,
                'class' => null,
                'class_sections' => null,
                'staff' => $staff,
                'address' => $guest_data[0]->address,
                'processing_charge_type'=>$pay_method->charge_type,
                'gateway_processing_charge'=>$gateway_processing_charge
            );

	
        }
      $paymentdata['currency_symbol']=$this->session->userdata('student')['currency_symbol'];
        $paymentdata['currency_name']=$this->session->userdata('student')['currency_name'];
        $this->session->set_userdata('course_amount', $paymentdata);
        $data = array();
        if (!empty($this->pay_method)) {
            $course_amount = $this->session->userdata('course_amount');
            
            $total_amount   = $course_amount['total_amount'];
            if ($this->pay_method->payment_type == "payu") {
                redirect(base_url("students/online_course/payu"));
            } elseif ($this->pay_method->payment_type == "stripe") {
                redirect(base_url("students/online_course/stripe"));
            } elseif ($this->pay_method->payment_type == "ccavenue") {
                redirect(base_url("students/online_course/ccavenue"));
            } elseif ($this->pay_method->payment_type == "paypal") {
                redirect(base_url("students/online_course/paypal"));
            } elseif ($this->pay_method->payment_type == "instamojo") {
                redirect(base_url("students/online_course/instamojo"));
            } elseif ($this->pay_method->payment_type == "paytm") {
                redirect(base_url("students/online_course/paytm"));
            } elseif ($this->pay_method->payment_type == "razorpay") {
                redirect(base_url("students/online_course/razorpay"));
            } elseif ($this->pay_method->payment_type == "paystack") {
                redirect(base_url("students/online_course/paystack"));
            } elseif ($this->pay_method->payment_type == "midtrans") {
                redirect(base_url("students/online_course/midtrans"));
            }elseif ($this->pay_method->payment_type == "ipayafrica") {
                redirect(base_url("students/online_course/ipayafrica"));
            }elseif ($this->pay_method->payment_type == "jazzcash") {
                redirect(base_url("students/online_course/jazzcash"));
            }elseif ($this->pay_method->payment_type == "pesapal") {
                redirect(base_url("students/online_course/pesapal"));
            }elseif ($this->pay_method->payment_type == "flutterwave") {
                redirect(base_url("students/online_course/flutterwave"));
            }elseif ($this->pay_method->payment_type == "billplz") {
                redirect(base_url("students/online_course/billplz"));
            }elseif ($this->pay_method->payment_type == "sslcommerz") {
                redirect(base_url("students/online_course/sslcommerz"));
            }elseif ($this->pay_method->payment_type == "walkingm") {
                redirect(base_url("students/online_course/walkingm"));
            }elseif ($this->pay_method->payment_type == "mollie") {
                redirect(base_url("students/online_course/mollie"));
            }elseif ($this->pay_method->payment_type == "cashfree") {
                redirect(base_url("students/online_course/cashfree"));
            }elseif ($this->pay_method->payment_type == "payfast") {
                redirect(base_url("students/online_course/payfast"));
            }elseif ($this->pay_method->payment_type == "toyyibpay") {
                redirect(base_url("students/online_course/toyyibpay"));
            }elseif ($this->pay_method->payment_type == "twocheckout") {
                redirect(base_url("students/online_course/twocheckout"));
            }elseif ($this->pay_method->payment_type == "skrill") {
                redirect(base_url("students/online_course/skrill"));
            }elseif ($this->pay_method->payment_type == "payhere") {
                redirect(base_url("students/online_course/payhere"));
            }elseif ($this->pay_method->payment_type == "onepay") {
                redirect(base_url("students/online_course/onepay"));
            }elseif ($this->pay_method->payment_type == "dpopay") {
                redirect(base_url("students/online_course/dpopay"));
            }elseif ($this->pay_method->payment_type == "momopay") {
                redirect(base_url("students/online_course/momopay"));
            }
        }
        
    } 

	/*
    This is used to show failed payment status
    */
    public function paymentfailed() {         
        $this->session->set_userdata('top_menu', 'user/studentcourse');
        $data['title'] = 'Invoice';
        $data['message'] = "dfsdfds";
        $setting_result = $this->setting_model->get();
        $data['settinglist'] = $setting_result;
        $this->load->view('layout/student/header', $data);
        $this->load->view('user/studentcourse/online_course/paymentfailed', $data);
        $this->load->view('layout/student/footer', $data);
    }

     public function paymentsuccess() {
         $this->cart->destroy();
        $this->load->view('user/studentcourse/paymentsuccess'); 
    }

     public function paymentprocessing() {
         
        $this->load->view('user/studentcourse/paymentprocessing'); 
    }

    public function delete_processingfee($id){

        if($this->course_payment_model->deleteByid($id)){
            echo 1;
        }else{
            echo 0;
        }
    }

      public function deleteBygateway_ins_id($id){

        if($this->course_payment_model->deleteBygateway_ins_id($id)){
            echo 1;
        }else{
            echo 0;
        }
    }
}