<?php
// migrate_v2_web.php
require_once 'config/database.php';

echo "<h1>Database Migration - v2</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "<p>--- Creating PROMOTIONS table ---</p>";
    $sql_promotions = "CREATE TABLE IF NOT EXISTS promotions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        service_id INT DEFAULT NULL,
        start_date DATE,
        end_date DATE,
        banner_image VARCHAR(255),
        cta_text VARCHAR(50) DEFAULT 'Book Now',
        cta_link VARCHAR(255),
        display_location SET('homepage', 'booking') DEFAULT 'homepage',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
    )";
    $db->exec($sql_promotions);
    echo "<p style='color:green;'>Success: Promotions table created.</p>";

    echo "<p>--- Creating BLOG_POSTS table ---</p>";
    $sql_blog = "CREATE TABLE IF NOT EXISTS blog_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        content LONGTEXT,
        featured_image VARCHAR(255),
        meta_title VARCHAR(255),
        meta_description TEXT,
        status ENUM('draft', 'published') DEFAULT 'draft',
        published_at DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql_blog);
    echo "<p style='color:green;'>Success: Blog Posts table created.</p>";

    echo "<h3>Migration Complete!</h3>";
    echo "<a href='index.php'>Go to Homepage</a>";

} catch (Exception $e) {
    echo "<p style='color:red;'>MIGRATION FAILED: " . $e->getMessage() . "</p>";
}
?>
