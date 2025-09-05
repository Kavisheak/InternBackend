<?php
require_once(__DIR__ . '/../../config/cors.php');
require_once "../../api/sessions.php";
require_once(__DIR__ . '/../models/admin.php');

$admin = new Admin();
$data = $admin->getCounts();

echo json_encode([
    "success" => true,
    "data" => $data
]);

exit;

