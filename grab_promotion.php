// ajax/grab_promotion.php
// Turn off error display to prevent malformed JSON
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    ob_start(); // Buffer output to catch any warnings
    
    require_once __DIR__ . '/../includes/csrf.php';
    startBookingSession();

    require_once __DIR__ . '/../config/database.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $promo_id = (int)($_POST['promo_id'] ?? 0);
    $customer_id = $_SESSION['customer_id'] ?? null;

    if (!$customer_id) {
        // Clear buffer before JSON
        ob_clean();
        echo json_encode(['success' => false, 'require_login' => true, 'message' => 'Please login to grab this offer']);
        exit;
    }

    if (!$promo_id) {
        throw new Exception('Invalid promotion ID');
    }

    $database = new Database();
    $db = $database->getConnection();

    // Check if campaign exists and is active
    $check_stmt = $db->prepare("SELECT id FROM promotions WHERE id = ? AND is_active = 1");
    $check_stmt->execute([$promo_id]);
    if (!$check_stmt->fetch()) {
        throw new Exception('This promotion is no longer available');
    }

    // Record the claim
    $stmt = $db->prepare("INSERT IGNORE INTO promotion_claims (promotion_id, customer_id) VALUES (?, ?)");
    $stmt->execute([$promo_id, $customer_id]);

    $message = ($stmt->rowCount() > 0) ? 'Offer grabbed successfully! Our team will contact you soon.' : 'You have already grabbed this offer!';

    ob_clean(); // Discard any warnings/notices
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
exit;
