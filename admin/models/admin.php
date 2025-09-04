<?php

require_once(__DIR__ . '/../../config/Database.php');
require_once(__DIR__ . '/../../api/sessions.php');


class Admin {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getCounts() {
        // Total users except admins and suspended users except admins
        $userStmt = $this->conn->prepare(
            "SELECT COUNT(*) as total, SUM(is_active=0) as suspended 
             FROM users WHERE role != 'admin'"
        );
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
                s.fname as student_fname,
                s.lname as student_lname,
                u.email as student_email
            FROM companyreport cr
            LEFT JOIN student s ON cr.Student_Id = s.Student_Id
            LEFT JOIN users u ON s.User_Id = u.User_Id
            WHERE cr.Company_Id = :companyId
            ORDER BY cr.reported_at DESC
        ");
        $stmt->execute([':companyId' => $companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get reports for a company by website user id or by company id
    public function getReportsForCompanyUser($userId) {
        // try find company by User_Id
        $cstmt = $this->conn->prepare("SELECT Com_Id, company_name FROM company WHERE User_Id = :uid LIMIT 1");
        $cstmt->execute([':uid' => $userId]);
        $company = $cstmt->fetch(PDO::FETCH_ASSOC);
        if (!$company) {
            // maybe user passed Com_Id directly
            $cstmt2 = $this->conn->prepare("SELECT Com_Id, company_name FROM company WHERE Com_Id = :cid LIMIT 1");
            $cstmt2->execute([':cid' => $userId]);
            $company = $cstmt2->fetch(PDO::FETCH_ASSOC);
            if (!$company) return [];
        }
        $companyId = $company['Com_Id'];

        $stmt = $this->conn->prepare(
            "SELECT cr.Company_Report_Id as id, cr.reason, cr.reported_at, s.Student_Id as reporter_id, CONCAT(s.fname, ' ', s.lname) as reporter_name, u.email as reporter_email
             FROM companyreport cr
             LEFT JOIN student s ON cr.Student_Id = s.Student_Id
             LEFT JOIN users u ON s.User_Id = u.User_Id
             WHERE cr.Company_Id = :companyId
             ORDER BY cr.reported_at DESC"
        );
        $stmt->execute([':companyId' => $companyId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['reporter_type'] = 'student';
            $r['target_company_id'] = (int)$companyId;
            $r['target_company_name'] = $company['company_name'] ?? null;
        }
        return $rows;
    }

    // Get reports for a student by website user id or by student id
    public function getReportsForStudentUser($userId) {
        // try find student by User_Id
        $sstmt = $this->conn->prepare("SELECT Student_Id, fname, lname FROM student WHERE User_Id = :uid LIMIT 1");
        $sstmt->execute([':uid' => $userId]);
        $student = $sstmt->fetch(PDO::FETCH_ASSOC);
        if (!$student) {
            $sstmt2 = $this->conn->prepare("SELECT Student_Id, fname, lname FROM student WHERE Student_Id = :sid LIMIT 1");
            $sstmt2->execute([':sid' => $userId]);
            $student = $sstmt2->fetch(PDO::FETCH_ASSOC);
            if (!$student) return [];
        }
        $studentId = $student['Student_Id'];

        // reports made against this student (companies reporting student)
        $stmt = $this->conn->prepare(
            "SELECT sr.Student_Report_Id as id, sr.reason, sr.reported_at, c.Com_Id as reporter_id, c.company_name as reporter_name
             FROM studentreport sr
             LEFT JOIN company c ON sr.Company_Id = c.Com_Id
             WHERE sr.Student_Id = :studentId"
        );
        $stmt->execute([':studentId' => $studentId]);
        $received = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($received as &$r) {
            $r['reporter_type'] = 'company';
            $r['target_student_id'] = (int)$studentId;
            $r['target_student_name'] = trim(($student['fname'] ?? '') . ' ' . ($student['lname'] ?? ''));
            $r['reporter_email'] = null;
        }

        // reports made by this student against companies
        $stmt2 = $this->conn->prepare(
            "SELECT cr.Company_Report_Id as id, cr.reason, cr.reported_at, cr.Company_Id as target_company_id, c.company_name as target_company_name
             FROM companyreport cr
             LEFT JOIN company c ON cr.Company_Id = c.Com_Id
             WHERE cr.Student_Id = :studentId"
        );
        $stmt2->execute([':studentId' => $studentId]);
        $made = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        foreach ($made as &$m) {
            $m['reporter_type'] = 'student';
            $m['reporter_id'] = (int)$studentId;
            $m['reporter_name'] = trim(($student['fname'] ?? '') . ' ' . ($student['lname'] ?? ''));
            // try to include reporter email if linked
            $uem = $this->conn->prepare("SELECT email FROM users WHERE User_Id = :uid LIMIT 1");
            $uem->execute([':uid' => $userId]);
            $ue = $uem->fetch(PDO::FETCH_ASSOC);
            $m['reporter_email'] = $ue['email'] ?? null;
        }

        // merge received and made reports, sort by reported_at desc
        $all = array_merge($received, $made);
        usort($all, function($a, $b) {
            return strcmp($b['reported_at'] ?? '', $a['reported_at'] ?? '');
        });
        return $all;
    }

    public function getNonAdminUsers() {
        $stmt = $this->conn->prepare(
            "SELECT u.User_Id as id, u.username, u.email, u.role, u.is_active, u.created_at,
                    c.Com_Id as Com_Id, s.Student_Id as Student_Id,
                    -- reports received (about this user/entity)
                    CASE
                        WHEN u.role = 'company' THEN IFNULL((SELECT COUNT(*) FROM companyreport cr WHERE cr.Company_Id = c.Com_Id), 0)
                        WHEN u.role = 'student' THEN IFNULL((SELECT COUNT(*) FROM studentreport sr WHERE sr.Student_Id = s.Student_Id), 0)
                        ELSE 0
                    END as reports_received,
                    -- reports made by this user/entity
                    CASE
                        WHEN u.role = 'company' THEN IFNULL((SELECT COUNT(*) FROM studentreport sr WHERE sr.Company_Id = c.Com_Id), 0)
                        WHEN u.role = 'student' THEN IFNULL((SELECT COUNT(*) FROM companyreport cr WHERE cr.Student_Id = s.Student_Id), 0)
                        ELSE 0
                    END as reports_made,
                    -- applications (for students) and internships (for companies)
                    CASE
                        WHEN u.role = 'student' THEN IFNULL((SELECT COUNT(*) FROM application a WHERE a.Student_Id = s.Student_Id), 0)
                        ELSE 0
                    END as applications,
                    CASE
                        WHEN u.role = 'company' THEN IFNULL((SELECT COUNT(*) FROM internship i WHERE i.Company_Id = c.Com_Id), 0)
                        ELSE 0
                    END as internships
             FROM users u
             LEFT JOIN company c ON c.User_Id = u.User_Id
             LEFT JOIN student s ON s.User_Id = u.User_Id
             WHERE u.role != 'admin'"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Diagnostic helpers
    public function getStudentByUserId($uid) {
        $stmt = $this->conn->prepare("SELECT Student_Id, fname, lname FROM student WHERE User_Id = :uid LIMIT 1");
        $stmt->execute([':uid' => $uid]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getStudentById($sid) {
        $stmt = $this->conn->prepare("SELECT Student_Id, fname, lname FROM student WHERE Student_Id = :sid LIMIT 1");
        $stmt->execute([':sid' => $sid]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function countStudentReports($sid) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as c FROM studentreport WHERE Student_Id = :sid");
        $stmt->execute([':sid' => $sid]);
        return (int)$stmt->fetchColumn();
    }

    public function getCompanyByUserId($uid) {
        $stmt = $this->conn->prepare("SELECT Com_Id, company_name FROM company WHERE User_Id = :uid LIMIT 1");
        $stmt->execute([':uid' => $uid]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCompanyById($cid) {
        $stmt = $this->conn->prepare("SELECT Com_Id, company_name FROM company WHERE Com_Id = :cid LIMIT 1");
        $stmt->execute([':cid' => $cid]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function countCompanyReports($cid) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as c FROM companyreport WHERE Company_Id = :cid");
        $stmt->execute([':cid' => $cid]);
        return (int)$stmt->fetchColumn();
    }
}