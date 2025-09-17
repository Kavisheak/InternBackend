<?php
require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/cors.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$mentorId = isset($data['mentor_id']) ? intval($data['mentor_id']) : 0;
$companyId = isset($data['company_id']) ? intval($data['company_id']) : 0;

if (!$mentorId || !$companyId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$db = (new Database())->getConnection();

// Delete allocations for this mentor
$stmtAlloc = $db->prepare("DELETE FROM mentor_allocations WHERE mentor_id = ? AND company_id = ?");
$stmtAlloc->execute([$mentorId, $companyId]);

// Delete mentor
$stmt = $db->prepare("DELETE FROM mentors WHERE id = ? AND company_id = ?");
$success = $stmt->execute([$mentorId, $companyId]);

echo json_encode(['success' => $success]);