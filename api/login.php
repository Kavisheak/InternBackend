<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure CORS headers are set before any session/cookie output
require_once '../config/cors.php'; // must include before any output
// use shared session bootstrap so cookie params (SameSite) are consistent
require_once 'sessions.php';
require_once '../config/Database.php';
require_once '../config/maintenance_check.php';
require_once '../models/User.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email, $data->password)) {
    echo json_encode([
        "success" => false,
        "message" => "Missing email or password."
    ]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$userData = $user->verifyLogin($data->email, $data->password);

// If user found, ensure they are not suspended. Prefer server-side authoritative check.
if ($userData) {
    // If the users table uses is_active flag, block when it's 0
    if (isset($userData['is_active']) && intval($userData['is_active']) === 0) {
        echo json_encode(["success" => false, "message" => "Account suspended. Contact administrator."]);
        exit;
    }

    // As a defensive check, also verify the user isn't present in suspended_users (if your suspend flow copies details there)
    try {
        $sstmt = $db->prepare('SELECT id FROM suspended_users WHERE user_id = :uid LIMIT 1');
        $sstmt->execute([':uid' => $userData['User_Id']]);
        $srow = $sstmt->fetch(PDO::FETCH_ASSOC);
        if ($srow) {
            echo json_encode(["success" => false, "message" => "Account suspended. Contact administrator."]);
            exit;
        }
    } catch (Exception $e) {
        // ignore DB check failures; fall back to is_active above
    }
}

// If maintenance mode is active, allow only admins to log in
if (is_maintenance_mode()) {
    if (!$userData || !isset($userData['role']) || $userData['role'] !== 'admin') {
        echo json_encode(["success" => false, "message" => "The site is under maintenance. Only administrators can sign in now."]);
        exit;
    }
}

if ($userData) {
    // Save user info to session
    $_SESSION['user_id'] = $userData['User_Id'];
    $_SESSION['email'] = $userData['email'];
    $_SESSION['username'] = $userData['username'];
    $_SESSION['role'] = $userData['role'];

    $company = null;

    // if user is a company, attach company_id into session for ownership checks
    if (isset($userData['role']) && $userData['role'] === 'company') {
        try {
            $cstmt = $db->prepare('SELECT Com_Id FROM company WHERE User_Id = :uid LIMIT 1');
            $cstmt->execute([':uid' => $userData['User_Id']]);
            $company = $cstmt->fetch(PDO::FETCH_ASSOC);
            if ($company && isset($company['Com_Id'])) {
                $_SESSION['company_id'] = (int)$company['Com_Id'];
            }
        } catch (Exception $e) {
            // ignore errors
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "Login successful.",
        "username" => $userData['username'],
        "role" => $userData['role'],
        "user_id" => $userData['User_Id'],
        "company_id" => isset($company['Com_Id']) ? (int)$company['Com_Id'] : null
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid email or password."
    ]);

}