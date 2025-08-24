<?php

require_once(__DIR__ . '/../../config/Database.php');
require_once "../../api/sessions.php";


class Admin {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getCounts() {
        // Total users and suspended users
        $userStmt = $this->conn->prepare("SELECT COUNT(*) as total, SUM(is_active=0) as suspended FROM users");
        $userStmt->execute();
        $userCounts = $userStmt->fetch(PDO::FETCH_ASSOC);

        // Total active internships
        $internStmt = $this->conn->prepare("SELECT COUNT(*) as total FROM internship WHERE is_active=1");
        $internStmt->execute();
        $internCounts = $internStmt->fetch(PDO::FETCH_ASSOC);

        return [
            "users" => [
                "total" => (int)$userCounts['total'],
                "suspended" => (int)$userCounts['suspended']
            ],
            "internships" => [
                "total" => (int)$internCounts['total']
            ]
        ];
    }

    public function getCompanyReports($companyId) {
        $stmt = $this->conn->prepare("
            SELECT 
                cr.Company_Report_Id as id,
                cr.reason,
                cr.reported_at,
                s.Student_Id,
                s.name as student_name,
                s.email as student_email
            FROM companyreport cr
            LEFT JOIN student s ON cr.Student_Id = s.Student_Id
            WHERE cr.Company_Id = :companyId
            ORDER BY cr.reported_at DESC
        ");
        $stmt->execute([':companyId' => $companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}