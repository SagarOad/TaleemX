<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Video_transcript_model
 *
 * Stores and fetches subtitles/transcripts for course preview and lesson
 * videos in a single polymorphic `video_transcripts` table.
 *
 *   entity_type = 'course'  -> entity_id = online_courses.id
 *   entity_type = 'lesson'  -> entity_id = online_course_lesson.id
 *
 * The extraction API integration also lives here so that both
 * `onlinecourse/Course` and `onlinecourse/Courselesson` controllers can
 * share the exact same request/response handling.
 */
class Video_transcript_model extends MY_Model
{
    const TABLE = 'video_transcripts';

    const ENTITY_COURSE = 'course';
    const ENTITY_LESSON = 'lesson';

    public function __construct()
    {
        parent::__construct();
        $this->config->load('subtitle_extractor', true);
    }

    /**
     * Map an internal video provider value (e.g. the `course_provider` /
     * `video_provider` column) to the `type` field expected by the external
     * subtitle-extractor API. Unknown providers fall back to 'youtube' because
     * that is the primary supported source.
     *
     * @param string $video_provider
     * @return string
     */
    public function map_provider_to_api_type($video_provider)
    {
        $p = strtolower(trim((string) $video_provider));
        switch ($p) {
            case 'youtube':
                return 'youtube';
            case 'file':
            case 's3_bucket':
            case 'html5':
                return 'file';
            case 'vimeo':
                return 'vimeo';
            default:
                return 'youtube';
        }
    }

    /**
     * Call the external subtitle extraction microservice.
     *
     * For `youtube` (and other URL-based providers) a JSON body is sent:
     *     {"type":"youtube","url":"https://..."}
     *
     * For `file` a multipart/form-data request is sent with a binary `file`
     * part, e.g.:
     *     Content-Type: multipart/form-data
     *     type=file
     *     file=@/path/to/video.mp4
     *
     * @param string      $video_url        Video URL (required for URL-based types).
     *                                      Ignored for `file` type when $local_file_path
     *                                      is given, but kept for logging/fingerprints.
     * @param string      $api_type         Value to send as the `type` field. Defaults
     *                                      to 'youtube' for backwards compatibility.
     * @param string|null $local_file_path  Absolute path to a local file to upload when
     *                                      $api_type is 'file'. Required for `file` type.
     * @param string|null $upload_file_name Original filename to forward in multipart
     *                                      payload (helps services that validate by name/ext).
     * @return array Associative array: [
     *     'ok'        => bool,
     *     'status'    => int,
     *     'data'      => array|null  (decoded API `data` object on success),
     *     'raw'       => array|null  (full decoded response),
     *     'error'     => string|null,
     * ]
     */
    public function call_extract_api($video_url, $api_type = 'youtube', $local_file_path = null, $upload_file_name = null)
    {
        $video_url = trim((string) $video_url);
        $api_type  = trim((string) $api_type);
        if ($api_type === '') {
            $api_type = 'youtube';
        }

        $is_file_upload = ($api_type === 'file');
        if (!$is_file_upload && $video_url === '') {
            return array(
                'ok'     => false,
                'status' => 0,
                'data'   => null,
                'raw'    => null,
                'error'  => 'Video URL is required.',
            );
        }
        if ($is_file_upload) {
            if ($local_file_path === null || $local_file_path === '' || !is_file($local_file_path)) {
                return array(
                    'ok'     => false,
                    'status' => 0,
                    'data'   => null,
                    'raw'    => null,
                    'error'  => 'Video file not found for extraction.',
                );
            }
        }

        $api_url = (string) $this->config->item('subtitle_extractor_api_url', 'subtitle_extractor');
        if ($api_url === '') {
            return array(
                'ok'     => false,
                'status' => 0,
                'data'   => null,
                'raw'    => null,
                'error'  => 'Subtitle extractor endpoint is not configured.',
            );
        }

        $connect_timeout = (int) $this->config->item('subtitle_extractor_connect_timeout', 'subtitle_extractor');
        $request_timeout = (int) $this->config->item('subtitle_extractor_request_timeout', 'subtitle_extractor');
        if ($connect_timeout <= 0) {
            $connect_timeout = 10;
        }
        if ($request_timeout <= 0) {
            $request_timeout = 180;
        }

        $ch = curl_init($api_url);
        $curl_opts = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_CONNECTTIMEOUT => $connect_timeout,
            CURLOPT_TIMEOUT        => $request_timeout,
        );

        if ($is_file_upload) {
            $mime = 'application/octet-stream';
            if (function_exists('mime_content_type')) {
                $detected = @mime_content_type($local_file_path);
                if (is_string($detected) && $detected !== '') {
                    $mime = $detected;
                }
            }
            $multipart_name = trim((string) $upload_file_name);
            if ($multipart_name === '') {
                $multipart_name = basename($local_file_path);
            }

            $curl_file = class_exists('CURLFile')
                ? new CURLFile($local_file_path, $mime, $multipart_name)
                : '@' . $local_file_path;

            $curl_opts[CURLOPT_POSTFIELDS] = array(
                'type' => $api_type,
                'file' => $curl_file,
            );
            // NOTE: Do NOT set Content-Type manually for multipart; cURL will
            // compute the boundary. We only pass Accept to hint JSON response.
            $curl_opts[CURLOPT_HTTPHEADER] = array(
                'Accept: application/json',
            );
        } else {
            $curl_opts[CURLOPT_POSTFIELDS] = json_encode(array(
                'type' => $api_type,
                'url'  => $video_url,
            ));
            $curl_opts[CURLOPT_HTTPHEADER] = array(
                'Content-Type: application/json',
                'Accept: application/json',
            );
        }

        curl_setopt_array($ch, $curl_opts);

        // Subtitle extraction (especially for file uploads) can easily exceed
        // the default 30-60s PHP max_execution_time. Extend the PHP time limit
        // to at least cover our cURL request budget, with a little buffer.
        // This applies only to the current request.
        if (function_exists('set_time_limit')) {
            @set_time_limit($request_timeout + 30);
        }
        @ini_set('default_socket_timeout', (string) ($request_timeout + 30));

        $response_body = curl_exec($ch);
        $curl_error    = curl_error($ch);
        $status_code   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response_body === false) {
            return array(
                'ok'     => false,
                'status' => $status_code,
                'data'   => null,
                'raw'    => null,
                'error'  => 'Subtitle service unreachable: ' . ($curl_error !== '' ? $curl_error : 'unknown error'),
            );
        }

        $decoded = json_decode((string) $response_body, true);
        if (!is_array($decoded)) {
            return array(
                'ok'     => false,
                'status' => $status_code,
                'data'   => null,
                'raw'    => null,
                'error'  => 'Subtitle service returned invalid JSON.',
            );
        }

        $api_ok = isset($decoded['ok']) ? (bool) $decoded['ok'] : ($status_code >= 200 && $status_code < 300);
        if (!$api_ok) {
            $error_msg = isset($decoded['error']) && $decoded['error'] !== ''
                ? (string) $decoded['error']
                : 'Subtitle extraction failed.';
            return array(
                'ok'     => false,
                'status' => $status_code,
                'data'   => null,
                'raw'    => $decoded,
                'error'  => $error_msg,
            );
        }

        // The API can return subtitles under slightly different shapes
        // depending on the type (YouTube vs file upload vs other providers).
        // We normalise them here into {segments, subtitles, source}.
        $normalized = $this->_normalize_extract_response($decoded);
        if ($normalized === null) {
            $snippet = substr((string) $response_body, 0, 600);
            log_message('error', 'Subtitle response missing required fields. Raw: ' . $snippet);
            return array(
                'ok'     => false,
                'status' => $status_code,
                'data'   => null,
                'raw'    => $decoded,
                'error'  => 'Subtitle response missing required fields. API returned: ' . $snippet,
            );
        }

        return array(
            'ok'     => true,
            'status' => $status_code,
            'data'   => $normalized,
            'raw'    => $decoded,
            'error'  => null,
        );
    }

    /**
     * Normalise the subtitle extractor response into a consistent shape:
     *   [
     *     'segments'  => array(['start'=>float, 'end'=>float, 'text'=>string], ...),
     *     'subtitles' => string (full transcript text),
     *     'source'    => string (optional provenance),
     *   ]
     *
     * Returns null if the response cannot be made sense of.
     */
    private function _normalize_extract_response(array $decoded)
    {
        // Prefer $decoded['data'] but fall back to the root if the API is
        // already flattening the payload.
        $candidates = array();
        if (isset($decoded['data']) && is_array($decoded['data'])) {
            $candidates[] = $decoded['data'];
        }
        $candidates[] = $decoded;

        foreach ($candidates as $d) {
            $segments = null;
            $text     = null;
            $source   = isset($d['source']) ? (string) $d['source'] : '';

            // ---- Segments -------------------------------------------------
            if (isset($d['segments']) && is_array($d['segments'])) {
                $segments = $d['segments'];
            } elseif (isset($d['transcript']) && is_array($d['transcript'])
                      && isset($d['transcript']['segments']) && is_array($d['transcript']['segments'])) {
                $segments = $d['transcript']['segments'];
            } elseif (isset($d['chunks']) && is_array($d['chunks'])) {
                $segments = array();
                foreach ($d['chunks'] as $chunk) {
                    if (!is_array($chunk)) { continue; }
                    $seg = array(
                        'start' => isset($chunk['start']) ? (float) $chunk['start']
                                    : (isset($chunk['timestamp'][0]) ? (float) $chunk['timestamp'][0] : 0),
                        'end'   => isset($chunk['end'])   ? (float) $chunk['end']
                                    : (isset($chunk['timestamp'][1]) ? (float) $chunk['timestamp'][1] : 0),
                        'text'  => isset($chunk['text']) ? (string) $chunk['text'] : '',
                    );
                    $segments[] = $seg;
                }
            }

            // ---- Full transcript text ------------------------------------
            if (isset($d['subtitles'])) {
                $text = (string) $d['subtitles'];
            } elseif (isset($d['transcript'])) {
                if (is_string($d['transcript'])) {
                    $text = $d['transcript'];
                } elseif (is_array($d['transcript']) && isset($d['transcript']['text'])) {
                    $text = (string) $d['transcript']['text'];
                }
            } elseif (isset($d['text']) && is_string($d['text'])) {
                $text = $d['text'];
            } elseif (isset($d['full_text']) && is_string($d['full_text'])) {
                $text = $d['full_text'];
            }

            // If we have segments but no text, synthesise it by joining.
            if ($text === null && is_array($segments) && !empty($segments)) {
                $parts = array();
                foreach ($segments as $seg) {
                    if (is_array($seg) && isset($seg['text'])) {
                        $parts[] = trim((string) $seg['text']);
                    }
                }
                $text = trim(implode(' ', array_filter($parts, 'strlen')));
            }

            // If we have text but no segments, create a single segment.
            if ((!is_array($segments) || empty($segments)) && is_string($text) && $text !== '') {
                $segments = array(array(
                    'start' => 0,
                    'end'   => 0,
                    'text'  => $text,
                ));
            }

            if (is_array($segments) && !empty($segments) && is_string($text) && $text !== '') {
                return array(
                    'segments'  => $segments,
                    'subtitles' => $text,
                    'source'    => $source,
                );
            }
        }

        return null;
    }

    /**
     * Produce a stable fingerprint for a given video target so the UI can
     * detect if the URL or provider-supplied id changed since the last
     * successful extraction.
     */
    public function compute_fingerprint($video_provider, $video_url, $video_id)
    {
        $payload = strtolower(trim((string) $video_provider)) . '|'
            . trim((string) $video_url) . '|'
            . trim((string) $video_id);
        return md5($payload);
    }

    /**
     * Insert or update the transcript row for the given entity.
     * Returns the transcript row id on success, false on failure.
     */
    public function upsert_transcript($entity_type, $entity_id, array $meta, array $api_data)
    {
        $entity_type = (string) $entity_type;
        $entity_id   = (int) $entity_id;

        if ($entity_id <= 0 || !in_array($entity_type, array(self::ENTITY_COURSE, self::ENTITY_LESSON), true)) {
            return false;
        }

        $segments        = isset($api_data['segments']) && is_array($api_data['segments']) ? $api_data['segments'] : array();
        $full_transcript = isset($api_data['subtitles']) ? (string) $api_data['subtitles'] : '';
        $source          = isset($api_data['source']) ? (string) $api_data['source'] : '';

        $now         = date('Y-m-d H:i:s');
        $fingerprint = $this->compute_fingerprint(
            isset($meta['video_provider']) ? $meta['video_provider'] : '',
            isset($meta['video_url']) ? $meta['video_url'] : '',
            isset($meta['video_id']) ? $meta['video_id'] : ''
        );

        $row = array(
            'entity_type'       => $entity_type,
            'entity_id'         => $entity_id,
            'video_provider'    => isset($meta['video_provider']) ? (string) $meta['video_provider'] : null,
            'video_url'         => isset($meta['video_url']) ? (string) $meta['video_url'] : null,
            'video_id'          => isset($meta['video_id']) ? (string) $meta['video_id'] : null,
            'video_fingerprint' => $fingerprint,
            'source'            => $source !== '' ? $source : null,
            'segment_count'     => count($segments),
            'full_transcript'   => $full_transcript,
            'segments_json'     => json_encode($segments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status'            => 'success',
            'error_message'     => null,
            'last_extracted_at' => $now,
            'updated_at'        => $now,
        );

        $this->db->trans_start();
        $this->db->trans_strict(false);

        $existing = $this->get_by_entity($entity_type, $entity_id);
        if (!empty($existing) && isset($existing['id'])) {
            $record_id = (int) $existing['id'];
            $this->db->where('id', $record_id);
            $this->db->update(self::TABLE, $row);
            $this->log(UPDATE_RECORD_CONSTANT . ' On video_transcripts id ' . $record_id, $record_id, 'Update');
        } else {
            $row['created_at'] = $now;
            $this->db->insert(self::TABLE, $row);
            $record_id = (int) $this->db->insert_id();
            $this->log(INSERT_RECORD_CONSTANT . ' On video_transcripts id ' . $record_id, $record_id, 'Insert');
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }

        return $record_id;
    }

    /**
     * Fetch transcript row (associative array) by entity link, or null.
     */
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

    /**
     * Build a WebVTT (Web Video Text Tracks) string from the stored segments of
     * a given entity. Returns null if no transcript exists.
     */
    public function build_vtt($entity_type, $entity_id)
    {
        $row = $this->get_by_entity($entity_type, $entity_id);
        if ($row === null || (isset($row['status']) && $row['status'] !== 'success')) {
            return null;
        }
        $segments = array();
        if (!empty($row['segments_json'])) {
            $decoded = json_decode((string) $row['segments_json'], true);
            if (is_array($decoded)) {
                $segments = $decoded;
            }
        }
        if (empty($segments)) {
            return null;
        }

        $formatTs = function ($seconds) {
            $sec = max(0.0, (float) $seconds);
            $h   = floor($sec / 3600);
            $m   = floor(($sec - $h * 3600) / 60);
            $s   = $sec - ($h * 3600) - ($m * 60);
            return sprintf('%02d:%02d:%06.3f', $h, $m, $s);
        };

        $out = "WEBVTT\n\n";
        $i   = 0;
        foreach ($segments as $seg) {
            if (!is_array($seg)) { continue; }
            $start = isset($seg['start']) ? (float) $seg['start'] : 0;
            $end   = isset($seg['end'])   ? (float) $seg['end']   : ($start + 2);
            if ($end <= $start) { $end = $start + 2; }
            $text  = isset($seg['text']) ? trim((string) $seg['text']) : '';
            if ($text === '') { continue; }
            $i++;
            $out .= $i . "\n";
            $out .= $formatTs($start) . ' --> ' . $formatTs($end) . "\n";
            $out .= $text . "\n\n";
        }
        return $i > 0 ? $out : null;
    }

    /**
     * Return the plain segments array (with start/end/text) for a given entity,
     * or an empty array. Convenient for rendering a transcript panel.
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
     * Return the plain text transcript (full_transcript column) for a given
     * entity. Falls back to joining the segments if the full text is empty but
     * segments are present. Returns an empty string when nothing is available.
     *
     * This is the "text" that powers downstream AI features like summarize /
     * explain on the Caption AI endpoint.
     */
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
                        if ($t !== '') { $parts[] = $t; }
                    }
                }
                return trim(implode(' ', $parts));
            }
        }
        return '';
    }

    /**
     * Call the external Caption AI microservice with action=summarize or
     * action=explain. `$question` is only relevant for explain and may be
     * left empty for the initial "explain this video" round.
     *
     * Returns:
     *   array('ok' => true,  'answer' => string, 'raw' => array)
     *   array('ok' => false, 'error'  => string, 'status' => int)
     */
    public function call_caption_ai($action, $text, $question = '')
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
        if ($connect_timeout <= 0) { $connect_timeout = 6; }
        if ($request_timeout <= 0) { $request_timeout = 180; }

        // Give PHP room for the full external request so long captions do not
        // trip the default 60s ceiling.
        @set_time_limit($request_timeout + 30);
        @ini_set('default_socket_timeout', (string) ($request_timeout + 10));

        $body = array('action' => $action, 'text' => $text);
        if ($action === 'explain') {
            $q = trim((string) $question);
            if ($q !== '') {
                $body['question'] = $q;
            }
        }

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
            if ($msg === '') { $msg = 'AI request failed (HTTP ' . $status_code . ').'; }
            return array('ok' => false, 'error' => $msg, 'status' => $status_code);
        }

        $answer = $this->_extract_caption_ai_answer($decoded);
        return array('ok' => true, 'answer' => $answer, 'raw' => $decoded, 'status' => $status_code);
    }

    /**
     * Pull a human-readable answer out of whatever shape the Caption AI
     * microservice returned. Handles the common cases:
     *   { "answer": "..." }
     *   { "response": "..." }
     *   { "result": "..." } / { "text": "..." } / { "message": "..." }
     *   { "data": { "answer": "..." } } / etc.
     */
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

    /**
     * Light-weight status snapshot used by UI badges. Returns:
     *   null                                             - no row yet
     *   [ 'status' => 'success', 'fingerprint_match' => bool, ...]
     */
    public function get_status_for_ui($entity_type, $entity_id, $video_provider, $video_url, $video_id)
    {
        $row = $this->get_by_entity($entity_type, $entity_id);
        if ($row === null) {
            return null;
        }
        $current_fingerprint = $this->compute_fingerprint($video_provider, $video_url, $video_id);
        return array(
            'status'             => (string) $row['status'],
            'segment_count'      => (int) $row['segment_count'],
            'last_extracted_at'  => $row['last_extracted_at'],
            'fingerprint_match'  => isset($row['video_fingerprint']) && $row['video_fingerprint'] === $current_fingerprint,
        );
    }
}
