
<?php
require_once '../../config/Database.php';
require_once '../../api/sessions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$interviewId = $data['interview_id'] ?? null;
$feedback = $data['feedback'] ?? null;

if (!$interviewId || $feedback === null) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("UPDATE interview_schedule SET company_feedback = ? WHERE id = ?");
    $stmt->execute([$feedback, $interviewId]);
    echo json_encode(['success' => true, 'message' => 'Feedback updated']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}