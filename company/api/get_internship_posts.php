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

$stmt = $db->prepare("SELECT Internship_Id as id, title FROM internship WHERE Company_Id = ?");
$stmt->execute([$companyId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));