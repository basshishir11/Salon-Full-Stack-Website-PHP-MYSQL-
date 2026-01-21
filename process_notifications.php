<?php
// cron/process_notifications.php
// Run this script periodically to process and trigger notifications
// Windows: Schedule via Task Scheduler to run every 1-2 minutes

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $today = date('Y-m-d');
    $processed_count = 0;
    
    // Get notification settings
    $notify_before_mins = $db->query("SELECT value FROM settings WHERE `key` = 'notify_before_mins'")->fetchColumn() ?: 15;
    $avg_service_duration = $db->query("SELECT value FROM settings WHERE `key` = 'avg_service_duration'")->fetchColumn() ?: 20;
    
    // Get all waiting tokens for today
    $query = "SELECT t.*, c.name, c.phone 
              FROM tokens t 
              JOIN customers c ON t.customer_id = c.id 
              WHERE DATE(t.created_at) = :today 
              AND t.status IN ('Waiting', 'In Service')
              ORDER BY t.id ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['today' => $today]);
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tokens as $token) {
        $gender = $token['gender'];
        
        // Calculate position in queue
        $pos_query = "SELECT COUNT(*) as position FROM tokens 
                      WHERE DATE(created_at) = :today
                      AND gender = :gender
                      AND status = 'Waiting'
                      AND id < :id";
        $pos_stmt = $db->prepare($pos_query);
        $pos_stmt->execute([
            'today' => $today,
            'gender' => $gender,
            'id' => $token['id']
        ]);
        $position = $pos_stmt->fetch(PDO::FETCH_ASSOC)['position'] + 1;
        
        // Check if we need to send 15-minute warning
        if (!$token['notify_15min_sent'] && $position <= 2 && $token['status'] === 'Waiting') {
            // Mark as sent
            $update = $db->prepare("UPDATE tokens SET notify_15min_sent = 1 WHERE id = :id");
            $update->execute(['id' => $token['id']]);
            
            echo "✓ 15-min notification marked for Token {$token['token_number']} ({$token['name']})\n";
            $processed_count++;
        }
        
        // Check if we need to send "Your Turn" notification
        if (!$token['notify_turn_sent'] && ($position == 1 || $token['status'] === 'In Service')) {
            // Mark as sent
            $update = $db->prepare("UPDATE tokens SET notify_turn_sent = 1 WHERE id = :id");
            $update->execute(['id' => $token['id']]);
            
            echo "✓ Your-turn notification marked for Token {$token['token_number']} ({$token['name']})\n";
            $processed_count++;
        }
    }
    
    if ($processed_count > 0) {
        echo "\n Total: {$processed_count} notifications processed.\n";
    } else {
        echo "No notifications to process at this time.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
