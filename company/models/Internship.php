<?php
class Internship
{
    private $db;
    private $user_id;
    private $company_id;

    public function __construct($db, $user_id)
    {
        $this->db = $db;
        $this->user_id = $user_id;
        $this->company_id = $this->getCompanyId();
    }

    private function getCompanyId()
    {
        $stmt = $this->db->prepare("SELECT Com_Id FROM Company WHERE User_Id = :user_id");
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        return $company ? $company['Com_Id'] : null;
    }

    public function createOrUpdate($data)
    {
        $fields = [
            'title' => trim($data['title'] ?? ''),
            'location' => trim($data['location'] ?? ''),
            'internship_type' => strtolower(trim($data['internshipType'] ?? '')),
            'salary' => trim($data['salary'] ?? ''),
            'duration' => trim($data['duration'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'requirements' => trim($data['requirements'] ?? ''),
            'deadline' => trim($data['deadline'] ?? ''),
            'application_limit' => intval($data['applicationLimit'] ?? 0),
        ];

        if (!empty($data['id'])) {
            // Update
            $query = "UPDATE Internship SET 
                        title = :title,
                        location = :location,
                        internship_type = :internship_type,
                        salary = :salary,
                        duration = :duration,
                        description = :description,
                        requirements = :requirements,
                        deadline = :deadline,
                        application_limit = :application_limit,
                        updated_at = NOW()
                      WHERE Internship_Id = :id AND Company_Id = :company_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
        } else {
            // Insert
            $query = "INSERT INTO Internship 
                        (title, location, internship_type, salary, duration, description, requirements, deadline, application_limit, Company_Id)
                      VALUES 
                        (:title, :location, :internship_type, :salary, :duration, :description, :requirements, :deadline, :application_limit, :company_id)";
            $stmt = $this->db->prepare($query);
        }

        foreach ($fields as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }

        $stmt->bindValue(':company_id', $this->company_id);

        return $stmt->execute();
    }

    public function getAll()
    {
        $query = "SELECT Internship.*, Company.company_name AS company 
                  FROM Internship
                  INNER JOIN Company ON Internship.Company_Id = Company.Com_Id
                  WHERE Internship.is_active = 1 AND Internship.Company_Id = :company_id
                  ORDER BY Internship.updated_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':company_id', $this->company_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $query = "SELECT 
                    Internship_Id AS id,
                    title,
                    location,
                    internship_type,
                    salary,
                    duration,
                    description,
                    requirements,
                    deadline,
                    application_limit
                  FROM Internship
                  WHERE Internship_Id = :id AND Company_Id = :company_id AND is_active = 1
                  LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':company_id', $this->company_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM Internship WHERE Internship_Id = :id AND Company_Id = :company_id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':company_id', $this->company_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
