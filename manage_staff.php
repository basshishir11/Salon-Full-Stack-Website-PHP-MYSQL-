<?php
// pages/admin/manage_staff.php
require_once '../../includes/auth.php';
requireAdmin();

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
$message = '';
$error = '';

// Add new staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gender = $_POST['gender'] ?? 'Male';
    $specialty = trim($_POST['specialty'] ?? '');
    $commission = floatval($_POST['commission_percent'] ?? 0);
    $salary = floatval($_POST['salary'] ?? 0);
    $join_date = $_POST['join_date'] ?? date('Y-m-d');
    $services = $_POST['services'] ?? [];

    // Handle photo upload
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $photo = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], '../../uploads/staff/' . $photo);
        }
    }

    if ($name) {
        $stmt = $db->prepare("INSERT INTO staff (name, phone, email, photo, gender, specialty, commission_percent, salary, join_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $email, $photo, $gender, $specialty, $commission, $salary, $join_date]);
        $staffId = $db->lastInsertId();

        // Link services
        if (!empty($services)) {
            $serviceStmt = $db->prepare("INSERT INTO staff_services (staff_id, service_id) VALUES (?, ?)");
            foreach ($services as $serviceId) {
                $serviceStmt->execute([$staffId, $serviceId]);
            }
        }

        $message = 'Staff member added successfully!';
    } else {
        $error = 'Name is required.';
    }
}

// Update staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    $id = intval($_POST['staff_id']);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gender = $_POST['gender'] ?? 'Male';
    $specialty = trim($_POST['specialty'] ?? '');
    $commission = floatval($_POST['commission_percent'] ?? 0);
    $salary = floatval($_POST['salary'] ?? 0);
    $join_date = $_POST['join_date'] ?? null;
    $status = $_POST['status'] ?? 'Active';
    $services = $_POST['services'] ?? [];

    // Handle photo upload
    $photoSql = '';
    $photoParam = [];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $photo = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], '../../uploads/staff/' . $photo);
            $photoSql = ', photo = ?';
            $photoParam = [$photo];
        }
    }

    if ($name && $id) {
        $stmt = $db->prepare("UPDATE staff SET name = ?, phone = ?, email = ?, gender = ?, specialty = ?, commission_percent = ?, salary = ?, join_date = ?, status = ? $photoSql WHERE id = ?");
        $params = [$name, $phone, $email, $gender, $specialty, $commission, $salary, $join_date, $status];
        $params = array_merge($params, $photoParam, [$id]);
        $stmt->execute($params);

        // Update services
        $db->prepare("DELETE FROM staff_services WHERE staff_id = ?")->execute([$id]);
        if (!empty($services)) {
            $serviceStmt = $db->prepare("INSERT INTO staff_services (staff_id, service_id) VALUES (?, ?)");
            foreach ($services as $serviceId) {
                $serviceStmt->execute([$id, $serviceId]);
            }
        }

        $message = 'Staff member updated successfully!';
    }
}

// Delete staff
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $db->prepare("DELETE FROM staff WHERE id = ?")->execute([$id]);
    $message = 'Staff member deleted.';
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $db->prepare("UPDATE staff SET status = IF(status = 'Active', 'Inactive', 'Active') WHERE id = ?")->execute([$id]);
    header('Location: manage_staff.php');
    exit;
}

// Get all staff
$staffList = $db->query("SELECT * FROM staff ORDER BY status DESC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get all services for dropdown
$allServices = $db->query("SELECT id, name FROM services ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);


// Get staff services mapping
$staffServicesMap = [];
$ssResult = $db->query("SELECT staff_id, service_id FROM staff_services");
while ($row = $ssResult->fetch(PDO::FETCH_ASSOC)) {
    $staffServicesMap[$row['staff_id']][] = $row['service_id'];
}

// Get performance stats
$today = date('Y-m-d');
$thisMonth = date('Y-m');

// Staff performance data
$performanceData = [];
foreach ($staffList as $staff) {
    $sid = $staff['id'];
    
    // Today's completed tokens
    $todayCompleted = $db->prepare("SELECT COUNT(*) FROM tokens WHERE staff_id = ? AND DATE(completed_at) = ? AND status = 'Completed'");
    $todayCompleted->execute([$sid, $today]);
    
    // This month completed
    $monthCompleted = $db->prepare("SELECT COUNT(*) FROM tokens WHERE staff_id = ? AND DATE_FORMAT(completed_at, '%Y-%m') = ? AND status = 'Completed'");
    $monthCompleted->execute([$sid, $thisMonth]);
    
    // This month revenue (from revenue table if staff_id exists, else estimate from tokens)
    $monthRevenue = 0;
    try {
        $revStmt = $db->prepare("SELECT SUM(r.final_amount) FROM revenue r JOIN tokens t ON r.token_id = t.id WHERE t.staff_id = ? AND DATE_FORMAT(r.created_at, '%Y-%m') = ?");
        $revStmt->execute([$sid, $thisMonth]);
        $monthRevenue = $revStmt->fetchColumn() ?: 0;
    } catch (Exception $e) {}
    
    $performanceData[$sid] = [
        'today' => $todayCompleted->fetchColumn(),
        'month' => $monthCompleted->fetchColumn(),
        'revenue' => $monthRevenue,
        'commission' => $monthRevenue * ($staff['commission_percent'] / 100)
    ];
}

// Edit mode
$editStaff = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    foreach ($staffList as $s) {
        if ($s['id'] == $editId) {
            $editStaff = $s;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-box .number { font-size: 24px; font-weight: bold; color: var(--primary-color); }
        .stat-box .label { font-size: 11px; color: #666; margin-top: 3px; }
        
        .staff-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .staff-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.2s;
        }
        .staff-card:hover { transform: translateY(-3px); }
        .staff-card.inactive { opacity: 0.6; }
        
        .staff-header {
            background: linear-gradient(135deg, var(--primary-color), #6c5ce7);
            color: #fff;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .staff-header.female { background: linear-gradient(135deg, #e84393, #fd79a8); }
        
        .staff-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            overflow: hidden;
        }
        .staff-photo img { width: 100%; height: 100%; object-fit: cover; }
        
        .staff-info h3 { margin: 0; font-size: 16px; }
        .staff-info p { margin: 3px 0 0; font-size: 12px; opacity: 0.9; }
        
        .staff-body { padding: 15px; }
        .staff-detail { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
        .staff-detail:last-child { border-bottom: none; }
        .staff-detail .label { color: #666; }
        .staff-detail .value { font-weight: 600; color: #333; }
        
        .staff-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            padding: 12px 15px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
        }
        .staff-stat { text-align: center; }
        .staff-stat .num { font-size: 16px; font-weight: bold; color: var(--primary-color); }
        .staff-stat .lbl { font-size: 10px; color: #666; }
        
        .staff-actions {
            display: flex;
            gap: 8px;
            padding: 12px 15px;
            border-top: 1px solid #eee;
        }
        .staff-actions .btn { flex: 1; padding: 8px; font-size: 12px; min-height: auto; }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
        }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        
        .form-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .form-card h3 {
            margin: 0 0 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            font-size: 16px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        .form-group.full-width { grid-column: 1 / -1; }
        
        .services-checkboxes {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            max-height: 120px;
            overflow-y: auto;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .services-checkboxes label {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            background: #fff;
            border-radius: 15px;
            font-size: 12px;
            cursor: pointer;
            border: 1px solid #ddd;
            transition: all 0.2s;
        }
        .services-checkboxes label:hover { border-color: var(--primary-color); }
        .services-checkboxes input:checked + span { color: var(--primary-color); font-weight: 600; }
        
        .message { padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        .message.success { background: #d1fae5; color: #065f46; }
        .message.error { background: #fee2e2; color: #991b1b; }
        
        .btn-sm { padding: 6px 12px; font-size: 12px; min-height: auto; }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .empty-state i { font-size: 48px; color: #ddd; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="admin-nav">
        <div><strong><i class="fas fa-users-cog"></i> Staff Management</strong></div>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_tokens.php">Tokens</a>
            <a href="revenue_analytics.php">Analytics</a>
            <a href="../../ajax/logout.php" onclick="event.preventDefault(); fetch('../../ajax/logout.php').then(() => window.location.href='login.php')">Logout</a>
        </div>
    </div>

    <div class="container" style="max-width: 1100px; padding: 20px;">
        
        <?php if ($message): ?>
            <div class="message success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="number"><?php echo count(array_filter($staffList, fn($s) => $s['status'] === 'Active')); ?></div>
                <div class="label"><i class="fas fa-user-check"></i> Active Staff</div>
            </div>
            <div class="stat-box">
                <div class="number"><?php echo array_sum(array_column($performanceData, 'today')); ?></div>
                <div class="label"><i class="fas fa-calendar-day"></i> Today's Services</div>
            </div>
            <div class="stat-box">
                <div class="number"><?php echo array_sum(array_column($performanceData, 'month')); ?></div>
                <div class="label"><i class="fas fa-calendar"></i> This Month</div>
            </div>
            <div class="stat-box">
                <div class="number" style="color: #10b981;">Rs. <?php echo number_format(array_sum(array_column($performanceData, 'revenue'))); ?></div>
                <div class="label"><i class="fas fa-money-bill"></i> Month Revenue</div>
            </div>
        </div>

        <!-- Add/Edit Form -->
        <div class="form-card">
            <h3><i class="fas fa-<?php echo $editStaff ? 'edit' : 'plus-circle'; ?>"></i> <?php echo $editStaff ? 'Edit Staff Member' : 'Add New Staff Member'; ?></h3>
            <form method="post" enctype="multipart/form-data">
                <?php if ($editStaff): ?>
                    <input type="hidden" name="update_staff" value="1">
                    <input type="hidden" name="staff_id" value="<?php echo $editStaff['id']; ?>">
                <?php else: ?>
                    <input type="hidden" name="add_staff" value="1">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($editStaff['name'] ?? ''); ?>" required placeholder="e.g. Ram Sharma">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($editStaff['phone'] ?? ''); ?>" placeholder="e.g. 9800000000">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($editStaff['email'] ?? ''); ?>" placeholder="e.g. ram@email.com">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="Male" <?php echo ($editStaff['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($editStaff['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($editStaff['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Specialty</label>
                        <input type="text" name="specialty" value="<?php echo htmlspecialchars($editStaff['specialty'] ?? ''); ?>" placeholder="e.g. Hair Cutting, Facial">
                    </div>
                    <div class="form-group">
                        <label>Commission %</label>
                        <input type="number" name="commission_percent" step="0.01" min="0" max="100" value="<?php echo $editStaff['commission_percent'] ?? 0; ?>" placeholder="e.g. 10">
                    </div>
                    <div class="form-group">
                        <label>Monthly Salary (NPR)</label>
                        <input type="number" name="salary" min="0" value="<?php echo $editStaff['salary'] ?? 0; ?>" placeholder="e.g. 15000">
                    </div>
                    <div class="form-group">
                        <label>Join Date</label>
                        <input type="date" name="join_date" value="<?php echo $editStaff['join_date'] ?? date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Photo</label>
                        <input type="file" name="photo" accept="image/*">
                        <?php if (!empty($editStaff['photo'])): ?>
                            <small style="color: #666;">Current: <?php echo $editStaff['photo']; ?></small>
                        <?php endif; ?>
                    </div>
                    <?php if ($editStaff): ?>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="Active" <?php echo ($editStaff['status'] ?? '') === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($editStaff['status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="form-group full-width">
                        <label>Services They Can Perform</label>
                        <div class="services-checkboxes">
                            <?php 
                            $staffServiceIds = $staffServicesMap[$editStaff['id'] ?? 0] ?? [];
                            foreach ($allServices as $service): 
                            ?>
                                <label>
                                    <input type="checkbox" name="services[]" value="<?php echo $service['id']; ?>" 
                                        <?php echo in_array($service['id'], $staffServiceIds) ? 'checked' : ''; ?>>
                                    <span><?php echo htmlspecialchars($service['name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                            <?php if (empty($allServices)): ?>
                                <span style="color: #666; font-size: 12px;">No services found. Add services first.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $editStaff ? 'Update Staff' : 'Add Staff'; ?></button>
                    <?php if ($editStaff): ?>
                        <a href="manage_staff.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Staff List -->
        <div class="page-header">
            <h2 style="margin: 0; font-size: 18px;"><i class="fas fa-users"></i> Staff Members (<?php echo count($staffList); ?>)</h2>
        </div>

        <?php if (empty($staffList)): ?>
            <div class="empty-state">
                <i class="fas fa-user-plus"></i>
                <h3>No Staff Members Yet</h3>
                <p>Add your first staff member using the form above.</p>
            </div>
        <?php else: ?>
            <div class="staff-grid">
                <?php foreach ($staffList as $staff): ?>
                    <?php $perf = $performanceData[$staff['id']] ?? ['today' => 0, 'month' => 0, 'revenue' => 0, 'commission' => 0]; ?>
                    <div class="staff-card <?php echo $staff['status'] === 'Inactive' ? 'inactive' : ''; ?>">
                        <div class="staff-header <?php echo $staff['gender'] === 'Female' ? 'female' : ''; ?>">
                            <div class="staff-photo">
                                <?php if (!empty($staff['photo']) && file_exists('../../uploads/staff/' . $staff['photo'])): ?>
                                    <img src="../../uploads/staff/<?php echo $staff['photo']; ?>" alt="">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div class="staff-info">
                                <h3><?php echo htmlspecialchars($staff['name']); ?></h3>
                                <p><?php echo htmlspecialchars($staff['specialty'] ?: 'General'); ?></p>
                            </div>
                            <span class="status-badge <?php echo $staff['status'] === 'Active' ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $staff['status']; ?>
                            </span>
                        </div>
                        
                        <div class="staff-body">
                            <div class="staff-detail">
                                <span class="label"><i class="fas fa-phone"></i> Phone</span>
                                <span class="value"><?php echo htmlspecialchars($staff['phone'] ?: '-'); ?></span>
                            </div>
                            <div class="staff-detail">
                                <span class="label"><i class="fas fa-percent"></i> Commission</span>
                                <span class="value"><?php echo $staff['commission_percent']; ?>%</span>
                            </div>
                            <div class="staff-detail">
                                <span class="label"><i class="fas fa-wallet"></i> Salary</span>
                                <span class="value">Rs. <?php echo number_format($staff['salary']); ?></span>
                            </div>
                            <div class="staff-detail">
                                <span class="label"><i class="fas fa-calendar-alt"></i> Joined</span>
                                <span class="value"><?php echo $staff['join_date'] ? date('M Y', strtotime($staff['join_date'])) : '-'; ?></span>
                            </div>
                        </div>
                        
                        <div class="staff-stats">
                            <div class="staff-stat">
                                <div class="num"><?php echo $perf['today']; ?></div>
                                <div class="lbl">Today</div>
                            </div>
                            <div class="staff-stat">
                                <div class="num"><?php echo $perf['month']; ?></div>
                                <div class="lbl">This Month</div>
                            </div>
                            <div class="staff-stat">
                                <div class="num" style="color: #10b981;">Rs. <?php echo number_format($perf['commission']); ?></div>
                                <div class="lbl">Commission</div>
                            </div>
                        </div>
                        
                        <div class="staff-actions">
                            <a href="?edit=<?php echo $staff['id']; ?>" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i> Edit</a>
                            <a href="?toggle=<?php echo $staff['id']; ?>" class="btn btn-sm" style="background: <?php echo $staff['status'] === 'Active' ? '#fef3c7' : '#d1fae5'; ?>; color: <?php echo $staff['status'] === 'Active' ? '#92400e' : '#065f46'; ?>;">
                                <i class="fas fa-<?php echo $staff['status'] === 'Active' ? 'pause' : 'play'; ?>"></i>
                                <?php echo $staff['status'] === 'Active' ? 'Deactivate' : 'Activate'; ?>
                            </a>
                            <a href="?delete=<?php echo $staff['id']; ?>" class="btn btn-sm" style="background: #fee2e2; color: #991b1b;" onclick="return confirm('Delete this staff member?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
