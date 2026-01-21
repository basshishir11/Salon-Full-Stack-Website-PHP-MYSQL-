<?php
// includes/settings_helper.php
require_once __DIR__ . '/../config/database.php';

function getSetting($key, $default = '') {
    static $settings = null;
    
    if ($settings === null) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $stmt = $db->query("SELECT `key`, `value` FROM settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            $settings = [];
        }
    }
    
    return $settings[$key] ?? $default;
}

// Global variables for easy access
$shopName = getSetting('shop_name', 'Sharma Salon & Spa');
$shopPhone = getSetting('phone_call', '');
$shopWhatsapp = getSetting('phone_whatsapp', '');
?>
