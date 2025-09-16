<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/Interview.php';
require_once __DIR__ . '/../../api/sessions.php';
require_once __DIR__ . '/../../config/cors.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$userId = $_SESSION['user_id'] ?? null;
if (!$userId || $_SESSION['role'] !== 'company') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();

// Get company id
$stmt = $db->prepare("SELECT Com_Id FROM company WHERE User_Id = ?");
$stmt->execute([$userId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Company not found']);
    exit;
}
$companyId = $row['Com_Id'];

$interview = new Interview($db);

// Check if interview already existed (reschedule) BEFORE upsert
$stmtCheck = $db->prepare("SELECT id FROM interview_schedule WHERE application_id = ?");
$stmtCheck->execute([$data['candidate']['Application_Id']]);
$existingInterview = $stmtCheck->fetch(PDO::FETCH_ASSOC);

$success = $interview->upsertInterview(
    $data['candidate']['Application_Id'],
    $data['candidate']['id'],
    $companyId,
    $data['candidate']['Internship_Id'],
    $data['type'],
    $data['date'],
    $data['time'],
    $data['meetingLink'],
    $data['location']
);

if ($success) {
    require_once __DIR__ . '/../../students/models/StudentNotification.php';

    // Get student and internship/company info
    $stmt = $db->prepare("SELECT s.Student_Id, s.fname, s.lname, c.company_name, i.title 
        FROM student s
        JOIN application a ON a.Student_Id = s.Student_Id
        JOIN internship i ON a.Internship_Id = i.Internship_Id
        JOIN company c ON i.Company_Id = c.Com_Id
        WHERE s.Student_Id = :student_id AND a.Application_Id = :application_id");
    $stmt->execute([
        ':student_id' => $data['candidate']['id'],
        ':application_id' => $data['candidate']['Application_Id']
    ]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($info) {
        $studentId = $info['Student_Id'];
        $companyName = $info['company_name'];
        $internshipTitle = $info['title'];
        $studentName = trim($info['fname'] . " " . $info['lname']);
        $interviewDate = $data['date'];
        $interviewTime = $data['time'];

        $notif = new StudentNotification($db);

        // If interview existed before, it's a reschedule
        if ($existingInterview && $existingInterview['id']) {
            // This is a reschedule (could be from a request)
            // Check if there is a pending reschedule request for this interview
            $stmtReq = $db->prepare("SELECT rr.id FROM reschedule_requests rr
                JOIN interview_schedule iv ON rr.interview_id = iv.id
                WHERE iv.application_id = ? AND rr.status = 'pending'
                ORDER BY rr.requested_at DESC LIMIT 1");
            $stmtReq->execute([$data['candidate']['Application_Id']]);
            $rescheduleReq = $stmtReq->fetch(PDO::FETCH_ASSOC);

            if ($rescheduleReq) {
                // Notify accepted
                $notif->notifyRescheduleAccepted($studentId, $studentName, $internshipTitle, $companyName, $interviewDate, $interviewTime);
            } else {
                // Normal reschedule (not from request)
                $notif->notifyInterviewReschedule($studentId, $studentName, $internshipTitle, $companyName, $interviewDate, $interviewTime);
            }
        } else {
            $notif->notifyInterviewSchedule($studentId, $studentName, $internshipTitle, $companyName, $interviewDate, $interviewTime);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Interview scheduled/updated successfully']);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to schedule/update interview']);
    exit;
}