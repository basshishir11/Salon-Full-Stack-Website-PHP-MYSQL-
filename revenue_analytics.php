<?php
// pages/admin/revenue_analytics.php
require_once '../../includes/auth.php';
requireAdmin();

require_once '../../config/database.php';
require_once '../../includes/settings_helper.php';

$database = new Database();
$db = $database->getConnection();

// Handle new offline revenue form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_offline_revenue'])) {
    $date   = $_POST['date'] ?? date('Y-m-d');
    $amount = floatval($_POST['amount'] ?? 0);
    $service = trim($_POST['service'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $notes  = trim($_POST['notes'] ?? '');

    if ($amount > 0) {
        $stmt = $db->prepare("INSERT INTO offline_revenue (date, amount, service, payment_method, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$date, $amount, $service, $payment_method, $notes]);
        $success = true;
    }
}

// Track active tab from URL
$activeTab = $_GET['tab'] ?? 'add';

// Date range for charts (default: this month)
$startDate = $_GET['start'] ?? date('Y-m-01');
$endDate   = $_GET['end'] ?? date('Y-m-t');

// For year comparison
$year1 = $_GET['year1'] ?? date('Y');
$year2 = $_GET['year2'] ?? (date('Y') - 1);

// Get offline revenue grouped by date
$offlineStmt = $db->prepare("
    SELECT date, SUM(amount) AS total_offline
    FROM offline_revenue
    WHERE date BETWEEN ? AND ?
    GROUP BY date
    ORDER BY date ASC
");
$offlineStmt->execute([$startDate, $endDate]);
$offlineRows = $offlineStmt->fetchAll(PDO::FETCH_ASSOC);

// Get online revenue from existing revenue table (if exists)
$onlineRows = [];
try {
    $onlineStmt = $db->prepare("
        SELECT DATE(created_at) as date, SUM(final_amount) AS total_online
        FROM revenue
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $onlineStmt->execute([$startDate, $endDate]);
    $onlineRows = $onlineStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Build chart data
$labels = [];
$offlineData = [];
$onlineData = [];

$period = new DatePeriod(
    new DateTime($startDate),
    new DateInterval('P1D'),
    (new DateTime($endDate))->modify('+1 day')
);

$offlineByDate = [];
foreach ($offlineRows as $row) {
    $offlineByDate[$row['date']] = (float)$row['total_offline'];
}
$onlineByDate = [];
foreach ($onlineRows as $row) {
    $onlineByDate[$row['date']] = (float)$row['total_online'];
}

foreach ($period as $dt) {
    $d = $dt->format('Y-m-d');
    $labels[]      = $dt->format('M j');
    $offlineData[] = $offlineByDate[$d] ?? 0;
    $onlineData[]  = $onlineByDate[$d] ?? 0;
}

// Totals
$totalOffline = array_sum($offlineData);
$totalOnline  = array_sum($onlineData);
$totalRevenue = $totalOffline + $totalOnline;

// Get recent offline entries for table
$recentStmt = $db->prepare("SELECT * FROM offline_revenue ORDER BY date DESC, id DESC LIMIT 20");
$recentStmt->execute();
$recentEntries = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

// Year comparison data (monthly totals)
$year1Data = [];
$year2Data = [];
try {
    $y1Stmt = $db->prepare("
        SELECT MONTH(date) as month, SUM(amount) as total
        FROM offline_revenue
        WHERE YEAR(date) = ?
        GROUP BY MONTH(date)
    ");
    $y1Stmt->execute([$year1]);
    foreach ($y1Stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $year1Data[(int)$row['month']] = (float)$row['total'];
    }
    
    $y2Stmt = $db->prepare("
        SELECT MONTH(date) as month, SUM(amount) as total
        FROM offline_revenue
        WHERE YEAR(date) = ?
        GROUP BY MONTH(date)
    ");
    $y2Stmt->execute([$year2]);
    foreach ($y2Stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $year2Data[(int)$row['month']] = (float)$row['total'];
    }
} catch (Exception $e) {}

$monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$year1Monthly = [];
$year2Monthly = [];
for ($m = 1; $m <= 12; $m++) {
    $year1Monthly[] = $year1Data[$m] ?? 0;
    $year2Monthly[] = $year2Data[$m] ?? 0;
}

// Check if we have 2+ years of data for comparison
$yearsAvailable = $db->query("SELECT DISTINCT YEAR(date) as y FROM offline_revenue ORDER BY y DESC")->fetchAll(PDO::FETCH_COLUMN);
$canCompare = count($yearsAvailable) >= 2;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Analytics</title>
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
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin: 15px 0;
        }
        .stat-card {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number { font-size: 22px; font-weight: bold; color: var(--primary-color); }
        .stat-label { font-size: 11px; color: #666; margin-top: 3px; }
        .stat-card.offline .stat-number { color: #ef4444; }
        .stat-card.online .stat-number { color: #3b82f6; }
        .stat-card.total .stat-number { color: #10b981; }
        
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 12px;
            margin: 15px 0;
        }
        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            height: 200px;
        }
        .chart-container.medium { height: 220px; }
        .chart-container.small { height: 180px; }
        .chart-container h3 { font-size: 13px; margin-bottom: 8px; color: #333; }
        
        .settings-form {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 4px; font-weight: 600; font-size: 13px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 13px;
        }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--primary-color); }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
        }
        .section-title {
            font-size: 15px;
            font-weight: 600;
            margin: 20px 0 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--primary-color);
        }
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        .filter-bar {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
        }
        .filter-bar .form-group { margin-bottom: 0; }
        .filter-bar input, .filter-bar select { padding: 6px 8px; font-size: 12px; }
        .filter-bar .btn { padding: 6px 12px; font-size: 12px; min-height: auto; }
        
        .data-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .data-table th, .data-table td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        .data-table th { background: #f8f9fa; font-weight: 600; }
        .data-table tr:hover { background: #f8f9fa; }
        
        .tabs { display: flex; gap: 5px; margin-bottom: 15px; border-bottom: 2px solid #eee; flex-wrap: wrap; }
        .tab {
            padding: 8px 14px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 13px;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        .tab.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .btn-sm { padding: 6px 12px; font-size: 12px; min-height: auto; }
    </style>
</head>
<body>
    <div class="admin-nav">
        <div><strong><i class="fas fa-chart-bar"></i> Revenue Analytics</strong></div>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="revenue.php">Quick Revenue</a>
            <a href="../../ajax/logout.php" onclick="event.preventDefault(); fetch('../../ajax/logout.php').then(() => window.location.href='login.php')">Logout</a>
        </div>
    </div>

    <div class="container admin-container" style="max-width: 1000px; padding: 15px; background: transparent;">
        
        <h2 style="font-size: 20px; margin-bottom: 5px;">Revenue Analytics</h2>
        <p style="color: #666; margin-bottom: 15px; font-size: 13px;">Track online + offline revenue, compare years, and see trends.</p>

        <?php if (isset($success)): ?>
            <div class="success-msg"><i class="fas fa-check-circle"></i> Offline revenue added!</div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="dashboard-grid">
            <div class="stat-card offline">
                <div class="stat-number">Rs. <?php echo number_format($totalOffline); ?></div>
                <div class="stat-label"><i class="fas fa-store"></i> Offline</div>
            </div>
            <div class="stat-card online">
                <div class="stat-number">Rs. <?php echo number_format($totalOnline); ?></div>
                <div class="stat-label"><i class="fas fa-globe"></i> Online</div>
            </div>
            <div class="stat-card total">
                <div class="stat-number">Rs. <?php echo number_format($totalRevenue); ?></div>
                <div class="stat-label"><i class="fas fa-coins"></i> Total</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($recentEntries); ?></div>
                <div class="stat-label"><i class="fas fa-receipt"></i> Entries</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab <?php echo $activeTab === 'add' ? 'active' : ''; ?>" onclick="switchTab('add')"><i class="fas fa-plus"></i> Add</button>
            <button class="tab <?php echo $activeTab === 'charts' ? 'active' : ''; ?>" onclick="switchTab('charts')"><i class="fas fa-chart-line"></i> Charts</button>
            <button class="tab <?php echo $activeTab === 'compare' ? 'active' : ''; ?>" onclick="switchTab('compare')"><i class="fas fa-balance-scale"></i> Compare</button>
            <button class="tab <?php echo $activeTab === 'history' ? 'active' : ''; ?>" onclick="switchTab('history')"><i class="fas fa-history"></i> History</button>
        </div>

        <!-- Add Revenue Tab -->
        <div id="tab-add" class="tab-content <?php echo $activeTab === 'add' ? 'active' : ''; ?>">
            <div class="settings-form">
                <h3 class="section-title"><i class="fas fa-plus-circle"></i> Add Offline Revenue</h3>
                <form method="post">
                    <input type="hidden" name="add_offline_revenue" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Amount (NPR)</label>
                            <input type="number" name="amount" step="0.01" min="0" placeholder="e.g. 1500" required>
                        </div>
                        <div class="form-group">
                            <label>Service</label>
                            <input type="text" name="service" placeholder="e.g. Haircut">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Payment</label>
                            <select name="payment_method">
                                <option value="cash">Cash</option>
                                <option value="qr">QR</option>
                                <option value="card">Card</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <input type="text" name="notes" placeholder="Optional">
                        </div>
                        <div class="form-group" style="align-self: flex-end;">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Charts Tab -->
        <div id="tab-charts" class="tab-content <?php echo $activeTab === 'charts' ? 'active' : ''; ?>">
            <div class="filter-bar">
                <form method="get" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;">
                    <input type="hidden" name="tab" value="charts">
                    <div class="form-group">
                        <label>From</label>
                        <input type="date" name="start" value="<?php echo htmlspecialchars($startDate); ?>">
                    </div>
                    <div class="form-group">
                        <label>To</label>
                        <input type="date" name="end" value="<?php echo htmlspecialchars($endDate); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button>
                    <a href="?tab=charts&start=<?php echo date('Y-m-01'); ?>&end=<?php echo date('Y-m-t'); ?>" class="btn btn-secondary btn-sm">This Month</a>
                    <a href="?tab=charts&start=<?php echo date('Y-01-01'); ?>&end=<?php echo date('Y-12-31'); ?>" class="btn btn-secondary btn-sm">This Year</a>
                </form>
            </div>

            <div class="chart-grid">
                <div class="chart-container medium" style="grid-column: 1 / -1;">
                    <h3><i class="fas fa-chart-bar"></i> Daily Revenue (Online + Offline)</h3>
                    <canvas id="revenueChart"></canvas>
                </div>
                <div class="chart-container small">
                    <h3><i class="fas fa-pie-chart"></i> Distribution</h3>
                    <canvas id="pieChart"></canvas>
                </div>
                <div class="chart-container small">
                    <h3><i class="fas fa-chart-line"></i> Trend</h3>
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Compare Years Tab -->
        <div id="tab-compare" class="tab-content <?php echo $activeTab === 'compare' ? 'active' : ''; ?>">
            <?php if ($canCompare): ?>
            <div class="filter-bar">
                <form method="get" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;">
                    <input type="hidden" name="tab" value="compare">
                    <div class="form-group">
                        <label>Year 1</label>
                        <select name="year1">
                            <?php foreach ($yearsAvailable as $y): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $year1 ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Year 2</label>
                        <select name="year2">
                            <?php foreach ($yearsAvailable as $y): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $year2 ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-sync"></i> Compare</button>
                </form>
            </div>
            <div class="chart-container medium">
                <h3><i class="fas fa-balance-scale"></i> <?php echo $year1; ?> vs <?php echo $year2; ?></h3>
                <canvas id="compareChart"></canvas>
            </div>
            <?php else: ?>
            <div class="settings-form" style="text-align: center; padding: 30px;">
                <i class="fas fa-calendar-alt" style="font-size: 36px; color: #ddd; margin-bottom: 10px;"></i>
                <h3 style="font-size: 16px;">Not Enough Data</h3>
                <p style="color: #666; font-size: 13px;">Need at least 2 years of data to compare.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- History Tab -->
        <div id="tab-history" class="tab-content <?php echo $activeTab === 'history' ? 'active' : ''; ?>">
            <div class="settings-form">
                <h3 class="section-title"><i class="fas fa-history"></i> Recent Entries</h3>
                <?php if (empty($recentEntries)): ?>
                    <p style="color: #666; text-align: center; padding: 20px; font-size: 13px;">No entries yet.</p>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Service</th>
                            <th>Payment</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentEntries as $entry): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($entry['date'])); ?></td>
                            <td><strong>Rs. <?php echo number_format($entry['amount'], 2); ?></strong></td>
                            <td><?php echo htmlspecialchars($entry['service'] ?? '-'); ?></td>
                            <td><?php echo ucfirst($entry['payment_method'] ?? 'cash'); ?></td>
                            <td><?php echo htmlspecialchars($entry['notes'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script>
    function switchTab(tabName) {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        event.currentTarget.classList.add('active');
        document.getElementById('tab-' + tabName).classList.add('active');
    }

    const labels = <?php echo json_encode($labels); ?>;
    const offlineData = <?php echo json_encode($offlineData); ?>;
    const onlineData = <?php echo json_encode($onlineData); ?>;

    // Stacked Bar Chart
    new Chart(document.getElementById('revenueChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: 'Offline', data: offlineData, backgroundColor: 'rgba(239, 68, 68, 0.7)' },
                { label: 'Online', data: onlineData, backgroundColor: 'rgba(59, 130, 246, 0.7)' }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 12, font: { size: 10 } } } },
            scales: { x: { stacked: true, ticks: { font: { size: 9 } } }, y: { stacked: true, beginAtZero: true, ticks: { font: { size: 9 } } } }
        }
    });

    // Pie Chart
    new Chart(document.getElementById('pieChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Offline', 'Online'],
            datasets: [{ data: [<?php echo $totalOffline; ?>, <?php echo $totalOnline; ?>], backgroundColor: ['#ef4444', '#3b82f6'] }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } }
        }
    });

    // Line Chart
    new Chart(document.getElementById('lineChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total',
                data: labels.map((_, i) => offlineData[i] + onlineData[i]),
                borderColor: '#8E76FF',
                backgroundColor: 'rgba(142, 118, 255, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { ticks: { font: { size: 9 } } }, y: { beginAtZero: true, ticks: { font: { size: 9 } } } }
        }
    });

    <?php if ($canCompare): ?>
    new Chart(document.getElementById('compareChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($monthLabels); ?>,
            datasets: [
                { label: '<?php echo $year1; ?>', data: <?php echo json_encode($year1Monthly); ?>, backgroundColor: 'rgba(142, 118, 255, 0.7)' },
                { label: '<?php echo $year2; ?>', data: <?php echo json_encode($year2Monthly); ?>, backgroundColor: 'rgba(255, 107, 107, 0.7)' }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { boxWidth: 12, font: { size: 10 } } } },
            scales: { y: { beginAtZero: true, ticks: { font: { size: 9 } } }, x: { ticks: { font: { size: 9 } } } }
        }
    });
    <?php endif; ?>
    </script>
</body>
</html>
