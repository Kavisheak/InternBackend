<?php
session_start();

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

// ❌ Case 3: Not logged in at all
<<<<<<< HEAD
echo json_encode(["loggedIn" => false]);
=======
echo json_encode(["loggedIn" => false]);
>>>>>>> 0c8c34f472b4681f5c8e8ad3692b7c42492c26a0
