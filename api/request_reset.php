<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

error_reporting(E_ALL); ini_set('display_errors', 1);

require_once "../config/Database.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';

if (!$email) {
    echo json_encode(["success" => false, "message" => "Email is required."]); exit;
}

// Check if user exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "message" => "Email not found."]); exit;
}

// Generate token
$token = bin2hex(random_bytes(50));
date_default_timezone_set("Asia/Colombo");
$expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));
$stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
$stmt->execute([$token, $expiry, $email]);

$resetLink = "http://localhost:5175/reset-password?token=$token";

// Send email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'dhanu16rathnayake11@gmail.com';       // <-- use your Gmail
    $mail->Password = 'pfus hnsl qxmk wozp';          // <-- Gmail app password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('support@internfinder.com', 'InternSpark');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Password Reset';
    $mail->Body = "Click to reset your password: <a href='$resetLink'>$resetLink</a>";

    $mail->send();
    echo json_encode(["success" => true, "message" => "Reset link sent to your email."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Mailer Error: {$mail->ErrorInfo}"]);
}

exit;

