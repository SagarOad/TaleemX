<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Ask AI — LMS microservice URL (full path to /ask).
 *
 * Local dev:
 *   - AI on host: http://127.0.0.1:5050/ask (matches docker-compose AI_PORT default)
 *   - PHP inside Docker: set ASKAI_API_URL=http://lms-ai-service:5000/ask (compose does this)
 *
 * Production (HTTPS LMS cannot call plain http:// from the browser for same-origin
 * proxy calls; use HTTPS AI URL or an Nginx same-origin proxy, e.g. askai-proxy/ask).
 *
 * Optional: ASKAI_API_URL env overrides this file (recommended for servers).
 */
$__env = getenv('ASKAI_API_URL');
if ($__env !== false && $__env !== '') {
    $config['askai_api_url'] = rtrim((string) $__env, '/');
} else {
    // Production default (uncomment if not using env on server):
    // $config['askai_api_url'] = 'https://ai.pixciletechnologies.com/ask';
    $config['askai_api_url'] = 'http://127.0.0.1:5050/ask';
}

// Ensure /ask path if base URL was given without it
$__u = (string) $config['askai_api_url'];
if ($__u !== '' && stripos($__u, '/ask') === false) {
    $config['askai_api_url'] = rtrim($__u, '/') . '/ask';
}
