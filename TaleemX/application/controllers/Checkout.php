<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Checkout extends Front_Controller
{
    public $payment_method = array();
    public $pay_method     = array();
    public $user_data;

    public function __construct()
    {
        parent::__construct();
        $this->config->load("payroll");
        $this->load->library('Enc_lib');
        $this->load->library('Customlib');
        $this->load->model('course_model');
        $this->load->model('studentcourse_model');
        $this->load->model('paymentsetting_model');
        $this->pay_method = $this->paymentsetting_model->getActiveMethod();
        $this->result               = $this->customlib->getLoggedInUserData();        
        $this->load->library('cart');
    }
 
    public function index()
    { 
        $total_amount = $this->input->post("total_amount");  
        $cart_total= $this->cart->total();

            $newdata = array(
                'record_id'    => $this->input->post('record_id'),
                'total_amount' => $cart_total,
            );

            $role  = $this->result["role"];
            $data['role'] = $role ;            

            $this->session->set_userdata('payment_amount', $newdata);
            $this->session->unset_userdata('tempTotal_amount');
            $array = array('status' => '1', 'error' => '', 'message' => $this->lang->line('success_message'));    

        echo json_encode($array);
    }
 
    public function total_amount($total_amount){
        $this->session->userdata['tempTotal_amount']=$total_amount;
    }

    public function freeCourseEnrolled()
    {
        $course_id      = $this->input->post('course_id');	
        $course_details = $this->Course_model->getCourseName($course_id);		
		if(!empty($course_details[0]['free_course'])){
        $price          = $this->input->post('price');
        if (!empty($course_id)) {
            
            $course_data = array('student_id' => $this->user_data['id'],
                'course_id'                       => $course_id,
                'course_name'                     => $course_details[0]['course_title'],
                'amount'                          => $price,
                'payment_type'                    => '',
                'date'                            => date('Y-m-d'),
            );
			
			$data = array('student_id' => $this->user_data['id'],
                'id'                       => $course_id,                
            );

            $this->student_model->deletewishliststudent($data);		
			
            $this->payment_model->addStudentCourse($course_data);
            $this->payment_model->removeCartData($course_id);
            $instructor_data = $this->student_model->getStudentsAllDetails($this->user_data['id']);
            if ($course_id) {
                $sender_detail = array('email' => $instructor_data[0]['email'], 'student_id' => $instructor_data[0]['id'], 'course_id' => $course_id);
                $this->mailsmsconf->mailsms('course_enroll', $sender_detail);
            }
            $json_array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('success_message'));
        } else {
            $json_array = array('status' => 'fail', 'error' => 'Not Enrolled', 'message' => '');
        }
		}
        echo json_encode($json_array);
    }

    public function billpayment()
    {
        $role  = $this->result["role"];
    
        if($role=='student'){
            $student_id = $this->result["student_id"];
        }else{
            $student_id = $this->result["guest_id"];
        }

        if ($this->session->has_userdata('cart_contents')) {
            $s         = '';
            $cart_data = $this->cart->contents();
 
            foreach ($cart_data as $key => $value) {
                $courseslist = $this->course_model->singlecourselist($value['id']);
                $cart_data[$key]['actual_amount'] = $courseslist['price'];
                $cart_data[$key]['guest_id'] = $student_id;
                $gateway_processing_charge=0;
                    if($this->pay_method->charge_type=='percentage'){
                    $gateway_processing_charge=(($courseslist['price'] * $this->pay_method->charge_value)/100);
                    }elseif($this->pay_method->charge_type=='fix'){
                        $gateway_processing_charge=$this->pay_method->charge_value;
                    }else{
                     $gateway_processing_charge=0;   
                    } 
                    $cart_data[$key]['processing_charge_type']=$this->pay_method->charge_type;
                    $cart_data[$key]['gateway_processing_charge']=$gateway_processing_charge;
                $guest_data = $this->studentcourse_model->read_user_information($student_id);
                
                $cart_data[$key]['guest_name'] = $guest_data[0]->guest_name;
                $cart_data[$key]['email'] = $guest_data[0]->email;
                $cart_data[$key]['contact_no'] = $guest_data[0]->mobileno;
                $cart_data[$key]['address'] = $guest_data[0]->address;
                $cart_data[$key]['discount'] = $courseslist['discount'];
                $cart_data[$key]['staff'] = $courseslist["staff_name"].' '.$courseslist["staff_surname"];
                
                $checkpurchase = $this->customlib->getPurchasedCourseId($student_id, $value['id']);
                if (!empty($checkpurchase)) {
                    $s = $key;
                }
                $key = $s;
                unset($cart_data[$key]);
            }
            $cart_data   = array_values($cart_data);

            $update_data = $cart_data;
          
            $this->session->set_userdata("cart_data", $update_data);
        }
    
        $data = array();
        if (!empty($this->pay_method)) {

            if ($this->pay_method->payment_type == "payu") {
                redirect(base_url("students/online_course/payu/guest"));
            } elseif ($this->pay_method->payment_type == "stripe") {
                redirect(base_url("students/online_course/stripe/guest"));
            } elseif ($this->pay_method->payment_type == "ccavenue") {
                redirect(base_url("students/online_course/ccavenue/guest"));
            } elseif ($this->pay_method->payment_type == "paypal") {
                redirect(base_url("students/online_course/paypal/guest"));
            } elseif ($this->pay_method->payment_type == "instamojo") {
                redirect(base_url("students/online_course/instamojo/guest"));
            } elseif ($this->pay_method->payment_type == "paytm") {
                redirect(base_url("students/online_course/paytm/guest"));
            } elseif ($this->pay_method->payment_type == "razorpay") {
                redirect(base_url("students/online_course/razorpay/guest"));
            } elseif ($this->pay_method->payment_type == "paystack") {
                redirect(base_url("students/online_course/paystack/guest"));
            } elseif ($this->pay_method->payment_type == "midtrans") {
                redirect(base_url("students/online_course/midtrans/guest"));
            }elseif ($this->pay_method->payment_type == "ipayafrica") {
                redirect(base_url("students/online_course/ipayafrica/guest"));
            }elseif ($this->pay_method->payment_type == "jazzcash") {
                redirect(base_url("students/online_course/jazzcash/guest"));
            }elseif ($this->pay_method->payment_type == "pesapal") {
                redirect(base_url("students/online_course/pesapal/guest"));
            }elseif ($this->pay_method->payment_type == "flutterwave") {
                redirect(base_url("students/online_course/flutterwave/guest"));
            }elseif ($this->pay_method->payment_type == "billplz") {
                redirect(base_url("students/online_course/billplz/guest"));
            }elseif ($this->pay_method->payment_type == "sslcommerz") {
                redirect(base_url("students/online_course/sslcommerz/guest"));
            }elseif ($this->pay_method->payment_type == "walkingm") {
                redirect(base_url("students/online_course/walkingm/guest"));
            }elseif ($this->pay_method->payment_type == "mollie") {
                redirect(base_url("students/online_course/mollie/guest"));
            }elseif ($this->pay_method->payment_type == "cashfree") {
                redirect(base_url("students/online_course/cashfree/guest"));
            }elseif ($this->pay_method->payment_type == "payfast") {
                redirect(base_url("students/online_course/payfast/guest"));
            }elseif ($this->pay_method->payment_type == "toyyibpay") {
                redirect(base_url("students/online_course/toyyibpay/guest"));
            }elseif ($this->pay_method->payment_type == "twocheckout") {
                redirect(base_url("students/online_course/twocheckout/guest"));
            }elseif ($this->pay_method->payment_type == "skrill") {
                redirect(base_url("students/online_course/skrill/guest"));
            }elseif ($this->pay_method->payment_type == "payhere") {
                redirect(base_url("students/online_course/payhere/guest"));
            }elseif ($this->pay_method->payment_type == "onepay") {
                redirect(base_url("students/online_course/onepay/guest"));
            }
        }
    }

    public function calculate()
    {
        $amount = 0;
        echo json_encode(array('amount' => $amount));
    }

    public function paymentfailed()
    {
        $data                = array();
        $data['title']       = 'Invoice';
        $setting_result      = $this->setting_model->get();
        $data['settinglist'] = $setting_result;
        $this->load->view("layout/front/header");
        $this->load->view('front/paymentfailed', $data);
        $this->load->view("layout/front/footer");
    }
 
    public function successinvoice($invoice_id)
    {
        $data['title']              = 'Invoice';
        $setting_result             = $this->setting_model->get();
        $data['settinglist']        = $setting_result;
        $userpayment                = $this->payment_model->paymentByID($invoice_id);
        $record                     = $userpayment->paid_amount;
        $data['userpayment']        = $userpayment;
        $data['userpayment_detail'] = $record;
        $this->load->view('layout/front/header', $data);
        $this->load->view('front/invoice', $data);
        $this->load->view('layout/front/footer', $data);
    }
}
