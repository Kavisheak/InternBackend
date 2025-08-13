<?php
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/Database.php';

header("Content-Type: application/json");

// Get and validate ID param
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $query = "
      SELECT 
        i.Internship_Id AS id,
        i.title,
        i.location,
        i.duration,
        i.salary AS pay,
        i.internship_type AS workType,
        i.description,
        i.requirements,
        i.deadline,
        i.status,
        i.application_limit,
        i.Company_Id,
        c.company_name AS company,
        i.created_at
      FROM internship i
      LEFT JOIN company c ON i.Company_Id = c.Com_Id
      WHERE i.is_active = 1 AND i.Internship_Id = :id
      LIMIT 1
    ";

    $stmt = $db->prepare($query);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $internship = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$internship) {
        echo json_encode(['success' => false, 'message' => 'Internship not found']);
        exit;
    }

    // Normalize workType
    $mapping = ['on-site' => 'On-site', 'remote' => 'Remote', 'hybrid' => 'Hybrid'];
    $raw = strtolower($internship['workType'] ?? '');
    $internship['workType'] = $mapping[$raw] ?? $internship['workType'];

    // Convert requirements to array
    if ($internship['requirements']) {
        $lines = preg_split('/\r?\n/', $internship['requirements']);
        $internship['requirements'] = array_values(array_filter(array_map('trim', $lines)));
    } else {
        $internship['requirements'] = [];
    }

    if (!isset($internship['company']) || $internship['company'] === null) {
        $internship['company'] = 'Unknown Company';
    }

    echo json_encode(['success' => true, 'internship' => $internship]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}