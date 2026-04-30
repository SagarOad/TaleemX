<?php

defined('BASEPATH') or exit('No direct script access allowed');

// use \Firebase\JWT\JWT;

require_once APPPATH . 'third_party/omnipay/vendor/autoload.php';

class Zoom_api
{

    /** @var CI_Controller */
    public $CI;

    /** @var string */
    private $zoom_api_key    = '';

    /** @var string */
    private $zoom_api_secret = '';

    /** @var string */
    private $REDIRECT_URI;

    /** @var string */
    private $REDIRECT_STAFF_URI;

    /** @var \GuzzleHttp\Client */
    private $CLIENT;

    public function __construct($parameters = array())

    {
        $this->CI = &get_instance();
        $this->CI->load->library('session');
        $this->REDIRECT_URI = base_url() . 'admin/conference/generatetoken';
        $this->REDIRECT_STAFF_URI = base_url() . 'admin/conference/stafftoken';

        $this->CLIENT = new \GuzzleHttp\Client(['base_uri' => 'https://api.zoom.us']);
        $this->CI = &get_instance();
        $this->CI->load->model('zoomsetting_model');
        if (!empty($parameters)) {
            $this->zoom_api_key    = $parameters['zoom_api_key'];
            $this->zoom_api_secret = $parameters['zoom_api_secret'];
            if ($this->zoom_api_key == "" && $this->zoom_api_secret == "") {
                $setting_result = $this->CI->zoomsetting_model->get();
                if (!empty($setting_result)) {
                    $this->zoom_api_key    = $setting_result->zoom_api_key;
                    $this->zoom_api_secret = $setting_result->zoom_api_secret;
                }
            }
        }
    }


    public function oAuthUrl()
    {
        return "https://zoom.us/oauth/authorize?response_type=code&client_id={$this->zoom_api_key}&redirect_uri={$this->REDIRECT_URI}";
    }


    public function oAuthStaffUrl()
    {
        return "https://zoom.us/oauth/authorize?response_type=code&client_id={$this->zoom_api_key}&redirect_uri={$this->REDIRECT_STAFF_URI}";
    }


    public function token($code, $redirect = "Admin")
    {
        $msg = [];

        try {

            $response = $this->CLIENT->request('POST', '/oauth/token', [
                "headers" => [
                    "Authorization" => "Basic " . base64_encode($this->zoom_api_key . ':' . $this->zoom_api_secret)
                ],
                'form_params' => [
                    "grant_type" => "authorization_code",
                    "code" => $code,
                    "redirect_uri" => ($redirect == "Admin") ? $this->REDIRECT_URI : $this->REDIRECT_STAFF_URI
                ],
            ]);


            $token = json_decode($response->getBody()->getContents(), true);
            $msg = ['status' => 1, 'msg' => "Access token generated successfully.", 'token' => json_encode($token)];
        } catch (Exception $e) {
            $msg = ['status' => 0, 'msg' => 'Access token invalid or missing'];
        }

        return $msg;
    }

    // public function getAccessToken()
    // {
    // $arr_token = json_decode($this->CI->session->zoom_access_token);

    // return $accessToken = $arr_token->access_token;
    // }

    public function getAccessToken()
    {
        $tokenJson = $this->CI->session->userdata('zoom_access_token');

        if (empty($tokenJson)) {
            return null;
        }

        $arr_token = json_decode($tokenJson);

        return $arr_token->access_token ?? null;
    }


    public function getRefreshToken()
    {

        // $arr_token = json_decode($this->CI->session->zoom_access_token);

        // $refreshToken = $arr_token->refresh_token;


        $tokenJson = $this->CI->session->userdata('zoom_access_token');

        if (empty($tokenJson)) {
            return false;
        }

        $arr_token = json_decode($tokenJson);

        if (empty($arr_token->refresh_token)) {
            return false;
        }

        $refreshToken = $arr_token->refresh_token;


        try {
            $response = $this->CLIENT->request('POST', '/oauth/token', [
                "headers" => [
                    "Authorization" => "Basic " . base64_encode($this->zoom_api_key . ':' . $this->zoom_api_secret)
                ],
                'form_params' => [
                    "grant_type" => "refresh_token",
                    "refresh_token" => $refreshToken
                ],
            ]);
            $response_token = json_decode($response->getBody()->getContents(), true);

            $this->CI->session->unset_userdata('zoom_access_token');
            $this->CI->session->set_userdata('zoom_access_token', json_encode($response_token));

            return true;
        } catch (\Exception $e) {
            echo 'Failed during refresh token ' . $e->getMessage();
            return false;
        }
    }

    public function createAMeeting($json = [], $user_id = 'me')
    {

        $post_time           = $json['date'];
        $start_time          = date("Y-m-d\TH:i:s", strtotime($post_time));
        $createAMeetingArray = array();
        if (!empty($json['alternative_host_ids'])) {
            if (count($json['alternative_host_ids']) > 1) {
                $alternative_host_ids = implode(",", $json['alternative_host_ids']);
            } else {
                $alternative_host_ids = $json['alternative_host_ids'][0];
            }
        }
        $createAMeetingArray['topic']      = $json['title'];
        $createAMeetingArray['agenda']     = !empty($json['agenda']) ? $json['agenda'] : "";
        $createAMeetingArray['type']       = !empty($json['type']) ? $json['type'] : 2; //Scheduled
        $createAMeetingArray['start_time'] = $start_time;
        $createAMeetingArray['timezone']   = $json['timezone'];
        $createAMeetingArray['password']   = !empty($json['password']) ? $json['password'] : "";
        $createAMeetingArray['duration']   = !empty($json['duration']) ? $json['duration'] : 60;
        $createAMeetingArray['settings']   = array(
            'join_before_host'  => !empty($json['join_before_host']) ? true : false,
            'host_video'        => !empty($json['host_video']) ? true : false,
            'participant_video' => !empty($json['client_video']) ? true : false,
            'mute_upon_entry'   => !empty($json['option_mute_participants']) ? true : false,
            'enforce_login'     => !empty($json['option_enforce_login']) ? true : false,
            'auto_recording'    => !empty($json['option_auto_recording']) ? $json['option_auto_recording'] : "none",
            'alternative_hosts' => isset($alternative_host_ids) ? $alternative_host_ids : "",
        );
        //==========================

        try {
            $response = $this->CLIENT->request('POST', "/v2/users/{$user_id}/meetings", [
                "headers" => [
                    "Authorization" => "Bearer " . $this->getAccessToken()
                ],
                'json' => $createAMeetingArray
            ]);

            if ($response->getStatusCode() == 201) {
                return array('status' => true, 'data' => json_decode($response->getBody(), true));
            }

            throw new Exception("Not able to find error");
        } catch (\Exception $e) {
            if ($e->getCode() == 401 && $this->getRefreshToken()) {
                return $this->createMeeting($json, $user_id);
            }
            if ($e->getCode() == 300) {
                return array('status' => false, 'message' => 'Invalid enforce_login_domains, separate multiple domains by semicolon. A maximum of {rateLimitNumber} meetings can be created/updated for a single user in one day.');
            }
            if ($e->getCode() == 404) {
                return array('status' => false, 'message' => 'User {userId} not exist or not belong to this account.');
            }
            if ($e->getCode() != 401) {
                return array('status' => false, 'message' => $e->getMessage());
            }
            return array('status' => false, 'message' => 'Not able to refresh token');
        }
    }

    public function deleteMeeting($meeting_id = '', $query = [])
    {

        try {
            $response = $this->CLIENT->request('DELETE', "/v2/meetings/{$meeting_id}", [
                "headers" => [
                    "Authorization" => "Bearer " . $this->getAccessToken()
                ],
                'query' => $query
            ]);

            if ($response->getStatusCode() == 204) {
                return array('status' => true, 'message' => 'Meeting deleted.');
            }
            throw new Exception("Not able to find error");
        } catch (\Exception $e) {
            if ($e->getCode() == 401 && $this->getRefreshToken()) {
                return $this->deleteMeeting($meeting_id, $query);
            }
            if ($e->getCode() == 400) {
                return array('status' => false, 'message' => 'User does not belong to this account or dont have access');
            }
            if ($e->getCode() == 404) {
                return array('status' => false, 'message' => 'Meeting with this {meetingId} is not found or has expired.');
            }
            if ($e->getCode() != 401) {
                return array('status' => false, 'message' => $e->getMessage());
            }
            return array('status' => false, 'message' => 'Not able to refresh token');
        }
    }

    public function getApiKey()
    {
        return $this->zoom_api_key;
    }

    public function getApiSecret()
    {
        return $this->zoom_api_secret;
    }

    public function getMeeting($meetingId, $user_id = 'me', $query = [])
    {

        try {
            $response = $this->CLIENT->request('GET', "/v2/meetings/" . $meetingId, [
                "headers" => [
                    "Authorization" => "Bearer " . $this->getAccessToken()
                ],
                'query' => $query
            ]);

            return array('status' => true, 'data' => json_decode($response->getBody(), true));
        } catch (\Exception $e) {
            if ($e->getCode() == 401 && $this->getRefreshToken()) {
                return $this->getMeeting($meetingId, $user_id, $query);
            } else {
                return array('status' => false, 'message' => $e->getMessage());
            }
        }
    }
}
