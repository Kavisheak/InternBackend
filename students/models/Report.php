
<?php
class Report {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function addCompanyReport($student_id, $company_id, $reason) {
        $sql = "INSERT INTO companyreport (Student_Id, Company_Id, reason) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$student_id, $company_id, $reason]);
    }

    public function alreadyReported($student_id, $company_id) {
        $sql = "SELECT Company_Report_Id FROM companyreport WHERE Student_Id = ? AND Company_Id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$student_id, $company_id]);
        return $stmt->fetch() ? true : false;
    }
}