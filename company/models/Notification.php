<?php
class Notification {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Create a notification
    public function create($message, $type = 'application') {
        $stmt = $this->db->prepare("INSERT INTO notification (message, type) VALUES (?, ?)");
        $stmt->execute([$message, $type]);
        return $this->db->lastInsertId();
    }

    // Get notification by ID
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM notification WHERE Nid = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
