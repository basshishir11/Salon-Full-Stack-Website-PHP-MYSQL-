<?php
// pages/admin/customer_history.php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/database.php';

$customer_id = $_GET['id'] ?? '';
if (empty($customer_id)) {
    header("Location: manage_customers.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get customer
$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    header("Location: manage_customers.php");
    exit;
}

// Get visit history (tokens)
$stmt = $db->prepare("SELECT * FROM tokens WHERE customer_id = ? ORDER BY created_at DESC");
$stmt->execute([$customer_id]);
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get rewards
$stmt = $db->prepare("SELECT * FROM rewards WHERE customer_id = ? ORDER BY created_at DESC");
$stmt->execute([$customer_id]);
$rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer History</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-nav {
            background: var(--dark-gray);
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .admin-nav a { color: #fff; margin-left: 15px; text-decoration: underline; }
        .customer-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .stat-row {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .stat-item {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 8px;
        }
        .stat-item span { display: block; }
        .stat-item .num { font-size: 24px; font-weight: bold; }
        .stat-item .label { font-size: 12px; opacity: 0.9; }
        .history-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #ddd;
        }
        .history-card.completed { border-left-color: var(--success-color); }
        .history-card.cancelled { border-left-color: var(--danger-color); }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #842029; }
    </style>
</head>
<body>

<div class="admin-nav">
    <div><strong>Customer History</strong></div>
    <div>
        <a href="manage_customers.php">Back to Customers</a>
        <a href="dashboard.php">Dashboard</a>
    </div>
</div>

<div class="container" style="max-width: 800px;">
    <div class="customer-header">
        <h2 style="margin: 0;"><?php echo htmlspecialchars($customer['name']); ?></h2>
        <p style="margin: 5px 0; opacity: 0.9;"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['phone']); ?></p>
        
        <div class="stat-row">
            <div class="stat-item">
                <span class="num"><?php echo $customer['visit_count']; ?></span>
                <span class="label">Total Visits</span>
            </div>
            <div class="stat-item">
                <span class="num"><?php echo count($rewards); ?></span>
                <span class="label">Rewards</span>
            </div>
            <div class="stat-item">
                <span class="num"><?php echo htmlspecialchars($customer['referral_code'] ?? '-'); ?></span>
                <span class="label">Referral Code</span>
            </div>
        </div>
    </div>

    <h3><i class="fas fa-history"></i> Visit History</h3>
    
    <?php if (empty($visits)): ?>
        <p style="color: #666;">No visits yet.</p>
    <?php else: ?>
        <?php foreach ($visits as $visit): ?>
        <div class="history-card <?php echo strtolower($visit['status']); ?>">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong><?php echo htmlspecialchars($visit['token_number']); ?></strong>
                    <span class="badge badge-<?php echo $visit['status'] === 'Completed' ? 'success' : ($visit['status'] === 'Cancelled' ? 'danger' : 'warning'); ?>">
                        <?php echo $visit['status']; ?>
                    </span>
                </div>
                <small style="color: #666;"><?php echo date('M d, Y h:i A', strtotime($visit['created_at'])); ?></small>
            </div>
            <p style="margin: 10px 0 0; color: #666; font-size: 14px;">
                <?php echo htmlspecialchars($visit['services_summary']); ?>
            </p>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($rewards)): ?>
    <h3 style="margin-top: 30px;"><i class="fas fa-gift"></i> Rewards</h3>
    <?php foreach ($rewards as $reward): ?>
    <div class="history-card">
        <div style="display: flex; justify-content: space-between;">
            <div>
                <strong><?php echo htmlspecialchars($reward['reward_type']); ?></strong>
                <span class="badge badge-<?php echo $reward['status'] === 'Claimed' ? 'success' : 'warning'; ?>">
                    <?php echo $reward['status']; ?>
                </span>
            </div>
            <small style="color: #666;"><?php echo date('M d, Y', strtotime($reward['created_at'])); ?></small>
        </div>
        <p style="margin: 5px 0 0; color: #666; font-size: 14px;"><?php echo htmlspecialchars($reward['description']); ?></p>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
