<?php
// Ensure CORS headers before session operations
require_once '../config/cors.php'; // must include before any output
require_once "../api/sessions.php";
require_once '../config/Database.php';
require_once '../models/User.php';

session_start();

// --- Restore session from cookie if not logged in ---
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$_COOKIE['remember_token']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Rebuild session from cookie
        $_SESSION['user_id'] = $user['User_Id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
    }
}

// --- Handle logout (destroy session) ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Remove remember_token cookie
    setcookie('remember_token', '', time() - 3600, '/');
    session_unset();
    session_destroy();
    echo json_encode(["success" => true, "message" => "Session closed"]);
    exit;
}

// --- Default response (if session is active) ---
if (isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => true,
        "message" => "Session active",
        "user" => [
            "id" => $_SESSION['user_id'],
            "email" => $_SESSION['email'],
            "username" => $_SESSION['username'],
            "role" => $_SESSION['role']
        ]
    ]);
} else {
    echo json_encode(["success" => false, "message" => "No active session"]);
}
