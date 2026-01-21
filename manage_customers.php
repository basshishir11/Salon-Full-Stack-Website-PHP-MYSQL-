<?php
// pages/admin/manage_customers.php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get all customers with their visit history
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM tokens t WHERE t.customer_id = c.id AND t.status = 'Completed') as completed_visits,
          (SELECT MAX(created_at) FROM tokens t WHERE t.customer_id = c.id) as last_visit
          FROM customers c 
          ORDER BY c.created_at DESC";
$stmt = $db->query($query);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --admin-bg: #f8fafc;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        body {
            background-color: var(--admin-bg) !important;
        }

        .admin-nav {
            background: #1e293b;
            color: #fff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-nav a { 
            color: #cbd5e1; 
            margin-left: 20px; 
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .admin-nav a:hover { color: #fff; }

        .dashboard-container {
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
            flex-wrap: wrap;
            gap: 20px;
            border-left: 5px solid var(--primary-color);
        }

        .page-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }

        .search-container {
            position: relative;
            flex-grow: 1;
            max-width: 400px;
        }

        .search-box {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: #f1f5f9;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--primary-color);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(142, 118, 255, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }

        .table-card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .customer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .customer-table th {
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

        .customer-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #475569;
        }

        .customer-table tr:hover {
            background-color: #f8fafc;
        }

        .cust-main-info {
            display: flex;
            flex-direction: column;
        }

        .cust-name {
            font-weight: 600;
            color: #1e293b;
        }

        .visit-badge {
            background: #f0fdf4;
            color: #166534;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .ref-code {
            font-family: monospace;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 6px;
            color: #475569;
            font-size: 0.85rem;
        }

        .btn-history {
            background: var(--primary-color);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-history:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        @media (max-width: 992px) {
            .customer-table thead { display: none; }
            .customer-table td {
                display: block;
                text-align: right;
                padding: 10px 20px;
            }
            .customer-table td::before {
                content: attr(data-label);
                float: left;
                font-weight: 600;
                color: #64748b;
            }
            .customer-table tr {
                border-bottom: 2px solid #f1f5f9;
                padding: 15px 0;
                display: block;
            }
        }
    </style>
</head>
<body>

<div class="admin-nav">
    <div><strong>Customer Management</strong></div>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_rewards.php" style="color: #fbbf24;"><i class="fas fa-gift"></i> Rewards</a>
        <a href="../../ajax/logout.php" onclick="event.preventDefault(); fetch('../../ajax/logout.php').then(() => window.location.href='login.php');">Logout</a>
    </div>
</div>

<div class="dashboard-container">
    <div class="page-header">
        <h2>Customers (<?php echo count($customers); ?>)</h2>
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-box" id="searchBox" placeholder="Search by name or phone...">
        </div>
    </div>

    <div class="table-card">
        <table class="customer-table" id="customerTable">
            <thead>
                <tr>
                    <th>Customer Details</th>
                    <th>Engagement</th>
                    <th>Referral</th>
                    <th>Last Visit</th>
                    <th>Joined</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                <tr>
                    <td data-label="Customer">
                        <div class="cust-main-info">
                            <span class="cust-name"><?php echo htmlspecialchars($customer['name']); ?></span>
                            <span style="font-size: 0.85rem; color: #64748b;"><?php echo htmlspecialchars($customer['phone']); ?></span>
                        </div>
                    </td>
                    <td data-label="Engagement">
                        <span class="visit-badge">
                            <i class="fas fa-calendar-check"></i> <?php echo $customer['visit_count']; ?> Visits
                        </span>
                    </td>
                    <td data-label="Referral">
                        <span class="ref-code">
                            <?php echo htmlspecialchars($customer['referral_code'] ?? '-'); ?>
                        </span>
                    </td>
                    <td data-label="Last Visit">
                        <span style="font-size: 0.85rem;">
                            <?php echo $customer['last_visit'] ? date('M d, Y', strtotime($customer['last_visit'])) : 'Never'; ?>
                        </span>
                    </td>
                    <td data-label="Joined">
                        <span style="font-size: 0.85rem;">
                            <?php echo date('M d, Y', strtotime($customer['created_at'])); ?>
                        </span>
                    </td>
                    <td data-label="Action">
                        <a href="customer_history.php?id=<?php echo $customer['id']; ?>" class="btn-history">
                            <i class="fas fa-history"></i> History
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($customers)): ?>
        <div class="text-center py-5" style="color: #94a3b8;">
            <i class="fas fa-users-slash mb-3" style="font-size: 3rem;"></i>
            <p>No customers found in the system.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('searchBox').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#customerTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>

</body>
</html>

