<?php
echo "--- Starting Diagnosis ---\n";

// 1. Try to connect to MySQL Server only
try {
    $link = new PDO("mysql:host=localhost", "root", "");
    echo "[PASS] Connected to MySQL Server.\n";
    
    // 2. Check if database exists
    $stmt = $link->query("SHOW DATABASES LIKE 'sharma_salon'");
    $db_exists = $stmt->fetch();
    
    if ($db_exists) {
        echo "[PASS] Database 'sharma_salon' exists.\n";
        
        // 3. Connect to specific DB
        $link = new PDO("mysql:host=localhost;dbname=sharma_salon", "root", "");
        echo "[PASS] Connected to 'sharma_salon'.\n";
        
        // 4. Check admins table
        $stmt = $link->query("SHOW TABLES LIKE 'admins'");
        if ($stmt->fetch()) {
            echo "[PASS] Table 'admins' exists.\n";
            
            $stmt = $link->query("SELECT count(*) FROM admins");
            $count = $stmt->fetchColumn();
            echo "[INFO] Admin count: $count\n";
        } else {
            echo "[FAIL] Table 'admins' does NOT exist.\n";
        }
        
    } else {
        echo "[FAIL] Database 'sharma_salon' does NOT exist.\n";
        echo "Attempting to create it...\n";
        $link->exec("CREATE DATABASE sharma_salon");
        echo "Database created. Please run the SQL import.\n";
    }
    
} catch (PDOException $e) {
    echo "[FAIL] Connection Failed: " . $e->getMessage() . "\n";
}
echo "--- End Diagnosis ---\n";
?>
