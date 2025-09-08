<?php

// Ensure CORS headers are set before any output and use shared sessions bootstrap
session_start();

require_once '../config/Database.php';
require_once '../config/cors.php';
require_once 'sessions.php';
require_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// ✅ Case 1: Already logged in with session
if (isset($_SESSION['user_id'])) {
    echo json_encode([
        "loggedIn" => true,
        "user_id" => $_SESSION['user_id'],
        "username" => $_SESSION['username'],
        "role" => $_SESSION['role']
    ]);
    exit;
}

// If we reach here, the user is not logged in
echo json_encode(["loggedIn" => false]);
exit;
// ✅ Case 2: No session, check remember_token cookie
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    $stmt = $db->prepare("SELECT User_Id, username, role, email FROM users WHERE remember_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Restore session
        $_SESSION['user_id'] = $user['User_Id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

        echo json_encode([
            "loggedIn" => true,
            "user_id" => $user['User_Id'],
            "username" => $user['username'],
            "role" => $user['role']
        ]);
        exit;
    }
}