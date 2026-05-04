<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Reads/writes the single-row push_notification_config table that drives the
 * Firebase Cloud Messaging integration. This is the authoritative source of
 * the project_id and service-account JSON that Pushnotification uses.
 *
 * Also exposes a small helper for writing to the notification_log audit
 * table so the library and the test-send action share one code path.
 */
class Push_notification_config_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns the singleton config row as an associative array, or a default
     * (everything empty / disabled) when the table doesn't exist yet (eg. the
     * SQL migration hasn't been run). Never throws.
     */
    public function get()
    {
        if (!$this->db->table_exists('push_notification_config')) {
            return $this->emptyConfig();
        }

        $row = $this->db->select()->from('push_notification_config')->order_by('id', 'asc')->limit(1)->get()->row_array();
        if (empty($row)) {
            return $this->emptyConfig();
        }

        return $row;
    }

    /**
     * Upserts the single config row. $data may include any subset of the
     * configurable columns; missing columns are left untouched.
     */
    public function save($data)
    {
        if (!$this->db->table_exists('push_notification_config')) {
            return false;
        }

        $allowed = array(
            'is_enabled',
            'project_id',
            'service_account_json',
            'default_channel_id',
            'default_sound',
            'default_click_action',
        );
        $payload = array();
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $payload[$col] = $data[$col];
            }
        }
        if (empty($payload)) {
            return false;
        }

        $existing = $this->db->select('id')->from('push_notification_config')->order_by('id', 'asc')->limit(1)->get()->row_array();
        if (!empty($existing)) {
            $this->db->where('id', $existing['id'])->update('push_notification_config', $payload);
            return (int) $existing['id'];
        }

        $this->db->insert('push_notification_config', $payload);
        return (int) $this->db->insert_id();
    }

    /**
     * Append a row to the notification_log audit table. Best-effort: if the
     * table doesn't exist (migration not run) we silently skip so the caller
     * (typically Pushnotification::send) is never broken by logging.
     *
     * Named writeAttempt() (not log()) to avoid colliding with the audit
     * helper MY_Model::log($message, $record_id, $action).
     */
    public function writeAttempt($data)
    {
        if (!$this->db->table_exists('notification_log')) {
            return false;
        }

        $row = array(
            'user_id'        => isset($data['user_id']) ? $data['user_id'] : null,
            'user_type'      => isset($data['user_type']) ? $data['user_type'] : null,
            'device_token'   => isset($data['device_token']) ? mb_substr((string) $data['device_token'], 0, 500) : null,
            'title'          => isset($data['title']) ? mb_substr((string) $data['title'], 0, 255) : null,
            'body'           => isset($data['body']) ? (string) $data['body'] : null,
            'action'         => isset($data['action']) ? mb_substr((string) $data['action'], 0, 100) : null,
            'payload'        => isset($data['payload']) ? (is_string($data['payload']) ? $data['payload'] : json_encode($data['payload'])) : null,
            'status'         => isset($data['status']) ? mb_substr((string) $data['status'], 0, 20) : null,
            'error'          => isset($data['error']) ? (string) $data['error'] : null,
            'fcm_message_id' => isset($data['fcm_message_id']) ? mb_substr((string) $data['fcm_message_id'], 0, 255) : null,
        );

        $this->db->insert('notification_log', $row);
        return (int) $this->db->insert_id();
    }

    private function emptyConfig()
    {
        return array(
            'id'                   => null,
            'is_enabled'           => 0,
            'project_id'           => null,
            'service_account_json' => null,
            'default_channel_id'   => 'default_channel',
            'default_sound'        => 'default',
            'default_click_action' => null,
        );
    }
}
