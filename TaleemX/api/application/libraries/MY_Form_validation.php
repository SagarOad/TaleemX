<?php

defined('BASEPATH') or exit('No direct script access allowed');

class MY_Form_validation extends CI_Form_validation
{
    public $CI;

    public function __construct()
    {
        parent::__construct();
        $this->CI = &get_instance();
        $this->CI->load->helper('saudi_phone');
    }

    /**
     * Saudi Arabia phone: +966 followed by 9 national digits (optional empty).
     */
    public function saudi_phone($str)
    {
        if ($str === null || $str === '') {
            return true;
        }

        return is_valid_saudi_e164_phone($str);
    }
}
