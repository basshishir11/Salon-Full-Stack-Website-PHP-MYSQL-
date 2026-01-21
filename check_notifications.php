<?php
// ajax/check_notifications.php
// session_start(); // Not needed for GET notification checks
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$token_id = $_GET['token_id'] ?? '';

if (empty($token_id)) {
    echo json_encode(['success' => false, 'message' => 'Token ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get token details
    $query = "SELECT t.*, c.name FROM tokens t 
              JOIN customers c ON t.customer_id = c.id 
              WHERE t.id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute(['id' => $token_id]);
    $token = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$token) {
        echo json_encode(['success' => false, 'message' => 'Token not found']);
        exit;
    }
    
    // Calculate current position in queue
    $position_query = "SELECT COUNT(*) as position FROM tokens 
                       WHERE DATE(created_at) = DATE(:created_at)
                       AND gender = :gender
                       AND status = 'Waiting'
                       AND id < :id";
    $pos_stmt = $db->prepare($position_query);
    $pos_stmt->execute([
        'created_at' => $token['created_at'],
        'gender' => $token['gender'],
        'id' => $token_id
    ]);
    $position = $pos_stmt->fetch(PDO::FETCH_ASSOC)['position'] + 1;
    
    $response = [
        'success' => true,
        'token_number' => $token['token_number'],
        'status' => $token['status'],
        'queue_position' => $position,
        'estimated_wait' => $token['estimated_wait_mins'],
        'customer_name' => $token['name'],
        'should_notify_15min' => false,
        'should_notify_turn' => false
    ];
    
    // Check if notifications should be sent
    if ($token['status'] === 'Waiting') {
        // Check for 15-minute notification
        if (!$token['notify_15min_sent'] && $position <= 2) {
            $response['should_notify_15min'] = true;
        }
        
        // Check for "Your Turn" notification
        if (!$token['notify_turn_sent'] && $position == 1) {
            $response['should_notify_turn'] = true;
        }
    } elseif ($token['status'] === 'In Service') {
        $response['should_notify_turn'] = !$token['notify_turn_sent'];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
