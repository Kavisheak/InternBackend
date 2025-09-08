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
    // Add logo_img to SELECT
    $stmt = $db->prepare("SELECT company_name, industry, company_size, location, website, about, logo_img FROM company WHERE Com_Id = ?");
    $stmt->execute([$companyId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($profile) {
        echo json_encode(["success" => true, "profile" => $profile]);
    } else {
        echo json_encode(["success" => false, "message" => "Company not found"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error"]);
}