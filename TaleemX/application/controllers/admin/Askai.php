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
    // Generous so the agent has room for retrieval + LLM call + execute on
    // cold-cache / first-call paths. The AI service itself enforces its own
    // per-step timeouts; this is just the outer ceiling.
    const REQUEST_TIMEOUT_SEC = 90;
    const REQUEST_TIMEOUT_ARABIC_SEC = 150;

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

        $respond_arabic = false;
        if (isset($payload['respond_arabic'])) {
            $flag = $payload['respond_arabic'];
            if (is_bool($flag)) {
                $respond_arabic = $flag;
            } else {
                $respond_arabic = in_array(
                    strtolower(trim((string) $flag)),
                    array('1', 'true', 'yes', 'on'),
                    true
                );
            }
        }

        $api_url = (string) $this->config->item('askai_api_url', 'askai');
        if ($api_url === '') {
            http_response_code(500);
            echo json_encode(array('error' => 'Ask AI endpoint is not configured.'));
            return;
        }

        $timeout_sec = $respond_arabic ? self::REQUEST_TIMEOUT_ARABIC_SEC : self::REQUEST_TIMEOUT_SEC;

        $ch = curl_init($api_url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(array(
                'question'       => $question,
                'respond_arabic' => $respond_arabic,
            )),
            CURLOPT_HTTPHEADER     => array('Content-Type: application/json', 'Accept: application/json'),
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_SEC,
            CURLOPT_TIMEOUT        => $timeout_sec,
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

        $body_str = (string) $response_body;
        if ($body_str !== '' && $body_str[0] === '<') {
            http_response_code(504);
            echo json_encode(array(
                'error' => 'AI service timed out or returned an error page. '
                    . 'Try again, use English for large reports, or ask a simpler question.',
            ));
            return;
        }

        $decoded = json_decode($body_str, true);
        if (!is_array($decoded)) {
            http_response_code(502);
            echo json_encode(array(
                'error' => 'AI service returned an invalid response. '
                    . 'If Arabic is enabled, wait and retry or switch to English.',
            ));
            return;
        }

        if ($status_code >= 200 && $status_code < 300) {
            // Pass the full AI payload through (presentation hints, chart data,
            // follow-up suggestions, source badge, module label, etc.) so the
            // frontend can render rich UI without making a second call.
            $passthrough = array(
                'answer'         => isset($decoded['answer']) ? (string) $decoded['answer'] : '',
                'request_id'     => isset($decoded['request_id']) ? (string) $decoded['request_id'] : '',
                'source'         => isset($decoded['source']) ? (string) $decoded['source'] : '',
                'status'         => isset($decoded['status']) ? (string) $decoded['status'] : '',
                'sql'            => isset($decoded['sql']) ? (string) $decoded['sql'] : '',
                'presentation'   => isset($decoded['presentation']) ? (string) $decoded['presentation'] : 'text',
                'structured_data'=> isset($decoded['structured_data']) ? $decoded['structured_data'] : null,
                'suggestions'    => isset($decoded['suggestions']) && is_array($decoded['suggestions'])
                    ? array_values($decoded['suggestions']) : array(),
                'intent'         => isset($decoded['intent']) ? (string) $decoded['intent'] : 'general',
                'module'         => isset($decoded['module']) ? (string) $decoded['module'] : 'general',
                'module_label'   => isset($decoded['module_label']) ? (string) $decoded['module_label'] : 'General',
            );
            echo json_encode($passthrough);
            return;
        }

        http_response_code($status_code > 0 ? $status_code : 502);
        echo json_encode(array(
            'error' => isset($decoded['error']) ? (string) $decoded['error'] : 'AI request failed.',
        ));
    }

    /**
     * Proxy 👍/👎 feedback to the AI service so a good answer can be
     * appended to the learned vector bank for future fast-path matching.
     *
     * POST /admin/askai/feedback
     * body: { "request_id": "...", "verdict": "good"|"bad", "note"?: "..." }
     */
    public function feedback()
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->load->config('askai', true);

        $raw_input = file_get_contents('php://input');
        $payload   = json_decode((string) $raw_input, true);
        if (!is_array($payload)) {
            $payload = array();
        }

        $request_id = isset($payload['request_id']) ? trim((string) $payload['request_id']) : '';
        $verdict    = isset($payload['verdict']) ? strtolower(trim((string) $payload['verdict'])) : '';
        $note       = isset($payload['note']) ? trim((string) $payload['note']) : '';

        if ($request_id === '' || !in_array($verdict, array('good', 'bad'), true)) {
            http_response_code(400);
            echo json_encode(array('error' => 'request_id and verdict (good|bad) are required.'));
            return;
        }

        $ask_url = (string) $this->config->item('askai_api_url', 'askai');
        if ($ask_url === '') {
            http_response_code(500);
            echo json_encode(array('error' => 'Ask AI endpoint is not configured.'));
            return;
        }
        // Derive the feedback URL from the ask URL: same host, /ask → /ask/feedback.
        $feedback_url = preg_replace('#/ask/?$#', '/ask/feedback', $ask_url);
        if ($feedback_url === $ask_url) {
            // /ask wasn't found in the URL; append it as a best-effort path.
            $feedback_url = rtrim($ask_url, '/') . '/feedback';
        }

        $ch = curl_init($feedback_url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(array(
                'request_id' => $request_id,
                'verdict'    => $verdict,
                'note'       => $note,
            )),
            CURLOPT_HTTPHEADER     => array('Content-Type: application/json', 'Accept: application/json'),
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_SEC,
            CURLOPT_TIMEOUT        => 15,
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

        http_response_code($status_code > 0 ? $status_code : 200);
        echo json_encode($decoded);
    }
}
