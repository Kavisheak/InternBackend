<?php
// filepath: c:\xampp\htdocs\InternBackend\company\api\applications.php

require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../models/Application.php';

header("Content-Type: application/json");

// Check if company is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$db = (new Database())->getConnection();
$appModel = new Application($db);

// Get company id
$companyId = $appModel->getCompanyIdByUserId($_SESSION['user_id']);
if (!$companyId) {
    echo json_encode(["success" => false, "message" => "Company not found"]);
    exit;
}

// Filter by internshipId if provided
$internshipId = isset($_GET['internshipId']) ? intval($_GET['internshipId']) : null;
$appRows = $appModel->getApplications($companyId, $internshipId);

$applications = [];
foreach ($appRows as $row) {
    $skills = $appModel->getSkillsByStudentId($row["Student_Id"]);
    $applications[] = [
        "id" => (int)$row["Application_Id"],
        "student_id" => (int)$row["Student_Id"],
        "internship_id" => (int)$row["Internship_Id"],
        "internship_title" => $row["internship_title"],
        "name" => trim($row["fname"] . " " . $row["lname"]),
        "image" => $row["profile_img"],
        "cv" => $row["cv_file"],
        "email" => $row["email"],
        "phone" => $row["phone"],
        "github" => $row["github"],
        "linkedin" => $row["linkedin"],
        "education" => $row["education"],
        "experience" => $row["experience"],
        "applied" => $row["applied_date"],
        "status" => $row["status"],
        "role" => "Student",
        "skills" => implode(", ", $skills),
        "gender" => $row["gender"],
    ];
}

echo json_encode(["success" => true, "applications" => $applications]);