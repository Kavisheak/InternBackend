<?php
class Notifications {
    protected $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a notification and return its Nid
    public function createNotification($message, $type = "info") {
        $stmt = $this->conn->prepare("INSERT INTO notification (message, type) VALUES (?, ?)");
        $stmt->execute([$message, $type]);
        return $this->conn->lastInsertId();
    }
}