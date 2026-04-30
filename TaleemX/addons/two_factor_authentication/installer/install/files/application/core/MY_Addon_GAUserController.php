<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Addon_GAUserController extends Student_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->config('google-authenticate-config');
        $this->load->library('google_authenticator');
        $this->load->model(array("google_authenticator/gauth_model","google_authenticator/gauthuser_model"));

    }

}