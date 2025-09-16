<?php
require_once __DIR__ . '/../../config/cors.php';
require_once '../../config/Database.php';
require_once '../../api/sessions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();

// Get student id
$studentId = $_SESSION['student_id'] ?? null;
if (!$studentId) {
    $userId = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT Student_Id FROM student WHERE User_Id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    $studentId = $row['Student_Id'];
}

// Get form data
$interviewId = $_POST['interview_id'] ?? null;
$reasonType = $_POST['reason_type'] ?? '';
$reasonText = $_POST['reason_text'] ?? '';

if (!$interviewId || !$reasonType || !$reasonText) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Check if request already exists
$stmt = $db->prepare("SELECT id FROM reschedule_requests WHERE interview_id = ? AND student_id = ?");
$stmt->execute([$interviewId, $studentId]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle medical proof upload only for medical reason
$medicalProofPath = null;
if ($reasonType === 'medical') {
    if (isset($_FILES['medical_proof']) && $_FILES['medical_proof']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/medical_proofs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filename = uniqid('proof_') . '.pdf';
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['medical_proof']['tmp_name'], $targetPath)) {
            $medicalProofPath = 'uploads/medical_proofs/' . $filename;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload medical proof']);
            exit;
        }
    } else {
        // Medical reason must have proof
        echo json_encode(['success' => false, 'message' => 'Medical proof required for medical reason']);
        exit;
    }
}

if ($existing) {
    // Update existing request
    if ($reasonType === 'medical') {
        $stmt = $db->prepare(
            "UPDATE reschedule_requests SET reason_type = ?, reason_text = ?, medical_proof = ?, requested_at = NOW() WHERE id = ?"
        );
        $success = $stmt->execute([
            $reasonType,
            $reasonText,
            $medicalProofPath,
            $existing['id']
        ]);
    } else {
        // For non-medical, remove medical proof
        $stmt = $db->prepare(
            "UPDATE reschedule_requests SET reason_type = ?, reason_text = ?, medical_proof = NULL, requested_at = NOW() WHERE id = ?"
        );
        $success = $stmt->execute([
            $reasonType,
            $reasonText,
            $existing['id']
        ]);
    }
} else {
    // Insert new request
    if ($reasonType === 'medical') {
        $stmt = $db->prepare(
            "INSERT INTO reschedule_requests 
                (interview_id, student_id, reason_type, reason_text, medical_proof, requested_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $success = $stmt->execute([
            $interviewId,
            $studentId,
            $reasonType,
            $reasonText,
            $medicalProofPath
        ]);
    } else {
        $stmt = $db->prepare(
            "INSERT INTO reschedule_requests 
                (interview_id, student_id, reason_type, reason_text, medical_proof, requested_at)
             VALUES (?, ?, ?, ?, NULL, NOW())"
        );
        $success = $stmt->execute([
            $interviewId,
            $studentId,
            $reasonType,
            $reasonText
        ]);
    }
}

if ($success) {
    echo json_encode(['success' => true, 'message' => $existing ? 'Reschedule request updated' : 'Reschedule request submitted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit request']);
}