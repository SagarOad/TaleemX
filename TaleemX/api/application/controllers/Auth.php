<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('auth_model');
    }

    public function login()
    {
        $method = $this->input->server('REQUEST_METHOD');

        if ($method != 'POST') {
            json_output(400, array('status' => 400, 'message' => 'Bad request.'));
        } else {
            $check_auth_client = $this->auth_model->check_auth_client();
            if ($check_auth_client == true) {
                $params = json_decode(file_get_contents('php://input'), true);
                if (!is_array($params)) {
                    json_output(400, array('status' => 400, 'message' => 'Invalid JSON body.'));
                }

                $username = isset($params['username']) ? trim((string) $params['username']) : '';
                $password = isset($params['password']) ? (string) $params['password'] : '';
                // Push token is registered only via webservice/registerDevice (optional legacy: deviceToken).
                $app_key = '';
                if (!empty($params['deviceToken'])) {
                    $app_key = trim((string) $params['deviceToken']);
                }

                if ($username === '' || $password === '') {
                    json_output(400, array('status' => 400, 'message' => 'username and password are required.'));
                }

                $response = $this->auth_model->login($username, $password, $app_key);
                json_output(200, $response);
            }
        }
    }

}
