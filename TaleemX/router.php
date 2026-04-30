<?php
/**
 * Router for PHP built-in dev server.
 *
 * Serves existing static files directly and routes everything else through
 * CodeIgniter's front controller (index.php).
 */

$uri  = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

// Route API requests to the API front controller during local dev.
if (strpos($uri, '/api/') === 0 || $uri === '/api') {
    $_SERVER['SCRIPT_NAME']     = '/api/index.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/api/index.php';
    $_SERVER['PHP_SELF']        = '/api/index.php';
    $queryString = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';
    $_SERVER['REQUEST_URI'] = preg_replace('#^/api#', '', $uri, 1) . $queryString;
    $previousCwd = getcwd();
    chdir(__DIR__ . '/api');
    require __DIR__ . '/api/index.php';
    chdir($previousCwd);
    return;
}

$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
$_SERVER['PHP_SELF']        = '/index.php';

require __DIR__ . '/index.php';
