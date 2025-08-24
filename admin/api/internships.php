<?php
require_once(__DIR__ . '/../models/internships.php');
require_once(__DIR__ . '/../../config/cors.php');

$internships = new Internships();
$list = $internships->getAllInternships(); 

echo json_encode([
    "success" => true,
    "data" => $list
]);