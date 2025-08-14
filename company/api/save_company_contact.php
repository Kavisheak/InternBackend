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
require_once __DIR__ . '/../models/Company.php';

$db = (new Database())->getConnection();
$companyModel = new Company($db);

$userId = $_SESSION['user_id'];
$companyId = $companyModel->getCompanyIdByUserId($userId);

if (!$companyId) {
    echo json_encode(["success" => false, "message" => "Company profile not found for user"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
if (!$input || !isset($input['contacts'])) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

$contacts = $input['contacts']; // Array of contacts

// Remove empty secondary contact
$filteredContacts = array_filter($contacts, function($contact) {
    return !empty($contact['name']) || !empty($contact['email']) || !empty($contact['phone']);
});

$result = $companyModel->saveContacts($companyId, $filteredContacts);

if (!$result['success']) {
    error_log("Contact save error: " . $result['message']);
    echo json_encode($result);
    exit;
}

echo json_encode(["success" => true, "message" => "Contacts saved" ]);