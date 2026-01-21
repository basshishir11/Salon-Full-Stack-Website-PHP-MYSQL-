<?php
// fix_promotions_table.php
// Run this once: https://lifestylesalon.gt.tc/fix_promotions_table.php

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Fixing Promotions Table</h2>";

try {
    // Check if service_id column exists
    $check = $db->query("SHOW COLUMNS FROM promotions LIKE 'service_id'");

    if ($check->rowCount() == 0) {
        // Add service_id column
        $db->exec("ALTER TABLE promotions ADD COLUMN service_id INT NULL AFTER id");
        echo "<p style='color: green;'>✅ Added service_id column</p>";
    } else {
        echo "<p>✅ service_id column already exists</p>";
    }

    // Check if applicable_services column exists (some versions use this instead)
    $check2 = $db->query("SHOW COLUMNS FROM promotions LIKE 'applicable_services'");

    if ($check2->rowCount() == 0) {
        $db->exec("ALTER TABLE promotions ADD COLUMN applicable_services TEXT NULL");
        echo "<p style='color: green;'>✅ Added applicable_services column</p>";
    } else {
        echo "<p>✅ applicable_services column already exists</p>";
    }

    echo "<hr>";
    echo "<p><strong>All fixes applied!</strong></p>";
    echo "<p><a href='pages/admin/manage_promotions.php'>Go to Manage Promotions →</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>