<?php
require_once "../../api/sessions.php";
require_once(__DIR__ . '/../models/admin.php');
require_once(__DIR__ . '/../../config/cors.php');

$admin = new Admin();

echo json_encode([
    "success" => true,
    "data" => $admin->getCounts()
]);