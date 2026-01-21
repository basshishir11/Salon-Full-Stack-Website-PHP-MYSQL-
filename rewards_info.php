<?php
// pages/rewards_info.php - Customer facing rewards info page
require_once '../includes/header.php'; 
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get milestones
$milestones = $db->query("SELECT * FROM loyalty_milestones ORDER BY visits_required")->fetchAll(PDO::FETCH_ASSOC);

// Check if customer phone is provided to show their progress
$phone = $_GET['phone'] ?? '';
$customer = null;
$rewards = [];

if (!empty($phone)) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE phone = ?");
    $stmt->execute([$phone]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        $r_stmt = $db->prepare("SELECT * FROM rewards WHERE customer_id = ? ORDER BY created_at DESC");
        $r_stmt->execute([$customer['id']]);
        $rewards = $r_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<style>
.loyalty-hero {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    padding: 30px 20px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 30px;
}
.loyalty-hero h2 { margin: 0 0 10px; }
.milestone-list {
    display: grid;
    gap: 15px;
}
.milestone-item {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}
.milestone-number {
    width: 50px;
    height: 50px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
}
.milestone-item.achieved .milestone-number {
    background: var(--success-color);
}
.progress-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.progress-bar {
    background: #eee;
    height: 10px;
    border-radius: 5px;
    overflow: hidden;
    margin: 10px 0;
}
.progress-fill {
    background: var(--primary-color);
    height: 100%;
    transition: width 0.3s;
}
.search-form {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}
.search-form input {
    flex: 1;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
}
.reward-card {
    background: #fff3cd;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 10px;
    border-left: 4px solid #ffc107;
}
.reward-card.claimed {
    background: #d4edda;
    border-left-color: #28a745;
}
</style>

<div class="loyalty-hero">
    <h2><i class="fas fa-gift"></i> Loyalty Rewards</h2>
    <p>Earn rewards with every visit!</p>
</div>

<form class="search-form" method="GET">
    <input type="tel" name="phone" placeholder="Enter your phone number" value="<?php echo htmlspecialchars($phone); ?>">
    <button type="submit" class="btn btn-primary" style="width: auto;">Check</button>
</form>

<?php if ($customer): ?>
<div class="progress-section">
    <h3>Welcome, <?php echo htmlspecialchars($customer['name']); ?>!</h3>
    <p>You have <strong><?php echo $customer['visit_count']; ?></strong> visits.</p>
    
    <?php
    $effective_visits = (($customer['visit_count'] - 1) % 12) + 1;
    $next_milestone = null;
    foreach ($milestones as $m) {
        if ($m['visits_required'] > $effective_visits) {
            $next_milestone = $m;
            break;
        }
    }
    if ($next_milestone):
    ?>
    <p style="color: #666;">Next reward at <?php echo $next_milestone['visits_required']; ?> visits: <?php echo $next_milestone['reward_type']; ?></p>
    <div class="progress-bar">
        <div class="progress-fill" style="width: <?php echo ($effective_visits / $next_milestone['visits_required']) * 100; ?>%"></div>
    </div>
    <?php endif; ?>
    
    <p style="margin-top: 10px; font-size: 14px;">Your referral code: <code style="background: #eee; padding: 5px 10px; border-radius: 4px;"><?php echo htmlspecialchars($customer['referral_code']); ?></code></p>
</div>

<?php if (!empty($rewards)): ?>
<h3>Your Rewards</h3>
<?php foreach ($rewards as $r): ?>
<div class="reward-card <?php echo $r['status'] === 'Claimed' ? 'claimed' : ''; ?>">
    <strong><?php echo htmlspecialchars($r['reward_type']); ?></strong>
    <span style="float: right; font-size: 12px;"><?php echo $r['status']; ?></span>
    <p style="margin: 5px 0 0; font-size: 14px; color: #666;"><?php echo htmlspecialchars($r['description']); ?></p>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php elseif (!empty($phone)): ?>
<div class="card" style="text-align: center;">
    <p>No customer found with this phone number.</p>
    <a href="../pages/book_step1.php" class="btn btn-primary">Book your first appointment</a>
</div>
<?php endif; ?>

<h3 style="margin-top: 30px;">How It Works</h3>
<div class="milestone-list">
    <?php foreach ($milestones as $m): ?>
    <div class="milestone-item <?php echo ($customer && $customer['visit_count'] >= $m['visits_required']) ? 'achieved' : ''; ?>">
        <div class="milestone-number"><?php echo $m['visits_required']; ?></div>
        <div>
            <strong><?php echo htmlspecialchars($m['reward_type']); ?></strong>
            <p style="margin: 0; font-size: 14px; color: #666;"><?php echo htmlspecialchars($m['description']); ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="text-center mt-3">
    <a href="../index.php" class="btn btn-secondary">Back to Home</a>
</div>

<?php require_once '../includes/footer.php'; ?>
