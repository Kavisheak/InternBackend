<?php
require_once(__DIR__ . '/../models/users.php');
require_once(__DIR__ . '/../../config/cors.php');

$users = new Users();
$list = $users->getAllUsers();

echo json_encode([
    "success" => true,
    "data" => $list
]);