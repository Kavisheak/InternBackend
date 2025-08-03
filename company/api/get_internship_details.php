<?php
session_start();

// Include CORS and DB config
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/Database.php';

header("Content-Type: application/json");

// ✅ Step 1: Session check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

try {
    // ✅ Step 2: DB connection
    $db = (new Database())->getConnection();

    // ✅ Step 3: Get company ID
    $getCompanyId = $db->prepare("SELECT Com_Id FROM Company WHERE User_Id = :user_id");
    $getCompanyId->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $getCompanyId->execute();
    $company = $getCompanyId->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        echo json_encode(["success" => false, "message" => "Company not found"]);
        exit;
    }

    $companyId = $company['Com_Id'];

    // ✅ Step 4: Check internship ID param
    if (!isset($_GET['id'])) {
        echo json_encode(["success" => false, "message" => "Internship ID is required"]);
        exit;
    }

    $internshipId = intval($_GET['id']);

    // ✅ Step 5: Fetch internship details
    $query = "SELECT 
                Internship_Id AS id,
                title,
                location,
                internship_type,
                salary,
                duration,
                description,
                requirements,
                deadline,
                application_limit
              FROM Internship
              WHERE Internship_Id = :id AND Company_Id = :company_id AND is_active = 1
              LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $internshipId, PDO::PARAM_INT);
    $stmt->bindParam(':company_id', $companyId, PDO::PARAM_INT);
    $stmt->execute();

    $internship = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$internship) {
        echo json_encode(["success" => false, "message" => "Internship not found or access denied"]);
        exit;
    }

    // ✅ Step 6: Return internship
    echo json_encode(["success" => true, "internship" => $internship]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "DB error: " . $e->getMessage()]);
}
