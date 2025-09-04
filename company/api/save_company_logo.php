<?php
require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/cors.php';
header("Content-Type: application/json");

// Only allow logged-in company users
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

require_once __DIR__ . '/../../config/Database.php';

try {
    $db = (new Database())->getConnection();

    // Get company id from user id
    $stmt = $db->prepare("SELECT Com_Id FROM company WHERE User_Id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        echo json_encode(["success" => false, "message" => "Company not found"]);
        exit;
    }

    // Handle logo upload
    if (isset($_FILES['logo_img']) && $_FILES['logo_img']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['logo_img']['name'], PATHINFO_EXTENSION);
        $filename = "logo_" . $_SESSION['user_id'] . "_" . time() . "." . $ext;
        $target = "../../uploads/" . $filename;
        if (move_uploaded_file($_FILES['logo_img']['tmp_name'], $target)) {
            $logoPath = "uploads/" . $filename;

            // Update company table with new logo path
            $stmt = $db->prepare("UPDATE company SET logo_img = ? WHERE Com_Id = ?");
            $stmt->execute([$logoPath, $company['Com_Id']]);

            echo json_encode(["success" => true, "logo_img" => $logoPath]);
            exit;
        } else {
            echo json_encode(["success" => false, "message" => "Failed to save logo"]);
            exit;
        }
    } else {
        echo json_encode(["success" => false, "message" => "No logo file uploaded"]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
    exit;
}