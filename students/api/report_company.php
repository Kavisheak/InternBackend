<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../api/sessions.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/Report.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($data['company_id']) || !isset($data['reason'])) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

$db = (new Database())->getConnection();
$report = new Report($db);

// Get Student_Id from User_Id
$user_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT Student_Id FROM student WHERE User_Id = ?");
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Student not found.']);
    exit;
}
$student_id = $row['Student_Id'];

$company_id = intval($data['company_id']);
$reason = trim($data['reason']);

if ($report->alreadyReported($student_id, $company_id)) {
    echo json_encode(['success' => false, 'message' => 'You have already reported this company.']);
    exit;
}

if ($report->addCompanyReport($student_id, $company_id, $reason)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit report.']);
}