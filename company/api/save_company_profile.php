<?php
// Start session
require_once "../../api/sessions.php";

// CORS headers (must be before any output)
require_once __DIR__ . '/../../config/cors.php';

// Preflight request support
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// JSON response header
header("Content-Type: application/json");

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

// Required files
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/Company.php'; // Corrected relative path

// Parse JSON input
$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

// Get DB connection
$db = (new Database())->getConnection();
$company = new Company($db);

// Set company properties
$company->user_id      = $_SESSION['user_id'];
$company->company_name = trim($input['companyName'] ?? '');
$company->industry     = trim($input['industry'] ?? '');
$company->company_size = trim($input['companySize'] ?? '');
$company->location     = trim($input['location'] ?? '');
$company->website      = trim($input['website'] ?? '');
$company->about        = trim($input['about'] ?? '');

// Save or update in DB
if ($company->saveOrUpdate()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to save company profile"]);
}
