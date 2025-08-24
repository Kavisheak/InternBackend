
<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../api/sessions.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/Report.php';

header("Content-Type: application/json");

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Only allow company users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$application_id = isset($data['application_id']) ? intval($data['application_id']) : 0;
$reason = trim($data['reason'] ?? '');

if ($application_id <= 0 || !$reason) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $report = new Report($db);

    // Get company id from session user_id
    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT Com_Id FROM company WHERE User_Id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Company not found.']);
        exit;
    }
    $company_id = $row['Com_Id'];

    // Get student id from application
    $stmt2 = $db->prepare("SELECT Student_Id FROM application WHERE Application_Id = ?");
    $stmt2->execute([$application_id]);
    $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    if (!$row2) {
        echo json_encode(['success' => false, 'message' => 'Application not found.']);
        exit;
    }
    $student_id = $row2['Student_Id'];

    // Prevent duplicate report
    if ($report->alreadyReported($company_id, $student_id)) {
        echo json_encode(['success' => false, 'message' => 'You have already reported this student.']);
        exit;
    }

    // Save report
    if ($report->addStudentReport($company_id, $student_id, $reason)) {
        echo json_encode(['success' => true, 'message' => 'Report submitted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit report.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}