<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Subtitle extraction microservice.
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
    $config['subtitle_extractor_api_url'] = 'https://ai.pixciletechnologies.com/extract-subtitles';
}

$config['subtitle_extractor_connect_timeout'] = 10;
$config['subtitle_extractor_request_timeout'] = 180;
