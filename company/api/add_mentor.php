<?php
require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../models/Internship.php'; // adjust path as needed

header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true);

$db = (new Database())->getConnection(); // <-- ADD THIS LINE

$companyId = isset($data['company_id']) ? intval($data['company_id']) : 0;
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$expertise = trim($data['expertise'] ?? '');

if (!$companyId || !$name || !$email || !$expertise) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Prevent same email for different names
$stmt = $db->prepare("SELECT name FROM mentors WHERE company_id = ? AND email = ?");
$stmt->execute([$companyId, $email]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);
if ($existing && strtolower($existing['name']) !== strtolower($name)) {
    echo json_encode(['success' => false, 'message' => 'This email is already used by another mentor name!']);
    exit;
}

// Prevent duplicate (same name, email, expertise)
$stmt = $db->prepare("SELECT id FROM mentors WHERE company_id = ? AND name = ? AND email = ? AND expertise = ?");
$stmt->execute([$companyId, $name, $email, $expertise]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Mentor already exists for this expertise']);
    exit;
}

// Insert mentor
$stmt = $db->prepare("INSERT INTO mentors (company_id, name, email, expertise) VALUES (?, ?, ?, ?)");
$success = $stmt->execute([$companyId, $name, $email, $expertise]);

if ($success) {
    echo json_encode(['success' => true, 'mentor' => [
        'id' => $db->lastInsertId(),
        'name' => $name,
        'email' => $email,
        'expertise' => $expertise
    ]]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add mentor']);
}