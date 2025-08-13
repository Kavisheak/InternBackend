<?php
// filepath: c:\xampp\htdocs\InternBackend\company\api\applications.php

session_start();
require_once __DIR__ . "/../../config/cors.php";
require_once __DIR__ . "/../../config/Database.php";

header("Content-Type: application/json");

// Check if company is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'company') {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

// Get company id from session
$userId = $_SESSION['user_id'];
try {
    $db = (new Database())->getConnection();
    // Get company id from company table using user_id
    $stmt = $db->prepare("SELECT Com_Id FROM company WHERE User_Id = ?");
    $stmt->execute([$userId]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$company) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Company not found"]);
        exit;
    }
    $companyId = $company['Com_Id'];

    // Fetch all applications for this company, join student, internship, users, and skills
    $sql = "
        SELECT
            a.Application_Id AS id,
            CONCAT(s.fname, ' ', s.lname) AS name,
            s.gender,
            i.title AS role,
            DATE_FORMAT(a.applied_date, '%Y-%m-%d') AS applied,
            s.education,
            s.experience,
            GROUP_CONCAT(sk.skill_name SEPARATOR ', ') AS skills,
            u.email,
            s.phone,
            s.profile_img AS image,
            s.cv_file AS cv,
            a.status
        FROM application a
        JOIN internship i ON a.Internship_Id = i.Internship_Id
        JOIN student s ON a.Student_Id = s.Student_Id
        JOIN users u ON s.User_Id = u.User_Id
        LEFT JOIN skill sk ON sk.Student_Id = s.Student_Id
        WHERE i.Company_Id = ?
        GROUP BY a.Application_Id
        ORDER BY a.applied_date DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$companyId]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "applications" => $applications]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error"]);
}