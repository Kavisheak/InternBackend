<?php
require_once '../../config/Database.php';
require_once '../../api/sessions.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check company session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$interviewId = $data['interview_id'] ?? null;
$status = $data['status'] ?? null;
$feedback = $data['feedback'] ?? null;

$allowedStatuses = ['pending', 'accepted', 'rejected', 'absent'];
if (!$interviewId || !$status || !in_array($status, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid parameters']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    if ($feedback !== null) {
        $stmt = $db->prepare("UPDATE interview_schedule SET status = ?, company_feedback = ? WHERE id = ?");
        $stmt->execute([$status, $feedback, $interviewId]);
    } else {
        $stmt = $db->prepare("UPDATE interview_schedule SET status = ? WHERE id = ?");
        $stmt->execute([$status, $interviewId]);
    }

    if ($stmt->rowCount() > 0) {
        // Fetch student and internship info
        $stmt2 = $db->prepare(
            "SELECT s.Student_Id, s.fname, s.lname, i.title AS internship_title, c.company_name
             FROM interview_schedule iv
             JOIN student s ON iv.student_id = s.Student_Id
             JOIN internship i ON iv.internship_id = i.Internship_Id
             JOIN company c ON iv.company_id = c.Com_Id
             WHERE iv.id = ?"
        );
        $stmt2->execute([$interviewId]);
        $info = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($info) {
            require_once __DIR__ . '/../../students/models/StudentNotification.php';
            $notif = new StudentNotification($db);

            $studentId = $info['Student_Id'];
            $studentName = trim($info['fname'] . ' ' . $info['lname']);
            $internshipTitle = $info['internship_title'];
            $companyName = $info['company_name'];

            // Choose a professional message based on status
            switch (strtolower($status)) {
                case "accepted":
                    $message = "Congratulations! You have successfully passed the interview for '$internshipTitle' at '$companyName'. Please check your interview dashboard for next steps and onboarding details.";
                    break;
                case "rejected":
                    $message = "Thank you for attending the interview for '$internshipTitle' at '$companyName'. We appreciate your effort, but you were not selected this time. Please check your interview dashboard for more details and future opportunities.";
                    break;
                case "absent":
                    $message = "We noticed you were absent for your interview for '$internshipTitle' at '$companyName'. Please check your interview dashboard for more information or contact the company if you have concerns.";
                    break;
                default:
                    $message = "Your interview status for '$internshipTitle' at '$companyName' has been updated. Please check your interview dashboard for details.";
            }

            $notif->createNotification($message, "interview");
            $nid = $db->lastInsertId();
            $stmt3 = $db->prepare("INSERT INTO studentnotification (Nid, Student_Id, seen) VALUES (?, ?, 0)");
            $stmt3->execute([$nid, $studentId]);
        }

        echo json_encode(['success' => true, 'message' => 'Interview status and feedback updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made or invalid interview ID']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

