<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Caption AI microservice — summarize / explain extracted video captions.
 *
 * Request body (JSON):
 *   { "action": "summarize", "text": "<transcript>" }
 *   { "action": "explain",   "text": "<transcript>", "question": "<optional>" }
 *
 * Response body (JSON): a common shape is { "answer": "..." } but we fall back
 * across a few common keys (answer / response / result / text) in the proxy so
 * minor API variations don't break the UI.
 *
 * Optional override via environment variable:
 *   CAPTION_AI_API_URL=https://ai.example.com/caption-ai
 */
$__env = getenv('CAPTION_AI_API_URL');
if ($__env !== false && $__env !== '') {
    $config['caption_ai_api_url'] = rtrim((string) $__env, '/');
} else {
    $config['caption_ai_api_url'] = 'https://ai.pixciletechnologies.com/caption-ai';
}

$config['caption_ai_connect_timeout'] = 6;
$config['caption_ai_request_timeout'] = 180;
