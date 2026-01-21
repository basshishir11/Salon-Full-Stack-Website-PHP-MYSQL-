<?php
// ajax/delete_service.php
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

    // Get service to delete image
    $query = "SELECT image_path FROM services WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $service_id);
    $stmt->execute();
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    // Delete image file if exists
    if ($service && !empty($service['image_path'])) {
        $image_file = __DIR__ . '/../' . $service['image_path'];
        if (file_exists($image_file)) {
            unlink($image_file);
        }
    }

    // Delete service
    $delete_query = "DELETE FROM services WHERE id = :id";
    $stmt = $db->prepare($delete_query);
    $stmt->bindParam(':id', $service_id);
    $stmt->execute();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
