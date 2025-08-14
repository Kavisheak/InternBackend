<?php
// filepath: c:\xampp\htdocs\InternBackend\students\api\get_applications.php

session_start();
require_once __DIR__ . "/../../config/cors.php";
require_once __DIR__ . "/../../config/Database.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$userId = $_SESSION['user_id'];
try {
    $db = (new Database())->getConnection();
    // Get Student_Id
    $stmt = $db->prepare("SELECT Student_Id FROM student WHERE User_Id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(["success" => false, "message" => "Student not found"]);
        exit;
    }
    $studentId = $row['Student_Id'];

    // Get applications with internship details
    $sql = "
        SELECT
            a.Application_Id,
            i.title,
            c.company_name AS company,
            i.location,
            DATE_FORMAT(a.applied_date, '%Y-%m-%d') AS appliedDate,
            i.deadline,
            a.status,
            i.internship_type AS jobType,
            i.salary AS stipend,
            i.duration,
            i.description,
            i.requirements,
            i.Internship_Id
        FROM application a
        JOIN internship i ON a.Internship_Id = i.Internship_Id
        JOIN company c ON i.Company_Id = c.Com_Id
        WHERE a.Student_Id = ?
        ORDER BY a.applied_date DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$studentId]);
    $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Optionally, fetch skills for each internship
    foreach ($apps as &$app) {
        $skills = [];
        $reqs = explode("\n", $app['requirements']);
        foreach ($reqs as $req) {
            $skills[] = trim($req);
        }
        $app['skills'] = $skills;
    }

    echo json_encode(["success" => true, "applications" => $apps]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error"]);
}