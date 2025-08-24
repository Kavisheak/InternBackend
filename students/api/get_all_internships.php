<?php
require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/Database.php';

header("Content-Type: application/json");

// Dev error reporting (only in development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $db = (new Database())->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Join company to get its display name (adjust company_name if different)
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
    i.Company_Id,
    c.company_name AS company,
    i.created_at
  FROM internship i
  LEFT JOIN company c ON i.Company_Id = c.Com_Id
  WHERE i.is_active = 1
  ORDER BY i.created_at DESC
";

try {
    $stmt = $db->prepare($query);
    $stmt->execute();
    $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalize/massage fields
    $mapping = [
        'on-site' => 'On-site',
        'remote' => 'Remote',
        'hybrid' => 'Hybrid'
    ];

    foreach ($internships as &$internship) {
        // workType mapping
        $raw = isset($internship['workType']) ? strtolower($internship['workType']) : '';
        $internship['workType'] = $mapping[$raw] ?? $internship['workType'];

        // requirements to array
        $reqs = $internship['requirements'];
        if ($reqs) {
            $lines = preg_split('/\r?\n/', $reqs);
            $cleaned = array_values(array_filter(array_map('trim', $lines)));
            $internship['requirements'] = $cleaned;
        } else {
            $internship['requirements'] = [];
        }

        // Ensure company field exists so frontend can safely access it
        if (!isset($internship['company']) || $internship['company'] === null) {
            $internship['company'] = 'Unknown Company';
        }
    }
    unset($internship); // break reference

    echo json_encode([
        'success' => true,
        'internships' => $internships
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Query failed: ' . $e->getMessage()
    ]);
    exit;
}
