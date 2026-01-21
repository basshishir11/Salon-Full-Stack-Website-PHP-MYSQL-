<?php
// pages/admin/manage_promotions.php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM promotions WHERE id = ?")->execute([$id]);
    header('Location: manage_promotions.php?message=Campaign deleted successfully');
    exit;
}

// Handle Status Toggle
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE promotions SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: manage_promotions.php');
    exit;
}

// Fetch all promotions with claim counts
try {
    $query = "SELECT p.*, s.name as service_name, 
            (SELECT COUNT(*) FROM promotion_claims WHERE promotion_id = p.id) as claim_count 
            FROM promotions p 
            LEFT JOIN services s ON p.service_id = s.id 
            ORDER BY p.created_at DESC";
    $promotions = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if ($e->getCode() == '42S02') {
        die("<div style='padding: 20px; font-family: sans-serif; text-align: center; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; margin: 20px; border-radius: 8px;'>
            <h2 style='margin-top: 0;'>Database Update Required</h2>
            <p>It seems the new 'Claims' feature is missing its database table.</p>
            <p>Please <a href='../../migrate_v3_claims.php' target='_blank' style='font-weight: bold; color: #721c24;'>CLICK HERE</a> to run the database update script.</p>
            <p><small>Error details: " . htmlspecialchars($e->getMessage()) . "</small></p>
            <button onclick='location.reload()' style='margin-top: 10px; padding: 10px 20px; cursor: pointer;'>Refresh Page After Update</button>
        </div>");
    } else {
        die("Database Error: " . htmlspecialchars($e->getMessage()));
    }
}

// Fetch services for the form
$services = $db->query("SELECT id, name FROM services WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

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

    .promotions-container { 
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

    .promo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
    }

    .promo-card { 
        background: white; 
        border-radius: 20px; 
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); 
        overflow: hidden; 
        border: 1px solid var(--admin-border);
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
    }

    .promo-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .promo-image-wrapper {
        position: relative;
        height: 180px;
        background: #f1f5f9;
        overflow: hidden;
    }

    .promo-image { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
    }

    .promo-content { 
        padding: 24px; 
        flex: 1; 
        display: flex; 
        flex-direction: column; 
    }

    .promo-title { 
        font-size: 1.3rem; 
        font-weight: 800; 
        color: var(--admin-slate); 
        margin-bottom: 8px; 
        line-height: 1.2;
    }

    .promo-dates { 
        font-size: 0.85rem; 
        color: #64748b; 
        margin-bottom: 15px; 
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .info-tag {
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
    }

    .badge-active { background: #ecfdf5; color: #059669; }
    .badge-inactive { background: #fef2f2; color: #dc2626; }
    .badge-location { background: #eff6ff; color: #2563eb; }

    .promo-actions { 
        margin-top: 20px;
        display: flex; 
        gap: 8px; 
        padding-top: 15px;
        border-top: 1px solid #f1f5f9;
    }

    .btn-action {
        flex: 1;
        padding: 10px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.8rem;
        transition: all 0.2s;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: auto;
        width: auto;
    }

    .btn-edit { background: #f1f5f9; color: var(--admin-slate); }
    .btn-edit:hover { background: #e2e8f0; }
    .btn-delete { background: #fff1f2; color: #e11d48; }
    .btn-delete:hover { background: #ffe4e6; }

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
        max-width: 550px;
        padding: 40px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        position: relative;
        max-height: 90vh;
        overflow-y: auto;
    }

    .form-label {
        font-weight: 700;
        color: var(--admin-slate);
        font-size: 0.9rem;
        margin-bottom: 8px;
        display: block;
    }

    .form-control {
        border-radius: 12px;
        border: 2px solid var(--admin-border);
        padding: 12px 16px;
        font-weight: 500;
        transition: all 0.2s;
        width: 100%;
    }

    .form-control:focus {
        border-color: var(--admin-indigo);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        outline: none;
    }

    .btn-premium {
        background: var(--admin-indigo);
        color: white;
        border-radius: 14px;
        padding: 14px 24px;
        font-weight: 700;
        border: none;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        width: 100%;
    }

    .btn-premium:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
    }

    .btn-cancel {
        background: transparent;
        color: #64748b;
        font-weight: 700;
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        padding: 12px 24px;
        width: 100%;
        transition: all 0.2s;
    }

    .btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }

    /* Claims List Premium Styles */
    .claims-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
        padding: 5px;
    }

    .claim-item {
        display: flex;
        align-items: center;
        padding: 16px;
        border-radius: 16px;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        transition: all 0.2s;
        gap: 16px;
    }

    .claim-item:hover {
        background: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transform: translateX(4px);
        border-color: #e2e8f0;
    }

    .claim-avatar {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, #6366f1, #818cf8);
        color: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.1rem;
        box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2);
    }

    .claim-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .claim-name {
        font-weight: 700;
        color: var(--admin-slate);
        font-size: 1rem;
    }

    .claim-phone {
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .claim-date {
        font-size: 0.75rem;
        color: #94a3b8;
        font-weight: 600;
        background: white;
        padding: 4px 10px;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        display: inline-block;
    }

    .claim-actions {
        display: flex;
        gap: 8px;
    }

    .btn-icon-action {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        transition: all 0.2s;
        color: white;
        text-decoration: none;
    }

    .action-call { background: #10b981; }
    .action-call:hover { background: #059669; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); color: white; }

</style>

<div class="promotions-container">
    <div class="page-header">
        <h2><i class="fas fa-bullhorn" style="color: var(--admin-indigo);"></i> Campaigns</h2>
        <div>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm" style="min-height: auto; width: auto; padding: 12px 24px; margin-right: 12px; border-radius: 12px; font-weight: 700;">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
            <button class="btn btn-primary" style="min-height: auto; width: auto; padding: 12px 24px; border-radius: 12px; font-weight: 700; background: var(--admin-indigo); border: none;" onclick="openModal('add')">
                <i class="fas fa-plus me-2"></i> Create Campaign
            </button>
        </div>
    </div>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success d-flex align-items-center" style="border-radius: 16px; border: none; background: #ecfdf5; color: #065f46; font-weight: 600; padding: 16px 24px; margin-bottom: 30px;">
            <i class="fas fa-check-circle me-3 fa-lg"></i>
            <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($promotions)): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm border" style="border: 2px dashed #e2e8f0;">
            <div style="width: 80px; height: 80px; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="fas fa-tags" style="font-size: 2rem; color: #cbd5e1;"></i>
            </div>
            <h4 style="color: var(--admin-slate); font-weight: 800;">No Campaigns Yet</h4>
            <p class="text-muted">Start creating offers to attract more customers.</p>
            <button class="btn btn-primary mt-3" style="width: auto; margin: 0 auto; display: inline-flex; min-height: auto; padding: 12px 24px;" onclick="openModal('add')">Create First Promo</button>
        </div>
    <?php else: ?>
        <div class="promo-grid">
            <?php foreach ($promotions as $promo): ?>
                <div class="promo-card">
                    <div class="promo-image-wrapper">
                        <?php 
                        $media_path = "../../" . ($promo['banner_image'] ?: 'assets/img/placeholder.jpg');
                        $is_video = in_array(strtolower(pathinfo($media_path, PATHINFO_EXTENSION)), ['mp4', 'webm', 'ogg', 'mov']);
                        
                        if ($is_video): ?>
                            <video src="<?php echo $media_path; ?>" class="promo-image" muted loop onmouseover="this.play()" onmouseout="this.pause()"></video>
                        <?php else: ?>
                            <img src="<?php echo $media_path; ?>" class="promo-image" alt="Promo">
                        <?php endif; ?>
                        <div style="position: absolute; top: 15px; left: 15px;">
                            <?php if ($promo['is_active']): ?>
                                <span class="info-tag badge-active"><i class="fas fa-circle me-1" style="font-size: 6px;"></i> Active</span>
                            <?php else: ?>
                                <span class="info-tag badge-inactive">Paused</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="promo-content">
                        <div class="promo-title"><?php echo htmlspecialchars($promo['title']); ?></div>
                        <div class="promo-dates">
                            <i class="far fa-calendar-alt"></i> 
                            <?php echo date('M j', strtotime($promo['start_date'])); ?> - <?php echo date('M j, Y', strtotime($promo['end_date'])); ?>
                        </div>
                        
                        <?php if ($promo['service_name']): ?>
                            <div class="mb-3">
                                <span class="info-tag" style="background: #f1f5f9; color: #475569; padding: 4px 8px; font-size: 0.7rem;">
                                    <i class="fas fa-cut me-1"></i> <?php echo htmlspecialchars($promo['service_name']); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach (explode(',', $promo['display_location'] ?? 'homepage') as $loc): ?>
                                <span class="info-tag badge-location"><?php echo ucfirst(trim($loc)); ?></span>
                            <?php endforeach; ?>
                        </div>

                        <div class="promo-actions">
                            <button class="btn-action btn-edit" title="Edit Campaign" onclick="editPromo(<?php echo htmlspecialchars(json_encode($promo)); ?>)">
                                <i class="fas fa-pen"></i> Edit
                            </button>
                            <a href="?toggle=<?php echo $promo['id']; ?>" class="btn-action" style="background: #eff6ff; color: #1d4ed8;" title="Toggle Visibility">
                                <i class="fas fa-power-off"></i>
                            </a>
                            <a href="?delete=<?php echo $promo['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Delete this campaign?')" title="Delete">
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
<div id="promoModal" class="custom-modal">
    <div class="modal-content">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h3 id="modalTitle" style="margin: 0; font-weight: 900; color: var(--admin-slate); font-size: 1.5rem;">Create Campaign</h3>
            <button onclick="closeModal()" style="background: #f1f5f9; border: none; width: 36px; height: 36px; border-radius: 50%; color: #64748b; display: flex; align-items: center; justify-content: center;"><i class="fas fa-times"></i></button>
        </div>
        
        <form id="promoForm" enctype="multipart/form-data">
            <input type="hidden" name="id" id="promoId">
            
            <div class="form-group mb-4">
                <label class="form-label">Campaign Title *</label>
                <input type="text" name="title" id="title" class="form-control" required placeholder="e.g. Festival Special Offer">
            </div>

            <div class="form-group mb-4">
                <label class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3" placeholder="Tell customers more about this offer..."></textarea>
            </div>

            <div class="row gx-3">
                <div class="col-6 mb-4">
                    <label class="form-label">Launch Date *</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" required>
                </div>
                <div class="col-6 mb-4">
                    <label class="form-label">Expiry Date *</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" required>
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="form-label">Link to Service</label>
                <select name="service_id" id="service_id" class="form-control">
                    <option value="">-- General Offer (No link) --</option>
                    <?php foreach ($services as $svc): ?>
                        <option value="<?php echo $svc['id']; ?>"><?php echo htmlspecialchars($svc['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-4">
                <label class="form-label">Banner Media</label>
                <div style="background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 16px; padding: 20px; text-align: center;">
                    <input type="file" name="banner" id="banner" class="form-control" accept="image/*,video/*" style="border: none; background: transparent; padding: 0;">
                    <p class="text-muted mt-2 mb-0" style="font-size: 0.75rem;">Recommended size: 1200x400px (Images or Videos)</p>
                </div>
            </div>

            <div class="row gx-3">
                <div class="col-12 mb-4">
                    <label class="form-label">Destination URL</label>
                    <input type="text" name="cta_link" id="cta_link" class="form-control" placeholder="pages/book_step1.php">
                </div>
            </div>

            <div class="form-group mb-5">
                <label class="form-label">Target Placements</label>
                <div class="d-flex gap-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="location[]" value="homepage" id="locHome" checked>
                        <label class="form-check-label" for="locHome" style="color: #475569; font-weight: 600;">Homepage</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="location[]" value="booking" id="locBook">
                        <label class="form-check-label" for="locBook" style="color: #475569; font-weight: 600;">Booking Page</label>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-3">
                <button type="submit" class="btn-premium">Save Campaign</button>
                <button type="button" class="btn-cancel" onclick="closeModal()">Discard Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Claims Modal -->
<div id="claimsModal" class="custom-modal">
    <div class="modal-content" style="max-width: 550px; background: white;">
        <div class="d-flex justify-content-between align-items-center mb-2" style="border-bottom: 2px solid #f1f5f9; padding-bottom: 20px;">
            <div>
                <h3 id="claimsModalTitle" style="margin: 0; font-weight: 900; color: var(--admin-slate); font-size: 1.4rem;">Campaign Claims</h3>
                <p class="text-muted mb-0" style="font-size: 0.85rem; margin-top: 4px;">Customers who have booked or claimed this offer</p>
            </div>
            <button onclick="closeClaimsModal()" style="background: #f1f5f9; border: none; width: 40px; height: 40px; border-radius: 12px; color: #64748b; display: flex; align-items: center; justify-content: center; transition: all 0.2s;"><i class="fas fa-times"></i></button>
        </div>
        
        <div id="claimsList" style="max-height: 450px; overflow-y: auto; padding-right: 5px; margin-top: 10px;">
            <!-- Claims will be loaded here -->
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-3 text-muted fw-bold">Loading participants...</p>
            </div>
        </div>
    </div>
</div>

<script>
function getInitials(name) {
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
}

function viewClaims(promoId, title) {
    document.getElementById('claimsModalTitle').innerText = title;
    // document.getElementById('claimsList').innerHTML = '<div class="text-center py-4 text-muted">Loading claims...</div>'; // Replaced by better loader in default HTML
    document.getElementById('claimsModal').classList.add('active');
    
    fetch('../../ajax/get_promotion_claims.php?promo_id=' + promoId)
    .then(r => {
        if (!r.ok) throw new Error('Network response was not ok');
        return r.json();
    })
    .then(data => {
        const list = document.getElementById('claimsList');
        if (data.success) {
            if (data.claims.length === 0) {
                list.innerHTML = `
                    <div class="text-center py-5">
                        <div style="width: 70px; height: 70px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                            <i class="far fa-sad-tear" style="font-size: 2rem; color: #cbd5e1;"></i>
                        </div>
                        <h5 style="color: var(--admin-slate); font-weight: 700;">No claims yet</h5>
                        <p class="text-muted small">Share this campaign to get more engagement!</p>
                    </div>`;
                return;
            }
            
            let html = '<div class="claims-list">';
            data.claims.forEach(c => {
                const date = new Date(c.claimed_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                const initials = getInitials(c.name);
                
                html += `
                <div class="claim-item">
                    <div class="claim-avatar">${initials}</div>
                    <div class="claim-info">
                        <div class="claim-name">${c.name}</div>
                        <div class="claim-phone">
                            <i class="fas fa-phone-alt fa-xs"></i> ${c.phone}
                        </div>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-2">
                        <div class="claim-date">${date}</div>
                        <a href="tel:${c.phone}" class="btn-icon-action action-call" title="Call Customer">
                            <i class="fas fa-phone"></i>
                        </a>
                    </div>
                </div>`;
            });
            html += '</div>';
            list.innerHTML = html;
        } else {
            alert(data.message);
            if (data.message.includes('login')) location.reload();
        }
    })
    .catch(err => {
        console.error('Fetch error:', err);
        document.getElementById('claimsList').innerHTML = '<div class="text-center py-5 text-danger">Failed to load claims.<br><small>Check console for details.</small></div>';
    });
}

function closeClaimsModal() {
    document.getElementById('claimsModal').classList.remove('active');
}
function openModal(mode) {
    const modal = document.getElementById('promoModal');
    const form = document.getElementById('promoForm');
    modal.classList.add('active');
    
    if (mode === 'add') {
        document.getElementById('modalTitle').innerText = 'Create Campaign';
        form.reset();
        document.getElementById('promoId').value = '';
    }
}

function closeModal() {
    document.getElementById('promoModal').classList.remove('active');
}

function editPromo(promo) {
    openModal('edit');
    document.getElementById('modalTitle').innerText = 'Edit Campaign';
    document.getElementById('promoId').value = promo.id;
    document.getElementById('title').value = promo.title;
    document.getElementById('description').value = promo.description;
    document.getElementById('start_date').value = promo.start_date;
    document.getElementById('end_date').value = promo.end_date;
    document.getElementById('service_id').value = promo.service_id || '';
    document.getElementById('cta_link').value = promo.cta_link;
    
    // Checkboxes
    const locations = promo.display_location ? promo.display_location.split(',') : [];
    document.getElementById('locHome').checked = locations.includes('homepage');
    document.getElementById('locBook').checked = locations.includes('booking');
}

document.getElementById('promoForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const saveBtn = this.querySelector('button[type="submit"]');
    const originalText = saveBtn.innerText;
    
    saveBtn.disabled = true;
    saveBtn.innerText = 'Syncing...';
    
    fetch('../../ajax/save_promotion.php', {
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
            saveBtn.innerText = originalText;
        }
    })
    .catch(err => {
        console.error(err);
        alert('Server unreachable');
        saveBtn.disabled = false;
        saveBtn.innerText = originalText;
    });
};
</script>

<?php require_once '../../includes/footer.php'; ?>
