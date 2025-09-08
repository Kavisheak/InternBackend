<?php
require_once "../../api/sessions.php";
require_once '../../config/cors.php';
require_once '../../config/Database.php';

header("Content-Type: application/json");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

$db = (new Database())->getConnection();

// Step 1: Get the company ID of the logged-in user
$getCompanyId = $db->prepare("SELECT Com_Id FROM company WHERE User_Id = :user_id");
$getCompanyId->bindParam(':user_id', $_SESSION['user_id']);
$getCompanyId->execute();

$company = $getCompanyId->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    echo json_encode(["success" => false, "message" => "Company not found"]);
    exit;
}

$companyId = $company['Com_Id'];

// Step 2: Fetch internships from Internship table
$query = "SELECT 
            i.Internship_Id AS id,
            i.title,
            i.deadline,
            i.application_limit,
            (SELECT COUNT(*) FROM application a WHERE a.Internship_Id = i.Internship_Id) AS application_count
          FROM internship i
          WHERE i.Company_Id = :company_id 
            AND i.is_active = 1
            AND i.deadline >= CURDATE()
          ORDER BY i.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':company_id', $companyId);
$stmt->execute();

$internships = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Only include internships that are not filled
    if ($row['application_count'] < $row['application_limit']) {
        $internships[] = $row;
    }
}

// Respond with the internships and the correct count
echo json_encode([
    "success" => true,
    "internships" => $internships,
    "count" => count($internships)
]);