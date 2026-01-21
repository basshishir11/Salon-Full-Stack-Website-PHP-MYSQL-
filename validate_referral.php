<?php
// ajax/validate_referral.php
require_once __DIR__ . '/../includes/csrf.php';
startBookingSession();

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$code = $_GET['code'] ?? '';
$phone = $_GET['phone'] ?? '';

if (empty($code)) {
    echo json_encode(['valid' => false, 'message' => 'Code required']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, name, phone FROM customers WHERE referral_code = :code LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':code', $code);
$stmt->execute();

$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if ($customer) {
    // Check if the code belongs to the person trying to use it
    if (!empty($phone) && $customer['phone'] === $phone) {
        echo json_encode(['valid' => false, 'message' => 'You cannot use your own referral code']);
        exit;
    }
    echo json_encode(['valid' => true, 'referrer_id' => $customer['id']]);
} else {
    echo json_encode(['valid' => false, 'message' => 'Invalid referral code']);
}
?>
