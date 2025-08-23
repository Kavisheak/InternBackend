<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once '../config/cors.php'; // must include before any output
require_once '../config/Database.php';
require_once '../models/User.php';

// Handle preflight
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

// ✅ Verify login
$userData = $user->verifyLogin($data->email, $data->password);

if ($userData) {
    // Save session data
    $_SESSION['user_id'] = $userData['User_Id'];
    $_SESSION['email'] = $userData['email'];
    $_SESSION['username'] = $userData['username'];
    $_SESSION['role'] = $userData['role'];

    // ✅ Handle Remember Me
    $rememberMe = isset($data->rememberMe) && $data->rememberMe;

    if ($rememberMe) {
        // Generate secure token
        $token = bin2hex(random_bytes(16));

        // Store token in DB (your users table must have `remember_token` column)
        $stmt = $db->prepare("UPDATE users SET remember_token = ? WHERE User_Id = ?");
        $stmt->execute([$token, $userData['User_Id']]);

        // Set HttpOnly cookie for 7 days
        setcookie(
            "remember_token", 
            $token, 
            time() + (86400 * 7), // 7 days
            "/",                  // available across the site
            "",                   // domain (empty = current domain)
            false,                // set TRUE if using HTTPS
            true                  // HttpOnly: prevents JS access
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
