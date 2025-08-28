<?php
// Dev-only diagnostic endpoint to inspect session values from browser
header('Content-Type: application/json');
$origin = $_SERVER['HTTP_ORIGIN'] ?? null;
if ($origin) header("Access-Control-Allow-Origin: $origin");
header('Vary: Origin');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../../api/sessions.php';

$safe = [];
foreach ($_SESSION as $k => $v) {
    if (stripos($k, 'pass') !== false || stripos($k, 'token') !== false) continue;
    $safe[$k] = $v;
}

echo json_encode([
    'success' => true,
    'session' => $safe,
    'remote' => $_SERVER['REMOTE_ADDR'] ?? null
]);

?>
