<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../api/sessions.php';
require_once __DIR__ . '/../../config/maintenance_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

if (!$body || !is_array($body)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

$okAll = true;
$errors = [];
foreach ($body as $key => $val) {
    $ok = set_setting_value($key, $val === true ? '1' : ($val === false ? '0' : (string)$val));
    if (!$ok) {
        $okAll = false;
        $errors[] = $key;
    }
}

if ($okAll) echo json_encode(['success' => true, 'message' => 'Settings updated']);
else echo json_encode(['success' => false, 'message' => 'Failed to update: ' . implode(',', $errors)]);
