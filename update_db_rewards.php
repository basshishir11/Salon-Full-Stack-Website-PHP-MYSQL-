<?php
require_once 'config/database.php';
try {
    $database = new Database();
    $db = $database->getConnection();
    $db->exec("ALTER TABLE rewards ADD COLUMN assigned_service_id INT NULL DEFAULT NULL");
    echo "Database updated: Added assigned_service_id to rewards table.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
