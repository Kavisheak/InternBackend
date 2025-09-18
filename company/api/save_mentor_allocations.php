<?php
require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/cors.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['allocations']) || !isset($data['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$db = (new Database())->getConnection();
$allocations = $data['allocations'];
$companyId = intval($data['company_id']);

foreach ($allocations as $key => $mentorId) {
    // $key is "studentId-post"
    $parts = explode('-', $key, 2);
    $studentId = $parts[0];
    $post = $parts[1] ?? '';
    $stmt = $db->prepare("INSERT INTO mentor_allocations (student_id, mentor_id, company_id, post) VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE mentor_id = VALUES(mentor_id)");
    $stmt->execute([$studentId, $mentorId, $companyId, $post]);
}

echo json_encode(['success' => true]);