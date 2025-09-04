<?php
session_start();
require_once '../config/Database.php';
header("Content-Type: application/json");

$db = (new Database())->getConnection();

$response = ["loggedIn" => false];

// if session not already set, but a cookie exists, rebuild session
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $stmt = $db->prepare("SELECT User_Id, username, email, role FROM users WHERE remember_token = ?");
    $stmt->execute([$_COOKIE['remember_token']]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION['user_id'] = $row['User_Id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['email']    = $row['email'];
        $_SESSION['role']     = $row['role'];
    }
}

// always answer with session status
if (isset($_SESSION['user_id'])) {
    $response = [
        "loggedIn" => true,
        "user" => [
            "user_id" => $_SESSION['user_id'],
            "username" => $_SESSION['username'],
            "email" => $_SESSION['email'],
            "role" => $_SESSION['role']
        ]
    ];
}

echo json_encode($response);
