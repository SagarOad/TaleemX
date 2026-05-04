<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once(APPPATH . 'third_party/omnipay/vendor/autoload.php');

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;

/**
 * API-side mirror of the portal's Pushnotification library. Reads the same
 * `push_notification_config` table only (legacy on-disk JSON disabled).
 *
 * Public surface mirrors the portal library so call sites in either
 * application can be written identically:
 *
 *   $this->pushnotification->send($token, ['title' => ..., 'body' => ...], $action);
 *   $this->pushnotification->sendMany($tokens, $msg, $action);
 *
 * See application/libraries/Pushnotification.php for the design rationale.
 */
class Pushnotification
{
    /** @var CI_Controller */
    private $CI;

    private $defaultProjectId = 'taalimx-50c28';
    private $scope            = 'https://www.googleapis.com/auth/firebase.messaging';
    private $config;

    private static $tokenCache = array();

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    public function send($token, $msg, $action = '', $extra = array())
    {
        $token = trim((string) $token);
        if ($token === '') {
            return false;
        }

        try {
            $config = $this->loadConfig();
        } catch (\Throwable $e) {
            log_message('error', 'Pushnotification(api) config load failed: ' . $e->getMessage());
            return false;
        }

        if (empty($config['credentials'])) {
            log_message('error', 'Pushnotification(api) credentials not available; skipping send.');
            $this->logAttempt(array(
                'device_token' => $token,
                'title'        => isset($msg['title']) ? $msg['title'] : '',
                'body'         => isset($msg['body']) ? $msg['body'] : '',
                'action'       => (string) $action,
                'status'       => 'skipped',
                'error'        => 'credentials_missing',
            ));
            return false;
        }

        if (empty($config['is_enabled']) && empty($config['credentials_from_file'])) {
            $this->logAttempt(array(
                'device_token' => $token,
                'title'        => isset($msg['title']) ? $msg['title'] : '',
                'body'         => isset($msg['body']) ? $msg['body'] : '',
                'action'       => (string) $action,
                'status'       => 'disabled',
                'error'        => 'fcm_disabled_in_settings',
            ));
            return false;
        }

        $accessToken = $this->getAccessToken($config);
        if (empty($accessToken)) {
            return false;
        }

        $payload = $this->buildPayload($token, $msg, $action, $extra, $config);
        $url     = 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($config['project_id']) . '/messages:send';

        $response = $this->postToFcm($url, $accessToken, $payload);
        if ($response === false) {
            $this->logAttempt(array(
                'device_token' => $token,
                'title'        => isset($msg['title']) ? $msg['title'] : '',
                'body'         => isset($msg['body']) ? $msg['body'] : '',
                'action'       => (string) $action,
                'payload'      => $payload,
                'status'       => 'failed',
                'error'        => 'transport_error',
            ));
            return false;
        }

        $decoded = json_decode($response, true);
        $status  = 'failed';
        $error   = null;
        $msgId   = null;

        if (is_array($decoded) && isset($decoded['name'])) {
            $status = 'success';
            $msgId  = $decoded['name'];
        } elseif (is_array($decoded) && isset($decoded['error'])) {
            $errStatus = isset($decoded['error']['status']) ? (string) $decoded['error']['status'] : '';
            $errMsg    = isset($decoded['error']['message']) ? (string) $decoded['error']['message'] : '';
            $error     = trim($errStatus . ' ' . $errMsg);

            if ($this->isInvalidTokenError($errStatus, $decoded['error'])) {
                $status = 'invalid_token';
                $this->clearStaleToken($token);
            }
        }

        $this->logAttempt(array(
            'device_token'   => $token,
            'title'          => isset($msg['title']) ? $msg['title'] : '',
            'body'           => isset($msg['body']) ? $msg['body'] : '',
            'action'         => (string) $action,
            'payload'        => $payload,
            'status'         => $status,
            'error'          => $error,
            'fcm_message_id' => $msgId,
        ));

        return $response;
    }

    public function sendMany($tokens, $msg, $action = '', $extra = array())
    {
        $results = array();
        if (!is_array($tokens)) {
            return $results;
        }
        foreach ($tokens as $tok) {
            $tok = trim((string) $tok);
            if ($tok === '') {
                continue;
            }
            $results[$tok] = $this->send($tok, $msg, $action, $extra);
        }
        return $results;
    }

    public function to($data)
    {
        if (!is_array($data) || empty($data['token'])) {
            return false;
        }
        $msg = array(
            'title' => isset($data['title']) ? $data['title'] : '',
            'body'  => isset($data['body']) ? $data['body'] : '',
        );
        $action = isset($data['action']) ? $data['action'] : '';
        return $this->send($data['token'], $msg, $action);
    }

    private function loadConfig()
    {
        if (is_array($this->config)) {
            return $this->config;
        }

        $resolved = array(
            'is_enabled'             => 0,
            'project_id'             => null,
            'credentials'            => null,
            'credentials_from_file'  => false,
            'default_channel_id'     => 'default_channel',
            'default_sound'          => 'default',
            'default_click_action'   => null,
        );

        try {
            $this->CI->load->model('push_notification_config_model');
            $row = $this->CI->push_notification_config_model->get();
            if (!empty($row)) {
                $resolved['is_enabled']           = !empty($row['is_enabled']) ? 1 : 0;
                $resolved['default_channel_id']   = !empty($row['default_channel_id']) ? $row['default_channel_id'] : 'default_channel';
                $resolved['default_sound']        = !empty($row['default_sound']) ? $row['default_sound'] : 'default';
                $resolved['default_click_action'] = !empty($row['default_click_action']) ? $row['default_click_action'] : null;

                if (!empty($row['service_account_json'])) {
                    $decoded = json_decode($row['service_account_json'], true);
                    if (is_array($decoded) && !empty($decoded['client_email']) && !empty($decoded['private_key'])) {
                        $resolved['credentials'] = $decoded;
                        if (!empty($decoded['project_id'])) {
                            $resolved['project_id'] = $decoded['project_id'];
                        } elseif (!empty($row['project_id'])) {
                            $resolved['project_id'] = $row['project_id'];
                        }
                    } else {
                        log_message('error', 'Pushnotification(api): service_account_json in DB is not valid JSON.');
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Pushnotification(api): could not read push_notification_config: ' . $e->getMessage());
        }

        // Legacy on-disk service account (old Firebase project) intentionally not loaded — use admin UI only.

        if (empty($resolved['project_id'])) {
            $resolved['project_id'] = $this->defaultProjectId;
        }

        $this->config = $resolved;
        return $this->config;
    }

    private function getAccessToken(array $config)
    {
        $clientEmail = isset($config['credentials']['client_email']) ? $config['credentials']['client_email'] : '';
        if ($clientEmail === '') {
            return null;
        }

        $cached = isset(self::$tokenCache[$clientEmail]) ? self::$tokenCache[$clientEmail] : null;
        if (is_array($cached) && !empty($cached['access_token']) && !empty($cached['expires_at']) && $cached['expires_at'] > time() + 30) {
            return $cached['access_token'];
        }

        try {
            $credentials = new ServiceAccountCredentials($this->scope, $config['credentials']);
            $token       = $credentials->fetchAuthToken(HttpHandlerFactory::build());

            if (empty($token['access_token'])) {
                log_message('error', 'Pushnotification(api): access token could not be generated.');
                return null;
            }

            $expiresIn = isset($token['expires_in']) ? (int) $token['expires_in'] : 3600;
            self::$tokenCache[$clientEmail] = array(
                'access_token' => $token['access_token'],
                'expires_at'   => time() + max(60, $expiresIn - 60),
            );
            return $token['access_token'];
        } catch (\Throwable $e) {
            log_message('error', 'Pushnotification(api) auth failed: ' . $e->getMessage());
            return null;
        }
    }

    private function plainTextForPush($text)
    {
        $text = (string) $text;
        if ($text === '') {
            return '';
        }
        $text = preg_replace('/<\s*br\s*\/?>/i', ' ', $text);
        $text = preg_replace('/<\/\s*p\s*>/i', ' ', $text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }

    private function buildPayload($token, array $msg, $action, array $extra, array $config)
    {
        $title = $this->plainTextForPush(isset($msg['title']) ? $msg['title'] : '');
        $body  = $this->plainTextForPush(isset($msg['body']) ? $msg['body'] : '');

        $data = array(
            'title'  => $title,
            'body'   => $body,
            'action' => (string) $action,
            'sound'  => 'mySound',
        );
        if (!empty($extra) && is_array($extra)) {
            foreach ($extra as $k => $v) {
                if ($v === null) {
                    continue;
                }
                $data[(string) $k] = is_scalar($v) ? (string) $v : json_encode($v);
            }
        }

        $message = array(
            'token'        => $token,
            'notification' => array(
                'title' => $title,
                'body'  => $body,
            ),
            'data'         => $data,
            'android'      => array(
                'priority'     => 'high',
                'notification' => array(
                    'channel_id' => !empty($config['default_channel_id']) ? $config['default_channel_id'] : 'default_channel',
                    'sound'      => !empty($config['default_sound']) ? $config['default_sound'] : 'default',
                ),
            ),
            'apns'         => array(
                'headers' => array(
                    'apns-priority' => '10',
                ),
                'payload' => array(
                    'aps' => array(
                        'sound'             => !empty($config['default_sound']) ? $config['default_sound'] : 'default',
                        'content-available' => 1,
                    ),
                ),
            ),
        );

        if (!empty($config['default_click_action'])) {
            $message['android']['notification']['click_action'] = $config['default_click_action'];
            $message['apns']['payload']['aps']['category']      = $config['default_click_action'];
        }

        return array('message' => $message);
    }

    private function postToFcm($url, $accessToken, array $body)
    {
        $headers = array(
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);

        $result = curl_exec($ch);

        if ($result === false) {
            $error = curl_error($ch);
            $info  = curl_getinfo($ch);
            curl_close($ch);
            log_message('error', 'Pushnotification(api) cURL failed: ' . $error . ' info=' . json_encode($info));
            return false;
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 500) {
            log_message('error', 'Pushnotification(api) FCM 5xx: http=' . $httpCode . ' body=' . $result);
        }

        return $result;
    }

    private function isInvalidTokenError($status, array $errorBlock)
    {
        $status = strtoupper((string) $status);
        if (in_array($status, array('UNREGISTERED', 'NOT_FOUND'), true)) {
            return true;
        }

        if (!empty($errorBlock['details']) && is_array($errorBlock['details'])) {
            foreach ($errorBlock['details'] as $detail) {
                if (!is_array($detail)) {
                    continue;
                }
                $errorCode = isset($detail['errorCode']) ? strtoupper((string) $detail['errorCode']) : '';
                if (in_array($errorCode, array('UNREGISTERED', 'INVALID_ARGUMENT', 'SENDER_ID_MISMATCH'), true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function clearStaleToken($token)
    {
        if (!is_string($token) || $token === '') {
            return;
        }
        try {
            if ($this->CI->db->table_exists('students')) {
                $this->CI->db->where('app_key', $token)->update('students', array('app_key' => null));
                $this->CI->db->where('parent_app_key', $token)->update('students', array('parent_app_key' => null));
            }
        } catch (\Throwable $e) {
            log_message('error', 'Pushnotification(api): failed to clear stale token: ' . $e->getMessage());
        }
    }

    private function logAttempt(array $data)
    {
        try {
            $this->CI->load->model('push_notification_config_model');
            $this->CI->push_notification_config_model->writeAttempt($data);
        } catch (\Throwable $e) {
            log_message('error', 'Pushnotification(api): log write failed: ' . $e->getMessage());
        }
    }
}
