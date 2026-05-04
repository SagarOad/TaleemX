<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Admin UI for Push Notification (FCM) settings.
 *
 * - GET  admin/pushnotificationsetting           => index()
 *        Renders the settings + test-send page.
 * - POST admin/pushnotificationsetting/save      => save()
 *        Persists project_id, service-account JSON (file upload OR pasted),
 *        enabled toggle, default channel/sound/click_action.
 * - POST admin/pushnotificationsetting/sendtest  => sendtest()
 *        Sends a test push to the supplied device token and returns both a
 *        clean status message and the raw FCM response.
 *
 * Reuses the existing `notification_setting` privilege so no RBAC migration
 * is needed.
 */
class Pushnotificationsetting extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('push_notification_config_model');
    }

    public function index()
    {
        if (!$this->rbac->hasPrivilege('notification_setting', 'can_view')) {
            access_denied();
        }

        $this->session->set_userdata('top_menu', 'System Settings');
        $this->session->set_userdata('sub_menu', 'pushnotificationsetting/index');

        $data['title']  = $this->lang->line('push_notification_setting');
        $data['config'] = $this->push_notification_config_model->get();

        $this->load->view('layout/header', $data);
        $this->load->view('admin/pushnotificationsetting/index', $data);
        $this->load->view('layout/footer', $data);
    }

    public function save()
    {
        if (!$this->rbac->hasPrivilege('notification_setting', 'can_edit')) {
            access_denied();
        }

        $isEnabled         = (int) $this->input->post('is_enabled');
        $projectId         = trim((string) $this->input->post('project_id'));
        $defaultChannelId  = trim((string) $this->input->post('default_channel_id'));
        $defaultSound      = trim((string) $this->input->post('default_sound'));
        $defaultClickAction = trim((string) $this->input->post('default_click_action'));
        $jsonPasted        = (string) $this->input->post('service_account_json');

        $existing = $this->push_notification_config_model->get();
        $jsonToStore = !empty($existing['service_account_json']) ? $existing['service_account_json'] : null;

        // 1) File upload takes precedence
        if (isset($_FILES['service_account_file']) && !empty($_FILES['service_account_file']['name']) && $_FILES['service_account_file']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['service_account_file']['tmp_name'];
            if (is_uploaded_file($tmpPath)) {
                $size = (int) $_FILES['service_account_file']['size'];
                if ($size > 0 && $size < 1048576) {
                    $contents = @file_get_contents($tmpPath);
                    $parsed   = $contents !== false ? json_decode($contents, true) : null;
                    if (!$this->isValidServiceAccount($parsed)) {
                        $this->session->set_flashdata('msg', '<div class="alert alert-danger">' . $this->lang->line('invalid_service_account_json') . '</div>');
                        redirect('admin/pushnotificationsetting');
                        return;
                    }
                    $jsonToStore = $contents;
                    if ($projectId === '' && !empty($parsed['project_id'])) {
                        $projectId = $parsed['project_id'];
                    }
                } else {
                    $this->session->set_flashdata('msg', '<div class="alert alert-danger">' . $this->lang->line('invalid_service_account_json') . '</div>');
                    redirect('admin/pushnotificationsetting');
                    return;
                }
            }
        } elseif ($jsonPasted !== '') {
            // 2) Pasted JSON in the textarea
            $parsed = json_decode($jsonPasted, true);
            if (!$this->isValidServiceAccount($parsed)) {
                $this->session->set_flashdata('msg', '<div class="alert alert-danger">' . $this->lang->line('invalid_service_account_json') . '</div>');
                redirect('admin/pushnotificationsetting');
                return;
            }
            $jsonToStore = $jsonPasted;
            if ($projectId === '' && !empty($parsed['project_id'])) {
                $projectId = $parsed['project_id'];
            }
        }

        $payload = array(
            'is_enabled'           => $isEnabled ? 1 : 0,
            'project_id'           => $projectId !== '' ? $projectId : null,
            'service_account_json' => $jsonToStore,
            'default_channel_id'   => $defaultChannelId !== '' ? $defaultChannelId : 'default_channel',
            'default_sound'        => $defaultSound !== '' ? $defaultSound : 'default',
            'default_click_action' => $defaultClickAction !== '' ? $defaultClickAction : null,
        );

        $saved = $this->push_notification_config_model->save($payload);
        if ($saved === false) {
            $this->session->set_flashdata('msg', '<div class="alert alert-danger">' . $this->lang->line('push_notification_config_table_missing') . '</div>');
        } else {
            $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $this->lang->line('update_message') . '</div>');
        }

        redirect('admin/pushnotificationsetting');
    }

    public function sendtest()
    {
        if (!$this->rbac->hasPrivilege('notification_setting', 'can_edit')) {
            return $this->jsonResponse(array(
                'status'  => 0,
                'message' => $this->lang->line('access_denied') ?: 'Access denied',
            ));
        }

        $token = trim((string) $this->input->post('test_token'));
        $title = trim((string) $this->input->post('test_title'));
        $body  = trim((string) $this->input->post('test_body'));

        if ($token === '' || $title === '' || $body === '') {
            return $this->jsonResponse(array(
                'status'  => 0,
                'message' => $this->lang->line('required_fields_missing') ?: 'Token, title and body are required.',
            ));
        }

        $rawResponse = $this->pushnotification->send($token, array('title' => $title, 'body' => $body), 'admin_test');

        // Read the audit log entry the library just wrote so we can surface
        // the precise reason inline (no need for the admin to grep log files).
        $diagnostic = $this->fetchLastTestDiagnostic();

        if ($rawResponse === false) {
            $reason = $this->humanizeReason($diagnostic);
            return $this->jsonResponse(array(
                'status'      => 0,
                'message'     => 'Test push failed' . ($reason !== '' ? ': ' . $reason : '. Check application/logs/ for details.'),
                'raw'         => null,
                'diagnostic'  => $diagnostic,
            ));
        }

        $decoded = json_decode($rawResponse, true);
        if (is_array($decoded) && isset($decoded['name'])) {
            return $this->jsonResponse(array(
                'status'         => 1,
                'message'        => $this->lang->line('test_push_sent') ?: 'Test push sent successfully.',
                'fcm_message_id' => $decoded['name'],
                'raw'            => $rawResponse,
                'diagnostic'     => $diagnostic,
            ));
        }

        $errStatus  = '';
        $errMessage = '';
        if (is_array($decoded) && isset($decoded['error'])) {
            $errStatus  = isset($decoded['error']['status']) ? (string) $decoded['error']['status'] : '';
            $errMessage = isset($decoded['error']['message']) ? (string) $decoded['error']['message'] : '';
        }

        return $this->jsonResponse(array(
            'status'     => 0,
            'message'    => trim(($this->lang->line('test_push_failed') ?: 'Test push failed') . ': ' . $errStatus . ' ' . $errMessage),
            'raw'        => $rawResponse,
            'diagnostic' => $diagnostic,
        ));
    }

    private function fetchLastTestDiagnostic()
    {
        try {
            if (!$this->db->table_exists('notification_log')) {
                return null;
            }
            $row = $this->db->select('status, error')
                ->from('notification_log')
                ->where('action', 'admin_test')
                ->order_by('id', 'desc')
                ->limit(1)
                ->get()
                ->row_array();
            return !empty($row) ? $row : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function humanizeReason($diagnostic)
    {
        if (empty($diagnostic) || empty($diagnostic['error'])) {
            return '';
        }
        $err = (string) $diagnostic['error'];
        switch ($err) {
            case 'credentials_missing':
                return 'No Firebase credentials are configured. Upload your service-account JSON in the Configuration card.';
            case 'fcm_disabled_in_settings':
                return 'Status is set to Disabled. Enable it in the Configuration card and save.';
            case 'transport_error':
                return 'Network/SSL error reaching FCM. On XAMPP this is usually a stale CA bundle (curl.cainfo in php.ini). See application/logs/ for the cURL error.';
            default:
                return $err;
        }
    }

    private function isValidServiceAccount($parsed)
    {
        return is_array($parsed)
            && !empty($parsed['client_email'])
            && !empty($parsed['private_key'])
            && (isset($parsed['type']) ? $parsed['type'] === 'service_account' : true);
    }

    private function jsonResponse(array $data)
    {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($data));
    }
}
