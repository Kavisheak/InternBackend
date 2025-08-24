<?php
require_once "Notification.php";

class CompanyNotification extends Notification {

    public function __construct($db) {
        parent::__construct($db);
    }

    // Create notification for a company
    public function createForCompany($companyId, $message, $type = 'application') {
        $nid = $this->create($message, $type);
        $stmt = $this->db->prepare("INSERT INTO companynotification (Notification_Id, Company_Id, seen) VALUES (?, ?, 0)");
        $stmt->execute([$nid, $companyId]);
        return $nid;
    }

    // Get all notifications for a company
    public function getAll($companyId) {
        $stmt = $this->db->prepare("
            SELECT cn.Company_Notif_Id, n.message, n.type, n.created_at, cn.seen
            FROM companynotification cn
            JOIN notification n ON cn.Notification_Id = n.Nid
            WHERE cn.Company_Id = ?
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Mark single notification as read
    public function markAsRead($notifId, $companyId) {
        $stmt = $this->db->prepare("UPDATE companynotification SET seen = 1 WHERE Company_Notif_Id = ? AND Company_Id = ?");
        return $stmt->execute([$notifId, $companyId]);
    }

    // Mark all notifications as read
    public function markAllAsRead($companyId) {
        // seen is tinyint(1), so use integer 1
        $stmt = $this->db->prepare("UPDATE companynotification SET seen = 1 WHERE Company_Id = ?");
        return $stmt->execute([$companyId]);
    }

    // Clear all notifications
    public function clearAll($companyId) {
        // Only delete from companynotification table for this company
        $stmt = $this->db->prepare("DELETE FROM companynotification WHERE Company_Id = ?");
        return $stmt->execute([$companyId]);
    }

    // Sync application notifications
    public function syncApplicationNotifications($companyId) {
        // For each internship, update/create notification with current count
        $stmt = $this->db->prepare("SELECT Internship_Id, title FROM internship WHERE Company_Id = ?");
        $stmt->execute([$companyId]);
        $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($internships as $internship) {
            $internshipId = $internship['Internship_Id'];
            $title = $internship['title'];

            // Count applications in last 24 hours
            $stmt2 = $this->db->prepare("SELECT COUNT(*) FROM application WHERE Internship_Id = ? AND applied_date >= NOW() - INTERVAL 1 DAY");
            $stmt2->execute([$internshipId]);
            $count = $stmt2->fetchColumn();

            // Find existing notification for this internship
            $msgLike = "%for '{$title}' in the last 24 hours%";
            $stmt3 = $this->db->prepare(
                "SELECT cn.Company_Notif_Id, cn.Notification_Id, n.message
                 FROM companynotification cn
                 JOIN notification n ON cn.Notification_Id = n.Nid
                 WHERE cn.Company_Id = ? AND n.message LIKE ?"
            );
            $stmt3->execute([$companyId, $msgLike]);
            $notifRow = $stmt3->fetch(PDO::FETCH_ASSOC);

            $msg = "You have $count new application(s) for '{$title}' in the last 24 hours.";

            if ($notifRow) {
                // Only update if count changed
                if ($notifRow['message'] !== $msg) {
                    $stmt4 = $this->db->prepare("UPDATE notification SET message = ? WHERE Nid = ?");
                    $stmt4->execute([$msg, $notifRow['Notification_Id']]);
                    // Mark as unseen
                    $stmt5 = $this->db->prepare("UPDATE companynotification SET seen = 0 WHERE Company_Notif_Id = ?");
                    $stmt5->execute([$notifRow['Company_Notif_Id']]);
                }
                // If count is zero, delete notification
                if ($count == 0) {
                    $stmt6 = $this->db->prepare("DELETE FROM companynotification WHERE Company_Notif_Id = ?");
                    $stmt6->execute([$notifRow['Company_Notif_Id']]);
                    $stmt7 = $this->db->prepare("DELETE FROM notification WHERE Nid = ?");
                    $stmt7->execute([$notifRow['Notification_Id']]);
                }
            } else {
                // Only create if count > 0 and no notification exists
                if ($count > 0) {
                    $this->createForCompany($companyId, $msg, 'application');
                }
            }
        }
    }
}
