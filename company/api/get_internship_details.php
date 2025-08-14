<?php
require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../models/Internship.php';

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(["success" => false, "message" => "Internship ID is required"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $internship = new Internship($db, $_SESSION['user_id']);
    $record = $internship->getById(intval($_GET['id']));

    if ($record) {
        echo json_encode(["success" => true, "internship" => $record]);
    } else {
        echo json_encode(["success" => false, "message" => "Not found or unauthorized"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
