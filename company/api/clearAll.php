<?php
require_once "../models/CompanyNotification.php";
require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/cors.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$db = (new Database())->getConnection();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get company id
$stmt = $db->prepare("SELECT Com_Id FROM company WHERE User_Id = ?");
$stmt->execute([$_SESSION['user_id']]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    echo json_encode(['success' => false, 'message' => 'Company not found']);
    exit;
}

$companyId = $company['Com_Id'];

// Soft clear: update timestamp
$stmt = $db->prepare("UPDATE company SET notifications_cleared_at = NOW() WHERE Com_Id = ?");
$stmt->execute([$companyId]);

echo json_encode(['success' => true, 'message' => 'All notifications cleared']);
