<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Ask AI — LMS microservice URL (full path to /ask).
 *
 * Production (https://t2.pixciletechnologies.com/):
 *   Browsers block mixed content: an HTTPS page cannot call http:// APIs.
 *   So either:
 *   - Expose the AI over HTTPS (e.g. https://ai.yourdomain.com/ask), and set
 *     that URL here, OR
 *   - Add a same-origin reverse proxy (e.g. Nginx) so the app calls
 *     https://t2.pixciletechnologies.com/askai-proxy/ask and the server
 *     forwards to the real microservice.
 *
 * Optional: set environment variable ASKAI_API_URL on the server to override
 * this file without editing code.
 */
$__env = getenv('ASKAI_API_URL');
if ($__env !== false && $__env !== '') {
    $config['askai_api_url'] = rtrim((string) $__env, '/');
} else {
    $config['askai_api_url'] = 'https://ai.pixciletechnologies.com/ask';
}

// Ensure /ask path if base URL was given without it
$__u = (string) $config['askai_api_url'];
if ($__u !== '' && stripos($__u, '/ask') === false) {
    $config['askai_api_url'] = rtrim($__u, '/') . '/ask';
}
