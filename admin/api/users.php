<?php
require_once "../../api/sessions.php";
require_once(__DIR__ . '/../models/admin.php');
require_once(__DIR__ . '/../../config/cors.php');

$admin = new Admin();
$users = $admin->getNonAdminUsers();

echo json_encode([
    "success" => true,
    "data" => $users
]);