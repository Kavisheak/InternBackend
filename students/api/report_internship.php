
<?php
require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/Database.php';

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
$internship_id = isset($data['internship_id']) ? intval($data['internship_id']) : 0;
$reason = trim($data['reason'] ?? '');

if ($internship_id <= 0 || !$reason) {
    echo json_encode(["success" => false, "message" => "Invalid data"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    // Get student id
    $stmt = $db->prepare("SELECT Student_Id FROM student WHERE User_Id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        echo json_encode(["success" => false, "message" => "Student not found"]);
        exit;
    }
    $student_id = $student['Student_Id'];

    // Check if already reported
    $stmt = $db->prepare("SELECT Report_Id FROM report WHERE Student_Id = ? AND Internship_Id = ?");
    $stmt->execute([$student_id, $internship_id]);
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "You have already reported this internship."]);
        exit;
    }

    // Insert report
    $stmt = $db->prepare("INSERT INTO report (Student_Id, Internship_Id, reason) VALUES (?, ?, ?)");
    $stmt->execute([$student_id, $internship_id, $reason]);

    echo json_encode(["success" => true, "message" => "Report submitted successfully."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error"]);
}