<?php
header("Content-Type: application/json");
require_once(__DIR__ . '/../models/internships.php');
require_once(__DIR__ . '/../../config/cors.php');
require_once(__DIR__ . '/../../config/Database.php');
require_once "../../api/sessions.php";

$logPath = __DIR__ . '/../logs/report_details.log';
$entryBase = date('c') . " - REQUEST from " . ($_SERVER['REMOTE_ADDR'] ?? 'cli') . "\n";
$entryBase .= "POST=" . json_encode($_POST) . "\n";
$entryBase .= "SESSION=" . json_encode($_SESSION ?? []) . "\n";
file_put_contents($logPath, $entryBase, FILE_APPEND);



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
    // debug dump to help trace why fallback may not be used
    file_put_contents($logPath, date('c') . " - DEBUG session_has_identity=" . ($session_has_identity ? '1' : '0') . " isDevFallback=" . ($isDevFallback ? '1' : '0') . " post_role={$post_role} post_company={$post_company} post_user_id={$post_user_id} POST_RAW=" . json_encode($_POST) . " SESSION_RAW=" . json_encode($_SESSION ?? []) . "\n", FILE_APPEND);
    if ($session_has_identity) {
        $effective_role = $_SESSION['role'] ?? null;
        $effective_company = $_SESSION['company_id'] ?? null;
        file_put_contents($logPath, date('c') . " - Session identity used role={$effective_role} company={$effective_company}\n", FILE_APPEND);
    } elseif ($isDevFallback && ($post_role || $post_company || $post_user_id)) {
        // allow dev-supplied identity (unsafe for production)
        $effective_role = $post_role ?? null;
        $effective_company = $post_company ?? null;
        file_put_contents($logPath, date('c') . " - Dev fallback used role={$effective_role} company={$effective_company} post_user_id={$post_user_id}\n", FILE_APPEND);
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
            file_put_contents($logPath, date('c') . " - ADMIN delete id={$id} success\n", FILE_APPEND);
            echo json_encode(["success" => true, "message" => "Internship removed successfully"]);
        } else {
            file_put_contents($logPath, date('c') . " - ADMIN delete id={$id} not_found\n", FILE_APPEND);
            echo json_encode(["success" => false, "message" => "Internship not found"]);
        }
        exit;
    }

    // for company users, ensure company_id is set either in session or dev payload
    if ($effective_role === 'company') {
        if (!$effective_company) {
            file_put_contents($logPath, date('c') . " - COMPANY delete id={$id} failed_missing_company\n", FILE_APPEND);
            echo json_encode(["success" => false, "message" => "Missing company session - cannot verify ownership"]);
            exit;
        }
        // perform direct delete using provided company_id (works for session or dev fallback)
        $del = $db->prepare("DELETE FROM internship WHERE Internship_Id = :id AND Company_Id = :company_id");
        $del->execute([':id' => $id, ':company_id' => $effective_company]);
        $affected = $del->rowCount();
        file_put_contents($logPath, date('c') . " - COMPANY direct delete id={$id} company={$effective_company} affected={$affected}\n", FILE_APPEND);
        if ($affected > 0) {
            echo json_encode(["success" => true, "message" => "Internship removed successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to remove internship (not owner or not found)"]);
        }
    } else {
        // fallback: not admin nor company
        file_put_contents($logPath, date('c') . " - delete id={$id} insufficient_permissions role={$effective_role}\n", FILE_APPEND);
        echo json_encode(["success" => false, "message" => "Insufficient permissions to delete"]);
        exit;
    }

} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
}