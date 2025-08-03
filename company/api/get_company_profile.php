<?php
session_start();

// ✅ Include CORS headers
require_once __DIR__ . '/../../config/cors.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");

// 🔒 Check if session is set
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

// ✅ Load DB & Model
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/Company.php'; // Corrected relative path

$db = (new Database())->getConnection();
$company = new Company($db);

// 🔍 Get company profile using session user ID
$result = $company->getProfileByUserId($_SESSION['user_id']);

if ($result) {
    $result['email'] = $_SESSION['email'] ?? null;
    echo json_encode(["success" => true, "company" => $result]);
} else {
    echo json_encode(["success" => false, "message" => "Company profile not found"]);
}
