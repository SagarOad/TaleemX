<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Whatsappgateway
{

    private $_CI;
    private $sch_setting;

    public function __construct()
    {
        $this->_CI = &get_instance();
        $this->_CI->load->model('setting_model');
        $this->_CI->load->model('student_model');
        $this->_CI->load->model('whatsappconfig_model');
        $this->sch_setting = $this->_CI->setting_model->get(); 		
		
    }
	
	/* ================= META ================= */
	private function sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$language,$msg) {
		
		$params = [
			'access_token'    => $whatsapp_detail->authkey,
			'phone_number_id' => $whatsapp_detail->contact,
		];

		$this->_CI->load->library('meta_whatsapp', $params);

		$components = [
			[
				"type" => "body",
				"parameters" => []
			]
		];

		foreach ($msg as $value) {
			$components[0]['parameters'][] = [
				"type" => "text",
				"text" => $value
			];
		}

		return $this->_CI->meta_whatsapp->sendTemplate($send_to,$template_id,$language,$components);
		
	}
	/* ================= META ================= */
	
	/* ================= TWILIO ================= */
	public function sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg)
    {
        $params = [
            'username' => $whatsapp_detail->username,
            'password' => $whatsapp_detail->password,
            'api_version' => '2010-04-01',
            'number' => $whatsapp_detail->contact,
            'whatsapp_template_id' => $template_id,
        ];

        $this->_CI->load->library('twilio_whatsapp', $params);

        $response = $this->_CI->twilio_whatsapp->send($whatsapp_detail->contact, $send_to, $msg);

        return ($response && empty($response->error_message));
    }
	/* ================= TWILIO ================= */

    public function sendStudentLoginCredential($chk_mail_sms, $sender_details, $template, $template_id)
    {   
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        $msg        = $this->getLoginCredentialContent($sender_details['credential_for'], $sender_details, $template, $whatsapp_detail->type);		
		
        $send_to = $sender_details['contact_no'];
        if (!empty($whatsapp_detail)) {
            if ($whatsapp_detail->type == 'twilio') {                 
				
				return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
		
            } else if ($whatsapp_detail->type == 'meta') {				
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);				
				
			} else {

            }
        }
        return true;
    }

	public function getLoginCredentialContent($credential_for, $sender_details, $template, $sms_detail_type)
    {  
        if ($credential_for == "student") {
            $student                        = $this->_CI->student_model->get($sender_details['id']);
            $sender_details['url']          = base_url();
            $sender_details['display_name'] = $student['firstname'] . " " . $student['lastname'];
        } elseif ($credential_for == "parent") {
            $parent                         = $this->_CI->student_model->get($sender_details['id']);
            $sender_details['url']          = base_url();
            $sender_details['display_name'] = $parent['guardian_name'];
        } elseif ($credential_for == "staff") {
            $staff                          = $this->_CI->staff_model->get($sender_details['id']);
            $sender_details['url']          = base_url();
            $sender_details['display_name'] = $staff['name'];
        }  
		
		$sl = 0;
		
        foreach ($sender_details as $key => $value) {
            
			$foundValues = [];
			
			// Loop through the template and check for each placeholder
			preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
			
			// Extract values from the $data array if the key exists in the template
			foreach ($matches[1] as $key1) {
				if (isset($sender_details[$key1])) {
					$foundValues[$key1] = $sender_details[$key1]; // Add to foundValues array	
					
				}
			} 
			 
        }
		  
        return $foundValues;
    }

	
	public function sendStaffLoginCredential($chk_mail_sms, $sender_details, $template, $template_id)
    { 
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        $msg        = $this->getLoginCredentialContent($sender_details['credential_for'], $sender_details, $template, $whatsapp_detail->type);		
		
        $send_to = $sender_details['contact_no'];
        if (!empty($whatsapp_detail)) {
            if ($whatsapp_detail->type == 'twilio') {

                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            }
        }
        return true;
    }  


    public function sendstudentlhomework($sender_details, $template, $template_id)
    { 

        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        $msg = $this->getstudenthomeworkkey($sender_details, $template, $whatsapp_detail->type);    
        
        foreach ($sender_details as $student_key => $student_value) {
        //student and parent contact number loop will executed
        $send_to = $student_key;

        if (!empty($whatsapp_detail)) {
            if ($whatsapp_detail->type == 'twilio') {
				
                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            }
        }
        }
        return true;
    }


    public function getstudenthomeworkkey($sender_details, $template, $sms_detail_type)
    {  
        $sl = 0;
        $foundValues = [];
        foreach ($sender_details as $key => $value) {
            // Loop through the template and check for each placeholder
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {

                if (isset($sender_details[$key][$key1])) {
                    $foundValues[$key1] = $sender_details[$key][$key1]; // Add to foundValues array                       
                }
            } 
        }
        return $foundValues;
    }

    public function sendAbsentAttendancenotification($sender_details, $template, $template_id,$mobileno)
    { 
        
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        $msg = $this->extract_key($sender_details, $template); 
    
        $send_to = $mobileno;

        if (!empty($whatsapp_detail)) {
            if ($whatsapp_detail->type == 'twilio') {
                
				return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            }
        }

        return true;
    }

    public function sendPresentAttendancenotification($sender_details, $template, $template_id,$mobileno)
    { 
        
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        $msg = $this->extract_key($sender_details, $template); 
    
        $send_to = $mobileno;

        if (!empty($whatsapp_detail)) {
            if ($whatsapp_detail->type == 'twilio') {

                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            }
        }

        return true;
    }

    public function extract_key($sender_details, $template)
    {  
        foreach ($sender_details as $key => $value) {
            $foundValues = [];
            
            // Loop through the template and check for each placeholder
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {
                if (isset($sender_details[$key1])) {
                    $foundValues[$key1] = $sender_details[$key1]; // Add to foundValues array   
                    
                }
            } 
        }
        return $foundValues;
    }


    public function sentRegisterWhatsapp($student_id, $send_to, $template, $template_id)
    {
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        $msg = $this->getStudentRegistrationContent($student_id, $template);

        if (!empty($whatsapp_detail)) {
            if ($whatsapp_detail->type == 'twilio') {

                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            }
        }
        return true;
    }

    public function getStudentRegistrationContent($id, $template)
    {
        $session_name                    = $this->_CI->setting_model->getCurrentSessionName();
        $sender_details                         = $this->_CI->student_model->get($id);
        $sender_details['current_session_name'] = $session_name;
        $sender_details['student_name']         = $sender_details['firstname'] . " " . $sender_details['lastname'];

         foreach ($sender_details as $key => $value) {
            
            $foundValues = [];
            
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {
                if (isset($sender_details[$key1])) {
                    $foundValues[$key1] = $sender_details[$key1]; // Add to foundValues array   
                    
                }
            } 
        }
        return $foundValues;
    }

    public function sentAddFeeWhatsapp($sender_details, $send_to, $template, $template_id)
    {
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        $msg = $this->sentAddFeeWhatsapp_key($sender_details, $template);

        if (!empty($whatsapp_detail)) {
            if ($whatsapp_detail->type == 'twilio') {

                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            }
        }
        return true;
    }
	
	public function sentAddGroupFeeWhatsapp($sender_details, $send_to, $template, $template_id)
    {
		
		$whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
		
		$invoice_id=[];
        $sub_invoice_id=[];
        
        foreach ($sender_details['invoice'] as $inv_key => $inv_value) {
            $invoice_id[]=$inv_value['invoice_id'];
            $sub_invoice_id[]=$inv_value['sub_invoice_id'];
        }
        
        $sender_details['invoice_id']= "(".implode(',', $invoice_id).")";
        $sender_details['sub_invoice_id']= "(".implode(',', $sub_invoice_id).")";
		
        $msg = $this->getGroupAddFeeWhatsappKeys($sender_details, $template);

        if (!empty($whatsapp_detail)) {
            if ($whatsapp_detail->type == 'twilio') {

                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            }
        }
        return true;
    }
	
	public function getGroupAddFeeWhatsappKeys($data, $template)
	{
		$currency_symbol = $this->sch_setting[0]['currency_symbol'];

		if (is_string($data['invoice'])) {
			$data['invoice'] = json_decode($data['invoice'], true);
		}

		if (!is_array($data['invoice'])) {
			return [];
		}

		$fee_amount = 0;
		$payment_id = [];
		$fee = null;

		foreach ($data['invoice'] as $invoice_value) {

			$payment_id[] = $invoice_value['invoice_id'] . '/' . $invoice_value['sub_invoice_id'];

			if ($invoice_value['fee_category'] === 'transport') {
				$fee = $this->_CI->studentfeemaster_model
					->getTransportFeeByInvoice($invoice_value['invoice_id'], $invoice_value['sub_invoice_id']);
			} else {
				$fee = $this->_CI->studentfeemaster_model
					->getFeeByInvoice($invoice_value['invoice_id'], $invoice_value['sub_invoice_id']);
			}

			$amount_detail = json_decode($fee->amount_detail);
			$record = $amount_detail->{$invoice_value['sub_invoice_id']};

			$fee_amount +=
				($record->amount ?? 0) +
				($record->amount_fine ?? 0) -
				($record->amount_discount ?? 0);
		}

		$data['payment_id']  = '(' . implode(',', $payment_id) . ')';
		$data['class']       = $fee->class ?? '';
		$data['section']     = $fee->section ?? '';
		$data['amount']      = $currency_symbol . ($data['amount'] ?? 0);
		$data['fine_amount'] = $currency_symbol . ($data['fine_amount'] ?? 0);
		$data['fee_amount']  = $currency_symbol . amountFormat($fee_amount);

		$data['student_name'] = $this->_CI->customlib->getFullName(
			$fee->firstname ?? '',
			$fee->middlename ?? '',
			$fee->lastname ?? '',
			$this->sch_setting[0]['middlename'],
			$this->sch_setting[0]['lastname']
		);
	 
		$foundValues = [];
		preg_match_all('/{{(.*?)}}/', $template, $matches);

		foreach ($matches[1] as $key) {
			if (isset($data[$key])) {
				$foundValues[$key] = $data[$key];
			}
		}

		return $foundValues;
	}
	
	
    public function sentAddFeeWhatsapp_key($data, $template)
    {
        $currency_symbol      = $this->sch_setting[0]['currency_symbol'];
        $school_name          = $this->sch_setting[0]['name'];
        $invoice_data         = json_decode($data->invoice);
        $data->invoice_id     = $invoice_data->invoice_id;
        $data->sub_invoice_id = $invoice_data->sub_invoice_id;
        $data->payment_id     = $data->invoice_id."/".$data->sub_invoice_id;       
        $data->amount         = $currency_symbol . $data->amount;      
        
        if ($data->fee_category == "transport") {
            $fee = $this->_CI->studentfeemaster_model->getTransportFeeByInvoice($data->invoice_id, $data->sub_invoice_id);
        } else {
            $fee = $this->_CI->studentfeemaster_model->getFeeByInvoice($data->invoice_id, $data->sub_invoice_id);
        }
       
        $a                    = json_decode($fee->amount_detail);
        $record               = $a->{$data->sub_invoice_id};
        $fee_amount           = number_format((($record->amount)), 2, '.', ',');
        $data->firstname      = $fee->firstname;
        $data->lastname       = $fee->lastname;
        $data->class          = $fee->class;
        $data->section        = $fee->section;
        $data->fee_amount     = $currency_symbol . $fee_amount;
		 $data->fine_amount   = $currency_symbol . $record->amount_fine;
		
        $data->student_name   = $this->_CI->customlib->getFullName($fee->firstname, $fee->middlename, $fee->lastname, $this->sch_setting[0]['middlename'], $this->sch_setting[0]['lastname']); 
				
        foreach ($data as $key => $value) {
            
            $foundValues = [];
            
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {
                if (isset($data->$key1)) {
                    $foundValues[$key1] = $data->$key1; // Add to foundValues array   
                    
                }
            } 
        }
        return $foundValues;
    }

    public function sentExamResultWhatsapp($detail, $template, $template_id)
    {
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        $msg        = $this->getStudentResultContent_key($detail, $template);
        
        foreach ($detail['contact_numbers'] as $key => $contact_numbersvalue) {
            $send_to = $contact_numbersvalue;
            
             if (!empty($whatsapp_detail)) {
				 
				if ($whatsapp_detail->type == 'twilio') {

					return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
			   
				} else if ($whatsapp_detail->type == 'meta') {
				
					return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
				} else {

            } 
        }
        }

        return true;
    }

    public function getStudentResultContent_key($data, $template)
    {

        foreach ($data as $key => $value) {
            $foundValues = [];
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {
                if (isset($data[$key1])) {
                    $foundValues[$key1] = $data[$key1]; // Add to foundValues array   
                    
                }
            } 
        }
        return $foundValues;
    }

    public function sentOnlineexamStudentWhatsapp($detail, $template, $template_id)
    {
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
    
        if (!empty($whatsapp_detail)) {

            foreach ($detail as $student_key => $student_value) {
				
                $send_to = $student_key;
                $msg  = $this->getOnlineexamStudentContent_key($detail[$student_key], $template);
				
            if ($whatsapp_detail->type == 'twilio') {                

                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            } 
        }
        }
    }
    
    public function getOnlineexamStudentContent_key($data, $template)
    {

         foreach ($data as $key => $value) {
            
            $foundValues = [];
            
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {

                if (isset($data[$key1])) {
                    $foundValues[$key1] = $data[$key1]; // Add to foundValues array   
                    
                }
            } 
        }
        return $foundValues;
    }

    public function student_apply_leave($sender_details, $template,$template_id)
    {
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
    
        if (!empty($whatsapp_detail)) {

            $send_to = $sender_details['contact_no'];
            $msg        = $this->getstudent_apply_leaveContent($sender_details, $template);
			
            if ($whatsapp_detail->type == 'twilio') {                

                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            } 
        }
    }

    public function getstudent_apply_leaveContent($data, $template)
    {
         foreach ($data as $key => $value) {
            
            $foundValues = [];
            
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {

                if (isset($data[$key1])) {
                    $foundValues[$key1] = $data[$key1]; // Add to foundValues array   
                    
                }
            } 
        }
        return $foundValues;
    }

    //send staff attendance whatsapp and notification on app absent   
    public function sentAbsentStaffWhatsapp($sender_details, $template,$template_id)
    {
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        if (!empty($whatsapp_detail)) {

            $send_to = $sender_details['contact_no'];
            $msg        = $this->getAbsentStaffContent($sender_details, $template);
			
            if ($whatsapp_detail->type == 'twilio') {                

                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            } 
        }
    }
  
    public function getAbsentStaffContent($data, $template)
    {
        
        foreach ($data as $key => $value) {
            
            $foundValues = [];
            
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {

                if (isset($data[$key1])) {
                    $foundValues[$key1] = $data[$key1]; // Add to foundValues array   
                    
                }
            } 
        }
        return $foundValues;
    }
    //send staff attendance whatsapp notification on app absent   

    //send staff attendance whatsapp notification on app absent   
    public function sentPresentStaffWhatsapp($sender_details, $template,$template_id)
    {

        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        if (!empty($whatsapp_detail)) {

            $send_to = $sender_details['contact_no'];
            $msg        = $this->getPresentStaffContent($sender_details, $template);
			
            if ($whatsapp_detail->type == 'twilio') {                

                 return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				 
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            } 
        }
    }
  
    public function getPresentStaffContent($data, $template)
    {
        foreach ($data as $key => $value) {
            $foundValues = [];
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {
                if (isset($data[$key1])) {
                    $foundValues[$key1] = $data[$key1]; // Add to foundValues array   
                }
            } 
        }
        return $foundValues;
    }

    //send staff attendance whatsapp notification on app absent  

    public function sentCBSEExamResultWhatsapp($detail, $template,$chk_mail_sms,$send_to,$template_id)
    {
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();

        if (!empty($whatsapp_detail)) {
            
			$msg        = $this->getCBSEExamResultContent($detail, $template);
			
            if ($whatsapp_detail->type == 'twilio') {                

                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            } 
        }
    }

    public function getCBSEExamResultContent($data, $template)
    {

        foreach ($data as $key => $value) {
            $foundValues = [];
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {
                if (isset($data[$key1])) {
                    $foundValues[$key1] = $data[$key1]; // Add to foundValues array   
                }
            } 
        }
        return $foundValues;
    }

    //==================send notification by whatsapp FOR ONLINE COURSE===================//

    public function publishsendWhatsapp($sender_details,$template,$send_to,$template_id)
    {
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        $msg = $this->getpublishcontent($sender_details, $template);

         if (!empty($whatsapp_detail)) {            
            if ($whatsapp_detail->type == 'twilio') {
                
                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            }  
        }
    }

    public function getpublishcontent($data, $template)
    {
        foreach ($data as $key => $value) {
            $foundValues = [];
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {
                if (isset($data[$key1])) {
                    $foundValues[$key1] = $data[$key1]; // Add to foundValues array   
                }
            } 
        }
        return $foundValues;
    }

    public function purchasesendWhatsapp($sender_details,$template,$send_to,$template_id)
    {
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        $msg = $this->getpurchasecontent($sender_details, $template);

         if (!empty($whatsapp_detail)) {            
            if ($whatsapp_detail->type == 'twilio') {
                
				return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            }  
        }
    }

    public function getpurchasecontent($data, $template)
    {
        foreach ($data as $key => $value) {
            $foundValues = [];
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {
                if (isset($data[$key1])) {
                    $foundValues[$key1] = $data[$key1]; // Add to foundValues array   
                }
            } 
        }
        return $foundValues;
    }

    //==================send notification by whatsapp FOR ONLINE COURSE===================//


    //==================send notificaion by whatsapp in GMEET================//
    
    public function sentOnlineMeetingStaffwhatsapp($sender_details,$template,$send_to,$template_id)
    {
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        $msg = $this->getOnlineMeetingStaffcontent($sender_details["$send_to"], $template);
         if (!empty($whatsapp_detail)) {            
            if ($whatsapp_detail->type == 'twilio') {
                
				return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            }  
        }
    }

    public function getOnlineMeetingStaffcontent($data, $template)
    {
        foreach ($data as $key => $value) {
            $foundValues = [];
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {
                if (isset($data[$key1])) {
                    $foundValues[$key1] = $data[$key1]; // Add to foundValues array   
                }
            } 
        }
        return $foundValues;
    }

    public function sentstudentOnlineClasswhatsapp($sender_details,$template,$send_to,$template_id){
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        $msg = $this->getstudentOnlineClasscontent($sender_details["$send_to"], $template);

        if(!empty($whatsapp_detail)) {            
            if ($whatsapp_detail->type == 'twilio') {
				
                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            }  
        }
    }

    public function getstudentOnlineClasscontent($data, $template)
    {
        foreach ($data as $key => $value) {
            $foundValues = [];
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {
                if (isset($data[$key1])) {
                    $foundValues[$key1] = $data[$key1]; // Add to foundValues array   
                }
            } 
        }
        return $foundValues;
    }
    //==================send notificaion by whatsapp in GMEET================//

    //===============send notification by whatsapp in Zoom================//
    public function sentZoomMeetingStaffwhatsapp($sender_details,$template,$send_to,$template_id)
    {
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        $msg = $this->getZoomMeetingStaffcontent($sender_details["$send_to"], $template);
         if (!empty($whatsapp_detail)) {            
            if ($whatsapp_detail->type == 'twilio') {
				
                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            } 
        }
    }

    public function getZoomMeetingStaffcontent($data, $template)
    {
        foreach ($data as $key => $value) {
            $foundValues = [];
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {
                if (isset($data[$key1])) {
                    $foundValues[$key1] = $data[$key1]; // Add to foundValues array   
                }
            } 
        }
        return $foundValues;
    }

    public function sentstudentZoomClasswhatsapp($sender_details,$template,$send_to,$template_id)
    {
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        $msg = $this->getstudentZoomClasscontent($sender_details["$send_to"], $template);

        if(!empty($whatsapp_detail)) {         
            if ($whatsapp_detail->type == 'twilio') {
				
                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            }  
        }
    }

    public function getstudentZoomClasscontent($data, $template)
    {
        foreach ($data as $key => $value) {
            $foundValues = [];
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {
                if (isset($data[$key1])) {
                    $foundValues[$key1] = $data[$key1]; // Add to foundValues array   
                }
            } 
        }
        return $foundValues;
    }
    //===============send notification by whatsapp in Zoom================//

    //===================behavioural=====================//
    public function sentBehaviourIncidentAssignedstudentWhatsapp($sender_details,$template,$send_to,$template_id)
    {
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        $msg = $this->getstudentBehaviourIncidentAssignedcontent($sender_details["$send_to"], $template);

        if(!empty($whatsapp_detail)) {            
            if ($whatsapp_detail->type == 'twilio') {
				
                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            }  
        }
    }

    public function getstudentBehaviourIncidentAssignedcontent($data, $template)
    {
        foreach ($data as $key => $value) {
            $foundValues = [];
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {
                if (isset($data[$key1])) {
                    $foundValues[$key1] = $data[$key1]; // Add to foundValues array   
                }
            } 
        }
        return $foundValues;
    }
    //===================behavioural=====================//

    //==================online admission fees submission===============//
    public function sentstudentOnlineadmissionFeessubmissionWhatsapp($sender_details,$template,$send_to,$template_id)
    {
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        $msg = $this->getstudentOnlineadmissionFeescontent($sender_details, $template);
        if(!empty($whatsapp_detail)) {            
            if ($whatsapp_detail->type == 'twilio') {
				
                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            }  
        }
    }

    public function getstudentOnlineadmissionFeescontent($data, $template)
    {
       foreach ($data as $key => $value) {
            $foundValues = [];
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {
                if (isset($data[$key1])) {
                    $foundValues[$key1] = $data[$key1]; // Add to foundValues array   
                }
            } 
        }
        return $foundValues;
    }
    //==================online admission fees submission===============//

    //==================online admission admission form submission===================//
    public function sendOnlineadmissionformsubmit($sender_details,$template,$send_to,$template_id)
    {
        $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
        $msg = $this->getOnlineadmissionformsubmitcontent($sender_details, $template);
        if(!empty($whatsapp_detail)) {            
            if ($whatsapp_detail->type == 'twilio') {
                
				return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
					
            } else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			} else {

            }  
        }
    }

    public function getOnlineadmissionformsubmitcontent($data, $template)
    {
       foreach ($data as $key => $value) {
            $foundValues = [];
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {
                if (isset($data[$key1])) {
                    $foundValues[$key1] = $data[$key1]; // Add to foundValues array   
                }
            } 
        }
        return $foundValues;
    }
    //==================online admission admission form submission===================//


    //=========fees reminder===============//
    public function sentfeesreminderNotification($sender_details,$send_to,$template,$template_id)
    {
         $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
    
        if (!empty($whatsapp_detail)) {
			
			$msg        = $this->getfeesreminderContent($sender_details, $template);
			
            if ($whatsapp_detail->type == 'twilio') {                

                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
            }else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			}  
        }

    }

    public function getfeesreminderContent($data, $template)
    {
         foreach ($data as $key => $value) {
            $foundValues = [];
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {

                if (isset($data->$key1)) {
                    $foundValues[$key1] = $data->$key1; // Add to foundValues array   
                    
                }
            } 
        }
        return $foundValues;
    }
	
	

//=========fees reminder===============//


	public function sentFeeProcessingNotification($sender_details,$send_to,$template,$template_id)
    {
         $whatsapp_detail = $this->_CI->whatsappconfig_model->getActiveWhatsApp();
    
        if (!empty($whatsapp_detail)) {
			
			 $msg        = $this->getFeeProcessingContent($sender_details, $template);
			 
            if ($whatsapp_detail->type == 'twilio') {               

                return $this->sendByTwilio($whatsapp_detail, $send_to, $template_id, $msg);
				
				
            }else if ($whatsapp_detail->type == 'meta') {
				
				return $this->sendMetaTemplate($whatsapp_detail,$send_to,$template_id,$whatsapp_detail->language,$msg);
				
			}  
        }

    }

	public function getFeeProcessingContent($data, $template)
    {
         foreach ($data as $key => $value) {
            $foundValues = [];
            preg_match_all('/{{(.*?)}}/', $template, $matches); // Match all placeholders
            // Extract values from the $data array if the key exists in the template
            foreach ($matches[1] as $key1) {

                if (isset($data->$key1)) {
                    $foundValues[$key1] = $data->$key1; // Add to foundValues array   
                    
                }
            } 
        }
        return $foundValues;
    }


















	
	
	

}
