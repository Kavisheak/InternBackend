<?php
require_once(__DIR__ . '/../models/users.php');
require_once(__DIR__ . '/../../config/cors.php');
require_once "../../api/sessions.php";

$users = new Users();
$list = $users->getAllUsers();

echo json_encode([
    "success" => true,
    "data" => $list
]);