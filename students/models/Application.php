<?php
// filepath: c:\xampp\htdocs\InternBackend\students\models\Application.php

class Application {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getStudentIdByUserId($userId) {
        $stmt = $this->db->prepare("SELECT Student_Id FROM student WHERE User_Id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? intval($row['Student_Id']) : null;
    }

    public function checkDuplicate($studentId, $internshipId) {
        $stmt = $this->db->prepare("SELECT 1 FROM application WHERE Student_Id = ? AND Internship_Id = ?");
        $stmt->execute([$studentId, $internshipId]);
        return $stmt->fetchColumn() ? true : false;
    }

    public function apply($studentId, $internshipId) {
        $stmt = $this->db->prepare(
            "INSERT INTO application (Internship_Id, Student_Id, applied_date, status) VALUES (?, ?, NOW(), 'pending')"
        );
        return $stmt->execute([$internshipId, $studentId]);
    }

    // Get all applications for internships posted by this company, grouped by internship
    public function getApplicationsByCompany($companyId) {
        $sql = "
            SELECT
                i.Internship_Id,
                i.title,
                i.location,
                i.duration,
                i.salary,
                a.Application_Id,
                a.status,
                a.applied_date,
                s.Student_Id,
                s.fname,
                s.lname,
                s.gender,
                s.education,
                s.experience,
                s.phone,
                s.github,
                s.linkedin,
                s.profile_img,
                s.cv_file
            FROM internship i
            JOIN application a ON i.Internship_Id = a.Internship_Id
            JOIN student s ON a.Student_Id = s.Student_Id
            WHERE i.Company_Id = ?
            ORDER BY i.Internship_Id, a.applied_date DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$companyId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group applications by internship
        $grouped = [];
        foreach ($rows as $row) {
            $iid = $row['Internship_Id'];
            if (!isset($grouped[$iid])) {
                $grouped[$iid] = [
                    "internship" => [
                        "Internship_Id" => $row['Internship_Id'],
                        "title" => $row['title'],
                        "location" => $row['location'],
                        "duration" => $row['duration'],
                        "salary" => $row['salary'],
                    ],
                    "applications" => []
                ];
            }
            $grouped[$iid]["applications"][] = [
                "Application_Id" => $row['Application_Id'],
                "status" => $row['status'],
                "applied_date" => $row['applied_date'],
                "student" => [
                    "Student_Id" => $row['Student_Id'],
                    "fname" => $row['fname'],
                    "lname" => $row['lname'],
                    "gender" => $row['gender'],
                    "education" => $row['education'],
                    "experience" => $row['experience'],
                    "phone" => $row['phone'],
                    "github" => $row['github'],
                    "linkedin" => $row['linkedin'],
                    "profile_img" => $row['profile_img'],
                    "cv_file" => $row['cv_file'],
                ]
            ];
        }
        return array_values($grouped);
    }
}