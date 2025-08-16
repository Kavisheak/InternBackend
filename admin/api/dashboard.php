<?php
require_once(__DIR__ . '/../models/users.php');
require_once(__DIR__ . '/../models/internships.php');
require_once(__DIR__ . '/../../config/cors.php');

$users = new Users();
$internships = new Internships();

echo json_encode([
    "success" => true,
    "data" => [
        "users" => $users->getUserCounts(),
        "internships" => $internships->getInternshipCount()
    ]
]);