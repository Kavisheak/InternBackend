<?php
header("Content-Type: application/json");
require_once(__DIR__ . '/../models/admin.php');
require_once(__DIR__ . '/../../config/cors.php');

if (isset($_GET['company_id'])) {
    $admin = new Admin();
    $reports = $admin->getCompanyReports($_GET['company_id']);
    echo json_encode([
        "success" => true,
        "data" => $reports
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Missing company ID"
    ]);
}