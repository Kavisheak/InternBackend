<?php
// report_details.php
header('Content-Type: application/json; charset=utf-8');
// include cors and session helpers if available
if (file_exists(__DIR__ . '/../../config/cors.php')) require_once __DIR__ . '/../../config/cors.php';
if (file_exists(__DIR__ . '/../../api/sessions.php')) require_once __DIR__ . '/../../api/sessions.php';

require_once __DIR__ . '/../models/admin.php';

$out = ['success' => false];
try {
    $admin = new Admin();
    $userId = isset($_GET['userId']) ? trim($_GET['userId']) : null;
    $type = isset($_GET['type']) ? trim($_GET['type']) : null; // 'company' or 'student'

    if (!$userId || !$type) {
        http_response_code(400);
        $out['message'] = 'Missing userId or type parameter';
        echo json_encode($out);
        exit;
    }

    if ($type === 'company') {
        $rows = $admin->getReportsForCompanyUser($userId);
    } else if ($type === 'student') {
        $rows = $admin->getReportsForStudentUser($userId);
    } else {
        http_response_code(400);
        $out['message'] = 'Invalid type parameter';
        echo json_encode($out);
        exit;
    }

    $out['success'] = true;
    $out['data'] = $rows;

    // if no rows, provide diagnostic info about what ids/entities exist
    if (is_array($rows) && count($rows) === 0) {
        $out['diagnostic'] = [];
        if ($type === 'student') {
            $stuByUser = $admin->getStudentByUserId($userId);
            $out['diagnostic']['student_by_user'] = $stuByUser ?: null;

            $stuById = $admin->getStudentById($userId);
            $out['diagnostic']['student_by_id'] = $stuById ?: null;

            if ($stuByUser) {
                $out['diagnostic']['reports_for_student_by_user'] = $admin->countStudentReports($stuByUser['Student_Id']);
            }
            if ($stuById) {
                $out['diagnostic']['reports_for_student_by_id'] = $admin->countStudentReports($stuById['Student_Id']);
            }
        } else if ($type === 'company') {
            $comByUser = $admin->getCompanyByUserId($userId);
            $out['diagnostic']['company_by_user'] = $comByUser ?: null;

            $comById = $admin->getCompanyById($userId);
            $out['diagnostic']['company_by_id'] = $comById ?: null;

            if ($comByUser) {
                $out['diagnostic']['reports_for_company_by_user'] = $admin->countCompanyReports($comByUser['Com_Id']);
            }
            if ($comById) {
                $out['diagnostic']['reports_for_company_by_id'] = $admin->countCompanyReports($comById['Com_Id']);
            }
        }
    }
    echo json_encode($out);
} catch (Exception $e) {
    http_response_code(500);
    $out['message'] = 'Server error';
    $out['error'] = $e->getMessage();
    // attempt to log
    @file_put_contents(__DIR__ . '/../logs/report_details.log', date('c') . " - " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode($out);
}

