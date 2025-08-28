<?php
require_once(__DIR__ . '/../admin/models/admin.php');
$admin = new Admin();

function checkMaintenance($role = null) {
    global $admin;
    if ($admin->isMaintenanceMode() && $role !== 'admin') {
        echo json_encode(["success" => false, "message" => "Maintenance mode active. Only admins can log in or register."]);
        exit;
    }
}

function checkRegistration($role) {
    global $admin;
    if ($role === 'student' && !$admin->isUserRegistrationEnabled()) {
        echo json_encode(["success" => false, "message" => "Student registration is currently disabled."]);
        exit;
    }
    if ($role === 'company' && !$admin->isCompanyRegistrationEnabled()) {
        echo json_encode(["success" => false, "message" => "Company registration is currently disabled."]);
        exit;
    }
}