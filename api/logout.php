
<?php
require_once "../api/sessions.php";
require_once '../config/cors.php'; // must include before any output
require_once '../config/Database.php';
require_once '../models/User.php';

session_start();
session_unset();
session_destroy();
echo json_encode(["success" => true, "message" => "Session closed"]);