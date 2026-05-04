<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * API-side mirror of the portal's Push_notification_config_model. Reads/writes
 * the same `push_notification_config` and `notification_log` tables but
 * extends CI_Model directly (the API application has no MY_Model).
 */
class Push_notification_config_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

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
