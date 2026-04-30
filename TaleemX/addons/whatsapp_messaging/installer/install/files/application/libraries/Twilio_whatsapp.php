<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Twilio_whatsapp {
	
    private $_CI;
    private $session_name;
    private $username;
    private $password;
    private $api_version;
    private $number;
    private $whatsapp_template_id;
    

    function __construct($params) { 
        $this->_CI = & get_instance();
        $this->session_name = $this->_CI->setting_model->getCurrentSessionName(); 
        $this->username = $params['username'];
        $this->password = $params['password'];
        $this->api_version = $params['api_version'];
        $this->number = $params['number'];
        $this->whatsapp_template_id = $params['whatsapp_template_id'];
        
    } 

    function send($from, $to, $message) {			 
		
		    $sl= 0;			
			
			foreach ($message as $index => $value) { $sl++;
				$formatted_array[$sl] = trim((string)$value);
			}
          
            $post_data=[
                'To'=>'whatsapp:'.$to,
                'From'=>'whatsapp:'.$this->number,
                'ContentSid'=>$this->whatsapp_template_id,
                'ContentVariables'=>json_encode($formatted_array)
            ];	 
 
        $curl = curl_init();
        $url='https://api.twilio.com/'.$this->api_version.'/Accounts/'.$this->username.'/Messages.json';

        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt( $ch, CURLOPT_POST, TRUE ); 
    
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );       

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

		curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password"); //Your credentials goes here
		 
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));        

        $response = curl_exec($ch);        
 
		if (curl_errno($ch)) {
       
			return 'Error: ' . curl_error($ch);
		} else {
			 
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($httpCode >= 400) {				 
				return 'Error: Twilio returned HTTP code ' . $httpCode . ' Response: ' . $response;
			}
			return 'Response: ' . $response;
		}
    }		

}

?>