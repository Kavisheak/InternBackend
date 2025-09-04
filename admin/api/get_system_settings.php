<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../api/sessions.php';
require_once __DIR__ . '/../../config/maintenance_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$keys = [
    'maintenance_mode',
    'user_registration',
    'company_registration',
    'site_name'
];

$out = [];
foreach ($keys as $k) {
    $v = get_setting_value($k);
    $out[$k] = $v;
}

echo json_encode(['success' => true, 'settings' => $out]);
