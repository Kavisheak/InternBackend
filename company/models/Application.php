<?php
class Application {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Get company id by user id
    public function getCompanyIdByUserId($userId) {
        $stmt = $this->db->prepare("SELECT Com_Id FROM company WHERE User_Id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['Com_Id'] : null;
    }

    // Get applications for a company (optionally filter by internship)
    public function getApplications($companyId, $internshipId = null) {
        if ($internshipId) {
            $sql = "SELECT a.*, a.cv_url, s.fname, s.lname, s.profile_img, s.cv_file, s.phone, s.github, s.linkedin, s.education, s.experience, s.gender, u.email, i.title AS internship_title
                    FROM application a
                    JOIN student s ON a.Student_Id = s.Student_Id
                    JOIN users u ON s.User_Id = u.User_Id
                    JOIN internship i ON a.Internship_Id = i.Internship_Id
                    WHERE a.Internship_Id = ? AND a.Internship_Id IN (SELECT Internship_Id FROM internship WHERE Company_Id = ?)
                    ORDER BY a.applied_date DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$internshipId, $companyId]);
        } else {
            $sql = "SELECT a.*, a.cv_url, s.fname, s.lname, s.profile_img, s.cv_file, s.phone, s.github, s.linkedin, s.education, s.experience, s.gender, u.email, i.title AS internship_title
                    FROM application a
                    JOIN student s ON a.Student_Id = s.Student_Id
                    JOIN users u ON s.User_Id = u.User_Id
                    JOIN internship i ON a.Internship_Id = i.Internship_Id
                    WHERE a.Internship_Id IN (SELECT Internship_Id FROM internship WHERE Company_Id = ?)
                    ORDER BY a.applied_date DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$companyId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get skills for a student
    public function getSkillsByStudentId($studentId) {
        $stmt = $this->db->prepare("SELECT skill_name FROM skill WHERE Student_Id = ?");
        $stmt->execute([$studentId]);
        $skills = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $skills[] = $row["skill_name"];
        }
        return $skills;
    }

    // Update application status
    public function updateStatus($appId, $status) {
        $stmt = $this->db->prepare("UPDATE application SET status = ?, updated_at = NOW() WHERE Application_Id = ?");
        return $stmt->execute([$status, $appId]);
    }

    // Get notification info for status update
    public function getNotificationInfo($appId) {
        $stmt = $this->db->prepare(
            "SELECT a.Student_Id, i.title AS internship_title, c.company_name
             FROM application a
             JOIN internship i ON a.Internship_Id = i.Internship_Id
             JOIN company c ON i.Company_Id = c.Com_Id
             WHERE a.Application_Id = ?"
        );
        $stmt->execute([$appId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}