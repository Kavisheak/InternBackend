<?php
require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/Database.php';

header("Content-Type: application/json");

try {
    $db = (new Database())->getConnection();

    // Get all internships that are active and not expired
    $stmt = $db->prepare("
        SELECT 
            i.Internship_Id AS id,
            i.title,
            i.location,
            i.duration,
            i.salary,
            i.internship_type AS workType,
            i.description,
            i.requirements,
            i.deadline,
            i.application_limit,
            i.Company_Id,
            c.company_name AS company
        FROM internship i
        JOIN company c ON i.Company_Id = c.Com_Id
        WHERE i.is_active = 1 AND i.deadline >= CURDATE()
    ");
    $stmt->execute();
    $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // For each internship, get the application count and filter out filled ones
    $result = [];
    foreach ($internships as $internship) {
        $stmt2 = $db->prepare("SELECT COUNT(*) FROM application WHERE Internship_Id = ?");
        $stmt2->execute([$internship['id']]);
        $application_count = (int)$stmt2->fetchColumn();

        // Only include if not filled
        if (
            !isset($internship['application_limit']) ||
            $internship['application_limit'] === null ||
            $application_count < (int)$internship['application_limit']
        ) {
            $internship['application_count'] = $application_count;
            $result[] = $internship;
        }
    }

    echo json_encode([
        "success" => true,
        "internships" => $result
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error"
    ]);
}
