
<?php
require_once "../../api/sessions.php";
require_once "../../config/Database.php";
require_once __DIR__ . "/../../config/cors.php";

header("Content-Type: application/json");

// Only allow logged-in students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    $db = (new Database())->getConnection();

    // Get student id from user id
    $stmt = $db->prepare("SELECT Student_Id, profile_img FROM student WHERE User_Id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo json_encode(["success" => false, "message" => "Student not found"]);
        exit;
    }

    // Delete the image file from server if exists
    if (!empty($student['profile_img'])) {
        $imgPath = "../../" . $student['profile_img'];
        if (file_exists($imgPath)) {
            unlink($imgPath);
        }
    }

    // Remove image reference from DB
    $stmt = $db->prepare("UPDATE student SET profile_img = '' WHERE Student_Id = ?");
    $stmt->execute([$student['Student_Id']]);

    echo json_encode(["success" => true, "message" => "Profile image deleted"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error"]);
}