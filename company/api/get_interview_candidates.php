<?php
require_once '../../config/Database.php';
require_once '../../api/sessions.php';
require_once __DIR__ . '/../../config/cors.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();
$companyId = $_SESSION['company_id'] ?? null;

if (!$companyId) {
    echo json_encode(['success' => false, 'message' => 'Company not found']);
    exit;
}

$sql = "SELECT
            iv.id,
            s.fname,
            s.lname,
            u.email,
            iv.interview_date,
            iv.interview_time,
            iv.status,
            iv.company_feedback, 
            COALESCE(docs.cv_url, s.cv_file) AS cv_url,
            docs.certificates_json,
            docs.other_docs_json
        FROM interview_schedule iv
        JOIN student s ON iv.student_id = s.Student_Id
        JOIN users u ON s.User_Id = u.User_Id
        LEFT JOIN interview_documents docs ON docs.interview_id = iv.id AND docs.student_id = s.Student_Id
        WHERE iv.company_id = ?
          AND iv.interview_date <= CURDATE()
        ORDER BY iv.interview_date DESC, iv.interview_time DESC";

$stmt = $db->prepare($sql);
$stmt->execute([$companyId]);
$interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($interviews as &$iv) {
    $iv['certificates_url'] = $iv['certificates_json'] ? json_decode($iv['certificates_json'], true) : [];
    $iv['other_docs_url'] = $iv['other_docs_json'] ? json_decode($iv['other_docs_json'], true) : [];
    unset($iv['certificates_json'], $iv['other_docs_json']);
}

echo json_encode(['success' => true, 'interviews' => $interviews]);