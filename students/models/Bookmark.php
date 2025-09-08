<?php
class Bookmark {
    private $conn;
    private $table = "bookmarked";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function add($studentId, $internshipId) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (student_id, internship_id) VALUES (?, ?)");
        return $stmt->execute([$studentId, $internshipId]);
    }

    public function remove($studentId, $internshipId) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE student_id = ? AND internship_id = ?");
        return $stmt->execute([$studentId, $internshipId]);
    }

    public function isBookmarked($studentId, $internshipId) {
        $stmt = $this->conn->prepare("SELECT bookmark_id FROM {$this->table} WHERE student_id = ? AND internship_id = ?");
        $stmt->execute([$studentId, $internshipId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function getAll($studentId) {
        $stmt = $this->conn->prepare("SELECT internship_id FROM {$this->table} WHERE student_id = ?");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function removeExpiredBookmarks($studentId) {
        $stmt = $this->conn->prepare("
            DELETE FROM {$this->table}
            WHERE student_id = ?
            AND internship_id IN (
                SELECT Internship_Id FROM internship WHERE deadline < CURDATE()
            )
        ");
        return $stmt->execute([$studentId]);
    }
}