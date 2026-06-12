<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Read-only access to stored lesson/course transcripts and the Caption AI
 * microservice. Used by mobile lesson AI endpoints (summarize / explain / ask).
 */
class Video_transcript_model extends CI_Model
{
    const TABLE = 'video_transcripts';

    const ENTITY_COURSE = 'course';
    const ENTITY_LESSON = 'lesson';

    public function get_by_entity($entity_type, $entity_id)
    {
        $entity_id = (int) $entity_id;
        if ($entity_id <= 0) {
            return null;
        }
        $this->db->where('entity_type', (string) $entity_type);
        $this->db->where('entity_id', $entity_id);
        $this->db->limit(1);
        $query = $this->db->get(self::TABLE);
        $row   = $query->row_array();
        return !empty($row) ? $row : null;
    }

    public function get_full_text($entity_type, $entity_id)
    {
        $row = $this->get_by_entity($entity_type, $entity_id);
        if ($row === null) {
            return '';
        }
        $text = isset($row['full_transcript']) ? trim((string) $row['full_transcript']) : '';
        if ($text !== '') {
            return $text;
        }
        if (!empty($row['segments_json'])) {
            $decoded = json_decode((string) $row['segments_json'], true);
            if (is_array($decoded)) {
                $parts = array();
                foreach ($decoded as $seg) {
                    if (is_array($seg) && isset($seg['text']) && is_string($seg['text'])) {
                        $t = trim($seg['text']);
                        if ($t !== '') {
                            $parts[] = $t;
                        }
                    }
                }
                return trim(implode(' ', $parts));
            }
        }
        return '';
    }

    /**
     * Return the timed segments array ([{start,end,text}, ...]) for an entity,
     * or an empty array when no transcript exists.
     */
    public function get_segments($entity_type, $entity_id)
    {
        $row = $this->get_by_entity($entity_type, $entity_id);
        if ($row === null || empty($row['segments_json'])) {
            return array();
        }
        $decoded = json_decode((string) $row['segments_json'], true);
        return is_array($decoded) ? $decoded : array();
    }

    /**
     * @param string $action   'summarize' | 'explain'
     * @param string $text     Full transcript text.
     * @param string $question Optional follow-up question (explain only).
     * @param array  $context  Optional enrichment (segments, history,
     *                         lesson_title, lesson_summary, course_title, level).
     */
    public function call_caption_ai($action, $text, $question = '', $context = array())
    {
        $action = strtolower(trim((string) $action));
        if ($action !== 'summarize' && $action !== 'explain') {
            return array('ok' => false, 'error' => 'Invalid action.', 'status' => 0);
        }
        $text = trim((string) $text);
        if ($text === '') {
            return array(
                'ok'     => false,
                'error'  => 'No transcript is available for this video yet. Please extract subtitles first.',
                'status' => 0,
            );
        }

        $this->config->load('caption_ai', true);
        $api_url = (string) $this->config->item('caption_ai_api_url', 'caption_ai');
        if ($api_url === '') {
            return array('ok' => false, 'error' => 'Caption AI endpoint is not configured.', 'status' => 0);
        }
        $connect_timeout = (int) $this->config->item('caption_ai_connect_timeout', 'caption_ai');
        $request_timeout = (int) $this->config->item('caption_ai_request_timeout', 'caption_ai');
        if ($connect_timeout <= 0) {
            $connect_timeout = 6;
        }
        if ($request_timeout <= 0) {
            $request_timeout = 180;
        }

        @set_time_limit($request_timeout + 30);
        @ini_set('default_socket_timeout', (string) ($request_timeout + 10));

        $body = array('action' => $action, 'text' => $text);
        if ($action === 'explain') {
            $q = trim((string) $question);
            if ($q !== '') {
                $body['question'] = $q;
            }
        }

        $body = $this->_merge_caption_context($body, $context);

        $ch = curl_init($api_url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => array('Content-Type: application/json', 'Accept: application/json'),
            CURLOPT_CONNECTTIMEOUT => $connect_timeout,
            CURLOPT_TIMEOUT        => $request_timeout,
        ));
        $response_body = curl_exec($ch);
        $curl_error    = curl_error($ch);
        $status_code   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response_body === false) {
            log_message('error', 'caption_ai curl failed: ' . $curl_error);
            return array('ok' => false, 'error' => 'AI service unreachable: ' . $curl_error, 'status' => 0);
        }

        $decoded = json_decode((string) $response_body, true);
        if (!is_array($decoded)) {
            log_message('error', 'caption_ai invalid JSON: ' . substr((string) $response_body, 0, 500));
            return array('ok' => false, 'error' => 'AI service returned invalid JSON.', 'status' => $status_code);
        }

        if ($status_code < 200 || $status_code >= 300) {
            $msg = '';
            if (isset($decoded['error']) && is_string($decoded['error'])) {
                $msg = $decoded['error'];
            } elseif (isset($decoded['message']) && is_string($decoded['message'])) {
                $msg = $decoded['message'];
            }
            if ($msg === '') {
                $msg = 'AI request failed (HTTP ' . $status_code . ').';
            }
            return array('ok' => false, 'error' => $msg, 'status' => $status_code);
        }

        $answer = $this->_extract_caption_ai_answer($decoded);
        return array('ok' => true, 'answer' => $answer, 'raw' => $decoded, 'status' => $status_code);
    }

    private function _merge_caption_context(array $body, $context)
    {
        if (!is_array($context)) {
            return $body;
        }

        if (!empty($context['segments']) && is_array($context['segments'])) {
            $segments = array();
            foreach ($context['segments'] as $seg) {
                if (!is_array($seg) || !isset($seg['text'])) { continue; }
                $text = trim((string) $seg['text']);
                if ($text === '') { continue; }
                $segments[] = array(
                    'start' => isset($seg['start']) ? (float) $seg['start'] : 0,
                    'end'   => isset($seg['end']) ? (float) $seg['end'] : 0,
                    'text'  => $text,
                );
                if (count($segments) >= 1200) { break; }
            }
            if (!empty($segments)) {
                $body['segments'] = $segments;
            }
        }

        if (!empty($context['history']) && is_array($context['history'])) {
            $history = array();
            foreach ($context['history'] as $turn) {
                if (!is_array($turn)) { continue; }
                $role = isset($turn['role']) ? (string) $turn['role'] : '';
                $content = isset($turn['content']) ? trim((string) $turn['content']) : '';
                if (($role !== 'user' && $role !== 'assistant') || $content === '') { continue; }
                $history[] = array('role' => $role, 'content' => $content);
            }
            if (!empty($history)) {
                $body['history'] = array_slice($history, -12);
            }
        }

        foreach (array('lesson_title', 'lesson_summary', 'course_title', 'level') as $key) {
            if (!empty($context[$key]) && is_string($context[$key])) {
                $body[$key] = trim($context[$key]);
            }
        }

        return $body;
    }

    private function _extract_caption_ai_answer(array $decoded)
    {
        $keys = array('answer', 'response', 'result', 'text', 'output', 'summary', 'explanation', 'message');
        foreach ($keys as $k) {
            if (isset($decoded[$k]) && is_string($decoded[$k]) && trim($decoded[$k]) !== '') {
                return (string) $decoded[$k];
            }
        }
        if (isset($decoded['data']) && is_array($decoded['data'])) {
            foreach ($keys as $k) {
                if (isset($decoded['data'][$k]) && is_string($decoded['data'][$k]) && trim($decoded['data'][$k]) !== '') {
                    return (string) $decoded['data'][$k];
                }
            }
        }
        return '';
    }
}
