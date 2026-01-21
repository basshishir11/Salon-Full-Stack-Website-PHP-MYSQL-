<?php
// pages/admin/loyalty.php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get all pending and claimed rewards
$rewards = $db->query("SELECT r.*, c.name as customer_name, c.phone 
                       FROM rewards r 
                       JOIN customers c ON r.customer_id = c.id 
                       ORDER BY r.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$pending = array_filter($rewards, fn($r) => $r['status'] === 'Pending');
$claimed = array_filter($rewards, fn($r) => $r['status'] === 'Claimed');

// Get milestones
$milestones = $db->query("SELECT * FROM loyalty_milestones ORDER BY visits_required")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loyalty & Rewards</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-nav {
            background: var(--dark-gray);
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
        }
        .admin-nav a { color: #fff; margin-left: 15px; text-decoration: underline; }
        .milestone-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .milestone-card {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .milestone-card .visits { font-size: 32px; font-weight: bold; }
        .milestone-card .reward { font-size: 14px; margin-top: 10px; }
        .reward-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .reward-table th, .reward-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .reward-table th { background: var(--light-gray); font-weight: 600; }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-claimed { background: #d4edda; color: #155724; }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
        }
    </style>
</head>
<body>

<div class="admin-nav">
    <div><strong>Loyalty & Rewards</strong></div>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="../../ajax/logout.php" onclick="event.preventDefault(); fetch('../../ajax/logout.php').then(() => window.location.href='login.php');">Logout</a>
    </div>
</div>

<div class="container" style="max-width: 1000px; padding: 20px;">
    <h2>Loyalty Program</h2>

    <h3>Milestones</h3>
    <div class="milestone-cards">
        <?php foreach ($milestones as $m): ?>
        <div class="milestone-card">
            <div class="visits"><?php echo $m['visits_required']; ?> Visits</div>
            <div class="reward"><?php echo htmlspecialchars($m['reward_type']); ?></div>
            <div style="font-size: 12px; opacity: 0.8; margin-top: 5px;"><?php echo htmlspecialchars($m['description']); ?></div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($milestones)): ?>
        <p style="color: #666;">No milestones configured. Run seed_milestones.sql to add defaults.</p>
        <?php endif; ?>
    </div>

    <h3>Pending Rewards (<?php echo count($pending); ?>)</h3>
    <?php if (!empty($pending)): ?>
    <table class="reward-table">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Phone</th>
                <th>Reward</th>
                <th>Earned</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pending as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($r['phone']); ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($r['reward_type']); ?></strong>
                    <br><small><?php echo htmlspecialchars($r['description']); ?></small>
                </td>
                <td><?php echo date('M d, Y', strtotime($r['created_at'])); ?></td>
                <td>
                    <button class="btn-sm" style="background: var(--success-color); color: white;" onclick="claimReward(<?php echo $r['id']; ?>)">
                        <i class="fas fa-check"></i> Mark Claimed
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color: #666;">No pending rewards.</p>
    <?php endif; ?>

    <h3 style="margin-top: 30px;">Claimed Rewards (<?php echo count($claimed); ?>)</h3>
    <?php if (!empty($claimed)): ?>
    <table class="reward-table">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Reward</th>
                <th>Claimed</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($claimed, 0, 20) as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($r['reward_type']); ?></td>
                <td><?php echo date('M d, Y', strtotime($r['claimed_at'] ?? $r['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color: #666;">No claimed rewards yet.</p>
    <?php endif; ?>
</div>

<script>
function claimReward(rewardId) {
    if (!confirm('Mark this reward as claimed?')) return;
    
    fetch('../../ajax/claim_reward.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'reward_id=' + rewardId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to claim reward');
        }
    });
}
</script>

</body>
</html>
