
<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/cors.php';
header("Content-Type: application/json");
$companyId = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;
if ($companyId <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid company id"]);
    exit;
}
try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT contact_name, contact_email, contact_phone, contact_type FROM companycontact WHERE Company_Id = ?");
    $stmt->execute([$companyId]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["success" => true, "contacts" => $contacts]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error"]);
}