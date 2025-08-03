<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');  // error log file in same folder
error_reporting(E_ALL);

session_start();
header("Content-Type: application/json");

require_once __DIR__ . '../../../config/Database.php';
require_once __DIR__ . '../../../config/cors.php';

// Allow only DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

// Read and decode JSON input
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Debug log input and session user_id
file_put_contents(__DIR__ . '/error.log', "Input: " . print_r($data, true) . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/error.log', "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n", FILE_APPEND);

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing internship ID"]);
    exit;
}

try {
    $db = (new Database())->getConnection();

    $query = "DELETE FROM Internship 
              WHERE Internship_Id = :id 
              AND Company_Id = (SELECT Com_Id FROM Company WHERE User_Id = :user_id)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Internship deleted successfully."]);
    } else {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Internship not found or unauthorized"]);
    }
} catch (PDOException $e) {
    // Log full error message to error.log
    file_put_contents(__DIR__ . '/error.log', "PDOException: " . $e->getMessage() . "\n", FILE_APPEND);

    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
}
exit;
