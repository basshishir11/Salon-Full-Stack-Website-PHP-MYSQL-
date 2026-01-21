<?php
// ajax/sse_queue.php
// Server-Sent Events endpoint for real-time queue updates
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Forbidden');
}

// Optimization: Close session writing to prevent locking other requests
// SSE connections stay open for a long time; if the session is locked, 
// no other PHP page can load for this user until this script ends.
session_write_close();

// Disable all output buffering
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
@ini_set('implicit_flush', true);
@ob_end_clean();

// Set headers for SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

// Function to send SSE message
function sendSSE($data) {
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

// Send initial connection message
sendSSE(['type' => 'init', 'connected' => true, 'timestamp' => time()]);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Keep connection alive and send updates
    $iteration = 0;
    while ($iteration < 100) { // Limit to 100 iterations (5 minutes)
        $iteration++;
        
        // Get current queue status
        $today = date('Y-m-d');
        
        // Boys queue (Men)
        $boys_query = "SELECT t.*, c.name, c.phone 
                      FROM tokens t 
                      JOIN customers c ON t.customer_id = c.id 
                      WHERE DATE(t.created_at) = :today 
                      AND t.gender = 'Men' 
                      AND t.status IN ('Waiting', 'In Service')
                      ORDER BY t.id ASC";
        $boys_stmt = $db->prepare($boys_query);
        $boys_stmt->execute(['today' => $today]);
        $boys_tokens = $boys_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Girls queue (Women)
        $girls_query = "SELECT t.*, c.name, c.phone 
                       FROM tokens t 
                       JOIN customers c ON t.customer_id = c.id 
                       WHERE DATE(t.created_at) = :today 
                       AND t.gender = 'Women' 
                       AND t.status IN ('Waiting', 'In Service')
                       ORDER BY t.id ASC";
        $girls_stmt = $db->prepare($girls_query);
        $girls_stmt->execute(['today' => $today]);
        $girls_tokens = $girls_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Send update
        sendSSE([
            'type' => 'update',
            'timestamp' => time(),
            'boys' => $boys_tokens,
            'girls' => $girls_tokens,
            'boys_count' => count($boys_tokens),
            'girls_count' => count($girls_tokens)
        ]);
        
        // Wait 3 seconds before next update
        sleep(3);
        
        // Check if connection is still alive
        if (connection_aborted()) {
            break;
        }
    }
} catch (Exception $e) {
    sendSSE(['error' => $e->getMessage()]);
}
?>
