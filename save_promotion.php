<?php
// ajax/save_promotion.php
require_once '../includes/auth.php';
requireAdmin();
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $title = $_POST['title'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $service_id = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;
    $cta_link = $_POST['cta_link'] ?: 'pages/book_step1.php';
    $locations = isset($_POST['location']) ? implode(',', $_POST['location']) : 'homepage';

    $banner_path = null;
    
    // Handle Media Upload (Image or Video)
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/uploads/promotions/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm', 'ogg', 'mov'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $file_name = time() . '_' . uniqid() . '.' . $file_ext;
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['banner']['tmp_name'], $target_file)) {
                $banner_path = 'assets/uploads/promotions/' . $file_name;
            }
        }
    }

    if ($id) {
        // Update
        $sql = "UPDATE promotions SET title = ?, description = ?, start_date = ?, end_date = ?, service_id = ?, cta_link = ?, display_location = ?";
        $params = [$title, $description, $start_date, $end_date, $service_id, $cta_link, $locations];
        
        if ($banner_path) {
            $sql .= ", banner_image = ?";
            $params[] = $banner_path;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $db->prepare($sql);
        $success = $stmt->execute($params);
    } else {
        // Create
        $stmt = $db->prepare("INSERT INTO promotions (title, description, start_date, end_date, service_id, banner_image, cta_link, display_location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $success = $stmt->execute([$title, $description, $start_date, $end_date, $service_id, $banner_path, $cta_link, $locations]);
    }

    echo json_encode(['success' => $success, 'message' => $success ? 'Saved successfully' : 'Database error']);
    exit;
}
?>
