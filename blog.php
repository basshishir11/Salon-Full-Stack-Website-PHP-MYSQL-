<?php
// pages/blog.php
require_once '../includes/header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch all published posts
$stmt = $db->query("SELECT * FROM blog_posts WHERE status = 'published' ORDER BY published_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    :root {
        --blog-indigo: #6366f1;
        --blog-slate: #1e293b;
        --blog-bg: #f8fafc;
    }

    /* Override main container for full-width header feel */
    .container {
        max-width: 100% !important;
        padding: 0 !important;
    }

    .blog-hero {
        background: linear-gradient(135deg, #1e293b 0%, #312e81 100%);
        padding: 80px 20px;
        text-align: center;
        color: white;
        margin-bottom: 60px;
    }

    .blog-hero h1 {
        font-size: clamp(2.5rem, 6vw, 4rem);
        font-weight: 900;
        margin-bottom: 20px;
        letter-spacing: -1px;
    }

    .blog-hero p {
        font-size: 1.2rem;
        opacity: 0.8;
        max-width: 600px;
        margin: 0 auto;
    }

    .blog-section {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px 80px;
    }

    .blog-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 30px;
    }

    .blog-card {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        height: 100%;
        text-decoration: none;
        color: inherit;
    }

    .blog-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
        border-color: var(--blog-indigo);
    }

    .card-thumb-wrapper {
        position: relative;
        height: 240px;
        overflow: hidden;
    }

    .card-thumb {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .blog-card:hover .card-thumb {
        transform: scale(1.05);
    }

    .card-badge {
        position: absolute;
        top: 20px;
        left: 20px;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(4px);
        padding: 6px 14px;
        border-radius: 99px;
        font-size: 0.75rem;
        font-weight: 800;
        color: var(--blog-slate);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .card-body {
        padding: 30px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .card-date {
        font-size: 0.85rem;
        color: var(--blog-indigo);
        font-weight: 700;
        margin-bottom: 12px;
        display: block;
    }

    .card-title {
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--blog-slate);
        line-height: 1.3;
        margin-bottom: 15px;
    }

    .card-excerpt {
        color: #64748b;
        font-size: 1rem;
        line-height: 1.6;
        margin-bottom: 25px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .card-footer {
        margin-top: auto;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        color: var(--blog-indigo);
        font-size: 0.9rem;
    }

    /* Mobile handling */
    @media (max-width: 768px) {
        .blog-grid { grid-template-columns: 1fr; }
        .blog-hero { padding: 60px 20px; }
    }
</style>

<div class="blog-hero">
    <h1>Our Journal</h1>
    <p>Discover beauty tips, latest trends, and inside news from Sharma Salon & Spa.</p>
</div>

<div class="blog-section">
    <?php if (empty($posts)): ?>
        <div style="text-align: center; padding: 100px 20px;">
            <div style="width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="fas fa-feather-alt" style="font-size: 2rem; color: #cbd5e1;"></i>
            </div>
            <h2 style="color: var(--blog-slate); font-weight: 800;">No articles yet.</h2>
            <p style="color: #64748b;">We are currently working on some exciting content for you. Stay tuned!</p>
            <a href="../index.php" class="btn btn-primary" style="margin-top: 20px; width: auto; display: inline-flex;">Back to Home</a>
        </div>
    <?php else: ?>
        <div class="blog-grid">
            <?php foreach ($posts as $post): ?>
                <a href="blog_post.php?slug=<?php echo $post['slug']; ?>" class="blog-card">
                    <div class="card-thumb-wrapper">
                        <img src="../<?php echo !empty($post['cover_image']) ? $post['cover_image'] : 'assets/img/blog-placeholder.jpg'; ?>" class="card-thumb" alt="Post">
                        <span class="card-badge">Article</span>
                    </div>
                    <div class="card-body">
                        <span class="card-date"><?php echo strtoupper(date('M j, Y', strtotime($post['published_at']))); ?></span>
                        <h2 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h2>
                        <div class="card-excerpt">
                            <?php echo strip_tags($post['content']); ?>
                        </div>
                        <div class="card-footer">
                            CONTINUE READING <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>