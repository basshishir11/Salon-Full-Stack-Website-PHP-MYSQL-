<?php
// pages/admin/manage_rewards.php
session_start();
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/database.php';

// Check admin session
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Self-healing: Check if assigned_service_id exists
try {
    $db->query("SELECT assigned_service_id FROM rewards LIMIT 1");
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Unknown column') !== false) {
        $db->exec("ALTER TABLE rewards ADD COLUMN assigned_service_id INT NULL DEFAULT NULL");
    }
}

// Fetch total count regardless of status for debugging
$total_rewards_count = $db->query("SELECT COUNT(*) FROM rewards")->fetchColumn();

// Fetch pending rewards with customer spending data (handling NULL total_spend)
$query = "SELECT r.*, c.name, c.phone, c.visit_count,
          IFNULL((SELECT SUM(final_amount) FROM revenue rv JOIN tokens t ON rv.token_id = t.id WHERE t.customer_id = c.id), 0) as total_spend
          FROM rewards r 
          JOIN customers c ON r.customer_id = c.id 
          WHERE r.status = 'Pending' 
          ORDER BY r.created_at DESC";
$stmt = $db->query($query);
$rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all active services for the choice dropdown
$services_stmt = $db->query("SELECT id, name, price, gender_type FROM services WHERE is_active = 1 ORDER BY gender_type, name");
$all_services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
    :root {
        --admin-bg: #f8fafc;
        --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    body {
        background-color: var(--admin-bg) !important;
    }

    .rewards-dashboard {
        padding: 40px 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .page-header {
        background: white;
        padding: 24px;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-left: 5px solid var(--primary-color);
    }

    .page-header h2 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .rewards-table-container {
        background: white;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        overflow: visible; 
    }

    .rewards-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .rewards-table th {
        background: #f1f5f9;
        padding: 16px 20px;
        text-align: left;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        border-bottom: 1px solid #e2e8f0;
    }

    .rewards-table td {
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        color: #334155;
    }

    .rewards-table tr:hover {
        background-color: #f8fafc;
    }

    .cust-info {
        display: flex;
        flex-direction: column;
    }

    .cust-name {
        font-weight: 600;
        color: #1e293b;
    }

    .badge-pill {
        padding: 6px 12px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .badge-loyalty { background: #eff6ff; color: #2563eb; }
    .badge-referral { background: #fef2f2; color: #dc2626; }

    .spend-analysis {
        font-size: 0.8rem;
        padding: 8px;
        border-radius: 8px;
        background: #f1f5f9;
        margin-top: 5px;
        display: block;
    }

    .spend-high { border-left: 4px solid #16a34a; background: #f0fdf4; color: #166534; }
    .spend-med { border-left: 4px solid #ca8a04; background: #fefce8; color: #854d0e; }
    .spend-low { border-left: 4px solid #64748b; background: #f8fafc; color: #475569; }

    .service-select {
        padding: 8px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        font-size: 0.85rem;
        width: 100%;
        max-width: 250px;
        margin-top: 10px;
        background: white;
    }

    .btn-claim {
        background: var(--success-color);
        color: white;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.2s;
        width: 100%;
    }

    .btn-claim:hover { background: #1e7e34; transform: translateY(-1px); }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
    }
</style>

<div class="rewards-dashboard">
    <div class="page-header">
        <h2><i class="fas fa-gift text-primary"></i> Pending Rewards</h2>
        <a href="manage_tokens.php" class="btn btn-outline-secondary btn-sm" style="min-height: auto; width: auto; padding: 10px 20px;">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <?php if (empty($rewards)): ?>
        <div class="empty-state">
            <i class="fas fa-box-open" style="font-size: 4rem; color: #e2e8f0; margin-bottom: 20px;"></i>
            <h3 class="h5">No Pending Tasks</h3>
            <p class="text-muted">
                <?php if ($total_rewards_count > 0): ?>
                    All rewards have been assigned and claimed. Check back later!
                <?php else: ?>
                    No rewards have been generated yet. Rewards are created automatically when customers reach milestones (5, 10, etc. visits).
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="rewards-table-container">
            <table class="rewards-table">
                <thead>
                    <tr>
                        <th>Customer & History</th>
                        <th>Type</th>
                        <th>Reward Definition</th>
                        <th>Earned</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rewards as $reward): ?>
                        <?php 
                        $avg_spend = $reward['visit_count'] > 0 ? ($reward['total_spend'] / $reward['visit_count']) : 0;
                        $spend_class = 'spend-low';
                        $status_label = 'Standard Spender';
                        if ($avg_spend > 500) { $spend_class = 'spend-high'; $status_label = 'Premium Spender'; }
                        elseif ($avg_spend > 250) { $spend_class = 'spend-med'; $status_label = 'Regular Spender'; }
                        ?>
                        <tr>
                            <td data-label="Customer">
                                <span class="cust-name"><?php echo htmlspecialchars($reward['name']); ?></span>
                                <span class="cust-phone"><?php echo htmlspecialchars($reward['phone']); ?></span>
                                <div class="spend-analysis <?php echo $spend_class; ?>">
                                    <strong><?php echo $status_label; ?></strong><br>
                                    Avg: NPR <?php echo number_format($avg_spend, 0); ?> • Total: <?php echo $reward['visit_count']; ?> visits
                                </div>
                            </td>
                            <td data-label="Type">
                                <?php if ($reward['reward_type'] === 'Referral'): ?>
                                    <span class="badge-pill badge-referral"><i class="fas fa-user-plus"></i> Referral</span>
                                <?php else: ?>
                                    <span class="badge-pill badge-loyalty"><i class="fas fa-star"></i> Loyalty</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Definition">
                                <?php if ($reward['reward_type'] === 'Loyalty'): ?>
                                    <div style="margin-bottom: 5px; font-size: 0.85rem; color: #64748b;">
                                        Requirement met! Choose a reward:
                                    </div>
                                    <select class="service-select" id="service-<?php echo $reward['id']; ?>">
                                        <option value="">-- Assign a free service --</option>
                                        <?php foreach ($all_services as $svc): ?>
                                            <option value="<?php echo $svc['id']; ?>" <?php echo ($reward['assigned_service_id'] == $svc['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($svc['name']); ?> (NPR <?php echo number_format($svc['price'], 0); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <div style="font-size: 0.9rem;"><strong><?php echo htmlspecialchars($reward['description']); ?></strong></div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Earned">
                                <span style="font-size: 0.85rem; color: #64748b;"><?php echo date('M j, Y', strtotime($reward['created_at'])); ?></span>
                            </td>
                            <td data-label="Action">
                                <button class="btn-claim" onclick="processReward(<?php echo $reward['id']; ?>, '<?php echo $reward['reward_type']; ?>')">
                                    <i class="fas fa-check-circle"></i> Grant & Claim
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function processReward(rewardId, type) {
    let serviceId = null;
    if (type === 'Loyalty') {
        serviceId = document.getElementById('service-' + rewardId).value;
        if (!serviceId) {
            alert('Please select a service for this loyalty reward first.');
            return;
        }
    }

    if (!confirm('Mark this reward as granted and claimed?')) return;
    
    const btn = event.currentTarget;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    // Sequence: Assign -> Claim
    const runAssign = () => {
        if (type === 'Loyalty') {
            const formData = new FormData();
            formData.append('reward_id', rewardId);
            formData.append('service_id', serviceId);
            return fetch('../../ajax/assign_reward_service.php', { method: 'POST', body: formData, credentials: 'include' }).then(r => r.json());
        }
        return Promise.resolve({success: true});
    };

    runAssign()
    .then(data => {
        if (!data.success) throw new Error(data.message || 'Assignment failed');
        
        const formData = new FormData();
        formData.append('reward_id', rewardId);
        return fetch('../../ajax/claim_reward.php', { method: 'POST', body: formData, credentials: 'include' }).then(r => r.json());
    })
    .then(data => {
        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check"></i> Success!';
            setTimeout(() => location.reload(), 800);
        } else {
            throw new Error(data.message || 'Claim failed');
        }
    })
    .catch(err => {
        alert(err.message || 'Connection error');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
