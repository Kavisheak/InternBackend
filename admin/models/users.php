<?php
require_once(__DIR__ . '/../../config/Database.php');

class Users {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getUserCounts() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total, SUM(is_active=0) as suspended FROM users");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            "total" => (int)$result['total'],
            "suspended" => (int)$result['suspended']
        ];
    }

    public function getAllUsers() {
        $stmt = $this->conn->prepare("
            SELECT 
                User_Id as id,
                username,
                email,
                role,
                is_active,
                created_at,
                -- Example: last login, reports, applications, internships
                (SELECT COUNT(*) FROM report WHERE Student_Id = users.User_Id) as reports,
                (SELECT COUNT(*) FROM application WHERE Student_Id = users.User_Id) as applications,
                (SELECT COUNT(*) FROM internship WHERE Company_Id = (SELECT Com_Id FROM company WHERE User_Id = users.User_Id)) as internships
            FROM users
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}