<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class MY_Addon_GAController extends Admin_Controller
{

    public function __construct()
    {

        parent::__construct();
        $this->load->config('google-authenticate-config');
        $this->load->library('google_authenticator');
        $this->load->model(array("google_authenticator/gauth_model", "google_authenticator/gauthuser_model"));

        if ($this->uri->segment(2) == "gauthenticate" && ($this->router->fetch_class() == "admin" && $this->router->fetch_method() != "index")) {
          
            $this->auth->addonchk('sstfa', site_url('admin/gauthenticate/admin'));
            
        }elseif ($this->uri->segment(2) != "gauthenticate") {
            
             redirect('admin/unauthorized');
             
        }        

    }

}