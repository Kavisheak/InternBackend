<?php
header("Content-Type: application/json");
require_once(__DIR__ . '/../models/internships.php');
require_once(__DIR__ . '/../../config/cors.php');

if (isset($_GET['id'])) {
    $internshipId = intval($_GET['id']);

    try {
        $internships = new Internships();
        $internship = $internships->getInternshipById($internshipId);

        if ($internship) {
            echo json_encode([
                "success" => true,
                "data" => $internship
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Internship not found"
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Error: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Missing internship ID"
    ]);
}