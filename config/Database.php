<?php
class Database {
    private $host = "127.0.0.1"; // OR localhost
    private $db_name = "internsparkdatabase";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name}",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo json_encode([
                "success" => false,
                "message" => "DB Connection Error: " . $e->getMessage()
            ]);
            exit;
        }
        return $this->conn;
    }
}
