<?php
session_start();

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/Database.php';

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

try {
    $db = (new Database())->getConnection();

    $getCompanyId = $db->prepare("SELECT Com_Id FROM Company WHERE User_Id = :user_id");
    $getCompanyId->bindParam(':user_id', $_SESSION['user_id']);
    $getCompanyId->execute();
    $company = $getCompanyId->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        echo json_encode(["success" => false, "message" => "Company not found for user"]);
        exit;
    }

    $company_id = $company['Com_Id'];

    $query = "SELECT Internship.*, Company.company_name AS company 
              FROM Internship
              INNER JOIN Company ON Internship.Company_Id = Company.Com_Id
              WHERE Internship.is_active = 1 AND Internship.Company_Id = :company_id
              ORDER BY Internship.updated_at DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["success" => true, "internships" => $data]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
