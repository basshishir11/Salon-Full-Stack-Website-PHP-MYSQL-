<?php
// includes/auth.php
session_start();
require_once __DIR__ . '/../config/database.php';

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header("Location: ../../pages/admin/login.php");
        exit;
    }
}
?>
