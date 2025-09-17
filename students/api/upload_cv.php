<?php
// filepath: c:\xampp\htdocs\InternBackend\students\api\upload_cv.php
require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/cors.php';

$targetDir = "../../uploads/student_cvs/";
if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

if (!isset($_FILES['cv'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
    exit;
}
$file = $_FILES['cv'];
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = "cv_" . ($_SESSION['user_id'] ?? time()) . "_" . time() . "." . $ext;
$targetFile = $targetDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetFile)) {
    $url = "/InternBackend/uploads/student_cvs/" . $filename;
    echo json_encode(['success' => true, 'cv_url' => $url]);
} else {
    echo json_encode(['success' => false, 'message' => 'Upload failed.']);
}