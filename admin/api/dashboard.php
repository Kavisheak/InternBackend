<?php
require_once "../../api/sessions.php";
require_once(__DIR__ . '/../models/admin.php');
require_once(__DIR__ . '/../../config/cors.php');

$admin = new Admin();
// Fetch counts from admin model (should return an associative array)
$counts = $admin->getCounts();

echo json_encode([
    "success" => true,
    "data" => $counts
]);

