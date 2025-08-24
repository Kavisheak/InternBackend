<?php
ini_set('session.gc_maxlifetime', 1800); // 30 min inactivity
session_set_cookie_params([
    'lifetime' => 0, // expires when browser closes
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    echo json_encode(["success" => false, "message" => "Session expired due to inactivity."]);
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();