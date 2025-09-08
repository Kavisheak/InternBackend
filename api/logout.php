<?php
// Ensure CORS headers before session operations
require_once '../config/cors.php'; // must include before any output
require_once "../api/sessions.php";
require_once '../config/Database.php';
require_once '../models/User.php';

<<<<<<< HEAD
session_start();
require_once '../config/Database.php';

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
=======
session_unset();
session_destroy();
echo json_encode(["success" => true, "message" => "Session closed"]);
>>>>>>> de7540454a6b5dda92dbb8bf2643b13b5e70948d
