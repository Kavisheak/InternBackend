<?php
// filepath: c:\xampp\htdocs\InternBackend\students\api\delete_application.php

require_once "../../api/sessions.php";
require_once __DIR__ . "/../../config/cors.php";
require_once __DIR__ . "/../../config/Database.php";
require_once __DIR__ . '/../models/Application.php';

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$appId = isset($data['application_id']) ? intval($data['application_id']) : 0;

if ($appId <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid application id"]);
    exit;
}

$userId = $_SESSION['user_id'];
try {
    $db = (new Database())->getConnection();
    $appModel = new Application($db);

    $stmt = $db->prepare("SELECT Student_Id FROM student WHERE User_Id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(["success" => false, "message" => "Student not found"]);
        exit;
    }
    $studentId = $row['Student_Id'];

    // Use Application model for deletion
    $success = $appModel->deleteApplication($appId, $studentId);
    if ($success) {
        echo json_encode(["success" => true, "message" => "Application cancelled"]);
    } else {
        echo json_encode(["success" => false, "message" => "Delete failed"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error"]);
}