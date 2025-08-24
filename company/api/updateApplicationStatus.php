<?php
// filepath: c:\xampp\htdocs\InternBackend\company\api\updateApplicationStatus.php

require_once "../../api/sessions.php";
require_once __DIR__ . "/../../config/cors.php";
require_once __DIR__ . "/../../config/Database.php";

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
        echo json_encode(["success" => true, "message" => "Status updated"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Update failed"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error"]);
}