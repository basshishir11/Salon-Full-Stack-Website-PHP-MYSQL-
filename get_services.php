<?php
// ajax/get_services.php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$gender = $_GET['gender'] ?? '';

if (empty($gender)) {
    echo json_encode(['success' => false, 'message' => 'Gender required']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get services for this gender or Unisex
$query = "SELECT * FROM services WHERE (gender_type = :gender OR gender_type = 'Unisex') AND is_active = 1 ORDER BY name";
$stmt = $db->prepare($query);
$stmt->bindParam(':gender', $gender);
$stmt->execute();

$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'services' => $services]);
?>
