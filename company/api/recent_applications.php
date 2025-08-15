<?php
require_once "../../api/sessions.php";
require_once __DIR__ . "/../../config/cors.php";
require_once __DIR__ . "/../../config/Database.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$userId = $_SESSION['user_id'];
try {
    $db = (new Database())->getConnection();
    // Get company id
    $stmt = $db->prepare("SELECT Com_Id FROM company WHERE User_Id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(["success" => false, "message" => "Company not found"]);
        exit;
    }
    $companyId = $row['Com_Id'];

    // Get recent applications for this company (limit 6)
    $sql = "
        SELECT
            a.Application_Id,
            s.fname,
            s.lname,
            s.profile_img,
            s.gender,
            s.education,
            s.experience,
            s.github,
            s.linkedin,
            s.phone,
            s.cv_file,
            a.status,
            a.applied_date,
            i.title AS role
        FROM application a
        JOIN internship i ON a.Internship_Id = i.Internship_Id
        JOIN student s ON a.Student_Id = s.Student_Id
        WHERE i.Company_Id = ?
        ORDER BY a.applied_date DESC
        LIMIT 6
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$companyId]);
    $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format for frontend
    $result = array_map(function($app) {
        return [
            "id" => $app["Application_Id"],
            "name" => trim($app["fname"] . " " . $app["lname"]),
            "role" => $app["role"],
            "status" => $app["status"],
            "applied" => time_elapsed_string($app["applied_date"]),
            "image" => $app["profile_img"],
        ];
    }, $apps);

    echo json_encode(["success" => true, "applications" => $result]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error"]);
}

// Helper function for "2h ago" etc.
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'h',
        'i' => 'min',
        's' => 's',
    ];
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . $v;
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}