<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../api/sessions.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../models/Interview.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
try {
    $db = (new Database())->getConnection();
    $interviewModel = new Interview($db);

    // Get company id
    $stmt = $db->prepare("SELECT Com_Id FROM company WHERE User_Id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Company not found']);
        exit;
    }
    $companyId = $row['Com_Id'];

    $internshipTitle = $_GET['title'] ?? null;

    $sql = "SELECT 
                a.Application_Id,
                a.Internship_Id,
                a.Student_Id,
                s.fname,
                s.lname,
                u.email,
                i.title AS internship,
                a.status,
                s.profile_img,
                iv.interview_date,
                iv.interview_time,
                iv.id AS interview_id,
                rr.id AS reschedule_id,
                rr.status AS reschedule_status
            FROM application a
            JOIN student s ON a.Student_Id = s.Student_Id
            JOIN users u ON s.User_Id = u.User_Id
            JOIN internship i ON a.Internship_Id = i.Internship_Id
            LEFT JOIN interview_schedule iv ON iv.application_id = a.Application_Id
            LEFT JOIN reschedule_requests rr ON rr.interview_id = iv.id AND rr.status = 'pending'
            WHERE a.status = 'Accepted' AND i.Company_Id = ?";
    $params = [$companyId];
    if ($internshipTitle) {
        $sql .= " AND i.title = ?";
        $params[] = $internshipTitle;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $candidates = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $interview = $interviewModel->getInterviewByApplication($row['Application_Id']);
        if ($interview) {
            error_log("Interview found: " . json_encode($interview));
        } else {
            error_log("No interview for application_id " . $row['Application_Id']);
        }
        $candidates[] = [
            'id' => $row['Student_Id'],
            'name' => trim($row['fname'] . ' ' . $row['lname']),
            'email' => $row['email'],
            'internship' => $row['internship'],
            'status' => $row['status'],
            'profile_img' => $row['profile_img'],
            'Application_Id' => $row['Application_Id'],
            'Internship_Id' => $row['Internship_Id'],
            'interview_date' => $row['interview_date'] ?? null,
            'interview_time' => $row['interview_time'] ?? null,
            'interview_type' => $interview['interview_type'] ?? null,
            'meeting_link' => $interview['meeting_link'] ?? null,
            'location' => $interview['location'] ?? null,
            'interview_id' => $row['interview_id'],
            'reschedule_id' => $row['reschedule_id'],
            'reschedule_status' => $row['reschedule_status'],
        ];
    }

    echo json_encode(['success' => true, 'candidates' => $candidates]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}