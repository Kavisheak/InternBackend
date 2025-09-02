<?php
require_once '../config/cors.php';
require_once '../config/Database.php';
require_once '../models/User.php';

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->username, $data->email, $data->password, $data->role)) {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit;
}

// Password validation
$password = $data->password;
if (strlen($password) < 8 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password) ||
    !preg_match('/[\W]/', $password)) {
    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 8 characters long and include uppercase, lowercase, number, and special character."
    ]);
    exit;
}

$db = (new Database())->getConnection();
$user = new User($db);

if ($user->emailExists($data->email)) {
    echo json_encode(["success" => false, "message" => "Email already registered."]);
    exit;
}

$user->username = $data->username;
$user->email = $data->email;
$user->role = $data->role;
$user->setPassword($password);

if ($user->create()) {
    echo json_encode(["success" => true, "message" => "User registered successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Registration failed."]);
}