<?php
require_once(__DIR__ . '/../../config/cors.php');
require_once(__DIR__ . '/../../config/Database.php');
$db = (new Database())->getConnection();
$stmt = $db->prepare('SELECT * FROM review_requests WHERE status = "pending" ORDER BY requested_at DESC');
$stmt->execute();
echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);