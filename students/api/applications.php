<?php
// filepath: c:\xampp\htdocs\InternBackend\students\api\applications.php

require_once "../../api/sessions.php";
require_once __DIR__ . "/../../config/cors.php";
require_once __DIR__ . "/../../config/Database.php";
require_once __DIR__ . "/../models/Application.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['internship_id']) || !is_numeric($data['internship_id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing or invalid internship_id"]);
    exit;
}

$internshipId = intval($data['internship_id']);
$userId = intval($_SESSION['user_id']);

try {
    $db = (new Database())->getConnection();
    $app = new Application($db);

    $studentId = $app->getStudentIdByUserId($userId);
    if (!$studentId) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Student profile not found"]);
        exit;
    }

    if ($app->checkDuplicate($studentId, $internshipId)) {
        echo json_encode(["success" => false, "message" => "You have already applied for this internship"]);
        exit;
    }

    if ($app->apply($studentId, $internshipId)) {
        echo json_encode(["success" => true, "message" => "Application submitted successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to submit application"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to submit application"]);
}