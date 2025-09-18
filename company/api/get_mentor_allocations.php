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
$stmt = $db->prepare("SELECT student_id, mentor_id, post FROM mentor_allocations WHERE company_id = ?");
$stmt->execute([$companyId]);
$allocations = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $key = $row['student_id'] . '-' . $row['post'];
    $allocations[$key] = $row['mentor_id'];
}
echo json_encode($allocations);