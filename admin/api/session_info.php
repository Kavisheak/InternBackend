<?php
// Temporary debug endpoint to inspect server-side session and incoming cookie/header state.
// Call from the browser with credentials included to verify whether the PHP session is present.

// Include CORS headers (same as other endpoints)
if (file_exists(__DIR__ . '/../../config/cors.php')) {
    require_once __DIR__ . '/../../config/cors.php';
} else {
    // fallback minimal CORS for debugging
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    }
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

// Handle preflight quickly
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

// Start session using the project's session bootstrap if available
if (file_exists(__DIR__ . '/../../api/sessions.php')) {
    require_once __DIR__ . '/../../api/sessions.php';
} else {
    session_start();
}

$out = [
    'success' => true,
    'session_id' => session_id(),
    'session' => isset($_SESSION) ? $_SESSION : null,
    'cookie_superglobal' => isset($_COOKIE) ? $_COOKIE : null,
    'server_cookie_header' => isset($_SERVER['HTTP_COOKIE']) ? $_SERVER['HTTP_COOKIE'] : null,
    'origin' => isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : null,
    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
];

echo json_encode($out, JSON_PRETTY_PRINT);

