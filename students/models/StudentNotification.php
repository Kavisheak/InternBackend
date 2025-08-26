<?php
require_once __DIR__ . '/Notifications.php';

class StudentNotification extends Notifications {
    // Send notification to a student (if not already sent for this internship)
    public function notifyBeforeDeadline($studentId, $internshipId, $internshipTitle, $deadline) {
        // Check if already notified for this internship
        $stmt = $this->conn->prepare(
            "SELECT sn.SNID FROM studentnotification sn
             JOIN notification n ON sn.Nid = n.Nid
             WHERE sn.Student_Id = ? AND n.type = 'deadline' AND n.message LIKE ?"
        );
        $stmt->execute([$studentId, "%$internshipTitle%"]);
        if ($stmt->fetch()) return false;

        $message = "Reminder: The deadline for your bookmarked internship '$internshipTitle' is in less than 24 hours (" . $deadline . ").";
        $nid = $this->createNotification($message, "deadline");

        // Insert into studentnotification
        $stmt2 = $this->conn->prepare("INSERT INTO studentnotification (Nid, Student_Id, seen) VALUES (?, ?, 0)");
        return $stmt2->execute([$nid, $studentId]);
    }

    // Get all unread notifications for a student
    public function getUnread($studentId) {
        $stmt = $this->conn->prepare(
            "SELECT sn.SNID, n.message, n.type, n.created_at, sn.seen
             FROM studentnotification sn
             JOIN notification n ON sn.Nid = n.Nid
             WHERE sn.Student_Id = ? AND sn.seen = 0
             ORDER BY n.created_at DESC"
        );
        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all notifications for a student (read and unread)
    public function getAll($studentId) {
        $stmt = $this->conn->prepare(
            "SELECT sn.SNID, n.message, n.type, n.created_at, sn.seen
             FROM studentnotification sn
             JOIN notification n ON sn.Nid = n.Nid
             WHERE sn.Student_Id = ?
             ORDER BY n.created_at DESC"
        );
        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Mark all as read
    public function markAllAsRead($studentId) {
        $stmt = $this->conn->prepare("UPDATE studentnotification SET seen = 1 WHERE Student_Id = ?");
        return $stmt->execute([$studentId]);
    }

    // Notify student about application status update
    public function notifyStatusUpdate($studentId, $companyName, $internshipTitle, $status) {
        $message = "Your application for '$internshipTitle' at '$companyName' has been updated to '$status'.";
        $nid = $this->createNotification($message, "status_update");
        $stmt = $this->conn->prepare("INSERT INTO studentnotification (Nid, Student_Id, seen) VALUES (?, ?, 0)");
        return $stmt->execute([$nid, $studentId]);
    }

    // Notify student if they have 5 
    public function notifyIfReported($studentId) {
        // Count reports
        $stmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM studentreport WHERE Student_Id = ?");
        $stmt->execute([$studentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['cnt'] == 5) { // Only when count is exactly 5
            // Check if already notified
            $stmt2 = $this->conn->prepare(
                "SELECT sn.SNID FROM studentnotification sn
                 JOIN notification n ON sn.Nid = n.Nid
                 WHERE sn.Student_Id = ? AND n.type = 'report_warning'"
            );
            $stmt2->execute([$studentId]);
            if ($stmt2->fetch()) return false;

            $message = "You have received 5 reports. Please follow community guidelines and standards.";
            $nid = $this->createNotification($message, "report_warning");
            $stmt3 = $this->conn->prepare("INSERT INTO studentnotification (Nid, Student_Id, seen) VALUES (?, ?, 0)");
            return $stmt3->execute([$nid, $studentId]);
        }
        return false;
    }

    // Delete all notifications for a student
    public function clearAll($studentId) {
        // Delete from studentnotification (will not delete from notification table for data integrity)
        $stmt = $this->conn->prepare("DELETE FROM studentnotification WHERE Student_Id = ?");
        return $stmt->execute([$studentId]);
    }

    // Notify student about expired bookmark
    public function notifyBookmarkExpired($studentId, $internshipTitle) {
        // Check if already notified for this internship expiration
        $stmt = $this->conn->prepare(
            "SELECT sn.SNID FROM studentnotification sn
             JOIN notification n ON sn.Nid = n.Nid
             WHERE sn.Student_Id = ? AND n.type = 'bookmark_expired' AND n.message LIKE ?"
        );
        $stmt->execute([$studentId, "%$internshipTitle%"]);
        if ($stmt->fetch()) return false;

        $message = "Your bookmarked internship '$internshipTitle' has expired and was removed from your bookmarks.";
        $nid = $this->createNotification($message, "bookmark_expired");
        $stmt2 = $this->conn->prepare("INSERT INTO studentnotification (Nid, Student_Id, seen) VALUES (?, ?, 0)");
        return $stmt2->execute([$nid, $studentId]);
    }
}