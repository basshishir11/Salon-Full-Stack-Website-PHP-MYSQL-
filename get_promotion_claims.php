<?php
// ajax/get_promotion_claims.php
// ajax/get_promotion_claims.php
require_once __DIR__ . '/../includes/auth.php';
// Manual check to prevent HTML redirect breaking JSON
if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login as admin']);
    exit;
}
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$promo_id = (int)($_GET['promo_id'] ?? 0);

if (!$promo_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid promotion ID']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT c.name, c.phone, pc.claimed_at 
              FROM promotion_claims pc 
              JOIN customers c ON pc.customer_id = c.id 
              WHERE pc.promotion_id = ? 
              ORDER BY pc.claimed_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$promo_id]);
    $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'claims' => $claims]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
