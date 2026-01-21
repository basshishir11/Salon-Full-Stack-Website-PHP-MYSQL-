<?php
// pages/admin/referrals.php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get all referrals with details
$referrals = $db->query("SELECT r.*, 
                         referrer.name as referrer_name, referrer.phone as referrer_phone,
                         referred.name as referred_name, referred.phone as referred_phone
                         FROM referrals r
                         JOIN customers referrer ON r.referrer_customer_id = referrer.id
                         JOIN customers referred ON r.referred_customer_id = referred.id
                         ORDER BY r.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get top referrers
$top_referrers = $db->query("SELECT c.name, c.phone, c.referral_code, COUNT(r.id) as referral_count
                             FROM customers c
                             JOIN referrals r ON c.id = r.referrer_customer_id
                             WHERE r.status = 'Successful'
                             GROUP BY c.id
                             ORDER BY referral_count DESC
                             LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referrals</title>
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card h3 { font-size: 32px; margin: 0; }
        .stat-card p { margin: 5px 0 0; opacity: 0.9; }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .data-table th { background: var(--light-gray); font-weight: 600; }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
    </style>
</head>
<body>

<div class="admin-nav">
    <div><strong>Referral Management</strong></div>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="loyalty.php">Loyalty</a>
    </div>
</div>

<div class="container" style="max-width: 1000px; padding: 20px;">
    <h2>Referrals</h2>

    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo count($referrals); ?></h3>
            <p>Total Referrals</p>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));">
            <h3><?php echo count(array_filter($referrals, fn($r) => $r['status'] === 'Successful')); ?></h3>
            <p>Successful Referrals</p>
        </div>
    </div>

    <h3>Top Referrers</h3>
    <?php if (!empty($top_referrers)): ?>
    <table class="data-table" style="margin-bottom: 30px;">
        <thead>
            <tr>
                <th>Rank</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Referral Code</th>
                <th>Referrals</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($top_referrers as $i => $ref): ?>
            <tr>
                <td><?php echo $i + 1; ?></td>
                <td><strong><?php echo htmlspecialchars($ref['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($ref['phone']); ?></td>
                <td><code><?php echo htmlspecialchars($ref['referral_code']); ?></code></td>
                <td><span class="badge badge-success"><?php echo $ref['referral_count']; ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color: #666;">No referrals yet.</p>
    <?php endif; ?>

    <h3>All Referrals</h3>
    <?php if (!empty($referrals)): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Referrer</th>
                <th>Referred</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($referrals as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['referrer_name']); ?></td>
                <td><?php echo htmlspecialchars($r['referred_name']); ?></td>
                <td><span class="badge badge-success"><?php echo $r['status']; ?></span></td>
                <td><?php echo date('M d, Y', strtotime($r['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color: #666;">No referrals recorded yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
