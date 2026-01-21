<?php 
require_once 'includes/csrf.php';
startBookingSession();
require_once 'includes/header.php'; 
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();
$today = date('Y-m-d');

// Fetch active promotions for homepage
$promo_stmt = $db->prepare("SELECT * FROM promotions WHERE is_active = 1 AND start_date <= ? AND end_date >= ? AND FIND_IN_SET('homepage', display_location)");
$promo_stmt->execute([$today, $today]);
$active_promos = $promo_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent blog posts
$blog_stmt = $db->query("SELECT * FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 1");
$recent_posts = $blog_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="landing-page text-center">

    <div class="mb-3">
        <!-- Logo placeholder -->
    </div>

    <!-- Active Promotions Section -->
    <?php if (!empty($active_promos)): ?>
        <div class="promo-slider mb-4" style="overflow-x: auto; display: flex; gap: 15px; padding-bottom: 10px;">
            <script>
                window.activePromos = {};
                window.customerLoggedIn = <?php echo isset($_SESSION['customer_id']) ? 'true' : 'false'; ?>;
            </script>
            <?php foreach ($active_promos as $promo): ?>
            <script>
                if (!window.activePromos) window.activePromos = {};
                window.activePromos[<?php echo $promo['id']; ?>] = <?php echo json_encode($promo); ?>;
            </script>
                <div class="card" 
                     style="min-width: 280px; flex-shrink: 0; padding: 0; overflow: hidden; border: none; cursor: pointer;"
                     onclick="directGrab(<?php echo $promo['id']; ?>)">
                    <?php 
                    $is_video = in_array(strtolower(pathinfo($promo['banner_image'], PATHINFO_EXTENSION)), ['mp4', 'webm', 'ogg', 'mov']);
                    if ($promo['banner_image']): 
                        if ($is_video): ?>
                            <video src="<?php echo $promo['banner_image']; ?>" style="height: 140px; width: 100%; object-fit: cover;" autoplay muted loop playsinline></video>
                        <?php else: ?>
                            <div style="height: 140px; background: url('<?php echo $promo['banner_image']; ?>') center/cover;"></div>
                        <?php endif; 
                    endif; ?>
                    <div style="padding: 10px; text-align: left;">
                        <h4 style="font-size: 1.1rem; margin: 4px 0; font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($promo['title']); ?></h4>
                        <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 8px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?php echo htmlspecialchars($promo['description']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card" style="border-top: 4px solid var(--primary-color); padding: 30px;">
        <h2 class="h3" style="font-weight: 800; color: #1e293b;">Book your token</h2>
        <p class="mb-4 text-muted">Avoid the queue. Book online and we'll notify you automatically.</p>

        <a href="pages/book_step1.php" class="btn btn-primary" style="font-weight: 700; letter-spacing: 0.5px;">
            Book My Spot <i class="fas fa-arrow-right ms-2"></i>
        </a>
    </div>

    <div class="mt-4 d-grid gap-2" style="grid-template-columns: 1fr 1fr;">
        <a href="pages/my_appointments.php" class="btn" style="background: #eff6ff; color: #2563eb; border: none; font-size: 0.85rem; padding: 12px;">
            <i class="fas fa-calendar-alt mb-1 d-block"></i> My Portal
        </a>
        <a href="pages/services.php" class="btn" style="background: #fdf2f8; color: #db2777; border: none; font-size: 0.85rem; padding: 12px;">
            <i class="fas fa-cut mb-1 d-block"></i> Services
        </a>
    </div>

    <?php if (!empty($recent_posts)): ?>
        <?php $post = $recent_posts[0]; ?>
        <div class="mt-5 text-start">
            <div class="card" style="padding: 0; border: none; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px -5px rgba(0,0,0,0.05);">
                <div style="display: flex; flex-direction: row; min-height: 120px;">
                    <!-- Image on Left -->
                    <div style="width: 140px; min-width: 140px; height: 120px; flex-shrink: 0; overflow: hidden;">
                        <img src="<?php echo !empty($post['cover_image']) ? $post['cover_image'] : 'assets/img/blog-placeholder.jpg'; ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;" alt="Blog">
                    </div>

                    <!-- Text on Right -->
                    <div style="padding: 15px 20px; flex: 1; display: flex; flex-direction: column; justify-content: center;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <span style="font-size: 0.75rem; font-weight: 800; color: var(--primary-color); text-transform: uppercase; letter-spacing: 1px;">Salon News</span>
                            <a href="pages/blog.php" style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-decoration: none;">View All <i class="fas fa-chevron-right ms-1" style="font-size: 0.6rem;"></i></a>
                        </div>
                        <a href="pages/blog_post.php?slug=<?php echo $post['slug']; ?>" class="text-decoration-none">
                            <h5 style="font-size: 1.05rem; margin: 0; font-weight: 800; color: #1e293b; line-height: 1.4;"><?php echo htmlspecialchars($post['title']); ?></h5>
                        </a>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 8px;">
                            <i class="far fa-clock me-1"></i> <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-4 d-flex justify-content-center gap-4">
        <a href="pages/contact.php" class="text-muted" style="font-size: 0.85rem; text-decoration: none;">
            <i class="fas fa-map-marker-alt"></i> Contact & Map
        </a>
        <a href="pages/admin/login.php" class="text-muted" style="font-size: 0.85rem; text-decoration: none;">
            <i class="fas fa-lock"></i> Admin Portal
        </a>
    </div>

</div>

<!-- Promotion Detail Modal -->
<div id="promoDetailModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 2000; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); align-items: center; justify-content: center; padding: 20px;">
    <div style="background: white; border-radius: 24px; width: 100%; max-width: 450px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); position: relative;">
        <button onclick="closePromoModal()" style="position: absolute; top: 15px; right: 15px; z-index: 10; background: rgba(255,255,255,0.9); border: none; width: 32px; height: 32px; border-radius: 50%; color: #64748b; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"><i class="fas fa-times"></i></button>

        <div id="promoModalImage" style="height: 200px; display: flex; align-items: center; justify-content: center; background: #f8fafc; overflow: hidden;"></div>
        <div style="padding: 30px;">
            <h3 id="promoModalTitle" style="font-weight: 900; color: #1e293b; margin-bottom: 15px;">Offer Details</h3>
            <p id="promoModalDesc" style="color: #64748b; line-height: 1.6; margin-bottom: 25px; font-size: 1rem;"></p>

            <div id="promoGrabSection">
                <?php if (isset($_SESSION['customer_id'])): ?>
                    <button onclick="startGrabProcess()" id="grabBtn" class="btn btn-primary" style="background: #6366f1; border: none; font-weight: 700; min-height: auto;">
                        <i class="fas fa-gift me-2"></i> Grab This Offer Now
                    </button>
                <?php else: ?>
                    <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0;">
                        <p style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 12px; text-align: center;">Login with phone to grab this offer</p>
                        <div style="display: flex; gap: 8px;">
                            <input type="tel" id="grabPhone" placeholder="Enter phone number" style="flex: 1; padding: 10px 15px; border-radius: 10px; border: 2px solid #e2e8f0; font-size: 0.9rem;">
                            <button onclick="loginAndStartGrab()" id="loginGrabBtn" style="background: #6366f1; color: white; border: none; border-radius: 10px; padding: 0 15px; font-weight: 700; font-size: 0.85rem; min-height: 44px;">Login</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div id="processingMsg" style="display: none; text-align: center; padding: 20px;">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                <h4 style="margin-top: 15px; font-weight: 800; color: #1e293b;">Checking Eligibility...</h4>
                <p class="text-muted">Please wait a moment...</p>
            </div>

            <div id="winMsg" style="display: none; text-align: center; padding: 20px;">
                <i class="fas fa-trophy mb-3" style="font-size: 3rem; color: #f59e0b; animation: bounce 1s infinite;"></i>
                <h3 style="font-weight: 900; color: #1e293b;">You Won! 🎉</h3>
                <p style="color: #64748b; margin-bottom: 20px;">You are eligible for this exclusive offer.</p>
                <button onclick="finalizeGrab()" class="btn btn-primary w-100" style="background: #10b981; border: none; font-weight: 700; font-size: 1.1rem; padding: 12px;">
                    <i class="fas fa-check me-2"></i> Claim Now
                </button>
            </div>

            <div id="grabSuccessMsg" style="display: none; text-align: center; background: #ecfdf5; color: #059669; padding: 15px; border-radius: 16px; font-weight: 700;">
                <i class="fas fa-check-circle me-2"></i> Offer claimed!
            </div>
        </div>
    </div>
</div>

<style>
@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}
</style>

<script>
let currentPromoId = null;

function directGrab(id) {
    const promo = window.activePromos[id];
    if (!promo) return;

    if (!window.customerLoggedIn) {
        showPromoDetail(id);
        return;
    }

    const formData = new URLSearchParams();
    formData.append('promo_id', id);

    fetch('ajax/grab_promotion.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .finally(() => {
        if (promo.cta_link) {
            window.location.href = promo.cta_link;
        } else {
            showPromoDetail(id);
        }
    });
}

function showPromoDetail(idOrObj) {
    let promo = idOrObj;
    if (typeof idOrObj === 'number' || typeof idOrObj === 'string') {
        promo = window.activePromos[idOrObj];
    }

    if (!promo) {
        console.error('Promo not found');
        return;
    }

    currentPromoId = promo.id;
    document.getElementById('promoModalTitle').innerText = promo.title;
    document.getElementById('promoModalDesc').innerText = promo.description;

    const mediaContainer = document.getElementById('promoModalImage');
    const isVideo = ['mp4', 'webm', 'ogg', 'mov'].includes(promo.banner_image.split('.').pop().toLowerCase());

    if (isVideo) {
        mediaContainer.innerHTML = `<video src="${promo.banner_image}" style="width: 100%; height: 100%; object-fit: cover;" autoplay muted loop playsinline></video>`;
    } else {
        mediaContainer.innerHTML = '';
        mediaContainer.style.backgroundImage = 'url(' + (promo.banner_image || 'assets/img/placeholder.jpg') + ')';
        mediaContainer.style.backgroundSize = 'cover';
        mediaContainer.style.backgroundPosition = 'center';
    }

    document.getElementById('promoGrabSection').style.display = 'block';
    document.getElementById('processingMsg').style.display = 'none';
    document.getElementById('winMsg').style.display = 'none';
    document.getElementById('grabSuccessMsg').style.display = 'none';

    document.getElementById('promoDetailModal').style.display = 'flex';
}

function closePromoModal() {
    document.getElementById('promoDetailModal').style.display = 'none';
}

function startGrabProcess() {
    document.getElementById('promoGrabSection').style.display = 'none';
    document.getElementById('processingMsg').style.display = 'block';

    setTimeout(() => {
        document.getElementById('processingMsg').style.display = 'none';
        document.getElementById('winMsg').style.display = 'block';
    }, 3000);
}

function loginAndStartGrab() {
    const phone = document.getElementById('grabPhone').value;
    if (!phone) { alert('Please enter phone number'); return; }

    const btn = document.getElementById('loginGrabBtn');
    btn.disabled = true;
    btn.innerText = 'Verifying...';

    const formData = new URLSearchParams();
    formData.append('phone', phone);

    fetch('ajax/customer_login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.customerLoggedIn = true;
            startGrabProcess();
        } else {
            alert(data.message);
            btn.disabled = false;
            btn.innerText = 'Login';
        }
    })
    .catch(err => {
        alert('Login failed. Try again.');
        btn.disabled = false;
        btn.innerText = 'Login';
    });
}

function finalizeGrab() {
    const formData = new URLSearchParams();
    formData.append('promo_id', currentPromoId);

    fetch('ajax/grab_promotion.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('winMsg').style.display = 'none';
            const successMsg = document.getElementById('grabSuccessMsg');
            successMsg.style.display = 'block';

            let msgHtml = '<i class="fas fa-check-circle me-2"></i> ' + data.message;

            const promo = window.activePromos[currentPromoId];
            if (promo && promo.cta_link) {
                msgHtml += `
                    <div style="margin-top: 15px;">
                        <a href="${promo.cta_link}" class="btn btn-primary w-100" style="background: #6366f1; border: none; font-weight: 700; padding: 12px;">
                            ${promo.cta_text || 'Continue'} <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                `;
            }

            successMsg.innerHTML = msgHtml;
        } else {
            alert(data.message);
        }
    })
    .catch(err => alert('Claim failed. Please try again.'));
}
</script>

<!-- Floating Contact Buttons -->
<?php


// Use global values from settings_helper.php
$callRaw = $shopPhone ?? '';
$waRaw   = $shopWhatsapp ?? '';
$waMsg   = getSetting('whatsapp_message', 'Hello, I want to book a token.');

$callNumber = preg_replace('/\D+/', '', $callRaw);
$waNumber   = preg_replace('/\D+/', '', $waRaw);
?>
<div style="position: fixed; bottom: 20px; right: 20px; z-index: 1000; display: flex; flex-direction: column; gap: 10px;">
    <?php if ($waNumber): ?>
        <a href="https://wa.me/<?= $waNumber ?>?text=<?= urlencode($waMsg) ?>"
           target="_blank"
           style="width: 50px; height: 50px; background: #25D366; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.2); text-decoration: none; font-size: 1.5rem;">
            <i class="fab fa-whatsapp"></i>
        </a>
    <?php endif; ?>

    <?php if ($callNumber): ?>
        <a href="tel:<?= $callNumber ?>"
           style="width: 50px; height: 50px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.2); text-decoration: none; font-size: 1.2rem;">
            <i class="fas fa-phone"></i>
        </a>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

