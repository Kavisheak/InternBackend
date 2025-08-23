<?php


header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/cors.php';
require_once '../config/Database.php';
require_once '../models/User.php';

// Read input
$data = json_decode(file_get_contents("php://input"));

// Check required fields
if (
    !$data || 
    !isset($data->username, $data->email, $data->password, $data->role)
) {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit;
}

// Sanitize inputs
$username = trim(strip_tags($data->username));
$email = trim(strip_tags(strtolower($data->email)));
$role = trim(strip_tags($data->role));
$password = $data->password;

// --- Validation --- //

// Username
if (empty($username)) {
    echo json_encode(["success" => false, "message" => "Username is required."]);
    exit;
}
if (strlen($username) < 3 || strlen($username) > 30) {
    echo json_encode(["success" => false, "message" => "Username must be 3-30 characters."]);
    exit;
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode(["success" => false, "message" => "Username can only contain letters, numbers, and underscores."]);
    exit;
}

// Email
if (empty($email)) {
    echo json_encode(["success" => false, "message" => "Email is required."]);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format."]);
    exit;
}
if (strlen($email) > 100) {
    echo json_encode(["success" => false, "message" => "Email must be less than 100 characters."]);
    exit;
}

// Password
if (empty($password)) {
    echo json_encode(["success" => false, "message" => "Password is required."]);
    exit;
}
if (strlen($password) < 8) {
    echo json_encode(["success" => false, "message" => "Password must be at least 8 characters long."]);
    exit;
}
if (!preg_match('/[A-Z]/', $password)) {
    echo json_encode(["success" => false, "message" => "Password must include at least one uppercase letter."]);
    exit;
}
if (!preg_match('/[a-z]/', $password)) {
    echo json_encode(["success" => false, "message" => "Password must include at least one lowercase letter."]);
    exit;
}
if (!preg_match('/[0-9]/', $password)) {
    echo json_encode(["success" => false, "message" => "Password must include at least one number."]);
    exit;
}
if (!preg_match('/[\W_]/', $password)) {
    echo json_encode(["success" => false, "message" => "Password must include at least one special character."]);
    exit;
}

// Role
if (!in_array($role, ['student', 'company'])) {
    echo json_encode(["success" => false, "message" => "Invalid role selected."]);
    exit;
}

// --- Database operations --- //
try {
    $db = (new Database())->getConnection();
    $user = new User($db);

    if ($user->emailExists($email)) {
        echo json_encode(["success" => false, "message" => "Email already registered."]);
        exit;
    }

    $user->username = $username;
    $user->email = $email;
    $user->role = $role;
    $user->setPassword($password); // Assuming setPassword hashes it internally

    if ($user->create()) {
        echo json_encode(["success" => true, "message" => "User registered successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Registration failed."]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
}
?>
