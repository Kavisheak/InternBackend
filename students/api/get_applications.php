<?php
// filepath: c:\xampp\htdocs\InternBackend\students\api\get_applications.php

require_once "../../api/sessions.php";
require_once __DIR__ . "/../../config/cors.php";
require_once __DIR__ . "/../../config/Database.php";
require_once __DIR__ . '/../models/Application.php';

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$userId = $_SESSION['user_id'];
try {
    $db = (new Database())->getConnection();
    $appModel = new Application($db);

    $studentId = $appModel->getStudentIdByUserId($userId);
    if (!$studentId) {
        echo json_encode(["success" => false, "message" => "Student not found"]);
        exit;
    }

    $apps = $appModel->getApplicationsForStudent($studentId);

    echo json_encode(["success" => true, "applications" => $apps]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error"]);
}