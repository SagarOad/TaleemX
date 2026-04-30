<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Ask AI controller.
 *
 * UI route:
 *   GET  /admin/askai
 *
 * API proxy route (same-origin for browser safety):
 *   POST /admin/askai/ask
 *   body: { "question": "..." }
 */
class Askai extends Admin_Controller
{
    const CONNECT_TIMEOUT_SEC = 6;
    const REQUEST_TIMEOUT_SEC = 35;

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->session->set_userdata('top_menu', 'Ask AI');
        $this->session->set_userdata('sub_menu', 'admin/askai');

        $this->load->view('layout/header');
        $this->load->view('admin/askai/index');
        $this->load->view('layout/footer');
    }

    public function ask()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->load->config('askai', true);

        $raw_input = file_get_contents('php://input');
        $payload   = json_decode((string) $raw_input, true);
        if (!is_array($payload)) {
            $payload = array();
        }

        $question = isset($payload['question']) ? trim((string) $payload['question']) : '';
        if ($question === '') {
            http_response_code(400);
            echo json_encode(array('error' => 'Question is required.'));
            return;
        }

        $api_url = (string) $this->config->item('askai_api_url', 'askai');
        if ($api_url === '') {
            http_response_code(500);
            echo json_encode(array('error' => 'Ask AI endpoint is not configured.'));
            return;
        }

        $ch = curl_init($api_url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(array('question' => $question)),
            CURLOPT_HTTPHEADER     => array('Content-Type: application/json', 'Accept: application/json'),
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_SEC,
            CURLOPT_TIMEOUT        => self::REQUEST_TIMEOUT_SEC,
        ));

        $response_body = curl_exec($ch);
        $curl_error    = curl_error($ch);
        $status_code   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response_body === false) {
            http_response_code(502);
            echo json_encode(array('error' => 'AI service unreachable: ' . $curl_error));
            return;
        }

        $decoded = json_decode((string) $response_body, true);
        if (!is_array($decoded)) {
            http_response_code(502);
            echo json_encode(array('error' => 'AI service returned invalid JSON.'));
            return;
        }

        if ($status_code >= 200 && $status_code < 300) {
            echo json_encode(array('answer' => isset($decoded['answer']) ? (string) $decoded['answer'] : ''));
            return;
        }

        http_response_code($status_code > 0 ? $status_code : 502);
        echo json_encode(array(
            'error' => isset($decoded['error']) ? (string) $decoded['error'] : 'AI request failed.',
        ));
    }
}
