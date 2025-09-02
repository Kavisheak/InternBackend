<?php

require_once(__DIR__ . '/../../config/Database.php');


class Dashboard {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getCounts() {
        // Total users and suspended users
        $userStmt = $this->conn->prepare("SELECT COUNT(*) as total, SUM(is_active=0) as suspended FROM users");
        $userStmt->execute();
        $userCounts = $userStmt->fetch(PDO::FETCH_ASSOC);

        // Total active internships
        $internStmt = $this->conn->prepare("SELECT COUNT(*) as total FROM internship WHERE is_active=1");
        $internStmt->execute();
        $internCounts = $internStmt->fetch(PDO::FETCH_ASSOC);

        return [
            "users" => [
                "total" => (int)$userCounts['total'],
                "suspended" => (int)$userCounts['suspended']
            ],
            "internships" => [
                "total" => (int)$internCounts['total']
            ]
        ];
    }
}