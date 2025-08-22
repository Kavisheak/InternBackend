
<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../api/sessions.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/Bookmark.php';

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['internship_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing internship_id']);
    exit;
}

$db = (new Database())->getConnection();
$bookmark = new Bookmark($db);

// Get student id from users table
$stmt = $db->prepare("SELECT Student_Id FROM student WHERE User_Id = ?");
$stmt->execute([$_SESSION['user_id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}
$studentId = $row['Student_Id'];
$internshipId = $data['internship_id'];

if ($bookmark->isBookmarked($studentId, $internshipId)) {
    $success = $bookmark->remove($studentId, $internshipId);
    $action = 'removed';
} else {
    $success = $bookmark->add($studentId, $internshipId);
    $action = 'added';
}

echo json_encode([
    'success' => $success,
    'action' => $action
]);