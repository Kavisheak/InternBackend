<?php
require_once __DIR__ . '/../../config/cors.php';
require_once '../../config/Database.php';
require_once '../../api/sessions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();

// Get student id (adjust if needed)
$studentId = $_SESSION['student_id'] ?? null;
if (!$studentId) {
    // If you only have user_id, fetch student_id from student table
    $userId = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT Student_Id FROM student WHERE User_Id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    $studentId = $row['Student_Id'];
}

$sql = "SELECT 
            iv.id,
            iv.interview_date,
            iv.interview_time,
            iv.interview_type,
            iv.meeting_link,
            iv.location,
            i.title AS internship_title,
            c.company_name AS company_name,
            rr.id AS reschedule_id,
            rr.reason_type,
            rr.reason_text,
            rr.medical_proof
        FROM interview_schedule iv
        JOIN internship i ON iv.internship_id = i.Internship_Id
        JOIN company c ON iv.company_id = c.Com_Id
        LEFT JOIN reschedule_requests rr ON rr.interview_id = iv.id AND rr.student_id = ?
        WHERE iv.student_id = ?
        ORDER BY iv.interview_date DESC, iv.interview_time DESC";

$stmt = $db->prepare($sql);
$stmt->execute([$studentId, $studentId]);
$interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'interviews' => $interviews]);