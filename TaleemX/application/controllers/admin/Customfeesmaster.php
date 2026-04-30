<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Customfeesmaster extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->sch_setting_detail = $this->setting_model->getSetting();
        $this->load->model('feesessiongroup_model');
    }

    //------------**** studentwise fees master new code added****--------------//
    public function index(){        
        // $this->session->set_userdata('top_menu', 'Fees Collection');
        // $this->session->set_userdata('sub_menu', 'admin/feemaster');       
        $class                   = $this->class_model->get();
        $data['classlist']       = $class;
        $this->load->view('layout/header', $data);
        $this->load->view('admin/customfeesmaster/index', $data);
        $this->load->view('layout/footer', $data);
    }

    public function getStudentFeesMaster(){
        $student_session_id             = $_POST['student_session_id'];     
        $feegroup_result                = $this->feesessiongroup_model->getStudentfeeTypeByGroup($student_session_id);  
        $data['feemasterList']          = $feegroup_result;        
        $data['student_session_id']     = $student_session_id;
         
        if(!empty($feegroup_result)){
            $data['group']    = $this->feesessiongroup_model->searchstudentfeesmaster($feegroup_result[0]->fee_groups_id);
            $page = $this->load->view('admin/customfeesmaster/_prtialStudentFeesMaster', $data, true);
            echo json_encode(array('status' => 1, 'page' => $page));
        }else{
            echo json_encode(array('status' => 0, 'page' => ''));
        }
    }

    public function createInstallment(){
        $data['total_fees']             = $_POST['total_fees'];
        $data['down_payment']           = $_POST['down_payment'];
        $data['balance_fees']           = $_POST['balance_fees'];
        $data['no_of_installment']      = $_POST['no_of_installment'];
        $data['amount']                 = $_POST['amount'];
        $data['day']                    = $_POST['day'];
        $data['fine_type']              = $_POST['fine_type'];;
        $data['fine_type_value']        = $_POST['fine_type_value'];
        $data['student_name']           = $_POST['student_name'];
        $data['student_id']             = $_POST['student_id'];
        $result = $this->student_model->getByStudentSession($_POST['student_id']); 
        $data['group_name']  = $_POST['student_id'].'-'.$result['admission_no'].'-'.$result['firstname'];

        if(($data['total_fees'])==""  || ($data['balance_fees'])=="" || ($data['no_of_installment'])==""){
            echo json_encode(array('status' => 0, 'message' => $this->lang->line('please_fill_in_all_details')));
        }else{
            if($data['fine_type']=="none"){
                if($data['down_payment'] > $data['total_fees']){
                    echo json_encode(array('status' => 0, 'message' => $this->lang->line('1st_installment_should_not_be_greater_than_total_fees')));
                }else{
                    $page      = $this->load->view('admin/customfeesmaster/_prtialCreateInstallment', $data, true);
                    echo json_encode(array('status' => 1, 'page' => $page));
                }
            }else if(($data['fine_type'] != "none" && $data['fine_type_value']!="")){
                if($data['down_payment'] > $data['total_fees']){
                    echo json_encode(array('status' => 0, 'message' => $this->lang->line('1st_installment_should_not_be_greater_than_total_fees')));
                }else{
                    $page      = $this->load->view('admin/customfeesmaster/_prtialCreateInstallment', $data, true);
                    echo json_encode(array('status' => 1, 'page' => $page));
                }
            }else{
                  echo json_encode(array('status' => 0, 'message' => $this->lang->line('please_fill_in_all_details')));
            }
        }
    } 

    public function unassignfees(){       
        $fee_session_groups_id      =   $this->input->post('fee_session_groups_id');    
        $student_session_id         =   $this->input->post('student_session_id');   
        $fee_groups_id              =   $this->input->post('fee_groups_id');    

        $this->studentfeemaster_model->delete($fee_session_groups_id, $student_session_id);     
        $this->studentfeemaster_model->unassignfees($fee_session_groups_id, $fee_groups_id, $student_session_id);       
        redirect('admin/customfeesmaster/index');
    }

    public function savecustomfeesmaster(){
        $this->current_session   = $this->setting_model->getCurrentSession(); 
        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('section_id', $this->lang->line('section'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('student_session_id', $this->lang->line('student'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('total_fees', $this->lang->line('total_fees'), 'trim|required|xss_clean');         
        $this->form_validation->set_rules('balance_fees',$this->lang->line('balance_fees'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('no_of_installment',$this->lang->line('no_of_installment'), 'trim|required|xss_clean');
        $this->form_validation->set_rules('day', $this->lang->line('day'), 'trim|required|xss_clean');	 

        if ($this->form_validation->run() == false) {    

                $msg = array(
                    'class_id'              => form_error('class_id'),
                    'section_id'            => form_error('section_id'),
                    'student_session_id'    => form_error('student_session_id'),
                    'total_fees'            => form_error('total_fees'),
                    'balance_fees'          => form_error('balance_fees'),
                    'no_of_installment'     => form_error('no_of_installment'),
                    'day'                   => form_error('day'),
                    'installmentamount'     => form_error('installmentamount'),
                );

                $json_array = array('status' => 'fail', 'error' => $msg, 'message' => '');  
                echo json_encode($json_array);
                return false;          
        }else{
           //***validation***//
            $down_payment           =   $this->input->post('down_payment'); 
            $no_of_installment      =   $this->input->post('no_of_installment');            
            
            if($down_payment != ''){            
                $no_of_installment = $no_of_installment+1;          
            }else{          
                $no_of_installment = $no_of_installment;            
            }  

            $this->form_validation->set_rules('group_name_1', $this->lang->line('fee_group'), 'trim|required|xss_clean');     
            for($i=1;$i<=$no_of_installment;$i++){
                
                $this->form_validation->set_rules('fees_type_'.$i,$this->lang->line('fees_type'), 'trim|required|xss_clean');       
                $this->form_validation->set_rules('fees_code_'.$i,$this->lang->line('fees_code'), 'trim|required|xss_clean');
                $this->form_validation->set_rules('installmentamount_'.$i,$this->lang->line('amount'), 'trim|required|xss_clean|numeric');

                if ($this->form_validation->run() == false){           
                    $msg = array(
                        'group_name_1'  => form_error('group_name_1'),
                        'fees_type'     => form_error('fees_type_'.$i),
                        'fees_code'     => form_error('fees_code_'.$i),
                        'installmentamount'     => form_error('installmentamount_'.$i),
                    );
                    $json_array = array('status' => 'fail', 'error' => $msg, 'message' => '');  
                    echo json_encode($json_array);
                    return false;          
                }

            }
            //***validation***//
           
            //***save data***//
            $result = $this->student_model->getByStudentSession($this->input->post('student_session_id'));   
            // fees type
            
            for($i = 1; $i <= $no_of_installment; $i++){
                $dataFeesType = array(
                    'type'                 =>  $this->input->post('fees_type_'.$i),
                    'code'                 =>  $this->input->post('fees_code_'.$i),
                    'description'          => '',
                    'session_id'           => $this->current_session,
                    'student_session_id'   => $this->input->post('student_session_id'),
                    'nature'               => 'custom',                    
                );
                $this->feetype_model->add($dataFeesType);
            }       
            
            // fees group
            $dataFeesGroup = array(
                'name'        => $this->input->post('group_name_1'),
                'description' => '',
                'nature'      => 'custom',  
            );
            
            $fee_groups_id          =   $this->feegroup_model->add($dataFeesGroup);
            $fee_session_group_id   =   $this->feesessiongroup_model->group_exists($fee_groups_id);                     
            $feegroup_result        =   $this->feetype_model->getCustomFeesTypeByStudentSession($this->input->post('student_session_id'));           
            $fine_type              =   $this->input->post('fine_type');    
            
            foreach($feegroup_result as $key => $value){             
                    $key1 = $key + 1;
                    
                    $amount = $this->input->post('installmentamount_'.$key1);
                    $date   = $this->input->post('installmentdate_'.$key1);
                    if($date){
                        $due_date   =   date('Y-m-d', $this->customlib->datetostrtotime($date));
                    }else{
                        $due_date   =   NULL;
                    }            
                    
                    $fine_type_amount = $this->input->post('fine_type_amount_'.$key1);
                     
                    if($fine_type_amount > 0){                       
                        $fine_percentage    =   0;
                        $fine_amount        =   $fine_type_amount;
                        $finetype           =   'fix';                                              
                    }else{                      
                        $fine_percentage    =   0;
                        $fine_amount        =   0;
                        $finetype           =   'none';                     
                    }                   
                    
					if($amount){
                    $insert_array = array(
                        'fee_session_group_id'      => $fee_session_group_id,
                        'fee_groups_id'             => $fee_groups_id,
                        'feetype_id'                => $value['id'],
                        'amount'                    => convertCurrencyFormatToBaseAmount($amount),
                        'due_date'                  => $due_date,
                        'session_id'                => $this->setting_model->getCurrentSession(),
                        'fine_type'                 => $finetype,
                        'fine_percentage'           => $fine_percentage,
                        'fine_amount'               => convertCurrencyFormatToBaseAmount($fine_amount),
                    );
        
                    $feegroup_result = $this->feesessiongroup_model->add($insert_array);
					}
            }
            $insert_array = array(
                'student_session_id'   => $this->input->post('student_session_id'),
                'fee_session_group_id' => $fee_session_group_id,
            );
                    
            $inserted_id = $this->studentfeemaster_model->add($insert_array);
            //***save data***//
            $json_array = array('status' => 1, 'error' => '', 'message' => $this->lang->line('success_message'));      
            echo json_encode($json_array);
        }
    }
   



}

 ?>