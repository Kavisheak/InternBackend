<?php
// Ensure CORS headers are present for any endpoint that includes sessions.php
if (file_exists(__DIR__.'/../config/cors.php')) {
    require_once __DIR__.'/../config/cors.php';
}

ini_set('session.gc_maxlifetime', 1800); // 30 min inactivity

// Determine if the connection is secure (HTTPS). On local dev (http) we should NOT set Secure=true
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

// For HTTPS: use SameSite=None and Secure=true (required by browsers). For local HTTP: use SameSite=Lax and Secure=false.
$cookieParams = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => $isSecure ? 'None' : 'Lax'
];
session_set_cookie_params($cookieParams);
session_start();
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    echo json_encode(["success" => false, "message" => "Session expired due to inactivity."]);
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();