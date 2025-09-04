<?php
// suspend_user.php
// Suspends a user: copies user details to suspended_users and deactivates the user account.

// --- CORS handling (allow dev frontend origins and credentials) ---
$allowed = [
    'http://localhost:5173',
    'http://localhost:5174',
    'http://localhost'
];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

// Answer preflight requests immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/Database.php';
// sessions.php should start the session and set auth values
if (file_exists(__DIR__ . '/../../api/sessions.php')) {
    require_once __DIR__ . '/../../api/sessions.php';
} else {
    session_start();
}

// Development-only debug logging: record incoming cookie/header/session info to PHP error log
// This helps diagnose why the server sees no session (401). It only logs when origin looks like localhost.
try {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (stripos($origin, 'localhost') !== false || stripos($_SERVER['REMOTE_ADDR'] ?? '', '127.0.0.1') !== false) {
        error_log('[DEBUG suspend_user] HTTP_ORIGIN: ' . ($origin ?? '(none)'));
        error_log('[DEBUG suspend_user] HTTP_COOKIE: ' . ($_SERVER['HTTP_COOKIE'] ?? '(none)'));
        error_log('[DEBUG suspend_user] $_COOKIE: ' . print_r($_COOKIE, true));
        error_log('[DEBUG suspend_user] session_id: ' . session_id());
        error_log('[DEBUG suspend_user] $_SESSION: ' . print_r($_SESSION, true));
    }
} catch (Exception $e) {
    // Swallow any logging errors to not break endpoint
}

// Very small admin check - adapt to your project's session keys
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body || empty($body['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

$userId = (int)$body['user_id'];
// Ignore client-reported count; compute authoritative reports count on the server
$reportsCount = isset($body['reports_count']) ? (int)$body['reports_count'] : 0;

$db = (new Database())->getConnection();
try {
    $db->beginTransaction();

    // Make sure the suspended_users table exists (matches your phpMyAdmin dump)
    $createSql = "CREATE TABLE IF NOT EXISTS suspended_users (
      Suspended_Id int(11) NOT NULL,
      User_Id int(11) NOT NULL,
      username varchar(255) NOT NULL,
      email varchar(255) NOT NULL,
      role enum('student','company','admin') NOT NULL,
      reason text DEFAULT 'Exceeded report threshold',
      reports_count int(11) DEFAULT 0,
      suspended_at datetime DEFAULT current_timestamp(),
      original_created_at datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $db->exec($createSql);

    // Fetch the user row for insertion
    $stmt = $db->prepare('SELECT User_Id, username, email, role, created_at FROM users WHERE User_Id = :id FOR UPDATE');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    // Compute authoritative reports count depending on role
    $serverReports = 0;
    if (($user['role'] ?? '') === 'company') {
        // find Com_Id for this user
        $cstmt = $db->prepare('SELECT Com_Id FROM company WHERE User_Id = :uid LIMIT 1');
        $cstmt->execute([':uid' => $userId]);
        $company = $cstmt->fetch(PDO::FETCH_ASSOC);
        if ($company) {
            $cstmt2 = $db->prepare('SELECT COUNT(*) as c FROM companyreport WHERE Company_Id = :cid');
            $cstmt2->execute([':cid' => $company['Com_Id']]);
            $serverReports = (int)$cstmt2->fetchColumn();
        }
    } elseif (($user['role'] ?? '') === 'student') {
        $sstmt = $db->prepare('SELECT Student_Id FROM student WHERE User_Id = :uid LIMIT 1');
        $sstmt->execute([':uid' => $userId]);
        $student = $sstmt->fetch(PDO::FETCH_ASSOC);
        if ($student) {
            $sstmt2 = $db->prepare('SELECT COUNT(*) as c FROM studentreport WHERE Student_Id = :sid');
            $sstmt2->execute([':sid' => $student['Student_Id']]);
            $serverReports = (int)$sstmt2->fetchColumn();
        }
    }

    // Enforce threshold server-side
    $threshold = 10;
    if ($serverReports < $threshold) {
        $db->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "User has {$serverReports} reports — cannot suspend (requires {$threshold})."]);
        exit;
    }

    // Insert into suspended_users using server count
    $ins = $db->prepare('INSERT INTO suspended_users (User_Id, username, email, role, reason, reports_count, original_created_at) VALUES (:uid, :username, :email, :role, :reason, :reports, :orig)');
    $ins->execute([
        ':uid' => $user['User_Id'],
        ':username' => $user['username'],
        ':email' => $user['email'],
        ':role' => $user['role'],
        ':reason' => 'Exceeded report threshold',
        ':reports' => $serverReports,
        ':orig' => $user['created_at'] ?? null,
    ]);

    // Deactivate the user (assumes users has is_active flag)
    $up = $db->prepare('UPDATE users SET is_active = 0 WHERE User_Id = :id');
    $up->execute([':id' => $userId]);

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'User suspended']);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('suspend_user error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
