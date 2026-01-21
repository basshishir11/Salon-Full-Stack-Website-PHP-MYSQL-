<?php
// pages/admin/manage_services.php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get all services
$query = "SELECT s.*, c.name as category_name 
          FROM services s 
          JOIN categories c ON s.category_id = c.id 
          ORDER BY s.gender_type, s.name";
$stmt = $db->query($query);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by gender
$men_services = array_filter($services, fn($s) => $s['gender_type'] === 'Men');
$women_services = array_filter($services, fn($s) => $s['gender_type'] === 'Women');
$unisex_services = array_filter($services, fn($s) => $s['gender_type'] === 'Unisex');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services</title>
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

        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .service-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
            border: 1px solid #f1f5f9;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .service-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
        }

        .service-body {
            padding: 20px;
            flex-grow: 1;
        }

        .service-name {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #1e293b;
        }

        .service-meta {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .price-tag {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
            display: block;
        }

        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(34, 197, 94, 0.9);
            color: white;
            backdrop-filter: blur(4px);
        }

        .status-badge.inactive {
            background: rgba(239, 68, 68, 0.9);
        }

        .service-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
            background: #f8fafc;
            padding: 15px 20px;
            border-top: 1px solid #f1f5f9;
        }

        .btn-action {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            min-height: auto;
        }

        .btn-edit { background: #eff6ff; color: #2563eb; }
        .btn-edit:hover { background: #dbeafe; }
        
        .btn-toggle { background: #f0fdf4; color: #16a34a; }
        .btn-toggle.inactive { background: #fff1f2; color: #e11d48; }

        .gender-section {
            margin-bottom: 50px;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 24px;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>

<div class="admin-nav">
    <div><strong>Service Management</strong></div>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_rewards.php" style="color: #fbbf24;"><i class="fas fa-gift"></i> Rewards</a>
        <a href="../../ajax/logout.php" onclick="event.preventDefault(); fetch('../../ajax/logout.php').then(() => window.location.href='login.php');">Logout</a>
    </div>
</div>

<div class="dashboard-container">
    <div class="page-header">
        <h2>Service Management</h2>
        <a href="add_service.php" class="btn btn-primary" style="min-height: auto; width: auto; padding: 12px 24px;">
            <i class="fas fa-plus"></i> Add New Service
        </a>
    </div>

    <div class="gender-section">
        <h3 class="section-title" style="color: var(--men-blue);">
            <i class="fas fa-mars"></i> Men's Services
        </h3>
        <div class="service-grid">
            <?php foreach ($men_services as $service): ?>
                <?php renderServiceCard($service); ?>
            <?php endforeach; ?>
            <?php if (empty($men_services)): ?>
                <div class="text-center py-4 text-muted w-100">No services found in this category.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="gender-section">
        <h3 class="section-title" style="color: var(--women-pink);">
            <i class="fas fa-venus"></i> Women's Services
        </h3>
        <div class="service-grid">
            <?php foreach ($women_services as $service): ?>
                <?php renderServiceCard($service); ?>
            <?php endforeach; ?>
            <?php if (empty($women_services)): ?>
                <div class="text-center py-4 text-muted w-100">No services found in this category.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="gender-section">
        <h3 class="section-title" style="color: #9333ea;">
            <i class="fas fa-venus-mars"></i> Unisex Services
        </h3>
        <div class="service-grid">
            <?php foreach ($unisex_services as $service): ?>
                <?php renderServiceCard($service); ?>
            <?php endforeach; ?>
            <?php if (empty($unisex_services)): ?>
                <div class="text-center py-4 text-muted w-100">No services found in this category.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleService(serviceId) {
    const btn = event.currentTarget;
    const icon = btn.querySelector('i');
    icon.className = 'fas fa-spinner fa-spin';
    
    fetch('../../ajax/toggle_service.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'service_id=' + serviceId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to toggle service');
            icon.className = 'fas fa-toggle-on';
        }
    });
}

function deleteService(serviceId) {
    if (!confirm('Are you sure you want to delete this service? This action cannot be undone.')) return;

    fetch('../../ajax/delete_service.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'service_id=' + serviceId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to delete service');
        }
    });
}
</script>

</body>
</html>

<?php
function renderServiceCard($service) {
    $activeClass = $service['is_active'] ? '' : 'inactive';
    $statusText = $service['is_active'] ? 'Active' : 'Inactive';
    $statusClass = $service['is_active'] ? '' : 'inactive';
    ?>
    <div class="service-card <?php echo $activeClass; ?>">
        <span class="status-badge <?php echo $statusClass; ?>">
            <i class="fas <?php echo $service['is_active'] ? 'fa-check' : 'fa-times'; ?>"></i> <?php echo $statusText; ?>
        </span>
        
        <?php if (!empty($service['image_path']) && file_exists('../../' . $service['image_path'])): ?>
            <img src="../../<?php echo htmlspecialchars($service['image_path']); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" class="service-image">
        <?php else: ?>
            <div class="service-image">
                <i class="fas fa-cut" style="font-size: 3rem;"></i>
            </div>
        <?php endif; ?>
        
        <div class="service-body">
            <div class="service-name"><?php echo htmlspecialchars($service['name']); ?></div>
            <div class="service-meta">
                <span><i class="far fa-clock"></i> <?php echo $service['duration_mins']; ?> min</span>
                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($service['category_name']); ?></span>
            </div>
            <span class="price-tag">NPR <?php echo number_format($service['price'], 0); ?></span>
        </div>

        <div class="service-actions">
            <a href="edit_service.php?id=<?php echo $service['id']; ?>" class="btn-action btn-edit">
                <i class="fas fa-edit"></i> Edit
            </a>
            <button class="btn-action btn-toggle <?php echo $statusClass; ?>" onclick="toggleService(<?php echo $service['id']; ?>)">
                <i class="fas fa-toggle-on"></i> Status
            </button>
            <button class="btn-action" style="background: #fee2e2; color: #ef4444;" onclick="deleteService(<?php echo $service['id']; ?>)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
    <?php
}
?>

