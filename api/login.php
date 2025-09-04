<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once '../config/cors.php';
require_once '../config/Database.php';
require_once '../models/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email, $data->password)) {
    echo json_encode(["success" => false, "message" => "Missing email or password."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$userData = $user->verifyLogin($data->email, $data->password);

if ($userData) {
    $_SESSION['user_id'] = $userData['User_Id'];
    $_SESSION['email'] = $userData['email'];
    $_SESSION['username'] = $userData['username'];
    $_SESSION['role'] = $userData['role'];

    // ✅ Handle Remember Me
    if (!empty($data->rememberMe) && $data->rememberMe === true) {
        $token = bin2hex(random_bytes(32)); // secure 64-char token

        // Store token in DB
        $stmt = $db->prepare("UPDATE users SET remember_token = ? WHERE User_Id = ?");
        $stmt->execute([$token, $userData['User_Id']]);

        // Set secure cookie for 30 days
        setcookie(
            "remember_token",
            $token,
            time() + (86400 * 30), // 30 days
            "/",                   // cookie available site-wide
            "",                    // domain (empty = current)
            false,                 // secure (set to true if using HTTPS)
            true                   // HttpOnly (not accessible from JS)
        );
    }

    echo json_encode([
        "success" => true,
        "message" => "Login successful.",
        "username" => $userData['username'],
        "role" => $userData['role'],
        "user_id" => $userData['User_Id']
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid email or password."]);
}
