<?php
header("Content-Type: application/json");
require_once(__DIR__ . '/../models/internships.php');
require_once(__DIR__ . '/../../config/cors.php');
require_once(__DIR__ . '/../../config/Database.php');
require_once "../../api/sessions.php";

// Get user_id from session or request
$user_id = $_SESSION['user_id'] ?? null;
$post_user_id = $_POST['user_id'] ?? null;
$post_role = $_POST['role'] ?? null;
$post_company = isset($_POST['company_id']) ? (int)$_POST['company_id'] : null;
$isDevFallback = isset($_POST['dev']) && $_POST['dev'] == '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id'])) {
        echo json_encode(["success" => false, "message" => "Missing internship ID"]);
        exit;
    }

    $id = (int)$_POST['id'];

    // decide effective identity: prefer real session values; if none, allow dev fallback
    $session_has_identity = isset($_SESSION['role']) || isset($_SESSION['user_id']);
    if ($session_has_identity) {
        $effective_role = $_SESSION['role'] ?? null;
        $effective_company = $_SESSION['company_id'] ?? null;
    } elseif ($isDevFallback && ($post_role || $post_company || $post_user_id)) {
        // allow dev-supplied identity (unsafe for production)
        $effective_role = $post_role ?? null;
        $effective_company = $post_company ?? null;
    } else {
        echo json_encode(["success" => false, "message" => "Not authenticated"]);
        exit;
    }

    $db = (new Database())->getConnection();
    $internships = new Internships();

    // allow admin to delete any internship
    if ($effective_role === 'admin') {
        $del = $db->prepare("DELETE FROM internship WHERE Internship_Id = :id");
        $del->execute([':id' => $id]);
        if ($del->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Internship removed successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Internship not found"]);
        }
        exit;
    }

    // for company users, ensure company_id is set either in session or dev payload
    if ($effective_role === 'company') {
        if (!$effective_company) {
            echo json_encode(["success" => false, "message" => "Missing company session - cannot verify ownership"]);
            exit;
        }
        // perform direct delete using provided company_id (works for session or dev fallback)
        $del = $db->prepare("DELETE FROM internship WHERE Internship_Id = :id AND Company_Id = :company_id");
        $del->execute([':id' => $id, ':company_id' => $effective_company]);
        $affected = $del->rowCount();
        if ($affected > 0) {
            echo json_encode(["success" => true, "message" => "Internship removed successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to remove internship (not owner or not found)"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Insufficient permissions to delete"]);
        exit;
    }

} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
}