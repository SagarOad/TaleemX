<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Subtitle extraction microservice.
 *
 * Local: http://127.0.0.1:5050/extract-subtitles  |  Docker PHP: SUBTITLE_EXTRACTOR_API_URL=http://lms-ai-service:5000/extract-subtitles
 *
 * Allows override via environment variable so production deployments do not
 * need to modify code:
 *   SUBTITLE_EXTRACTOR_API_URL=https://ai.example.com/extract-subtitles
 *
 * The endpoint is expected to accept POST JSON body `{"url": "..."}` and
 * return a payload shaped like:
 *   {
 *     "status": 200,
 *     "ok": true,
 *     "data": {
 *       "subtitles": "<full text>",
 *       "segments": [ { "start": 0.0, "end": 1.2, "text": "..." }, ... ],
 *       "source": "faster_whisper"
 *     }
 *   }
 */
$__env = getenv('SUBTITLE_EXTRACTOR_API_URL');
if ($__env !== false && $__env !== '') {
    $config['subtitle_extractor_api_url'] = rtrim((string) $__env, '/');
} else {
    // Production: 'https://ai.pixciletechnologies.com/extract-subtitles'
    $config['subtitle_extractor_api_url'] = 'http://127.0.0.1:5050/extract-subtitles';
}

$config['subtitle_extractor_connect_timeout'] = 10;
$config['subtitle_extractor_request_timeout'] = 180;
