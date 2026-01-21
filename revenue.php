<?php
// pages/admin/revenue.php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue & Analytics</title>
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
        }
        .admin-nav a { color: #fff; margin-left: 15px; text-decoration: underline; }
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }
        .chart-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            height: 250px;
        }
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-tab {
            padding: 8px 16px;
            border: 2px solid var(--primary-color);
            border-radius: 20px;
            background: white;
            color: var(--primary-color);
            cursor: pointer;
            font-weight: 600;
        }
        .filter-tab.active {
            background: var(--primary-color);
            color: white;
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-box h3 { margin: 0; font-size: 28px; color: var(--primary-color); }
        .stat-box p { margin: 5px 0 0; color: #666; font-size: 14px; }
    </style>
</head>
<body>

<div class="admin-nav">
    <div><strong>Revenue & Analytics</strong></div>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="../../ajax/logout.php" onclick="event.preventDefault(); fetch('../../ajax/logout.php').then(() => window.location.href='login.php');">Logout</a>
    </div>
</div>

<div class="container" style="max-width: 1000px; padding: 20px;">
    <h2>Revenue & Analytics</h2>

    <div class="filter-tabs">
        <button class="filter-tab active" data-range="7days">Weekly</button>
        <button class="filter-tab" data-range="30days">Monthly</button>
        <button class="filter-tab" data-range="year">Yearly</button>
    </div>

    <div class="stats-row">
        <div class="stat-box">
            <h3 id="totalTokens">-</h3>
            <p>Completed Tokens</p>
        </div>
        <div class="stat-box">
            <h3 id="avgPerDay">-</h3>
            <p>Average per Day</p>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-container">
            <h3 style="font-size: 16px; margin-bottom: 10px;">Revenue Trend</h3>
            <canvas id="revenueChart"></canvas>
        </div>
        <div class="chart-container">
            <h3 style="font-size: 16px; margin-bottom: 10px;">Popular Services</h3>
            <canvas id="servicesChart"></canvas>
        </div>
    </div>
</div>

<script>
let revenueChart = null;
let servicesChart = null;

// Tab switching
document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        loadData(this.dataset.range);
    });
});

function loadData(range) {
    fetch('../../ajax/get_revenue.php?range=' + range)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStats(data);
                renderRevenueChart(data.daily_data, range);
                renderServicesChart(data.popular_services);
            }
        });
}

function updateStats(data) {
    const totalCount = data.daily_data.reduce((sum, d) => sum + parseInt(d.token_count), 0);
    const totalRevenue = data.daily_data.reduce((sum, d) => sum + parseFloat(d.daily_revenue || 0), 0);
    const days = data.daily_data.length || 1;
    
    document.getElementById('totalTokens').innerHTML = `Rs. ${totalRevenue.toLocaleString()}<br><span style="font-size:16px; color:#666">${totalCount} Tokens</span>`;
    document.getElementById('avgPerDay').innerHTML = `Rs. ${(totalRevenue / days).toFixed(0)}<br><span style="font-size:16px; color:#666">Daily Avg</span>`;
}

function renderRevenueChart(dailyData, range) {
    let labels = [];
    let values = [];

    if (range === 'year') {
        // Show last 12 months
        for (let i = 11; i >= 0; i--) {
            const date = new Date();
            date.setMonth(date.getMonth() - i);
            const monthStr = date.toISOString().split('T')[0].substring(0, 7); // YYYY-MM
            labels.push(date.toLocaleDateString('en-US', { month: 'short' }));
            
            const found = dailyData.find(d => d.date === monthStr);
            values.push(found ? parseFloat(found.daily_revenue) : 0);
        }
    } else {
        const days = range === '30days' ? 30 : 7;
        for (let i = days - 1; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            const dateStr = date.toISOString().split('T')[0];
            labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
            
            const found = dailyData.find(d => d.date === dateStr);
            values.push(found ? parseFloat(found.daily_revenue || 0) : 0);
        }
    }

    if (revenueChart) revenueChart.destroy();

    const ctx = document.getElementById('revenueChart').getContext('2d');
    revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue (Rs.)',
                data: values,
                borderColor: '#8E76FF',
                backgroundColor: 'rgba(142, 118, 255, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function renderServicesChart(services) {
    if (servicesChart) servicesChart.destroy();

    const labels = services.map(s => s.name);
    const values = services.map(s => parseInt(s.count));

    const ctx = document.getElementById('servicesChart').getContext('2d');
    servicesChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: [
                    'rgba(108, 99, 255, 0.8)',
                    'rgba(255, 107, 107, 0.8)',
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(74, 144, 217, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// Initial load
loadData('7days');
</script>

</body>
</html>
