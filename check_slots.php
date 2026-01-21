<?php
// ajax/check_slots.php
require_once __DIR__ . '/../includes/csrf.php';
// startBookingSession(); // Not needed for GET slot checks

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$date = $_GET['date'] ?? date('Y-m-d');
$gender = $_GET['gender'] ?? 'Men'; // Men or Women

try {
    $database = new Database();
    $db = $database->getConnection();

    // 2. Generate slots
    $start = strtotime('08:00');
    $end = strtotime('18:00');
    $slots = [];

    // 3. Query existing bookings for this date and gender
    $query = "SELECT appointment_time, COUNT(*) as count 
              FROM tokens 
              WHERE appointment_date = :date 
              AND gender = :gender
              AND status != 'Cancelled'
              GROUP BY appointment_time";
    $stmt = $db->prepare($query);
    $stmt->execute([':date' => $date, ':gender' => $gender]);
    $bookings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    while ($start <= $end) {
        $time = date('H:i:s', $start);
        $displayTime = date('h:i A', $start);
        
        $count = $bookings[$time] ?? 0;
        $max_capacity = 3;
        $remaining = $max_capacity - $count;
        
        $slots[] = [
            'time' => $time,
            'display' => $displayTime,
            'available' => $count < $max_capacity,
            'remaining' => $remaining,
            'status_text' => $count < $max_capacity ? ($remaining . " Slot" . ($remaining > 1 ? "s" : "")) : "Full"
        ];
        
        $start = strtotime('+30 minutes', $start);
    }

    echo json_encode([
        'success' => true,
        'date' => $date,
        'slots' => $slots
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
