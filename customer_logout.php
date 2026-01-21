<?php
// ajax/customer_logout.php
require_once __DIR__ . '/../includes/csrf.php';
startBookingSession();

unset($_SESSION['customer_id']);
unset($_SESSION['customer_name']);
unset($_SESSION['customer_phone']);

echo json_encode(['success' => true]);
?>
