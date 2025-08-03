<?php
class Student
{
    private $conn;
    private $table = "student"; // lowercase as per schema

    public $user_id;
    public $fname;
    public $lname;
    public $gender;
    public $education;
    public $experience;
    public $phone;
    public $github;
    public $linkedin;
    public $profile_img;
    public $cv_file;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Get student profile by User_Id
    public function getProfileByUserId($userId)
    {
        $query = "SELECT * FROM {$this->table} WHERE User_Id = :userId LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Insert or update profile data
    public function saveOrUpdate($data, $profilePath = null, $cvPath = null)
    {
        // Check if profile exists
        $existing = $this->getProfileByUserId($data['user_id']);

        if ($existing) {
            // UPDATE
            $query = "UPDATE {$this->table} SET 
                        fname = :fname,
                        lname = :lname,
                        gender = :gender,
                        education = :education,
                        experience = :experience,
                        phone = :phone,
                        github = :github,
                        linkedin = :linkedin";

            if ($profilePath !== null) {
                $query .= ", profile_img = :profile_img";
            }
            if ($cvPath !== null) {
                $query .= ", cv_file = :cv_file";
            }

            $query .= " WHERE User_Id = :user_id";

            $stmt = $this->conn->prepare($query);
        } else {
            // INSERT
            $query = "INSERT INTO {$this->table} 
                (fname, lname, gender, education, experience, phone, github, linkedin, profile_img, cv_file, User_Id)
                VALUES
                (:fname, :lname, :gender, :education, :experience, :phone, :github, :linkedin, :profile_img, :cv_file, :user_id)";

            $stmt = $this->conn->prepare($query);
        }

        // Bind parameters
        $stmt->bindValue(':fname', $data['fname']);
        $stmt->bindValue(':lname', $data['lname']);
        $stmt->bindValue(':gender', $data['gender']);
        $stmt->bindValue(':education', $data['education']);
        $stmt->bindValue(':experience', $data['experience']);
        $stmt->bindValue(':phone', $data['phone']);
        $stmt->bindValue(':github', $data['github']);
        $stmt->bindValue(':linkedin', $data['linkedin']);
        $stmt->bindValue(':user_id', $data['user_id']);

        if ($existing) {
            if ($profilePath !== null) {
                $stmt->bindValue(':profile_img', $profilePath);
            }
            if ($cvPath !== null) {
                $stmt->bindValue(':cv_file', $cvPath);
            }
        } else {
            // INSERT: bind profile_img and cv_file - if null, bind NULL
            $stmt->bindValue(':profile_img', $profilePath !== null ? $profilePath : null, PDO::PARAM_STR);
            $stmt->bindValue(':cv_file', $cvPath !== null ? $cvPath : null, PDO::PARAM_STR);
        }

        $result = $stmt->execute();

        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            return ["success" => false, "message" => "DB Error: " . $errorInfo[2]];
        }

        return ["success" => true, "message" => $existing ? "Profile updated" : "Profile created"];
    }

    // Get Student_Id by User_Id (for skills)
    public function getStudentIdByUserId($userId)
    {
        $query = "SELECT Student_Id FROM {$this->table} WHERE User_Id = :userId LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? intval($row['Student_Id']) : null;
    }

    // Save skills array for student
    public function saveSkills($studentId, $skills)
    {
        if (!$studentId) {
            return ["success" => false, "message" => "Invalid Student Id for skills"];
        }

        // Delete old skills
        $delStmt = $this->conn->prepare("DELETE FROM skill WHERE Student_Id = :studentId");
        $delStmt->bindParam(':studentId', $studentId);
        $delStmt->execute();

        // Insert new skills
        $insStmt = $this->conn->prepare("INSERT INTO skill (Student_Id, skill_name) VALUES (:studentId, :skillName)");

        foreach ($skills as $skill) {
            $insStmt->bindParam(':studentId', $studentId);
            $insStmt->bindParam(':skillName', $skill);
            $insStmt->execute();
        }

        return ["success" => true, "message" => "Skills saved"];
    }
}
