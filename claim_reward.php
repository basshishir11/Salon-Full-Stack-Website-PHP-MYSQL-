<?php
// ajax/claim_reward.php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$reward_id = $_POST['reward_id'] ?? '';

if (empty($reward_id)) {
    echo json_encode(['success' => false, 'message' => 'Reward ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("UPDATE rewards SET status = 'Claimed', claimed_at = NOW() WHERE id = :id");
    $stmt->execute([':id' => $reward_id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
