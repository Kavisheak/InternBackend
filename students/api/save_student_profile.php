<?php
require_once "../../api/sessions.php";
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

// Collect POST data safely
$data = [
    'fname'      => $_POST['fname'] ?? '',
    'lname'      => $_POST['lname'] ?? '',
    'gender'     => $_POST['gender'] ?? '',
    'education'  => $_POST['education'] ?? '',
    'experience' => $_POST['experience'] ?? '',
    'phone'      => $_POST['phone'] ?? '',
    'github'     => $_POST['github'] ?? '',
    'linkedin'   => $_POST['linkedin'] ?? '',
    'user_id'    => $userId,
];

// Handle uploads
$uploadDir = __DIR__ . '/../../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$profilePath = null;
if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
    $filename = 'profile_' . $userId . '_' . time() . '_' . basename($_FILES['profileImage']['name']);
    $profilePath = 'uploads/' . $filename;
    move_uploaded_file($_FILES['profileImage']['tmp_name'], __DIR__ . '/../../' . $profilePath);
}

$cvPath = null;
if (isset($_FILES['cvFile']) && $_FILES['cvFile']['error'] === UPLOAD_ERR_OK) {
    $filename = 'cv_' . $userId . '_' . time() . '_' . basename($_FILES['cvFile']['name']);
    $cvPath = 'uploads/' . $filename;
    move_uploaded_file($_FILES['cvFile']['tmp_name'], __DIR__ . '/../../' . $cvPath);
}

// Save or update profile
$profileResult = $studentModel->saveOrUpdate($data, $profilePath, $cvPath);
if (!$profileResult['success']) {
    echo json_encode($profileResult);
    exit;
}

// Save skills
$skills = [];
if (isset($_POST['skills'])) {
    $skills = json_decode($_POST['skills'], true);
    if (!is_array($skills)) {
        $skills = [];
    }
}

error_log("Skills received: " . print_r($skills, true));

$studentId = $studentModel->getStudentIdByUserId($userId);
if ($studentId) {
    $skillsResult = $studentModel->saveSkills($studentId, $skills);
    if (!$skillsResult['success']) {
        echo json_encode($skillsResult);
        exit;
    }
} else {
    // Could not get student id after saveOrUpdate
    echo json_encode(["success" => false, "message" => "Failed to save skills: no student ID found"]);
    exit;
}

echo json_encode(["success" => true, "message" => "Profile and skills saved successfully"]);
