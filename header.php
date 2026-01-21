<?php 
require_once 'settings_helper.php'; 

// Determine path to project root
$currentDir = dirname($_SERVER['PHP_SELF']);
$rootPath = ($currentDir === '/salon' || $currentDir === '/' || $currentDir === '\\') ? '' : '../';
if (strpos($currentDir, '/pages/admin') !== false) {
    $rootPath = '../../';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($shopName); ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo $rootPath; ?>assets/css/style.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Force background consistency */
        body {
            background: radial-gradient(circle at center, #ffffff 0%, #F4F0FF 100%) !important;
            background-attachment: fixed !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Logo / Header -->
        <header class="text-center mb-3">
            <h1 style="color: var(--primary-color);"><?php echo htmlspecialchars($shopName); ?></h1>
            <p>Relax while we wait for you.</p>
            <?php if (isset($_SESSION['customer_name'])): ?>
                <div class="mt-2" style="font-size: 14px;">
                    <a href="<?php echo $rootPath; ?>pages/my_appointments.php" class="text-primary" style="text-decoration: none; font-weight: 600;">
                        <i class="fas fa-calendar-check"></i> My Appointments
                    </a>
                </div>
            <?php endif; ?>
        </header>
        <main>
