<?php
require_once "../../config/Database.php";
require_once __DIR__ . '/../../config/cors.php';
require_once "../../vendor/autoload.php"; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true);
$start = $data['maintenance_start'] ?? null;
$end = $data['maintenance_end'] ?? null;

file_put_contents(__DIR__ . '/debug.log', print_r($data, true), FILE_APPEND);

if (!$start || !$end) {
    echo json_encode(['success' => false, 'message' => 'Start and end date/time required']);
    exit;
}

$db = (new Database())->getConnection();
$stmt = $db->query("SELECT email, username FROM users WHERE role != 'admin' AND is_active = 1");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'skavisheak.official@gmail.com';
$mail->Password = 'wrwr bhyd eknt drjp';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
$mail->setFrom('internspark@gmail.com', 'InternSpark');

$subject = "Scheduled Maintenance Notification";
$startFormatted = date('F j, Y, g:i a', strtotime($start));
$endFormatted = date('F j, Y, g:i a', strtotime($end));
$body = "Dear user,<br><br>
InternSpark will undergo scheduled maintenance from <b>$startFormatted</b> to <b>$endFormatted</b>.<br>
During this period, the site may be temporarily unavailable.<br><br>
We apologize for any inconvenience and appreciate your understanding.<br><br>
Best regards,<br>InternSpark Team";

$sent = 0;
$errors = [];
foreach ($users as $user) {
    try {
        $mail->clearAllRecipients();
        $mail->addAddress($user['email'], $user['username']);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $body;
        $mail->send();
        $sent++;
    } catch (Exception $e) {
        $errors[] = $user['email'] . ': ' . $mail->ErrorInfo;
    }
}

$response = ['success' => $sent > 0, 'sent' => $sent, 'errors' => $errors];
echo json_encode($response);

if ($response['success']) {
    echo '<script>toast.success("Maintenance email sent to all users.");</script>';
    echo '<script>setMaintenanceStart("");</script>';
    echo '<script>setMaintenanceEnd("");</script>';
} else {
    echo '<script>toast.error("' . ($response['message'] ?? "Failed to send maintenance email.") . '");</script>';
}