
<?php
require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/Database.php';

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(["alreadyReported" => false]);
    exit;
}

$internship_id = isset($_GET['internship_id']) ? intval($_GET['internship_id']) : 0;
if ($internship_id <= 0) {
    echo json_encode(["alreadyReported" => false]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT Student_Id FROM student WHERE User_Id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        echo json_encode(["alreadyReported" => false]);
        exit;
    }
    $student_id = $student['Student_Id'];

    $stmt = $db->prepare("SELECT Report_Id FROM report WHERE Student_Id = ? AND Internship_Id = ?");
    $stmt->execute([$student_id, $internship_id]);
    $alreadyReported = $stmt->fetch() ? true : false;

    echo json_encode(["alreadyReported" => $alreadyReported]);
} catch (Exception $e) {
    echo json_encode(["alreadyReported" => false]);
}