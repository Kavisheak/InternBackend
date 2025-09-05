<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../api/sessions.php'; // starts session
require_once __DIR__ . '/../../config/maintenance_check.php';

header('Content-Type: application/json');

// Only allow POST and only for admins
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$value = isset($body['value']) ? $body['value'] : null;

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

if ($value === null) {
    echo json_encode(['success' => false, 'message' => 'Missing value']);
    exit;
}

$ok = set_setting_value('maintenance_mode', $value ? '1' : '0');
if ($ok) echo json_encode(['success' => true, 'message' => 'Maintenance mode updated']);
else echo json_encode(['success' => false, 'message' => 'Failed to update']);
