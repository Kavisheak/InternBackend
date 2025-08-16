<?php
class User {
    private $conn;
    private $table = "Users"; // make sure your DB table is actually called 'Users'

    public $username;
    public $email;
    public $password;
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function emailExists($email) {
        $query = "SELECT User_Id FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function setPassword($password) {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  (username, email, password, role, is_active, created_at)
                  VALUES (:username, :email, :password, :role, 1, NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':role', $this->role);

        if ($stmt->execute()) {
            return true;
        } else {
            $error = $stmt->errorInfo();
            echo json_encode(["success" => false, "message" => "DB Error: " . $error[2]]);
            return false;
        }
    }

    // ✅ Add this for login
    public function verifyLogin($email, $password) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }

    public function getTotalUsers() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM " . $this->table);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getSuspendedUsers() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as suspended FROM " . $this->table . " WHERE is_active=0");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['suspended'];
    }
}
