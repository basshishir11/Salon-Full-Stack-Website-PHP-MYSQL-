<?php
// pages/blog_post.php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$slug = $_GET['slug'] ?? '';
$stmt = $db->prepare("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header("Location: blog.php");
    exit;
}

// SEO Config
$page_title = $post['meta_title'] ?: $post['title'];
$page_description = $post['meta_description'] ?: substr(strip_tags($post['content']), 0, 160);

require_once '../includes/header.php';
?>

<style>
    :root {
        --post-bg: #ffffff;
        --post-text: #334155;
        --post-indigo: #6366f1;
        --post-slate: #1e293b;
    }

    /* Override main container for full-width header feel */
    .container {
        max-width: 100% !important;
        padding: 0 !important;
    }

    .article-header {
        background: linear-gradient(to bottom, #f8fafc 0%, #ffffff 100%);
        padding: 60px 20px;
        text-align: center;
        border-bottom: 1px solid #f1f5f9;
        margin-bottom: 0;
    }

    .post-meta-top {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        background: #eff6ff;
        color: #2563eb;
        padding: 6px 16px;
        border-radius: 99px;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 25px;
    }

    .post-main-title {
        font-size: clamp(2rem, 5vw, 3.5rem);
        font-weight: 900;
        color: var(--post-slate);
        line-height: 1.1;
        margin-bottom: 30px;
        max-width: 900px;
        margin-left: auto;
        margin-right: auto;
    }

    .custom-breadcrumb {
        display: flex;
        justify-content: center;
        gap: 15px;
        list-style: none;
        padding: 0;
        margin: 0;
        font-size: 0.9rem;
        font-weight: 600;
        color: #94a3b8;
    }

    .custom-breadcrumb a {
        color: var(--post-indigo);
        text-decoration: none;
    }

    .custom-breadcrumb li::after {
        content: '/';
        margin-left: 15px;
    }

    .custom-breadcrumb li:last-child::after {
        content: none;
    }

    .article-body {
        max-width: 800px;
        margin: 0 auto;
        padding: 40px 20px;
        background: white;
    }

    .featured-img-container {
        max-width: 1000px;
        margin: -40px auto 40px;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    }

    .featured-image {
        width: 100%;
        height: auto;
        max-height: 550px;
        object-fit: cover;
    }

    .blog-content {
        font-size: 1.25rem;
        line-height: 1.8;
        color: var(--post-text);
        font-family: 'Poppins', sans-serif;
    }

    .blog-content h2, .blog-content h3 {
        color: var(--post-slate);
        font-weight: 800;
        margin-top: 50px;
        margin-bottom: 20px;
    }

    .blog-content p {
        margin-bottom: 25px;
    }

    .blog-content blockquote {
        background: #f8fafc;
        border-left: 5px solid var(--post-indigo);
        padding: 30px;
        margin: 40px 0;
        border-radius: 0 16px 16px 0;
        font-style: italic;
        color: #475569;
    }

    .post-footer {
        max-width: 800px;
        margin: 60px auto;
        padding: 40px 20px;
        border-top: 2px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 24px;
        background: #f1f5f9;
        color: var(--post-slate);
        border-radius: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s;
        min-height: auto;
    }

    .btn-back:hover {
        background: #e2e8f0;
    }

    .share-pill {
        display: flex;
        gap: 15px;
        align-items: center;
        background: #f8fafc;
        padding: 8px 20px;
        border-radius: 99px;
    }

    .share-pill a {
        color: #64748b;
        font-size: 1.1rem;
        transition: color 0.2s;
    }

    .share-pill a:hover {
        color: var(--post-indigo);
    }

    /* Mobile handling */
    @media (max-width: 768px) {
        .post-main-title { font-size: 2rem; }
        .article-header { padding: 40px 20px; }
    }
</style>

<article>
    <header class="article-header">
        <div class="container-inner">
            <div class="post-meta-top">
                <i class="fas fa-calendar-day"></i> <?php echo date('M j, Y', strtotime($post['published_at'])); ?>
                <span style="opacity: 0.3;">|</span>
                <i class="fas fa-clock"></i> 5 MIN READ
            </div>
            
            <h1 class="post-main-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            
            <ul class="custom-breadcrumb">
                <li><a href="../index.php">Home</a></li>
                <li><a href="blog.php">Blog</a></li>
                <li>Article Detail</li>
            </ul>
        </div>
    </header>

    <?php if ($post['featured_image']): ?>
        <div class="featured-img-container">
            <img src="../<?php echo $post['featured_image']; ?>" class="featured-image" alt="Featured">
        </div>
    <?php endif; ?>

    <div class="article-body">
        <div class="blog-content">
            <?php echo $post['content']; ?>
        </div>
    </div>

    <footer class="post-footer">
        <a href="blog.php" class="btn-back">
            <i class="fas fa-long-arrow-alt-left"></i> Back to Listing
        </a>
        
        <div class="share-pill">
            <span style="font-size: 0.8rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Share</span>
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-whatsapp"></i></a>
        </div>
    </footer>
</article>

<?php require_once '../includes/footer.php'; ?>
