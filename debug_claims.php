<?php
// debug_claims.php
require_once 'config/database.php';

echo "<h1>Claims Debugger</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if table exists
    $check = $db->query("SHOW TABLES LIKE 'promotion_claims'");
    if ($check->rowCount() == 0) {
        die("<h2 style='color:red;'>TABLE 'promotion_claims' DOES NOT EXIST!</h2>");
    } else {
        echo "<h2 style='color:green;'>Table 'promotion_claims' exists.</h2>";
    }

    // Show all claims
    $claims = $db->query("SELECT * FROM promotion_claims")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Total Claims: " . count($claims) . "</h3>";
    echo "<pre>";
    print_r($claims);
    echo "</pre>";
    
    // Show customers to verify
    echo "<h3>Recent Customers</h3>";
    $customers = $db->query("SELECT * FROM customers ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($customers);
    echo "</pre>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
