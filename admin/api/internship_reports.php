<?php
require_once __DIR__ . '/../../config/cors.php';
require_once '../../config/Database.php';
header('Content-Type: application/json');

$internship_id = $_GET['internship_id'] ?? null;
if (!$internship_id) {
    echo json_encode(['success' => false, 'message' => 'No internship id']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare(
        "SELECT r.Report_Id as id, r.reason, r.reported_at, s.fname, s.lname, s.Student_Id, u.email
         FROM report r
         LEFT JOIN student s ON r.Student_Id = s.Student_Id
         LEFT JOIN users u ON s.User_Id = u.User_Id
         WHERE r.Internship_Id = ?
         ORDER BY r.reported_at DESC"
    );
    $stmt->execute([$internship_id]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $reports]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}