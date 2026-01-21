<?php
// ajax/save_blog_post.php
require_once '../includes/auth.php';
requireAdmin();
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $title = $_POST['title'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $content = $_POST['content'] ?? '';
        $status = $_POST['status'] ?? 'draft';
        $meta_title = $_POST['meta_title'] ?? '';
        $meta_description = $_POST['meta_description'] ?? '';
        $published_at = ($status === 'published') ? date('Y-m-d H:i:s') : null;

        $image_path = null;
        
        // Handle Image Upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/blog/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = time() . '_' . uniqid() . '.' . $file_ext;
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = 'assets/uploads/blog/' . $file_name;
            }
        }

        if ($id) {
            // Update
            $sql = "UPDATE blog_posts SET title = ?, slug = ?, content = ?, status = ?, meta_title = ?, meta_description = ?";
            $params = [$title, $slug, $content, $status, $meta_title, $meta_description];
            
            if ($image_path) {
                $sql .= ", cover_image = ?";
                $params[] = $image_path;
            }
            
            if ($status === 'published') {
                $sql .= ", published_at = IFNULL(published_at, NOW())";
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $db->prepare($sql);
            $success = $stmt->execute($params);
        } else {
            // Create
            $stmt = $db->prepare("INSERT INTO blog_posts (title, slug, content, status, meta_title, meta_description, cover_image, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $success = $stmt->execute([$title, $slug, $content, $status, $meta_title, $meta_description, $image_path, $published_at]);
        }

        echo json_encode(['success' => $success, 'message' => $success ? 'Saved successfully' : 'Database error (perhaps slug already exists)']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid request method']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
