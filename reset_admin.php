<?php
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $email = 'saloon@11gmail.com';
    $password = 'salon@2026';
    $hash = password_hash($password, PASSWORD_BCRYPT);

    // Delete existing admin with this email
    $stmt = $db->prepare("DELETE FROM admins WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    // Insert new admin
    $stmt = $db->prepare("INSERT INTO admins (name, email, password_hash) VALUES ('Admin', :email, :pass)");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':pass', $hash);
    $stmt->execute();

    echo "Admin user reset successfully.\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
    echo "Hash: $hash\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
