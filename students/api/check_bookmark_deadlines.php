<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/StudentNotification.php';

$db = (new Database())->getConnection();
$notif = new StudentNotification($db);

// Get all bookmarks with internship info
$stmt = $db->query(
    "SELECT b.bookmark_id, b.student_id, b.internship_id, i.title, i.deadline
     FROM bookmarked b
     JOIN internship i ON b.internship_id = i.Internship_Id"
);

$now = new DateTime();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $deadline = new DateTime($row['deadline']);
    $interval = $now->diff($deadline);
    $hoursLeft = ($deadline->getTimestamp() - $now->getTimestamp()) / 3600;

    // Remove bookmark if deadline passed
    if ($now > $deadline) {
        $del = $db->prepare("DELETE FROM bookmarked WHERE bookmark_id = ?");
        $del->execute([$row['bookmark_id']]);
        continue;
    }

    // Notify if less than 24h left and not already notified
    if ($hoursLeft <= 24 && $hoursLeft > 0) {
        $notif->notifyBeforeDeadline($row['student_id'], $row['internship_id'], $row['title'], $row['deadline']);
    }
}
echo json_encode(['success' => true]);