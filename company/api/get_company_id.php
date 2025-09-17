<?php
require_once "../../api/sessions.php";
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../config/cors.php';

header('Content-Type: application/json');

if (isset($_SESSION['company_id'])) {
    echo json_encode(['company_id' => $_SESSION['company_id']]);
    exit;
}
echo json_encode(['company_id' => null]);