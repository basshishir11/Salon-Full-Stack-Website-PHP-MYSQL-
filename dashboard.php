<?php
// pages/admin/dashboard.php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/database.php';


$database = new Database();
$db = $database->getConnection();
$today = date('Y-m-d');


// Get today's stats
$boys_waiting = $db->query("SELECT COUNT(*) FROM tokens WHERE DATE(created_at) = '$today' AND gender = 'Men' AND status = 'Waiting'")->fetchColumn();
$girls_waiting = $db->query("SELECT COUNT(*) FROM tokens WHERE DATE(created_at) = '$today' AND gender = 'Women' AND status = 'Waiting'")->fetchColumn();
$completed_today = $db->query("SELECT COUNT(*) FROM tokens WHERE DATE(completed_at) = '$today' AND status = 'Completed'")->fetchColumn();
$cancelled_today = $db->query("SELECT COUNT(*) FROM tokens WHERE DATE(created_at) = '$today' AND status = 'Cancelled'")->fetchColumn();
$new_customers = $db->query("SELECT COUNT(*) FROM customers WHERE DATE(created_at) = '$today'")->fetchColumn();
$total_tokens_today = $db->query("SELECT COUNT(*) FROM tokens WHERE DATE(created_at) = '$today'")->fetchColumn();


// Get real revenue from today
$estimated_revenue = $db->query("SELECT SUM(final_amount) FROM revenue WHERE DATE(created_at) = '$today'")->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-nav {
            background: var(--dark-gray);
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-nav a { color: #fff; margin-left: 15px; text-decoration: underline; }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number { font-size: 28px; font-weight: bold; color: var(--primary-color); }
        .stat-label { font-size: 13px; color: #666; margin-top: 5px; }
        .stat-card.boys .stat-number { color: var(--men-blue); }
        .stat-card.girls .stat-number { color: var(--women-pink); }
        .stat-card.success .stat-number { color: var(--success-color); }
        .stat-card.revenue .stat-number { color: #28a745; }
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        .quick-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s;
        }
        .quick-link:hover { transform: translateY(-2px); }
        .quick-link i { font-size: 24px; color: var(--primary-color); }
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .chart-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            height: 220px;
        }
        .chart-container h3 { font-size: 15px; margin-bottom: 10px; color: #333; }
    </style>
</head>
<body>


<div class="admin-nav">
    <div><strong>Admin Panel</strong></div>
    <div>
        <a href="manage_promotions.php" style="margin-right: 15px; color: #8e76ff; font-weight: 600;"><i class="fas fa-tags"></i> Offers</a>
        <a href="manage_blog.php" style="margin-right: 15px; color: #4a90d9; font-weight: 600;"><i class="fas fa-newspaper"></i> Blog</a>
        <a href="manage_rewards.php" style="margin-right: 15px; color: #d97706; font-weight: 600;"><i class="fas fa-gift"></i> Rewards</a>
        <span><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
        <a href="../../ajax/logout.php" onclick="event.preventDefault(); fetch('../../ajax/logout.php').then(() => window.location.href='login.php');"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>


<div class="container admin-container" style="max-width: 1200px; padding: 20px; background: transparent;">
    <h2>Dashboard</h2>
    <p style="color: #666; margin-bottom: 20px;">Today: <?php echo date('l, F j, Y'); ?></p>


    <div class="dashboard-grid">
        <div class="stat-card boys">
            <div class="stat-number"><?php echo $boys_waiting; ?></div>
            <div class="stat-label"><i class="fas fa-mars"></i> Boys Waiting</div>
        </div>
        <div class="stat-card girls">
            <div class="stat-number"><?php echo $girls_waiting; ?></div>
            <div class="stat-label"><i class="fas fa-venus"></i> Girls Waiting</div>
        </div>
        <div class="stat-card success">
            <div class="stat-number"><?php echo $completed_today; ?></div>
            <div class="stat-label"><i class="fas fa-check"></i> Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_tokens_today; ?></div>
            <div class="stat-label"><i class="fas fa-ticket-alt"></i> Total Tokens</div>
        </div>
        <div class="stat-card revenue">
            <div class="stat-number">Rs. <?php echo number_format($estimated_revenue); ?></div>
            <div class="stat-label"><i class="fas fa-money-bill"></i> Est. Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $new_customers; ?></div>
            <div class="stat-label"><i class="fas fa-user-plus"></i> New Customers</div>
        </div>
    </div>


    <h3>Quick Actions</h3>
    <div class="quick-links">
        <a href="manage_tokens.php" class="quick-link">
            <i class="fas fa-ticket-alt"></i>
            <span>Manage Tokens</span>
        </a>
        <a href="manage_services.php" class="quick-link">
            <i class="fas fa-cut"></i>
            <span>Services</span>
        </a>
        <a href="manage_customers.php" class="quick-link">
            <i class="fas fa-users"></i>
            <span>Customers</span>
        </a>
        <a href="manage_rewards.php" class="quick-link" style="background: #fffbeb; border-color: #fde68a;">
            <i class="fas fa-gift" style="color: #d97706;"></i>
            <span style="color: #92400e;">Manage Rewards</span>
        </a>
        <a href="manage_promotions.php" class="quick-link">
            <i class="fas fa-percentage" style="color: #8e76ff;"></i>
            <span>Offers/Promo</span>
        </a>
        <a href="manage_blog.php" class="quick-link">
            <i class="fas fa-newspaper" style="color: #4a90d9;"></i>
            <span>Blog/News</span>
        </a>
        <a href="revenue.php" class="quick-link">
            <i class="fas fa-chart-line"></i>
            <span>Revenue</span>
        </a>
        <!-- NEW: Revenue Analytics Link -->
        <a href="revenue_analytics.php" class="quick-link" style="background: #ecfdf5; border-color: #a7f3d0;">
            <i class="fas fa-chart-bar" style="color: #10b981;"></i>
            <span style="color: #065f46;">Revenue Analytics</span>
        </a>
        <a href="settings.php" class="quick-link">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        // Staff management link  --->
        <a href="manage_staff.php" class="quick-link" style="background: #ede9fe; border-color: #c4b5fd;">
    <i class="fas fa-users-cog" style="color: #7c3aed;"></i>
    <span style="color: #5b21b6;">Staff Management</span>
</a>

    </div>


    <div class="chart-grid">
        <div class="chart-container">
            <h3><i class="fas fa-chart-line"></i> Revenue Trend (7 Days)</h3>
            <canvas id="revenueChart"></canvas>
        </div>
        <div class="chart-container">
            <h3><i class="fas fa-pie-chart"></i> Service Distribution</h3>
            <canvas id="servicesChart"></canvas>
        </div>
    </div>
</div>


<script>
// Fetch and render charts
fetch('../../ajax/get_revenue.php?range=7days')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderRevenueChart(data.daily_data);
            renderServicesChart(data.popular_services);
        }
    });


function renderRevenueChart(dailyData) {
    const labels = [];
    const values = [];
    
    // Fill in last 7 days
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        const dateStr = date.toISOString().split('T')[0];
        labels.push(date.toLocaleDateString('en-US', { weekday: 'short' }));
        
        const found = dailyData.find(d => d.date === dateStr);
        values.push(found ? parseFloat(found.daily_revenue || 0) : 0);
    }


    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue (Rs.)',
                data: values,
                borderColor: '#8E76FF',
                backgroundColor: 'rgba(142, 118, 255, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { font: { size: 10 } } },
                x: { ticks: { font: { size: 10 } } }
            }
        }
    });
}


function renderServicesChart(services) {
    const labels = services.map(s => s.name);
    const values = services.map(s => parseInt(s.count));


    const ctx = document.getElementById('servicesChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: [
                    '#8E76FF', '#FF6B6B', '#28A745', '#FFC107', '#4A90D9'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'right',
                    labels: { boxWidth: 10, font: { size: 10 } }
                }
            }
        }
    });
}
</script>


</body>
</html>
