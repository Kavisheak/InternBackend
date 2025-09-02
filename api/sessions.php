<?php
ini_set('session.gc_maxlifetime', 1800); // 30 min inactivity
// Allow cross-site cookies during local dev so frontend (different port) can send credentials
session_set_cookie_params([
    'lifetime' => 0, // expires when browser closes
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    // Allow cross-site cookies during local dev so frontend (different port) can send credentials
    'samesite' => 'None'
]);
session_start();
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    echo json_encode(["success" => false, "message" => "Session expired due to inactivity."]);
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();