
<?php
require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/cors.php';

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

$stmt = $db->prepare("SELECT contact_name, contact_email, contact_phone, contact_type FROM companycontact WHERE Company_Id = :companyId");
$stmt->bindParam(':companyId', $companyId);
$stmt->execute();
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["success" => true, "contacts" => $contacts]);