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

// Fetch lockout settings
$lockoutLimit = 3;
$lockoutMinutes = 15;
$stmt = $db->prepare("SELECT name, value FROM system_settings WHERE name IN ('max_failed_attempts', 'lockout_duration')");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($row['name'] === 'max_failed_attempts') $lockoutLimit = (int)$row['value'];
    if ($row['name'] === 'lockout_duration') $lockoutMinutes = (int)$row['value'];
}

// Get user row for lockout logic
$stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $data->email]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if locked out
if ($userRow && $userRow['lockout_until'] && strtotime($userRow['lockout_until']) > time()) {
    $remaining = ceil((strtotime($userRow['lockout_until']) - time()) / 60);
    echo json_encode([
        "success" => false,
        "message" => "Account locked due to too many failed login attempts. Try again in $remaining minute(s) or contact admin."
    ]);
    exit;
}

$userData = $user->verifyLogin($data->email, $data->password);

if ($userData) {
    // Reset failed_attempts and lockout
    $db->prepare("UPDATE users SET failed_attempts = 0, lockout_until = NULL WHERE User_Id = ?")
        ->execute([$userData['User_Id']]);

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
                echo json_encode(["success" => false, "message" => "Access to this account has been restricted as it was found to be in violation of our Terms and Conditions. Kindly reach out to the administrator for support"]);
                exit;
            }
        } catch (Exception $e) {
            // ignore DB check failures; fall back to is_active above
        }
    }

    // If maintenance mode is active, allow only admins to log in
    if (is_maintenance_mode()) {
        if (!$userData || !isset($userData['role']) || $userData['role'] !== 'admin') {
            echo json_encode(["success" => false, "message" => "We’re performing scheduled maintenance. Thank you for your patience—service will resume soon."]);
            exit;
        }
    }

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
    exit;
} else {
    // Failed login: increment failed_attempts
    if ($userRow) {
        $attempts = (int)$userRow['failed_attempts'] + 1;
        if ($attempts >= $lockoutLimit) {
            $lockoutUntil = date('Y-m-d H:i:s', strtotime("+$lockoutMinutes minutes"));
            $db->prepare("UPDATE users SET failed_attempts = ?, lockout_until = ? WHERE User_Id = ?")
                ->execute([$attempts, $lockoutUntil, $userRow['User_Id']]);
            echo json_encode([
                "success" => false,
                "message" => "Account locked due to too many failed login attempts. Try again in $lockoutMinutes minutes or contact admin."
            ]);
            exit;
        } else {
            $db->prepare("UPDATE users SET failed_attempts = ? WHERE User_Id = ?")
                ->execute([$attempts, $userRow['User_Id']]);
        }
    }
    echo json_encode([
        "success" => false,
        "message" => "Invalid email or password."
    ]);
    exit;
}
