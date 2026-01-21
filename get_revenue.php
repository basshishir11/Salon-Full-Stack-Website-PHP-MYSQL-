<?php
// ajax/get_revenue.php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $range = $_GET['range'] ?? '7days';
    $today = date('Y-m-d');
    $groupBy = 'DATE(r.created_at)';
    $selectDate = 'DATE(r.created_at) as date';

    switch ($range) {
        case '30days':
            $start_date = date('Y-m-d', strtotime('-30 days'));
            break;
        case 'week':
            $start_date = date('Y-m-d', strtotime('monday this week'));
            break;
        case 'year':
            $start_date = date('Y-m-d', strtotime('-365 days'));
            $groupBy = "DATE_FORMAT(r.created_at, '%Y-%m')";
            $selectDate = "DATE_FORMAT(r.created_at, '%Y-%m') as date";
            break;
        default:
            $start_date = date('Y-m-d', strtotime('-7 days'));
    }

    // Get revenue data with dynamic grouping
    $query = "SELECT $selectDate, COUNT(DISTINCT t.id) as token_count, SUM(r.final_amount) as daily_revenue
              FROM tokens t
              JOIN revenue r ON t.id = r.token_id
              WHERE t.status = 'Completed' 
              AND DATE(r.created_at) >= :start_date
              GROUP BY $groupBy
              ORDER BY date ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->execute();
    $daily_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get today's stats
    $today_query = "SELECT COUNT(*) as today_tokens FROM tokens WHERE DATE(created_at) = :today";
    $stmt = $db->prepare($today_query);
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $today_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $completed_today = $db->prepare("SELECT COUNT(*) as c FROM tokens WHERE DATE(completed_at) = :today AND status = 'Completed'");
    $completed_today->bindParam(':today', $today);
    $completed_today->execute();
    $completed = $completed_today->fetch(PDO::FETCH_ASSOC);

    // Get service popularity
    $service_query = "SELECT s.name, COUNT(*) as count 
                     FROM tokens t 
                     JOIN services s ON t.services_summary LIKE CONCAT('%', s.name, '%')
                     WHERE t.status = 'Completed' AND DATE(t.completed_at) >= :start_date
                     GROUP BY s.name 
                     ORDER BY count DESC 
                     LIMIT 5";
    $stmt = $db->prepare($service_query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->execute();
    $popular_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'daily_data' => $daily_data,
        'today_tokens' => $today_stats['today_tokens'],
        'completed_today' => $completed['c'],
        'popular_services' => $popular_services
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
