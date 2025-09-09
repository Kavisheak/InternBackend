<?php
require_once '../config/cors.php';
require_once '../config/Database.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || empty($data['email'])) {
    echo json_encode(['success' => false, 'message' => 'Missing email']);
    exit;
}

$db = (new Database())->getConnection();

// Find user by email
$stmt = $db->prepare('SELECT User_Id, username, role FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $data['email']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Insert review request
$req = $db->prepare('INSERT INTO review_requests (User_Id, email, username, role, requested_at, status) VALUES (:uid, :email, :username, :role, NOW(), "pending")');
$req->execute([
    ':uid' => $user['User_Id'],
    ':email' => $data['email'],
    ':username' => $user['username'],
    ':role' => $user['role'],
]);

echo json_encode(['success' => true, 'message' => 'Review request submitted']);