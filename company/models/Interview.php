<?php

class Interview
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db; // PDO connection
    }

    public function schedule($applicationId, $studentId, $companyId, $internshipId, $type, $date, $time, $meetingLink, $location)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO interview_schedule 
                (application_id, student_id, company_id, internship_id, interview_type, interview_date, interview_time, meeting_link, location, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        return $stmt->execute([
            $applicationId,
            $studentId,
            $companyId,
            $internshipId,
            $type,
            $date,
            $time,
            $meetingLink,
            $location
        ]);
    }

    public function getInterviewByApplication($applicationId)
    {
        $stmt = $this->db->prepare(
            "SELECT interview_date, interview_time, interview_type, meeting_link, location
             FROM interview_schedule
             WHERE application_id = ?
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$applicationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function upsertInterview($applicationId, $studentId, $companyId, $internshipId, $type, $date, $time, $meetingLink, $location)
    {
        // Prepare values based on type
        if ($type === "online") {
            $location = null;
        } elseif ($type === "onsite") {
            $meetingLink = null;
        }

        // Check if interview exists
        $stmt = $this->db->prepare("SELECT id FROM interview_schedule WHERE application_id = ?");
        $stmt->execute([$applicationId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Update
            $stmt = $this->db->prepare(
                "UPDATE interview_schedule SET
                    interview_type = ?,
                    interview_date = ?,
                    interview_time = ?,
                    meeting_link = ?,
                    location = ?,
                    updated_at = NOW()
                 WHERE application_id = ?"
            );
            return $stmt->execute([
                $type,
                $date,
                $time,
                $meetingLink,
                $location,
                $applicationId
            ]);
        } else {
            // Insert
            $stmt = $this->db->prepare(
                "INSERT INTO interview_schedule 
                    (application_id, student_id, company_id, internship_id, interview_type, interview_date, interview_time, meeting_link, location, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            return $stmt->execute([
                $applicationId,
                $studentId,
                $companyId,
                $internshipId,
                $type,
                $date,
                $time,
                $meetingLink,
                $location
            ]);
        }
    }

    public function getRescheduleRequest($interviewId)
    {
        $stmt = $this->db->prepare(
            "SELECT id, reason_type, reason_text, medical_proof, requested_at
             FROM reschedule_requests
             WHERE interview_id = ?
             ORDER BY requested_at DESC LIMIT 1"
        );
        $stmt->execute([$interviewId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateRescheduleStatus($requestId, $status)
    {
        $stmt = $this->db->prepare(
            "UPDATE reschedule_requests SET status = ? WHERE id = ?"
        );
        return $stmt->execute([$status, $requestId]);
    }
}