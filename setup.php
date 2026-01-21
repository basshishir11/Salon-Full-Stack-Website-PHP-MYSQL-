<?php
// setup.php
// Run this file in browser to setup database

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>System Setup</title><style>body{font-family:sans-serif;max-width:800px;margin:2rem auto;padding:1rem;line-height:1.6} .success{color:green} .error{color:red} .step{margin-bottom:1rem;padding:1rem;background:#f9f9f9;border-radius:4px}</style></head><body>";
echo "<h1>System Setup & Repair</h1>";

try {
    // 1. Connect without DB name
    echo "<div class='step'><strong>Step 1: Connecting to MySQL...</strong><br>";
    try {
        $pdo = new PDO("mysql:host=localhost", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<span class='success'>Connected to MySQL server successfully.</span></div>";
    } catch (PDOException $e) {
        throw new Exception("Could not connect to MySQL server. Is XAMPP running? Error: " . $e->getMessage());
    }

    // 2. Create DB
    echo "<div class='step'><strong>Step 2: Database Creation...</strong><br>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS sharma_salon");
    echo "<span class='success'>Database 'sharma_salon' ensured.</span></div>";

    // 3. Select DB
    $pdo->exec("USE sharma_salon");

    // 4. Create Tables
    echo "<div class='step'><strong>Step 3: Creating Tables...</strong><br>";
    $sql_file = __DIR__ . '/sql/database.sql';
    if (file_exists($sql_file)) {
        $sql = file_get_contents($sql_file);
        // Remove comments to check if empty, but keep for execution
        // We need to split statements.
        $statements = explode(';', $sql);
        $count = 0;
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    $count++;
                } catch (PDOException $e) {
                    // Ignore "Table exists" errors
                    if (strpos($e->getMessage(), "already exists") === false) {
                        echo "<span class='error'>Error running SQL: " . htmlspecialchars(substr($statement, 0, 50)) . "... (" . $e->getMessage() . ")</span><br>";
                    }
                }
            }
        }
        echo "<span class='success'>Processed $count SQL statements from database.sql</span></div>";
    } else {
        echo "<span class='error'>sql/database.sql file not found!</span></div>";
    }

    // 5. Seed Services
    echo "<div class='step'><strong>Step 4: Seeding Services...</strong><br>";
    $seed_file = __DIR__ . '/sql/seed_services.sql';
    if (file_exists($seed_file)) {
        $sql = file_get_contents($seed_file);
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                } catch (Exception $e) {
                   // Often duplicate entry if ran twice, ignore
                }
            }
        }
        echo "<span class='success'>Services seeded.</span></div>";
    }

    // 5b. Seed Milestones
    echo "<div class='step'><strong>Step 4b: Seeding Loyalty Milestones...</strong><br>";
    $milestone_file = __DIR__ . '/sql/seed_milestones.sql';
    if (file_exists($milestone_file)) {
        $sql = file_get_contents($milestone_file);
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                } catch (Exception $e) {
                   // Ignore duplicates
                }
            }
        }
        echo "<span class='success'>Milestones seeded.</span></div>";
    } else {
        echo "<span class='success'>No milestone file found (optional).</span></div>";
    }

    // 6. Admin User
    echo "<div class='step'><strong>Step 5: Admin User...</strong><br>";
    $email = 'saloon@11gmail.com';
    $password = 'salon@2026';
    
    // Check if table exists (it should now)
    try {
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            // Update
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE email = ?");
            $stmt->execute([$hash, $email]);
            echo "<span class='success'>Admin password reset to default.</span>";
        } else {
            // Create
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO admins (name, email, password_hash) VALUES ('Admin', ?, ?)");
            $stmt->execute([$email, $hash]);
            echo "<span class='success'>Admin user created.</span>";
        }
    } catch (Exception $e) {
        echo "<span class='error'>Error creating admin: " . $e->getMessage() . "</span>";
    }
    echo "</div>";

    echo "<div class='step' style='background:#e8f5e9; border:1px solid green'>
            <h2 style='margin-top:0'>Setup Complete!</h2>
            <p>The system is ready.</p>
            <a href='pages/admin/login.php' style='display:inline-block; padding:10px 20px; background:blue; color:white; text-decoration:none; border-radius:5px'>Go to Admin Login</a>
          </div>";

} catch (Exception $e) {
    echo "<div class='step' style='background:#ffebee; border:1px solid red'>
            <h2 style='margin-top:0'>Setup Failed</h2>
            <p style='color:red'>" . $e->getMessage() . "</p>
          </div>";
}
echo "</body></html>";
?>
