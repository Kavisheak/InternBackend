<?php
// filepath: c:\xampp\htdocs\InternBackend\company\api\applications.php

require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/cors.php';

header("Content-Type: application/json");

// Check if company is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$db = (new Database())->getConnection();

// Get company id
$stmt = $db->prepare("SELECT Com_Id FROM company WHERE User_Id = ?");
$stmt->execute([$_SESSION['user_id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(["success" => false, "message" => "Company not found"]);
    exit;
}
$companyId = $row['Com_Id'];

// Filter by internshipId if provided
$internshipId = isset($_GET['internshipId']) ? intval($_GET['internshipId']) : 0;

if ($internshipId > 0) {
    $sql = "SELECT a.*, s.fname, s.lname, s.profile_img, s.cv_file, s.phone, s.github, s.linkedin, s.education, s.experience, u.email, i.title AS internship_title
            FROM application a
            JOIN student s ON a.Student_Id = s.Student_Id
            JOIN users u ON s.User_Id = u.User_Id
            JOIN internship i ON a.Internship_Id = i.Internship_Id
            WHERE a.Internship_Id = ? AND a.Internship_Id IN (SELECT Internship_Id FROM internship WHERE Company_Id = ?)
            ORDER BY a.applied_date DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$internshipId, $companyId]);
} else {
    $sql = "SELECT a.*, s.fname, s.lname, s.profile_img, s.cv_file, s.phone, s.github, s.linkedin, s.education, s.experience, u.email, i.title AS internship_title
            FROM application a
            JOIN student s ON a.Student_Id = s.Student_Id
            JOIN users u ON s.User_Id = u.User_Id
            JOIN internship i ON a.Internship_Id = i.Internship_Id
            WHERE a.Internship_Id IN (SELECT Internship_Id FROM internship WHERE Company_Id = ?)
            ORDER BY a.applied_date DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$companyId]);
}

$applications = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
        "skills" => "", // Add skills if needed
    ];
}

echo json_encode(["success" => true, "applications" => $applications]);