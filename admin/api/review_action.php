<?php
require_once(__DIR__ . '/../../config/cors.php');
require_once(__DIR__ . '/../../config/Database.php');
$data = json_decode(file_get_contents("php://input"), true);
if (!$data || empty($data['request_id']) || empty($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}
$db = (new Database())->getConnection();
if ($data['action'] === 'accept') {
    $req = $db->prepare('SELECT User_Id FROM review_requests WHERE Request_Id = :rid');
    $req->execute([':rid' => $data['request_id']]);
    $user = $req->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        // Unsuspend user
        $up = $db->prepare('UPDATE users SET is_active = 1 WHERE User_Id = :uid');
        $up->execute([':uid' => $user['User_Id']]);

        // Delete all relevant reports for this user
        // Get user role
        $ust = $db->prepare('SELECT role FROM users WHERE User_Id = :uid LIMIT 1');
        $ust->execute([':uid' => $user['User_Id']]);
        $roleRow = $ust->fetch(PDO::FETCH_ASSOC);
        $role = $roleRow['role'] ?? '';

        if ($role === 'company') {
            $cstmt = $db->prepare('SELECT Com_Id FROM company WHERE User_Id = :uid LIMIT 1');
            $cstmt->execute([':uid' => $user['User_Id']]);
            $company = $cstmt->fetch(PDO::FETCH_ASSOC);
            if ($company) {
                $del = $db->prepare('DELETE FROM companyreport WHERE Company_Id = :cid');
                $del->execute([':cid' => $company['Com_Id']]);
            }
        } elseif ($role === 'student') {
            $sstmt = $db->prepare('SELECT Student_Id FROM student WHERE User_Id = :uid LIMIT 1');
            $sstmt->execute([':uid' => $user['User_Id']]);
            $student = $sstmt->fetch(PDO::FETCH_ASSOC);
            if ($student) {
                $del = $db->prepare('DELETE FROM studentreport WHERE Student_Id = :sid');
                $del->execute([':sid' => $student['Student_Id']]);
            }
        }
    }
    $stmt = $db->prepare('UPDATE review_requests SET status = "accepted", admin_response = :resp WHERE Request_Id = :rid');
    $stmt->execute([':resp' => $data['response'] ?? '', ':rid' => $data['request_id']]);
    echo json_encode(['success' => true, 'message' => 'User unsuspended, reports cleared, and request accepted']);
} else {
    $stmt = $db->prepare('UPDATE review_requests SET status = "rejected", admin_response = :resp WHERE Request_Id = :rid');
    $stmt->execute([':resp' => $data['response'] ?? '', ':rid' => $data['request_id']]);
    echo json_encode(['success' => true, 'message' => 'Request rejected']);
}