<?php
// ajax/assign_reward_service.php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$reward_id = $_POST['reward_id'] ?? '';
$service_id = $_POST['service_id'] ?? '';

if (empty($reward_id) || empty($service_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get service name for description update
    $stmt = $db->prepare("SELECT name FROM services WHERE id = ?");
    $stmt->execute([$service_id]);
    $service_name = $stmt->fetchColumn();

    if (!$service_name) {
        throw new Exception("Service not found");
    }

    $description = "FREE " . $service_name;

    // Update reward
    $update = $db->prepare("UPDATE rewards SET assigned_service_id = ?, description = ? WHERE id = ?");
    $res = $update->execute([$service_id, $description, $reward_id]);

    echo json_encode(['success' => true, 'message' => 'Reward updated successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
