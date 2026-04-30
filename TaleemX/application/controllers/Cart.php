<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cart extends Front_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        $this->setting  = $this->setting_model->get();
        $this->thumbnail_path = $this->config->item('course_thumbnail_path');      
        $this->load->library('cart');       
        $this->result   = $this->customlib->getLoggedInUserData();
        $this->load->library('captchalib');
        $this->load->library('module_lib');   
        
        $this->banner_content         = $this->config->item('ci_front_banner_content');
        $this->load->model(array('course_model','coursesection_model','courselesson_model','studentcourse_model','coursequiz_model','course_payment_model','courseofflinepayment_model','coursereport_model','paymentsetting_model')); 
         $this->pay_method = $this->paymentsetting_model->getActiveMethod();   
    }

    public function index()
    {
        $guest_login = $this->course_model->getOnlineCourseSettings();
        if($guest_login->guest_login != 1){
            redirect('page/404-page', 'refresh');
        }
        
        $this->data['active_menu'] = 'online_admission';
        $page                      = array('title' => '', 'meta_title' => '', 'meta_keyword' => '', 'meta_description' => '');
        $this->data['page_side_bar']  = false;
        $this->data['featured_image'] = false;
        $this->data['page']           = $page; 

        $home_page_slug = "online_course";
        $setting                   = $this->frontcms_setting_model->get();
        $this->data['active_menu'] = $home_page_slug;      
        $this->data['cookie_consent'] = $setting->cookie_consent; 

        $course_data = array();
        $cart_data   = array();
        if (!empty($this->cart->contents())) {
            $cart_data = $this->cart->contents();
            foreach ($cart_data as $key => $value) {

                $rowid = $value['rowid'];
                $result   = $this->course_model->singlecourselist($value['id']);
                if(!empty($result)){
                     $discount = 0;
                    if (!empty($result['discount'])) {
                        $discount = $result['price'] - (($result['price'] * $result['discount']) / 100);
                    }
                    if (($result["free_course"] == 'yes') && (empty($result["price"]))) {
                        $pricevalue = 0;
                    } elseif (($result["free_course"] == 'yes') && (!empty($result["price"]))) {
                        $pricevalue = 0;
                    } elseif (!empty($result["price"]) && (!empty($result["discount"]))) {
                        $pricevalue = $discount;
                    } else {
                        $pricevalue = $result['price'];
                    }
                    $cart_data[$value['id']]["price"] = $pricevalue;
                    $course_data[]            = $result;
                }

                $data = array(
                'rowid' => $rowid,
                'price'   => $pricevalue 
                );

                $this->cart->update($data);
            }
            
        }

        $this->data['thumbnail_path'] = $this->thumbnail_path;
        $this->data['course_data']    = $course_data;
        $this->data['cart_data']      = $cart_data;
        $setting                      = $this->setting[0];
        $this->data['currency']       = $setting['currency'];
        $this->data['academy_name']   = $setting['name'];
        $this->data['logoimage']      = $setting['image'];  
        
        $currencies = get_currency_list();
        $this->data['currencies'] = $currencies;
        if ($this->module_lib->hasModule('online_course')) { 
             $this->data['course_setting'] = $this->course_model->getOnlineCourseSettings();
        }
        $this->data['setting_data'] = $this->setting_model->get();
        $this->load_theme('course/cart');
    }

    public function getIPAddr()
    {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public function addcart()
    {
        $add_data      = array();
        $course_id     = $this->input->post('id');
        $coursesList   = $this->course_model->singlecourselist($course_id);
        $discount_amount         = ($coursesList['price'] * $coursesList['discount'])/100;
        $price         = $coursesList['price'] - $discount_amount;
      
        // $title = preg_replace('/[^a-zA-Z0-9_ -]/s', '', $coursesList['title']);
		$title = $coursesList['title'];
      
        if (is_numeric($price)) {
            $ip           = $this->getIPAddr();
            $session_data = $this->cart->contents();
            $gateway_processing_charge=0;
                    if($this->pay_method->charge_type=='percentage'){
                    $gateway_processing_charge=(($price* $this->pay_method->charge_value)/100);
                    }elseif($this->pay_method->charge_type=='fix'){
                        $gateway_processing_charge=$this->pay_method->charge_value;
                    }else{
                     $gateway_processing_charge=0;   
                    } 
            if (!empty($session_data)) {
                $key = array_column($session_data, 'id');
                if (in_array($course_id, $key)) {
                    $in_cart = 1;
					
                } else {
                    $add_data = $session_data;
                    $in_cart  = 0;
                }
            } else {
                $in_cart = 0;
            }

            if ($in_cart == 0) {
                $cart_items = $this->cart->contents() ;
               
                if ($this->session->has_userdata('student')) {

                    $role  = $this->result["role"];
                    $data['role'] = $role ;
                    if($role=='student'){
                        $userid = $this->result["student_id"];
                    }else{
                        $userid = $this->result["guest_id"];
                    }

                    $logged_in  = 'yes';
                    $guest_id = $this->session->userdata['student']['id'];
                   
                     if($role=='student'){
                        
                        $data = array(
                            'id'      => $course_id,
                            'qty'     => 1,
                            'price'   => $price,
                            'name'    => $title,
                            'options' => array('ip_address' => $ip, 'date' => date('Y-m-d'),'student_id'  => $this->result["student_id"])
                        );

                    }else{
                       
                        $data = array(
                            'id'      => $course_id,
                            'qty'     => 1,
                            'price'   => $price,
                           'processing_charge_type'=>$this->pay_method->charge_type,
                        'gateway_processing_charge'=>$gateway_processing_charge,
                            'name'    => $title,
                            'options' => array('ip_address' => $ip, 'date' => date('Y-m-d'),'guest_id'  => $this->result["guest_id"])
                        );
                    }

                    $this->cart->insert($data); 
                    $json_array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('successfully_added_to_cart'));
                } else {

                    $logged_in = 'no';
                    $data = array(
                        'id'      => $course_id,
                        'qty'     => 1,
                        'price'   => $price,
                        'name'    => $title,
                        'processing_charge_type'=>$this->pay_method->charge_type,
                        'gateway_processing_charge'=>$gateway_processing_charge,
                        'options' => array('ip_address' => $ip, 'date' => date('Y-m-d'),'guest_id'  => 0)
                    );

                    $this->cart->insert($data);
                    $json_array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('successfully_added_to_cart'));
                }

            } else {
                $json_array = array('status' => 'fail', 'message' => '', 'error' => $this->lang->line('already_in_your_cart'));
            }
            echo json_encode($json_array);
        }
    }

    public function removecart($id)
    {        
        if (!empty($this->cart->contents())) 
        {            
            $session_data = $this->cart->contents();
            foreach($session_data as $row ){                
                if($row['id']==$id){
                    $rowid = $row['rowid'];
                    $this->cart->remove($rowid);
                }
            }
        }
        redirect('cart');
    }

    public function checklogin()
    {
        if ($this->session->has_userdata('student')) {
            $json_arr = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('login') . ' ' . $this->lang->line('success'));
        } else {
            $json_arr = array('status' => 'fail', 'error' => $this->lang->line('user_should_be_login'), 'message' => $this->lang->line('user_should_be_login'));
        }
        echo json_encode($json_arr);
    } 
    
    public function removecartheader()
    {
        $id = $_POST['rowid'];
        $this->cart->remove($_POST['rowid']);        
    }

    public function addwishlist()
    {
        $course_id = $this->input->post('course_id');
        $price     = $this->input->post('price');
        if (!empty($course_id)) {
            if ($this->session->has_userdata('guest')) {
                $guest_id     = $this->session->userdata['guest']['id'];
                $check_exist    = $this->guest_model->checkwishlist($guest_id, $course_id);
                $check_mycourse = $this->guest_model->checkmycourse($guest_id, $course_id);
                if (($check_exist == 0) && ($check_mycourse == 0)) {
                    $msg        = "Already in your wishlist";
                    $json_array = array('status' => 'fail', 'error' => $msg, 'message' => '');
                } else {
                    $data = array('guest_id'       => $guest_id,
                        'course_id'                => $course_id,
                        'price'                    => $price,
                        'date_added'               => date("Y-m-d"),
                    );
                    $this->guest_model->addwishlist($data);
                    $json_array = array('status' => 'success', 'error' => '', 'message' => $this->lang->line('record_added_to_wishlist'));
                }
            } else {
                $msg        = "You need to login first";
                $json_array = array('status' => 'fail', 'error' => $msg, 'message' => '');
            }
        }
        echo json_encode($json_array);
    }

    public function carddatalist()
    {        
        $data['cart_data'] = $course = $this->cart->contents();
        $page = $this->load->view('themes/_cartlist', $data, true);
        $currency_symbol = $this->customlib->getSchoolCurrencyFormat();
        if ($this->cart->total()==0) {
            $course_total = 0;
        }else{ 
            $course_total = $this->cart->total(); 
        }
        $course_total   =   amountFormat($course_total);
        $total_amount   =   $this->lang->line('total') . " " . $currency_symbol.$course_total ;
        $course_count   =   count($course);
        $added_to_cart  =   "<button class='ptaddtocart' type='button' onclick='addtocart(".$_POST['id'].")'><i class='fa fa-shopping-cart'></i>".$this->lang->line('added_to_cart')."</button>";    
        echo json_encode(array('page' => $page, 'total_amount' => $total_amount, 'course_count' => $course_count, 'added_to_cart' => $added_to_cart));
    }
}