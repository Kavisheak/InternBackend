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

    public function apply($studentId, $internshipId, $preferredCv = null) {
        // If no preferred CV, get student's profile CV
        if (!$preferredCv) {
            $stmt = $this->db->prepare("SELECT cv_file FROM student WHERE Student_Id = ?");
            $stmt->execute([$studentId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $preferredCv = $row && $row['cv_file'] ? $row['cv_file'] : null;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO application (Internship_Id, Student_Id, applied_date, status, cv_url) VALUES (?, ?, NOW(), 'pending', ?)"
        );
        $success = $stmt->execute([$internshipId, $studentId, $preferredCv]);

        if ($success) {
            // Get company id and internship title
            $stmt2 = $this->db->prepare("SELECT Company_Id, title FROM internship WHERE Internship_Id = ?");
            $stmt2->execute([$internshipId]);
            $intern = $stmt2->fetch(PDO::FETCH_ASSOC);
            if ($intern) {
                $companyId = $intern['Company_Id'];
                $internshipTitle = $intern['title'];

                // Count applications for this internship in last 24 hours
                $stmt3 = $this->db->prepare("
                    SELECT COUNT(*) AS newapps
                    FROM application
                    WHERE Internship_Id = ? AND applied_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                ");
                $stmt3->execute([$internshipId]);
                $newApps = $stmt3->fetchColumn();

                // Build notification message
                $notifMsg = "You have {$newApps} new application" . ($newApps > 1 ? "s" : "") . " for '{$internshipTitle}' in the last 24 hours.";

                $this->db->prepare("INSERT INTO notification (message, type) VALUES (?, 'application')")
                    ->execute([$notifMsg]);
                $notifId = $this->db->lastInsertId();
                $this->db->prepare("INSERT INTO companynotification (Notification_Id, Company_Id) VALUES (?, ?)")
                    ->execute([$notifId, $companyId]);
            }
        }
        return $success;
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

    public function getApplicationsForStudent($studentId) {
        $sql = "
            SELECT
                a.Application_Id,
                i.title,
                c.company_name AS company,
                c.logo_img, -- <-- Add this line
                i.location,
                DATE_FORMAT(a.applied_date, '%Y-%m-%d') AS appliedDate,
                i.deadline,
                a.status,
                i.internship_type AS jobType,
                i.salary AS stipend,
                i.duration,
                i.description,
                i.requirements,
                i.Internship_Id
            FROM application a
            JOIN internship i ON a.Internship_Id = i.Internship_Id
            JOIN company c ON i.Company_Id = c.Com_Id
            WHERE a.Student_Id = ?
            ORDER BY a.applied_date DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$studentId]);
        $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Optionally, fetch skills for each internship
        foreach ($apps as &$app) {
            $skills = [];
            $reqs = explode("\n", $app['requirements']);
            foreach ($reqs as $req) {
                $skills[] = trim($req);
            }
            $app['skills'] = $skills;
        }
        return $apps;
    }

    public function deleteApplication($applicationId, $studentId) {
        $stmt = $this->db->prepare("DELETE FROM application WHERE Application_Id = ? AND Student_Id = ?");
        return $stmt->execute([$applicationId, $studentId]);
    }
}