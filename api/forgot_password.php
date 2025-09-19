<?php
require_once '../config/cors.php';
require_once '../config/Database.php';
require_once '../vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';

if (!$email) {
    echo json_encode(["success" => false, "message" => "Email is required."]);
    exit;
}

$db = (new Database())->getConnection();


$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["success" => false, "message" => "No account found with that email."]);
    exit;
}

// Generate token and expiry (15 minutes)
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

$stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
$stmt->execute([$token, $expires, $email]);

$mail = new PHPMailer(true);
try {
    $mail->isSMTP(); // <-- THIS IS REQUIRED!
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'skavisheak.official@gmail.com';
    $mail->Password = 'wrwr bhyd eknt drjp';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    $mail->setFrom('skavisheak.official@gmail.com', 'InternSpark'); // <-- Use your Gmail here

    $mail->addAddress($email, $user['username'] ?? '');

    $mail->isHTML(true);
    $mail->Subject = "Password Reset Request";
    $resetLink = "http://localhost:5173/reset-password?token=$token";
    $mail->Body = "Hello,<br><br>To reset your password, click the link below:<br>
        <a href='$resetLink'>$resetLink</a><br><br>
        If you did not request this, ignore this email.<br><br>InternSpark Team";

    $mail->send();
    echo json_encode(["success" => true, "message" => "Password reset link sent to your email."]);
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/mail_errors.log', $mail->ErrorInfo . PHP_EOL, FILE_APPEND);
    echo json_encode(["success" => false, "message" => "Failed to send email."]);
}

date_default_timezone_set('Asia/Colombo'); // or your timezone
