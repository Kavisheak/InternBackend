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
        ma.student_id, ma.mentor_id, ma.post, ma.mail_sent,
        s.fname AS student_name, u.email AS student_email,
        m.name AS mentor_name, m.email AS mentor_email,
        i.title AS internship_title
    FROM mentor_allocations ma
    JOIN student s ON ma.student_id = s.Student_Id
    JOIN users u ON s.User_Id = u.User_Id
    JOIN mentors m ON ma.mentor_id = m.id
    JOIN internship i ON i.title = ma.post AND i.Company_Id = ma.company_id
    WHERE ma.company_id = ? AND ma.mentor_id IS NOT NULL AND ma.mail_sent = 0
");
$stmt->execute([$companyId]);
$allocs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group students by mentor and internship
$mentorMap = [];
foreach ($allocs as $row) {
    $mentorMap[$row['mentor_id']]['mentor_name'] = $row['mentor_name'];
    $mentorMap[$row['mentor_id']]['mentor_email'] = $row['mentor_email'];
    $mentorMap[$row['mentor_id']]['internships'][$row['internship_title']][] = [
        'name' => $row['student_name'],
        'email' => $row['student_email']
    ];
}

// Fetch company name and email
$stmtCompany = $db->prepare("SELECT company_name, (SELECT email FROM users WHERE User_Id = company.User_Id) AS email FROM company WHERE Com_Id = ?");
$stmtCompany->execute([$companyId]);
$company = $stmtCompany->fetch(PDO::FETCH_ASSOC);
$companyName = $company && $company['company_name'] ? $company['company_name'] : 'Your Company';
$companyEmail = $company && $company['email'] ? $company['email'] : 'skavisheak.official@gmail.com';

// Send emails
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com'; 
$mail->SMTPAuth = true;
$mail->Username = 'skavisheak.official@gmail.com'; 
$mail->Password = 'wrwr bhyd eknt drjp';   
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
$mail->setFrom($companyEmail, $companyName);

$mentorSuccess = 0;
$studentSuccess = 0;

// Send to mentors
foreach ($mentorMap as $mentorId => $info) {
    try {
        $mail->clearAllRecipients();
        $mail->addAddress($info['mentor_email'], $info['mentor_name']);
        $mail->Subject = "Your Allocated Students";
        $body = "Dear {$info['mentor_name']},<br><br>You have been allocated the following students for each internship:<br>";
        foreach ($info['internships'] as $internshipTitle => $students) {
            $body .= "<b>$internshipTitle</b><ul>";
            foreach ($students as $stu) {
                $body .= "<li>{$stu['name']} ({$stu['email']})</li>";
            }
            $body .= "</ul>";
        }
        $body .= "<br>Regards,<br>{$companyName}";
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
        $body = "Dear {$row['student_name']},<br><br>
        You have been selected to the internship <b>{$row['internship_title']}</b>.<br>
        Your mentor is <b>{$row['mentor_name']}</b> ({$row['mentor_email']}).<br>
        Best wishes for your internship!<br><br>Regards,<br>{$companyName}";
        $mail->isHTML(true);
        $mail->Body = $body;
        $mail->send();
        $studentSuccess++;

        // Update mail_sent status
        $stmtUpdate = $db->prepare("UPDATE mentor_allocations SET mail_sent = 1 WHERE student_id = ? AND mentor_id = ? AND company_id = ? AND post = ?");
        $stmtUpdate->execute([$row['student_id'], $row['mentor_id'], $companyId, $row['post']]);
    } catch (Exception $e) {
        // log error if needed
    }
}

echo json_encode([
    'success' => true,
    'mentors_emailed' => $mentorSuccess,
    'students_emailed' => $studentSuccess
]);