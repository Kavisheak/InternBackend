<?php
header("Content-Type: application/json");
require_once(__DIR__ . '/../models/Internship.php');
require_once(__DIR__ . '/../../config/cors.php');
require_once(__DIR__ . '/../../config/Database.php');
require_once "../../api/sessions.php";



// Get user_id from session or request
$user_id = $_SESSION['user_id'] ?? $_POST['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id']) && $user_id) {
        $db = (new Database())->getConnection();
        $internshipObj = new Internship($db, $user_id);
        $result = $internshipObj->delete($_POST['id']);

        if ($result) {
            echo json_encode([
                "success" => true,
                "message" => "Internship removed successfully"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to remove internship"
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Missing internship ID or user ID"
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
}