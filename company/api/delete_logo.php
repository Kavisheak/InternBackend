
<?php
require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/cors.php';
require_once "../../config/Database.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    $db = (new Database())->getConnection();

    // Get company id and logo path
    $stmt = $db->prepare("SELECT Com_Id, logo_img FROM company WHERE User_Id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        echo json_encode(["success" => false, "message" => "Company not found"]);
        exit;
    }

    // Delete logo file from server
    if (!empty($company['logo_img'])) {
        $imgPath = "../../" . $company['logo_img'];
        if (file_exists($imgPath)) {
            unlink($imgPath);
        }
    }

    // Remove logo reference from DB
    $stmt = $db->prepare("UPDATE company SET logo_img = '' WHERE Com_Id = ?");
    $stmt->execute([$company['Com_Id']]);

    echo json_encode(["success" => true, "message" => "Logo deleted"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error"]);
}