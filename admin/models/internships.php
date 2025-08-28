<?php
require_once(__DIR__ . '/../../config/Database.php');
require_once "../../api/sessions.php";

class Internships {
    private $conn;
    private $company_id;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();

        // set company_id from session if available
        if (isset($_SESSION['company_id'])) {
            $this->company_id = $_SESSION['company_id'];
        }
    }

    public function getAllInternships() {
        $stmt = $this->conn->prepare("
            SELECT 
                i.Internship_Id as id,
                i.title,
                c.company_name as company,
                i.location,
                i.duration,
                i.salary,
                i.deadline,
                i.requirements,
                i.description,
                i.created_at as posted,
                (SELECT COUNT(*) FROM application WHERE Internship_Id = i.Internship_Id) as applications,
                CASE WHEN i.is_active = 1 THEN 'active' ELSE 'expired' END as status,
                i.internship_type as workType
            FROM internship i
            LEFT JOIN company c ON i.Company_Id = c.Com_Id
            ORDER BY i.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInternshipCount() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM internship WHERE is_active=1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            "total" => (int)$result['total']
        ];
    }

    public function getInternshipById($id) {
        $stmt = $this->conn->prepare("
            SELECT i.Internship_Id as id, i.title, i.location, i.duration, i.salary,
                   i.internship_type, i.description, i.requirements, i.deadline,
                   i.application_limit, i.is_active, i.created_at, i.updated_at,
                   c.company_name, c.location AS company_location, c.website, c.about
            FROM internship i
            JOIN company c ON i.Company_Id = c.Com_Id
            WHERE i.Internship_Id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("
            DELETE FROM internship 
            WHERE Internship_Id = :id AND Company_Id = :company_id
        ");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':company_id', $this->company_id, PDO::PARAM_INT);
    $stmt->execute();
    return ($stmt->rowCount() > 0);
    }
}
?>