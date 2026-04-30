<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Addon_QRAttendanceController extends Admin_Controller
{

    public function __construct()
    {

        parent::__construct();
        $this->load->config('qrattendance-config');
        $this->load->model("qrattendance/qrsetting_model");
        $this->load->model("attendencetype_model");
    
      

        
        if ($this->uri->segment(2) == "qrattendance" && ($this->router->fetch_class() == "setting" xor $this->router->fetch_method() == "index")) {

            $this->auth->addonchk('ssqra', site_url('admin/qrattendance/setting'));
        }


        if ($this->uri->segment(2) == "qrattendance" && $this->router->fetch_class() != "setting") {
            

            $this->auth->addonchk('ssqra', site_url('admin/qrattendance/setting'));
        } elseif ($this->uri->segment(2) != "qrattendance") {

            redirect('admin/unauthorized');
        }
        

    }

}
