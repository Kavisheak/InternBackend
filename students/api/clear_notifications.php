
<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../api/sessions.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/StudentNotification.php';

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();
$notif = new StudentNotification($db);

// Get student id from users table
$stmt = $db->prepare("SELECT Student_Id FROM student WHERE User_Id = ?");
$stmt->execute([$_SESSION['user_id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}
$studentId = $row['Student_Id'];

$notif->clearAll($studentId);
echo json_encode(['success' => true]);