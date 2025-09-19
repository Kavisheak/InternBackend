<?php
require_once '../config/cors.php';
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

error_reporting(E_ALL); ini_set('display_errors', 1);

require_once "../config/Database.php";

date_default_timezone_set('Asia/Colombo'); // or your timezone

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? '';
$password = $data['password'] ?? '';

if (!$token || !$password) {
    echo json_encode(["success" => false, "message" => "Token and password are required."]);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

file_put_contents(__DIR__ . '/debug_reset.log', "Token: $token\n", FILE_APPEND);

$stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Log DB values for debugging
    $stmt2 = $conn->prepare("SELECT reset_token, reset_expires, NOW() as now FROM users WHERE reset_token = ?");
    $stmt2->execute([$token]);
    $row = $stmt2->fetch(PDO::FETCH_ASSOC);
    file_put_contents(__DIR__ . '/debug_reset.log', print_r($row, true), FILE_APPEND);

    echo json_encode(["success" => false, "message" => "Invalid or expired token."]);
    exit;
}

// Hash new password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Update password and clear token
$stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
if ($stmt->execute([$hashedPassword, $token])) {
    echo json_encode(["success" => true, "message" => "Password reset successfully"]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error reseting password",
        "errorInfo" => $stmt->errorInfo()
    ]);
}
exit;
?>