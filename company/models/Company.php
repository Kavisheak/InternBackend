<?php
class Company {
    private $conn;
    private $table = "Company";

    public $user_id;
    public $company_name;
    public $industry;
    public $company_size;
    public $location;
    public $website;
    public $email; // You have this property but not saving it in DB currently
    public $about;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get company profile by user id
    public function getProfileByUserId($userId) {
        $query = "SELECT * FROM {$this->table} WHERE User_Id = :userId LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Save new or update existing company profile
    public function saveOrUpdate() {
        // Check if profile exists for this user
        if ($this->getProfileByUserId($this->user_id)) {
            $query = "UPDATE {$this->table} SET 
                        company_name = :company_name,
                        industry = :industry,
                        company_size = :company_size,
                        location = :location,
                        website = :website,
                        about = :about
                      WHERE User_Id = :user_id";
        } else {
            $query = "INSERT INTO {$this->table} 
                      (company_name, industry, company_size, location, website, about, User_Id) 
                      VALUES (:company_name, :industry, :company_size, :location, :website, :about, :user_id)";
        }

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':company_name', $this->company_name);
        $stmt->bindParam(':industry', $this->industry);
        $stmt->bindParam(':company_size', $this->company_size);
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':website', $this->website);
        $stmt->bindParam(':about', $this->about);
        $stmt->bindParam(':user_id', $this->user_id);

        $success = $stmt->execute();

        // Optional: Log error if failed
        if (!$success) {
            error_log("Company saveOrUpdate failed: " . implode(", ", $stmt->errorInfo()));
        }

        return $success;
    }

    public function getCompanyIdByUserId($userId) {
        $query = "SELECT Com_Id FROM company WHERE User_Id = :userId LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? intval($row['Com_Id']) : null;
    }

    public function saveContacts($companyId, $contacts) {
        if (!$companyId || !is_array($contacts)) {
            return ["success" => false, "message" => "Invalid company or contacts"];
        }

        // Delete old contacts
        $delStmt = $this->conn->prepare("DELETE FROM companycontact WHERE Company_Id = :companyId");
        $delStmt->bindParam(':companyId', $companyId);
        $delStmt->execute();

        // Insert new contacts
        $insStmt = $this->conn->prepare(
            "INSERT INTO companycontact (Company_Id, contact_name, contact_email, contact_phone, contact_type) 
             VALUES (:companyId, :name, :email, :phone, :type)"
        );
        foreach ($contacts as $contact) {
            $insStmt->bindParam(':companyId', $companyId);
            $insStmt->bindParam(':name', $contact['name']);
            $insStmt->bindParam(':email', $contact['email']);
            $insStmt->bindParam(':phone', $contact['phone']);
            $insStmt->bindParam(':type', $contact['type']);
            $insStmt->execute();
        }
        return ["success" => true, "message" => "Contacts saved"];
    }
}
