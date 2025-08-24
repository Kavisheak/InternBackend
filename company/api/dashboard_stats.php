<?php
// filepath: c:\xampp\htdocs\InternBackend\company\api\dashboard_stats.php
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

    // Active internships: not exceeded deadline and not filled
    $stmt = $db->prepare("
        SELECT i.Internship_Id, i.title, i.deadline, i.application_limit,
            (SELECT COUNT(*) FROM application a WHERE a.Internship_Id = i.Internship_Id) AS app_count
        FROM internship i
        WHERE i.Company_Id = ? AND i.is_active = 1 AND i.deadline >= CURDATE()
    ");
    $stmt->execute([$companyId]);
    $activeInternships = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['app_count'] < $row['application_limit']) {
            $activeInternships[] = $row;
        }
    }

    // Total applications for all company posts
    $stmt = $db->prepare("
        SELECT COUNT(*) AS total
        FROM application a
        JOIN internship i ON a.Internship_Id = i.Internship_Id
        WHERE i.Company_Id = ?
    ");
    $stmt->execute([$companyId]);
    $totalApplications = $stmt->fetchColumn();

    // New applications (applied today)
    $stmt = $db->prepare("
        SELECT COUNT(*) AS newapps
        FROM application a
        JOIN internship i ON a.Internship_Id = i.Internship_Id
        WHERE i.Company_Id = ? AND DATE(a.applied_date) = CURDATE()
    ");
    $stmt->execute([$companyId]);
    $newApplications = $stmt->fetchColumn();

    echo json_encode([
        "success" => true,
        "activeInternships" => $activeInternships,
        "totalApplications" => intval($totalApplications),
        "newApplications" => intval($newApplications)
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error"]);
}