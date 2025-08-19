
<?php
class Report {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Company reports a student
    public function addStudentReport($company_id, $student_id, $reason) {
        $sql = "INSERT INTO studentreport (Company_Id, Student_Id, reason) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$company_id, $student_id, $reason]);
    }

    // Check if this company already reported this student
    public function alreadyReported($company_id, $student_id) {
        $sql = "SELECT Student_Report_Id FROM studentreport WHERE Company_Id = ? AND Student_Id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$company_id, $student_id]);
        return $stmt->fetch() ? true : false;
    }
}