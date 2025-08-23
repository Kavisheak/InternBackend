<?php
class User {
    private $conn;
    private $table = "users"; // adjust to your table name

    public $id;
    public $username;
    public $email;
    public $password;
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ✅ Register new user
    public function register($username, $email, $password, $role = "user") {
        $query = "INSERT INTO " . $this->table . " (username, email, password, role) 
                  VALUES (:username, :email, :password, :role)";

        $stmt = $this->conn->prepare($query);
        $hashed = password_hash($password, PASSWORD_BCRYPT);

        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $hashed);
        $stmt->bindParam(":role", $role);

        return $stmt->execute();
    }

    // ✅ Verify login
    public function verifyLogin($email, $password) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user; // return user data
        }
        return false;
    }

    // ✅ Check if email exists
    public function emailExists($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ✅ Store reset token
    public function storeResetToken($email, $token) {
        $query = "UPDATE " . $this->table . " 
                  SET reset_token = :token, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) 
                  WHERE email = :email";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":email", $email);

        return $stmt->execute();
    }

    // ✅ Verify token
    public function verifyToken($token) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE reset_token = :token AND reset_expires > NOW() 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ✅ Update password
    public function updatePassword($token, $newPassword) {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);

        $query = "UPDATE " . $this->table . " 
                  SET password = :password, reset_token = NULL, reset_expires = NULL 
                  WHERE reset_token = :token";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":password", $hashed);
        $stmt->bindParam(":token", $token);

        return $stmt->execute();
    }
}
?>
