<?php
// simplify_categories.php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // 1. Ensure Men, Women, Unisex exist
    $standard_cats = ['Men', 'Women', 'Unisex'];
    $cat_ids = [];

    foreach ($standard_cats as $name) {
        $stmt = $db->prepare("SELECT id FROM categories WHERE name = :name");
        $stmt->execute(['name' => $name]);
        $row = $stmt->fetch();
        
        if ($row) {
            $cat_ids[$name] = $row['id'];
        } else {
            $stmt = $db->prepare("INSERT INTO categories (name) VALUES (:name)");
            $stmt->execute(['name' => $name]);
            $cat_ids[$name] = $db->lastInsertId();
        }
    }

    // 2. Update existing services to use these category IDs based on gender_type
    // This assumes gender_type is already set correctly (Men, Women, Unisex)
    $stmt = $db->prepare("UPDATE services SET category_id = :cat_id WHERE gender_type = :gender");
    
    foreach ($cat_ids as $gender => $id) {
        $stmt->execute(['cat_id' => $id, 'gender' => $gender]);
    }

    // 3. Delete other categories
    $placeholders = implode(',', array_fill(0, count($cat_ids), '?'));
    $stmt = $db->prepare("DELETE FROM categories WHERE id NOT IN ($placeholders)");
    $stmt->execute(array_values($cat_ids));

    $db->commit();
    echo "SUCCESS: Categories simplified and services migrated.";
} catch (Exception $e) {
    $db->rollBack();
    echo "ERROR: " . $e->getMessage();
}
