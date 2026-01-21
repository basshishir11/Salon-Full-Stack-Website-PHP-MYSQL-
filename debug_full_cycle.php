<?php
// debug_full_cycle.php
require_once 'config/database.php';

header('Content-Type: text/plain');

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "--- STARTING DIAGNOSTIC ---\n";

    // 1. Get or Create a Promo
    $stmt = $db->query("SELECT id FROM promotions LIMIT 1");
    $promo_id = $stmt->fetchColumn();
    if (!$promo_id) {
        $db->exec("INSERT INTO promotions (title, start_date, end_date) VALUES ('Test Promo', NOW(), NOW())");
        $promo_id = $db->lastInsertId();
        echo "[OK] Created Test Promo ID: $promo_id\n";
    } else {
        echo "[OK] Found Promo ID: $promo_id\n";
    }

    // 2. Get or Create a Customer
    $stmt = $db->query("SELECT id FROM customers LIMIT 1");
    $cust_id = $stmt->fetchColumn();
    if (!$cust_id) {
        $db->exec("INSERT INTO customers (name, phone) VALUES ('Debug User', '9999999999')");
        $cust_id = $db->lastInsertId();
        echo "[OK] Created Test Customer ID: $cust_id\n";
    } else {
        echo "[OK] Found Customer ID: $cust_id\n";
    }

    // 3. Simulate Claim
    echo "Attempting to insert claim for Promo $promo_id, Customer $cust_id...\n";
    $stmt = $db->prepare("INSERT IGNORE INTO promotion_claims (promotion_id, customer_id) VALUES (?, ?)");
    $stmt->execute([$promo_id, $cust_id]);
    echo "[OK] Claim Inserted/Ignored (Rows: " . $stmt->rowCount() . ")\n";

    // 4. Verify Raw Table
    $check = $db->query("SELECT COUNT(*) FROM promotion_claims WHERE promotion_id = $promo_id AND customer_id = $cust_id")->fetchColumn();
    echo "[CHECK] Raw Table Count: $check\n";

    // 5. Test Admin Query
    echo "Testing Admin Query logic...\n";
    $query = "SELECT c.name, c.phone, pc.claimed_at 
              FROM promotion_claims pc 
              JOIN customers c ON pc.customer_id = c.id 
              WHERE pc.promotion_id = ? 
              ORDER BY pc.claimed_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$promo_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "[RESULT] Admin Query found " . count($results) . " claims.\n";
    foreach ($results as $row) {
        echo " - Claimed by: " . $row['name'] . " (" . $row['phone'] . ")\n";
    }

    echo "--- DIAGNOSTIC COMPLETE ---\n";

} catch (Exception $e) {
    echo "\n[CRITICAL ERROR] " . $e->getMessage() . "\n";
}
?>
