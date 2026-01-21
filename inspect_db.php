<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "--- REWARDS TABLE ---\n";
$stmt = $db->query("DESCRIBE rewards");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- LOYALTY MILESTONES ---\n";
$stmt = $db->query("SELECT * FROM loyalty_milestones");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- SETTINGS ---\n";
$stmt = $db->query("SELECT * FROM settings");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
