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
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();
$interviewModel = new Interview($db);

$data = json_decode(file_get_contents('php://input'), true);
$requestId = $data['request_id'] ?? null;
$status = $data['status'] ?? null;

if (!$requestId || !in_array($status, ['accepted', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid data']);
    exit;
}

$success = $interviewModel->updateRescheduleStatus($requestId, $status);

// Send notification if rejected
if ($success && $status === 'rejected') {
    require_once __DIR__ . '/../../students/models/StudentNotification.php';

    // Get student and interview info
    $stmt = $db->prepare("SELECT rr.student_id, s.fname, s.lname, i.title AS internshipTitle, c.company_name
        FROM reschedule_requests rr
        JOIN interview_schedule iv ON rr.interview_id = iv.id
        JOIN internship i ON iv.internship_id = i.Internship_Id
        JOIN company c ON iv.company_id = c.Com_Id
        JOIN student s ON rr.student_id = s.Student_Id
        WHERE rr.id = ?");
    $stmt->execute([$requestId]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($info) {
        $studentId = $info['student_id'];
        $studentName = trim($info['fname'] . " " . $info['lname']);
        $internshipTitle = $info['internshipTitle'];
        $companyName = $info['company_name'];

        $notif = new StudentNotification($db);
        $notif->notifyRescheduleRejected($studentId, $studentName, $internshipTitle, $companyName);
    }
}

echo json_encode(['success' => $success, 'message' => $success ? 'Status updated' : 'Failed']);