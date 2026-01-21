<?php
// update_db_appointments.php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // 1. Add appointment_date and appointment_time to tokens table
    $db->exec("ALTER TABLE tokens 
               ADD COLUMN appointment_date DATE DEFAULT NULL,
               ADD COLUMN appointment_time TIME DEFAULT NULL,
               ADD INDEX idx_appointment (appointment_date, appointment_time)");
               
    echo "SUCCESS: Database columns and indexes for appointments added successfully.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
