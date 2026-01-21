<?php
require_once 'config/database.php';
try {
    $db = (new Database())->getConnection();
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "--- EXISTING TABLES ---\n";
    print_r($tables);
    
    if (in_array('promotions', $tables)) {
        echo "\n--- PROMOTIONS STRUCTURE ---\n";
        $res = $db->query("DESCRIBE promotions");
        print_r($res->fetchAll());
    } else {
        echo "\nERROR: promotions table NOT FOUND.\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
