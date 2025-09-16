<?php
// filepath: c:/xampp/htdocs/InternBackend/students/api/get_student_scheduled_interviews.php

require_once '../../config/Database.php';
require_once '../../api/sessions.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $db = (new Database())->getConnection();

    $stmt = $db->prepare("SELECT Student_Id FROM student WHERE User_Id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    $studentId = $row['Student_Id'];

    $sql = "SELECT
                iv.id,
                i.title AS internship_title,
                c.company_name,
                iv.interview_date,
                iv.interview_time,
                iv.status,
                iv.company_feedback,
                docs.cv_url,
                docs.certificates_json,
                docs.other_docs_json
            FROM interview_schedule iv
            JOIN internship i ON iv.internship_id = i.Internship_Id
            JOIN company c ON i.Company_Id = c.Com_Id
            LEFT JOIN interview_documents docs ON docs.interview_id = iv.id AND docs.student_id = ?
            WHERE iv.student_id = ?
            ORDER BY iv.interview_date ASC, iv.interview_time ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute([$studentId, $studentId]);
    $interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($interviews as &$iv) {
        $iv['cv'] = $iv['cv_url'] ?? "";
        $iv['certificates'] = $iv['certificates_json'] ? json_decode($iv['certificates_json'], true) : [];
        $iv['other_docs'] = $iv['other_docs_json'] ? json_decode($iv['other_docs_json'], true) : [];
        unset($iv['cv_url'], $iv['certificates_json'], $iv['other_docs_json']);
    }
    echo json_encode(['success' => true, 'interviews' => $interviews]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}