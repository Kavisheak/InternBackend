<?php
require_once "../../api/sessions.php";
require_once(__DIR__ . '/../models/admin.php');
require_once(__DIR__ . '/../../config/cors.php');

$admin = new Admin();
$users = $admin->getNonAdminUsers();

echo json_encode([
    "success" => true,
<<<<<<< HEAD
    "data" => $users
]);
=======
    "data" => $list
]);
>>>>>>> 0c8c34f472b4681f5c8e8ad3692b7c42492c26a0
