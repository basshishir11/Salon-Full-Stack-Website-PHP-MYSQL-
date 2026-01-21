<?php
// ajax/toggle_service.php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$service_id = $_POST['service_id'] ?? '';

if (empty($service_id)) {
    echo json_encode(['success' => false, 'message' => 'Service ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Toggle is_active
    $query = "UPDATE services SET is_active = NOT is_active WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $service_id);
    $stmt->execute();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
