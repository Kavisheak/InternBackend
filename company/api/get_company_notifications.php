
<?php
require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/Database.php';
header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$userId = $_SESSION['user_id'];
try {
    $db = (new Database())->getConnection();

    // Get company id
    $stmt = $db->prepare("SELECT Com_Id FROM company WHERE User_Id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(["success" => false, "message" => "Company not found"]);
        exit;
    }
    $companyId = $row['Com_Id'];

    $notifications = [];

    // 1. New applications today per post
    $stmt = $db->prepare("
        SELECT i.title, COUNT(a.Application_Id) as new_count
        FROM internship i
        JOIN application a ON a.Internship_Id = i.Internship_Id
        WHERE i.Company_Id = ? AND DATE(a.applied_date) = CURDATE()
        GROUP BY i.Internship_Id
    ");
    $stmt->execute([$companyId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ($row['new_count'] > 0) {
            $notifications[] = [
                "type" => "new_applications",
                "message" => "{$row['new_count']} new application(s) for \"{$row['title']}\".",
                "time" => "Today",
            ];
        }
    }

    // 2. Posts expiring in 24 hours
    $stmt = $db->prepare("
        SELECT title, deadline
        FROM internship
        WHERE Company_Id = ? AND deadline = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    ");
    $stmt->execute([$companyId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $notifications[] = [
            "type" => "expiring",
            "message" => "Your post \"{$row['title']}\" will expire tomorrow.",
            "time" => "Soon",
        ];
    }

    echo json_encode([
        "success" => true,
        "notifications" => $notifications
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error"]);
}