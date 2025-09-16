<?php
// filepath: c:/xampp/htdocs/InternBackend/students/api/upload_student_interview_documents.php

require_once '../../config/Database.php';
require_once '../../api/sessions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();

// Get student id from users table
$stmt = $db->prepare("SELECT Student_Id FROM student WHERE User_Id = ?");
$stmt->execute([$_SESSION['user_id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}
$studentId = $row['Student_Id'];

$interviewId = $_POST['interview_id'] ?? null;
if (!$interviewId) {
    echo json_encode(['success' => false, 'message' => 'Interview ID required']);
    exit;
}

// Handle file uploads
$uploadDir = "../../uploads/student_docs/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

function saveFile($file, $prefix) {
    global $uploadDir;
    $filename = $prefix . "_" . time() . "_" . basename($file['name']);
    $target = $uploadDir . $filename;
    if (move_uploaded_file($file['tmp_name'], $target)) {
        return "uploads/student_docs/" . $filename;
    }
    return "";
}

$cvUrl = "";
$certificates = [];
$otherDocs = [];

if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
    $cvUrl = saveFile($_FILES['cv'], "cv");
} else {
    // Get default CV from student table if not uploaded
    $stmt = $db->prepare("SELECT cv_file FROM student WHERE Student_Id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($student && !empty($student['cv_file'])) {
        $cvUrl = $student['cv_file'];
    }
}
if (isset($_FILES['certificates'])) {
    foreach ($_FILES['certificates']['tmp_name'] as $idx => $tmpName) {
        if ($tmpName) {
            $fileArr = [
                'name' => $_FILES['certificates']['name'][$idx],
                'tmp_name' => $tmpName
            ];
            $certUrl = saveFile($fileArr, "cert");
            if ($certUrl) $certificates[] = ['name' => $fileArr['name'], 'url' => $certUrl];
        }
    }
}
if (isset($_FILES['other_docs'])) {
    foreach ($_FILES['other_docs']['tmp_name'] as $idx => $tmpName) {
        if ($tmpName) {
            $fileArr = [
                'name' => $_FILES['other_docs']['name'][$idx],
                'tmp_name' => $tmpName
            ];
            $docUrl = saveFile($fileArr, "doc");
            if ($docUrl) $otherDocs[] = ['name' => $fileArr['name'], 'url' => $docUrl];
        }
    }
}

// Save to a table, e.g., interview_documents
$stmt = $db->prepare("REPLACE INTO interview_documents (interview_id, student_id, cv_url, certificates_json, other_docs_json)
    VALUES (?, ?, ?, ?, ?)");
$stmt->execute([
    $interviewId,
    $studentId,
    $cvUrl,
    json_encode($certificates),
    json_encode($otherDocs)
]);

echo json_encode(['success' => true, 'message' => 'Documents uploaded successfully']);