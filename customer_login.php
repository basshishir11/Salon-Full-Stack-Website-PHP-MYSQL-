<?php
// ajax/customer_login.php
require_once __DIR__ . '/../includes/csrf.php';
startBookingSession();

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$phone = trim($_POST['phone'] ?? '');

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone number is required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Find customer by phone (get latest if duplicates exist)
    $query = "SELECT id, name FROM customers WHERE phone = :phone ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([':phone' => $phone]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        // Auto-register new customer
        $name = 'New Customer'; // Default name for quick grab
        $insert = "INSERT INTO customers (name, phone) VALUES (:name, :phone)";
        $stmt = $db->prepare($insert);
        $stmt->execute([':name' => $name, ':phone' => $phone]);
        
        $customer_id = $db->lastInsertId();
        $customer = ['id' => $customer_id, 'name' => $name];
    }

    // Set customer session
    $_SESSION['customer_id'] = $customer['id'];
    $_SESSION['customer_name'] = $customer['name'];
    $_SESSION['customer_phone'] = $phone;

    echo json_encode([
        'success' => true, 
        'message' => 'Login successful',
        'name' => $customer['name']
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
