<?php
session_start();
require_once __DIR__ . '/../../config/cors.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/Student.php';

$db = (new Database())->getConnection();
$studentModel = new Student($db);

$userId = $_SESSION['user_id'];

$student = $studentModel->getProfileByUserId($userId);

if ($student) {
    // Fetch skills for student
    $studentId = $studentModel->getStudentIdByUserId($userId);
    $skills = [];

    if ($studentId) {
        $stmt = $db->prepare("SELECT skill_name FROM skill WHERE Student_Id = :studentId");
        $stmt->bindParam(':studentId', $studentId);
        $stmt->execute();
        $skills = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $student['skills'] = $skills;
    $student['email'] = $_SESSION['email'] ?? null;

    echo json_encode(["success" => true, "student" => $student]);
} else {
    // No student profile yet, return only email
    echo json_encode([
        "success" => true,
        "student" => [
            "email" => $_SESSION['email'] ?? null,
            "skills" => []
        ]
    ]);
}
