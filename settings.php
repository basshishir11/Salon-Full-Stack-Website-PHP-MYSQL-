<?php
// pages/admin/settings.php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['shop_name', 'shop_address', 'shop_email', 'phone_call', 'phone_whatsapp', 'whatsapp_message', 'avg_service_duration', 'notify_before_mins', 'map_embed'];
    
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $value = $_POST[$key];
            $stmt = $db->prepare("INSERT INTO settings (`key`, `value`) VALUES (:key, :value) ON DUPLICATE KEY UPDATE `value` = :value2");
            $stmt->execute(['key' => $key, 'value' => $value, 'value2' => $value]);
        }
    }
    
    $success = true;
}

// Get current settings
$settings = [];
$result = $db->query("SELECT * FROM settings");
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key']] = $row['value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
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
        .settings-form {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin: 30px 0 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="admin-nav">
    <div><strong>Settings</strong></div>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="../../ajax/logout.php" onclick="event.preventDefault(); fetch('../../ajax/logout.php').then(() => window.location.href='login.php');">Logout</a>
    </div>
</div>

<div class="container" style="max-width: 800px; padding: 20px;">
    <h2>Shop Settings</h2>

    <?php if (isset($success)): ?>
    <div class="success-msg">
        <i class="fas fa-check-circle"></i> Settings saved successfully!
    </div>
    <?php endif; ?>

    <form method="POST" class="settings-form">
        <h3 class="section-title"><i class="fas fa-store"></i> Shop Information</h3>
        
        <div class="form-group">
            <label>Shop Name</label>
            <input type="text" name="shop_name" value="<?php echo htmlspecialchars($settings['shop_name'] ?? 'Sharma Salon & Spa'); ?>">
        </div>

        <div class="form-group">
            <label>Address</label>
            <input type="text" name="shop_address" value="<?php echo htmlspecialchars($settings['shop_address'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="shop_email" value="<?php echo htmlspecialchars($settings['shop_email'] ?? ''); ?>">
        </div>

        <h3 class="section-title"><i class="fas fa-phone"></i> Contact</h3>

        <div class="form-group">
            <label>Phone (for calls)</label>
            <input type="text" name="phone_call" value="<?php echo htmlspecialchars($settings['phone_call'] ?? ''); ?>" placeholder="+977-9800000000">
        </div>

        <div class="form-group">
            <label>WhatsApp Number</label>
            <input type="text" name="phone_whatsapp" value="<?php echo htmlspecialchars($settings['phone_whatsapp'] ?? ''); ?>" placeholder="9779800000000">
        </div>

        <div class="form-group">
            <label>Default WhatsApp Message</label>
            <textarea name="whatsapp_message" rows="2"><?php echo htmlspecialchars($settings['whatsapp_message'] ?? 'Hello, I want to book a token.'); ?></textarea>
        </div>

        <h3 class="section-title"><i class="fas fa-bell"></i> Notifications</h3>

        <div class="form-group">
            <label>Average Service Duration (minutes)</label>
            <input type="number" name="avg_service_duration" value="<?php echo htmlspecialchars($settings['avg_service_duration'] ?? '20'); ?>" min="5" max="120">
            <small style="color: #666;">Used to estimate wait times</small>
        </div>

        <div class="form-group">
            <label>Notify Before Turn (minutes)</label>
            <input type="number" name="notify_before_mins" value="<?php echo htmlspecialchars($settings['notify_before_mins'] ?? '15'); ?>" min="5" max="60">
            <small style="color: #666;">Send reminder this many minutes before customer's turn</small>
        </div>

        <h3 class="section-title"><i class="fas fa-map-marker-alt"></i> Google Maps</h3>

        <div class="form-group">
            <label>Google Maps Embed URL</label>
            <textarea name="map_embed" rows="3" placeholder="Paste Google Maps iframe src URL here..."><?php echo htmlspecialchars($settings['map_embed'] ?? ''); ?></textarea>
            <small style="color: #666;">Get from Google Maps → Share → Embed a map → Copy the src URL from the iframe code</small>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Settings
        </button>
    </form>
</div>

</body>
</html>
