<?php
require_once 'config/database.php';
require_once 'includes/settings_helper.php';

echo "<h2>Debug Info</h2>";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "<br>";
echo "Basename: " . basename($_SERVER['PHP_SELF']) . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";

$whatsapp = getSetting('shop_whatsapp', '9779800000000');
$phone = getSetting('shop_phone', '977014000000');

echo "WhatsApp Setting: [" . htmlspecialchars($whatsapp) . "]<br>";
echo "Phone Setting: [" . htmlspecialchars($phone) . "]<br>";

if (basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['PHP_SELF'], 'admin') === false) {
    echo "<strong style='color:green'>Condition MATCHED: Buttons should show.</strong>";
} else {
    echo "<strong style='color:red'>Condition FAILED.</strong>";
}
?>
