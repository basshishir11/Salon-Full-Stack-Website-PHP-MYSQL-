<?php
// pages/admin/manage_blog.php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM blog_posts WHERE id = ?")->execute([$id]);
    header('Location: manage_blog.php?message=Post deleted successfully');
    exit;
}

// Fetch all posts
$posts = $db->query("SELECT * FROM blog_posts ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/header.php';
?>

<style>
    :root {
        --admin-indigo: #6366f1;
        --admin-slate: #1e293b;
        --admin-light: #f8fafc;
        --admin-border: #e2e8f0;
    }

    body {
        background-color: var(--admin-light) !important;
    }

    .blog-container { 
        padding: 40px 20px; 
        max-width: 1100px; 
        margin: 0 auto; 
    }

    .page-header { 
        background: white; 
        padding: 24px 32px; 
        border-radius: 20px; 
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04); 
        margin-bottom: 30px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        border: 1px solid var(--admin-border);
        flex-wrap: wrap;
        gap: 20px;
    }

    .page-header h2 { 
        margin: 0; 
        font-size: 1.6rem; 
        font-weight: 800; 
        color: var(--admin-slate); 
        display: flex; 
        align-items: center; 
        gap: 15px; 
    }

    .post-card { 
        background: white; 
        border-radius: 20px; 
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); 
        overflow: hidden; 
        margin-bottom: 20px; 
        display: flex; 
        border: 1px solid var(--admin-border);
        transition: transform 0.2s;
    }

    .post-card:hover {
        transform: translateX(5px);
    }

    .post-image { 
        width: 200px; 
        height: 140px; 
        object-fit: cover; 
        background: #f1f5f9; 
    }

    .post-content { 
        padding: 24px; 
        flex: 1; 
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .post-title { 
        font-size: 1.25rem; 
        font-weight: 800; 
        color: var(--admin-slate); 
        margin-bottom: 8px; 
    }

    .post-meta { 
        font-size: 0.85rem; 
        color: #64748b; 
        margin-bottom: 15px; 
        font-weight: 500;
    }

    .badge-status {
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 8px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .badge-published { background: #ecfdf5; color: #059669; }
    .badge-draft { background: #f1f5f9; color: #64748b; }

    .btn-action {
        padding: 8px 16px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.8rem;
        transition: all 0.2s;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-edit { background: #eff6ff; color: #1d4ed8; }
    .btn-delete { background: #fff1f2; color: #e11d48; }

    /* Custom Modal Fix */
    .custom-modal {
        display: none;
        align-items: center;
        justify-content: center;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 2000;
        padding: 20px;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(8px);
    }

    .custom-modal.active {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 24px;
        width: 100%;
        max-width: 850px;
        padding: 40px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        max-height: 90vh;
        overflow-y: auto;
    }

    .form-control {
        border-radius: 12px;
        border: 2px solid var(--admin-border);
        padding: 12px 16px;
        font-weight: 500;
        width: 100%;
    }

    .form-control:focus {
        border-color: var(--admin-indigo);
        outline: none;
    }

    .btn-premium {
        background: var(--admin-indigo);
        color: white;
        border-radius: 14px;
        padding: 14px 32px;
        font-weight: 700;
        border: none;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
</style>

<div class="blog-container">
    <div class="page-header">
        <h2><i class="fas fa-newspaper" style="color: #4a90d9;"></i> News & Articles</h2>
        <div>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm" style="min-height: auto; width: auto; padding: 12px 24px; margin-right: 12px; border-radius: 12px; font-weight: 700;">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
            <button class="btn btn-primary" style="min-height: auto; width: auto; padding: 12px 24px; border-radius: 12px; font-weight: 700; background: var(--admin-indigo); border: none;" onclick="openModal('add')">
                <i class="fas fa-plus me-2"></i> New Article
            </button>
        </div>
    </div>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success mt-1 mb-4" style="border-radius: 12px; border: none; background: #ecfdf5; color: #065f46; font-weight: 600; padding: 16px 24px;">
            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm border" style="border: 2px dashed #e2e8f0;">
            <i class="fas fa-edit mb-3" style="font-size: 3rem; color: #e2e8f0;"></i>
            <p class="text-muted">Your blog is currently empty.</p>
            <button class="btn btn-primary" onclick="openModal('add')" style="width: auto; margin-top: 15px;">Write First Post</button>
        </div>
    <?php else: ?>
        <div class="post-list">
            <?php foreach ($posts as $post): ?>
                <div class="post-card">
                    <img src="../../<?php echo !empty($post['cover_image']) ? $post['cover_image'] : 'assets/img/blog-placeholder.jpg'; ?>" class="post-image" alt="Thumb">
                    <div class="post-content">
                        <div class="post-title"><?php echo htmlspecialchars($post['title']); ?></div>
                        <div class="post-meta">
                            <span class="badge-status <?php echo $post['status'] === 'published' ? 'badge-published' : 'badge-draft'; ?>">
                                <?php echo ucfirst($post['status']); ?>
                            </span>
                            <span class="ms-3"><i class="far fa-link"></i> /blog/<?php echo $post['slug']; ?></span>
                        </div>
                        <div class="d-flex gap-2 mt-auto">
                            <button class="btn-action btn-edit" onclick="editPost(<?php echo htmlspecialchars(json_encode($post)); ?>)">
                                <i class="fas fa-edit"></i> Edit Post
                            </button>
                            <a href="?delete=<?php echo $post['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Delete this post?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Premium Modal -->
<div id="blogModal" class="custom-modal">
    <div class="modal-content">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h3 id="modalTitle" style="margin: 0; font-weight: 900; color: var(--admin-slate);">Create Article</h3>
            <button onclick="closeModal()" style="background: #f1f5f9; border: none; width: 36px; height: 36px; border-radius: 50%; color: #64748b; display: flex; align-items: center; justify-content: center;"><i class="fas fa-times"></i></button>
        </div>

        <form id="blogForm" enctype="multipart/form-data">
            <input type="hidden" name="id" id="postId">

            <div class="row">
                <div class="col-md-8">
                    <div class="form-group mb-4">
                        <label style="font-weight: 700; color: var(--admin-slate); font-size: 0.9rem; margin-bottom: 8px; display: block;">Headline *</label>
                        <input type="text" name="title" id="title" class="form-control" required placeholder="Catchy title for your post" onkeyup="generateSlug(this.value)">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-4">
                        <label style="font-weight: 700; color: var(--admin-slate); font-size: 0.9rem; margin-bottom: 8px; display: block;">Slug</label>
                        <input type="text" name="slug" id="slug" class="form-control" required readonly style="background: #f8fafc;">
                    </div>
                </div>
            </div>

            <div class="form-group mb-4">
                <label style="font-weight: 700; color: var(--admin-slate); font-size: 0.9rem; margin-bottom: 8px; display: block;">Article Content (HTML Compatible) *</label>
                <textarea name="content" id="content" class="form-control" rows="12" required placeholder="Write your story here..."></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <label style="font-weight: 700; color: var(--admin-slate); font-size: 0.9rem; margin-bottom: 8px; display: block;">Cover Image</label>
                    <input type="file" name="image" id="image" class="form-control" accept="image/*">
                </div>
                <div class="col-md-6 mb-4">
                    <label style="font-weight: 700; color: var(--admin-slate); font-size: 0.9rem; margin-bottom: 8px; display: block;">Publication Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="draft">Draft (Private)</option>
                        <option value="published">Published (Public)</option>
                    </select>
                </div>
            </div>

            <div style="background: #f8fafc; border-radius: 20px; padding: 25px; margin-top: 20px;">
                <h5 class="mb-3" style="font-weight: 800; color: var(--admin-slate); font-size: 1rem;"><i class="fas fa-search me-2"></i> SEO Configuration</h5>
                <div class="form-group mb-3">
                    <label style="font-weight: 700; color: var(--admin-slate); font-size: 0.8rem; margin-bottom: 5px; display: block;">Meta Title</label>
                    <input type="text" name="meta_title" id="meta_title" class="form-control">
                </div>
                <div class="form-group mb-0">
                    <label style="font-weight: 700; color: var(--admin-slate); font-size: 0.8rem; margin-bottom: 5px; display: block;">Meta Description</label>
                    <textarea name="meta_description" id="meta_description" class="form-control" rows="2"></textarea>
                </div>
            </div>

            <div class="mt-5 d-flex justify-content-end gap-3">
                <button type="button" class="btn btn-outline-secondary" style="border-radius: 14px; padding: 12px 24px; min-height: auto;" onclick="closeModal()">Discard</button>
                <button type="submit" class="btn-premium">Publish Post</button>
            </div>
        </form>
    </div>
</div>

<script>
function generateSlug(text) {
    const slug = text.toLowerCase().replace(/[^\w ]+/g, '').replace(/ +/g, '-');
    document.getElementById('slug').value = slug;
}

function openModal(mode) {
    document.getElementById('blogModal').classList.add('active');
    if (mode === 'add') {
        document.getElementById('modalTitle').innerText = 'Create Article';
        document.getElementById('blogForm').reset();
        document.getElementById('postId').value = '';
    }
}

function closeModal() {
    document.getElementById('blogModal').classList.remove('active');
}

function editPost(post) {
    openModal('edit');
    document.getElementById('modalTitle').innerText = 'Edit Article';
    document.getElementById('postId').value = post.id;
    document.getElementById('title').value = post.title;
    document.getElementById('slug').value = post.slug;
    document.getElementById('content').value = post.content;
    document.getElementById('status').value = post.status;
    document.getElementById('meta_title').value = post.meta_title || '';
    document.getElementById('meta_description').value = post.meta_description || '';
}

document.getElementById('blogForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const saveBtn = this.querySelector('button[type="submit"]');
    saveBtn.disabled = true;
    saveBtn.innerText = 'Syncing...';

    fetch('../../ajax/save_blog_post.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
            saveBtn.disabled = false;
            saveBtn.innerText = 'Publish Post';
        }
    })
    .catch(err => {
        console.error(err);
        alert('Server unreachable');
        saveBtn.disabled = false;
        saveBtn.innerText = 'Publish Post';
    });
};
</script>

<?php require_once '../../includes/footer.php'; ?>