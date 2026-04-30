<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

use Omnipay\Omnipay;

require_once(APPPATH . 'third_party/omnipay/vendor/autoload.php');

class Course_paypal_payment {

    private $_CI;
    public $api_config;
    public $currency;

    function __construct() {
        $this->_CI = & get_instance();
        $this->api_config = $this->_CI->paymentsetting_model->getActiveMethod();
        $this->currency = $this->_CI->setting_model->getCurrency();
    }

    public function payment($data, $pay_mode = "parent") {

       
        $name = $data['name'];
        $amount_balance = $data['amount'];
        $currency = $data['currency'];
        
        $gateway = Omnipay::create('PayPal_Express');
        $gateway->setUsername($this->api_config->api_username);
        $gateway->setPassword($this->api_config->api_password);
        $gateway->setSignature($this->api_config->api_signature);
        $gateway->setTestMode(FALSE);

        $params = array(
            'course_name' => $data['course_name'],
            'name' => $name,
            'description' =>$data["description"],
            'amount' => number_format($amount_balance, 2, '.', ''),
            'currency' => $currency,
        );
        if ($pay_mode == "parent") {
            $params['cancelUrl'] =$data['cancelUrl'];
            $params['returnUrl'] = $data['returnUrl'];
        } else {
            $params['cancelUrl'] =$data['cancelUrl'];
            $params['returnUrl'] = $data['returnUrl'];
        }
        $response = $gateway->purchase($params)->send();
        return $response;
    }

    public function success($data, $pay_mode = "parent") {

       
        $name = $data['name'];
        $amount_balance = $data['amount'];
        $currency = $data['currency'];
       

        $gateway = Omnipay::create('PayPal_Express');
        $gateway->setUsername($this->api_config->api_username);
        $gateway->setPassword($this->api_config->api_password);
        $gateway->setSignature($this->api_config->api_signature);
        $gateway->setTestMode(FALSE);


         $params = array(
            'course_name' => $data['course_name'],
            'name' => $name,
            'description' =>$data["description"],
            'amount' => number_format($amount_balance, 2, '.', ''),
            'currency' => $currency,
        );
        if ($pay_mode == "parent") {
            $params['cancelUrl'] =$data['cancelUrl'];
            $params['returnUrl'] = $data['returnUrl'];
        } else {
            $params['cancelUrl'] =$data['cancelUrl'];
            $params['returnUrl'] = $data['returnUrl'];
        }
        $response = $gateway->completePurchase($params)->send();

        return $response;
    }

}

?>