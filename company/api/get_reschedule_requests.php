
<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/Interview.php';
require_once __DIR__ . '/../../api/sessions.php';
require_once __DIR__ . '/../../config/cors.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();
$interviewModel = new Interview($db);

$interviewId = $_GET['interview_id'] ?? null;
if (!$interviewId) {
    echo json_encode(['success' => false, 'message' => 'Missing interview ID']);
    exit;
}

$request = $interviewModel->getRescheduleRequest($interviewId);

echo json_encode(['success' => true, 'request' => $request]);