<?php
// filepath: c:\xampp\htdocs\InternBackend\company\api\updateApplicationStatus.php

require_once "../../api/sessions.php";
require_once __DIR__ . "/../../config/cors.php";
require_once __DIR__ . "/../../config/Database.php";
// Add StudentNotification class
require_once __DIR__ . "/../../students/models/StudentNotification.php";

header("Content-Type: application/json");

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

// Check company session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'company') {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$appId = isset($data['application_id']) ? intval($data['application_id']) : 0;
$status = isset($data['status']) ? trim($data['status']) : "";

if ($appId <= 0 || $status === "") {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("UPDATE application SET status = ?, updated_at = NOW() WHERE Application_Id = ?");
    $success = $stmt->execute([$status, $appId]);
    if ($success) {
        // Fetch student, internship, and company info for notification
        $stmt2 = $db->prepare(
            "SELECT a.Student_Id, i.title AS internship_title, c.company_name
             FROM application a
             JOIN internship i ON a.Internship_Id = i.Internship_Id
             JOIN company c ON i.Company_Id = c.Com_Id
             WHERE a.Application_Id = ?"
        );
        $stmt2->execute([$appId]);
        $row = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $notif = new StudentNotification($db);
            $notif->notifyStatusUpdate(
                $row['Student_Id'],
                $row['company_name'],
                $row['internship_title'],
                $status
            );
        }

        echo json_encode(["success" => true, "message" => "Status updated and student notified"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Update failed"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error"]);
}