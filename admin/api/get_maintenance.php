<?php
require_once __DIR__ . '/../../api/sessions.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/maintenance_check.php';

header('Content-Type: application/json');

// require admin session
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$v = get_setting_value('maintenance_mode');
echo json_encode(['success' => true, 'value' => ($v === '1' ? 1 : 0)]);
