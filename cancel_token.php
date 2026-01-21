<?php
// ajax/cancel_token.php
require_once __DIR__ . '/../includes/csrf.php';
startBookingSession();

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$token_id = $_POST['token_id'] ?? '';

if (empty($token_id)) {
    echo json_encode(['success' => false, 'message' => 'Token ID is required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if token exists and is in 'Waiting' status
    $query = "SELECT status FROM tokens WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $token_id]);
    $token = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$token) {
        echo json_encode(['success' => false, 'message' => 'Token not found']);
        exit;
    }

    if ($token['status'] !== 'Waiting') {
        echo json_encode(['success' => false, 'message' => 'This token cannot be cancelled as it is already ' . strtolower($token['status'])]);
        exit;
    }

    // Update status to 'Cancelled'
    $update_query = "UPDATE tokens SET status = 'Cancelled' WHERE id = :id";
    $stmt = $db->prepare($update_query);
    $stmt->execute([':id' => $token_id]);

    echo json_encode(['success' => true, 'message' => 'Token cancelled successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
