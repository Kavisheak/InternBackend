<?php
// admin/api/unsuspend_user.php
// Unsuspend a user: reactivate account, delete reports, move suspended record to earlier_suspended

// CORS - allow localhost origins including ports
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $origin = $_SERVER['HTTP_ORIGIN'];
    if (stripos($origin, 'localhost') !== false || stripos($origin, '127.0.0.1') !== false) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/Database.php';
if (file_exists(__DIR__ . '/../../api/sessions.php')) {
    require_once __DIR__ . '/../../api/sessions.php';
} else {
    session_start();
}

// Admin guard
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

$db = (new Database())->getConnection();
try {
    $db->beginTransaction();

    // Lock user row
    $ust = $db->prepare('SELECT User_Id, username, email, role, created_at FROM users WHERE User_Id = :id FOR UPDATE');
    $ust->execute([':id' => $userId]);
    $user = $ust->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Try to read suspended_users record for suspended_at and reports_count if present
    $suspendedAt = null;
    $reportsCount = 0;
    $sstmt = $db->prepare('SELECT Suspended_Id, reports_count, suspended_at FROM suspended_users WHERE User_Id = :uid LIMIT 1');
    $sstmt->execute([':uid' => $userId]);
    $srow = $sstmt->fetch(PDO::FETCH_ASSOC);
    if ($srow) {
        $suspendedAt = $srow['suspended_at'] ?? null;
        $reportsCount = (int)($srow['reports_count'] ?? 0);
    }

    // If suspended_users had no authoritative reports_count, compute from report tables
    if ($reportsCount === 0) {
        if (($user['role'] ?? '') === 'company') {
            $cstmt = $db->prepare('SELECT Com_Id FROM company WHERE User_Id = :uid LIMIT 1');
            $cstmt->execute([':uid' => $userId]);
            $company = $cstmt->fetch(PDO::FETCH_ASSOC);
            if ($company) {
                $c2 = $db->prepare('SELECT COUNT(*) FROM companyreport WHERE Company_Id = :cid');
                $c2->execute([':cid' => $company['Com_Id']]);
                $reportsCount = (int)$c2->fetchColumn();
            }
        } elseif (($user['role'] ?? '') === 'student') {
            $s2 = $db->prepare('SELECT Student_Id FROM student WHERE User_Id = :uid LIMIT 1');
            $s2->execute([':uid' => $userId]);
            $student = $s2->fetch(PDO::FETCH_ASSOC);
            if ($student) {
                $r2 = $db->prepare('SELECT COUNT(*) FROM studentreport WHERE Student_Id = :sid');
                $r2->execute([':sid' => $student['Student_Id']]);
                $reportsCount = (int)$r2->fetchColumn();
            }
        }
    }

    // Insert into earlier_suspended (archive)
    $ins = $db->prepare('INSERT INTO earlier_suspended (User_Id, username, email, role, reason, reports_count, suspended_at, original_created_at, unsuspended_at) VALUES (:uid, :username, :email, :role, :reason, :reports, :suspended_at, :orig, NOW())');
    $ins->execute([
        ':uid' => $user['User_Id'],
        ':username' => $user['username'],
        ':email' => $user['email'],
        ':role' => $user['role'],
        ':reason' => 'Unsuspended by admin',
        ':reports' => $reportsCount,
        ':suspended_at' => $suspendedAt,
        ':orig' => $user['created_at'] ?? null,
    ]);

    // Reactivate user
    $up = $db->prepare('UPDATE users SET is_active = 1 WHERE User_Id = :id');
    $up->execute([':id' => $userId]);

    // Delete related reports so report count becomes 0
    if (($user['role'] ?? '') === 'company') {
        $cstmt = $db->prepare('SELECT Com_Id FROM company WHERE User_Id = :uid LIMIT 1');
        $cstmt->execute([':uid' => $userId]);
        $company = $cstmt->fetch(PDO::FETCH_ASSOC);
        if ($company) {
            $del = $db->prepare('DELETE FROM companyreport WHERE Company_Id = :cid');
            $del->execute([':cid' => $company['Com_Id']]);
        }
    } elseif (($user['role'] ?? '') === 'student') {
        $s2 = $db->prepare('SELECT Student_Id FROM student WHERE User_Id = :uid LIMIT 1');
        $s2->execute([':uid' => $userId]);
        $student = $s2->fetch(PDO::FETCH_ASSOC);
        if ($student) {
            $del = $db->prepare('DELETE FROM studentreport WHERE Student_Id = :sid');
            $del->execute([':sid' => $student['Student_Id']]);
        }
    }

    // Remove suspended_users record (if exists)
    $del2 = $db->prepare('DELETE FROM suspended_users WHERE User_Id = :uid');
    $del2->execute([':uid' => $userId]);

    $db->commit();

    echo json_encode(['success' => true, 'message' => ($user['username'] ?? 'User') . ' unsuspended']);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('unsuspend_user error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}
