<?php
require_once '../../config/Database.php';
require_once '../../api/sessions.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check company session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$interviewId = $data['interview_id'] ?? null;
$status = $data['status'] ?? null;
$feedback = $data['feedback'] ?? null;

$allowedStatuses = ['pending', 'accepted', 'rejected', 'absent'];
if (!$interviewId || !$status || !in_array($status, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid parameters']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    if ($feedback !== null) {
        $stmt = $db->prepare("UPDATE interview_schedule SET status = ?, company_feedback = ? WHERE id = ?");
        $stmt->execute([$status, $feedback, $interviewId]);
    } else {
        $stmt = $db->prepare("UPDATE interview_schedule SET status = ? WHERE id = ?");
        $stmt->execute([$status, $interviewId]);
    }

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Interview status and feedback updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made or invalid interview ID']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

