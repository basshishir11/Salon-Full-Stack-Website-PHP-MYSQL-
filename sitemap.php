<?php
// sitemap.php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get the base URL (you might want to configure this in settings)
// For now, we'll try to detect it or use a default
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/salon/";

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

// Home Page
add_url($base_url . "index.php", "1.0", "daily");

// Core Pages
$pages = ['services.php', 'blog.php', 'contact.php', 'rewards_info.php'];
foreach ($pages as $p) {
    add_url($base_url . "pages/" . $p, "0.8", "weekly");
}

// Services
$services = $db->query("SELECT id FROM services WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
// If services have individual pages, add them here. Currently they are in a list on services.php

// Blog Posts
$posts = $db->query("SELECT slug, created_at FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($posts as $post) {
    add_url($base_url . "pages/blog_post.php?slug=" . $post['slug'], "0.6", "monthly", $post['created_at']);
}

echo '</urlset>';

function add_url($loc, $priority = "0.5", $changefreq = "weekly", $lastmod = null) {
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . htmlspecialchars($loc) . '</loc>' . PHP_EOL;
    if ($lastmod) {
        $date = date('Y-m-d', strtotime($lastmod));
        echo '    <lastmod>' . $date . '</lastmod>' . PHP_EOL;
    } else {
        echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
    }
    echo '    <changefreq>' . $changefreq . '</changefreq>' . PHP_EOL;
    echo '    <priority>' . $priority . '</priority>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}
?>
