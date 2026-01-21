<?php
// ajax/update_service.php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$service_id = $_POST['service_id'] ?? '';
$name = trim($_POST['name'] ?? '');
$category_id = $_POST['category_id'] ?? '';
$gender_type = $_POST['gender_type'] ?? '';
$price = $_POST['price'] ?? '';
$duration_mins = $_POST['duration_mins'] ?? '';

// Validate
if (empty($service_id) || empty($name) || empty($category_id) || empty($gender_type) || empty($price) || empty($duration_mins)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get current service
    $query = "SELECT image_path FROM services WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $service_id);
    $stmt->execute();
    $current_service = $stmt->fetch(PDO::FETCH_ASSOC);

    $image_path = $current_service['image_path'];

    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../assets/images/services/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($file_extension, $allowed_extensions)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image format']);
            exit;
        }

        // Delete old image if exists
        if (!empty($image_path)) {
            $old_image = __DIR__ . '/../' . $image_path;
            if (file_exists($old_image)) {
                unlink($old_image);
            }
        }

        // Upload new image
        $filename = 'service_' . time() . '_' . uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_path = 'assets/images/services/' . $filename;
        }
    }

    // Update service
    $query = "UPDATE services 
              SET category_id = :category_id, 
                  name = :name, 
                  price = :price, 
                  duration_mins = :duration, 
                  gender_type = :gender, 
                  image_path = :image 
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':duration', $duration_mins);
    $stmt->bindParam(':gender', $gender_type);
    $stmt->bindParam(':image', $image_path);
    $stmt->bindParam(':id', $service_id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Service updated successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
