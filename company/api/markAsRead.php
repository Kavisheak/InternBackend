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

$stmt = $db->prepare("SELECT Com_Id FROM company WHERE User_Id = ?");
$stmt->execute([$_SESSION['user_id']]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$company) {
    echo json_encode(['success' => false, 'message' => 'Company not found']);
    exit;
}

$notif = new CompanyNotification($db);
$notif->markAllAsRead($company['Com_Id']);
echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
