<?php
require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$companyId = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
if (!$companyId) {
    echo json_encode(['success' => false, 'message' => 'Invalid company id']);
    exit;
}

$db = (new Database())->getConnection();

// Get all allocations with student and mentor info
$stmt = $db->prepare("
    SELECT 
        ma.student_id, ma.mentor_id,
        s.fname AS student_name, u.email AS student_email,
        m.name AS mentor_name, m.email AS mentor_email
    FROM mentor_allocations ma
    JOIN student s ON ma.student_id = s.Student_Id
    JOIN users u ON s.User_Id = u.User_Id
    JOIN mentors m ON ma.mentor_id = m.id
    WHERE ma.company_id = ?
");
$stmt->execute([$companyId]);
$allocs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group students by mentor
$mentorMap = [];
foreach ($allocs as $row) {
    $mentorMap[$row['mentor_id']]['mentor_name'] = $row['mentor_name'];
    $mentorMap[$row['mentor_id']]['mentor_email'] = $row['mentor_email'];
    $mentorMap[$row['mentor_id']]['students'][] = [
        'name' => $row['student_name'],
        'email' => $row['student_email']
    ];
}

// Send emails
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com'; // <-- your SMTP server
$mail->SMTPAuth = true;
$mail->Username = 'skavisheak.official@gmail.com'; // <-- your SMTP username
$mail->Password = 'wrwr bhyd eknt drjp';   // <-- your SMTP password
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
$mail->setFrom('skavisheak.official@gmail.com', 'Your Company');

$mentorSuccess = 0;
$studentSuccess = 0;

// Send to mentors
foreach ($mentorMap as $mentorId => $info) {
    try {
        $mail->clearAllRecipients();
        $mail->addAddress($info['mentor_email'], $info['mentor_name']);
        $mail->Subject = "Your Allocated Students";
        $body = "Dear {$info['mentor_name']},<br><br>You have been allocated the following students:<ul>";
        foreach ($info['students'] as $stu) {
            $body .= "<li>{$stu['name']} ({$stu['email']})</li>";
        }
        $body .= "</ul><br>Regards,<br>Your Company";
        $mail->isHTML(true);
        $mail->Body = $body;
        $mail->send();
        $mentorSuccess++;
    } catch (Exception $e) {
        // log error if needed
    }
}

// Send to students
foreach ($allocs as $row) {
    try {
        $mail->clearAllRecipients();
        $mail->addAddress($row['student_email'], $row['student_name']);
        $mail->Subject = "Your Mentor Allocation";
        $body = "Dear {$row['student_name']},<br><br>Your mentor is <b>{$row['mentor_name']}</b> ({$row['mentor_email']}).<br>Best wishes for your internship!<br><br>Regards,<br>Your Company";
        $mail->isHTML(true);
        $mail->Body = $body;
        $mail->send();
        $studentSuccess++;
    } catch (Exception $e) {
        // log error if needed
    }
}

echo json_encode([
    'success' => true,
    'mentors_emailed' => $mentorSuccess,
    'students_emailed' => $studentSuccess
]);