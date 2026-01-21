<?php
// migrate_v3_claims.php
require_once 'config/database.php';

echo "<h1>Database Migration - v3 (Claims)</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "<p>--- Creating PROMOTION_CLAIMS table ---</p>";
    $sql = "CREATE TABLE IF NOT EXISTS promotion_claims (
        id INT AUTO_INCREMENT PRIMARY KEY,
        promotion_id INT NOT NULL,
        customer_id INT NOT NULL,
        claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
        UNIQUE KEY unique_claim (promotion_id, customer_id)
    )";
    $db->exec($sql);
    echo "<p style='color:green;'>Success: promotion_claims table created.</p>";

    echo "<h3>Migration Complete!</h3>";
    echo "<a href='index.php'>Go to Homepage</a>";

} catch (Exception $e) {
    echo "<p style='color:red;'>MIGRATION FAILED: " . $e->getMessage() . "</p>";
}
?>
