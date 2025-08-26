<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../api/sessions.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/Bookmark.php';
require_once __DIR__ . '/../models/StudentNotification.php';

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();
$bookmark = new Bookmark($db);
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

// Get all bookmarked internships
$internshipIds = $bookmark->getAll($studentId);

if (!empty($internshipIds)) {
    $placeholders = implode(',', array_fill(0, count($internshipIds), '?'));
    $stmt = $db->prepare("SELECT Internship_Id, title, deadline FROM internship WHERE Internship_Id IN ($placeholders)");
    $stmt->execute($internshipIds);
    $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $expiredTitles = [];
    foreach ($internships as $internship) {
        if ($internship['deadline'] && strtotime($internship['deadline']) < strtotime(date('Y-m-d'))) {
            $bookmark->remove($studentId, $internship['Internship_Id']);
            $notif->notifyBookmarkExpired($studentId, $internship['title']);
            $expiredTitles[] = $internship['title'];
        }
    }
}

echo json_encode(['success' => true, 'expired' => $expiredTitles]);