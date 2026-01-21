<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->query("SELECT id, title, cover_image, featured_image FROM blog_posts LIMIT 5");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Blog Posts in Database:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Title</th><th>cover_image</th><th>featured_image</th></tr>";

foreach ($posts as $post) {
    echo "<tr>";
    echo "<td>" . $post['id'] . "</td>";
    echo "<td>" . htmlspecialchars($post['title']) . "</td>";
    echo "<td>" . ($post['cover_image'] ?? 'NULL') . "</td>";
    echo "<td>" . ($post['featured_image'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check if file exists
if (!empty($posts[0]['cover_image'])) {
    $path = $posts[0]['cover_image'];
    echo "<h3>Checking file: " . $path . "</h3>";
    echo "File exists: " . (file_exists($path) ? "YES ✅" : "NO ❌") . "<br>";
    echo "<h3>Try viewing image:</h3>";
    echo "<img src='" . $path . "' style='max-width: 300px; border: 2px solid green;'>";
}
?>
