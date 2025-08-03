<?php
session_start();

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/Database.php';

header("Content-Type: application/json");

// ✅ Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

// ✅ Decode JSON input
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON input"]);
    exit;
}

// ✅ Connect to DB
$db = (new Database())->getConnection();

// ✅ Get company ID
function getCompanyIdFromUser($user_id, $db) {
    $query = "SELECT Com_Id FROM Company WHERE User_Id = :user_id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['Com_Id'] : null;
}

$company_id = getCompanyIdFromUser($_SESSION['user_id'], $db);
if (!$company_id) {
    echo json_encode(["success" => false, "message" => "Company not found for the user"]);
    exit;
}

// ✅ Sanitize inputs
$title = trim($data['title'] ?? '');
$location = trim($data['location'] ?? '');
$internship_type = strtolower(trim($data['internshipType'] ?? ''));
$salary = trim($data['salary'] ?? '');
$duration = trim($data['duration'] ?? '');
$description = trim($data['description'] ?? '');
$requirements = trim($data['requirements'] ?? '');
$deadline = trim($data['deadline'] ?? '');
$application_limit = intval($data['applicationLimit'] ?? 0);
$internship_id = isset($data['id']) ? intval($data['id']) : null;

// ✅ Prepare SQL
if ($internship_id) {
    // 🔁 Update
    $query = "UPDATE Internship SET 
                title = :title,
                location = :location,
                internship_type = :internship_type,
                salary = :salary,
                duration = :duration,
                description = :description,
                requirements = :requirements,
                deadline = :deadline,
                application_limit = :application_limit,
                updated_at = NOW()
              WHERE Internship_Id = :id AND Company_Id = :company_id";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $internship_id, PDO::PARAM_INT);
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
} else {
    // ➕ Insert
    $query = "INSERT INTO Internship 
                (title, location, internship_type, salary, duration, description, requirements, deadline, application_limit, Company_Id)
              VALUES 
                (:title, :location, :internship_type, :salary, :duration, :description, :requirements, :deadline, :application_limit, :company_id)";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
}

// ✅ Bind common fields
$stmt->bindParam(':title', $title);
$stmt->bindParam(':location', $location);
$stmt->bindParam(':internship_type', $internship_type);
$stmt->bindParam(':salary', $salary);
$stmt->bindParam(':duration', $duration);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':requirements', $requirements);
$stmt->bindParam(':deadline', $deadline);
$stmt->bindParam(':application_limit', $application_limit, PDO::PARAM_INT);

// ✅ Execute and respond
if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => $internship_id ? "Internship updated successfully." : "Internship posted successfully."
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Database operation failed."]);
}
