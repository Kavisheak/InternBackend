<?php
require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/cors.php';

header('Content-Type: application/json');

$companyId = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;
if (!$companyId) {
    echo json_encode([]);
    exit;
}

$db = (new Database())->getConnection();

$stmt = $db->prepare("
    SELECT 
        sch.student_id AS id,
        s.fname AS name,
        u.email AS email,
        i.title AS post,
        sch.internship_id,
        sch.status
    FROM interview_schedule sch
    JOIN student s ON sch.student_id = s.Student_Id
    JOIN users u ON s.User_Id = u.User_Id
    JOIN internship i ON sch.internship_id = i.Internship_Id
    WHERE LOWER(sch.status) = 'accepted'
      AND i.Company_Id = ?
");
$stmt->execute([$companyId]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch skills for each student
foreach ($students as &$student) {
    $skillStmt = $db->prepare("SELECT skill_name FROM skill WHERE Student_Id = ?");
    $skillStmt->execute([$student['id']]);
    $student['skills'] = array_column($skillStmt->fetchAll(PDO::FETCH_ASSOC), 'skill_name');
}
unset($student);

echo json_encode($students);