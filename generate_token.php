<?php
// ajax/generate_token.php
require_once __DIR__ . '/../includes/csrf.php';
startBookingSession();

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Verify CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$referral = trim($_POST['referral'] ?? '');
$app_date = $_POST['appointment_date'] ?? null;
$app_time = $_POST['appointment_time'] ?? null;
$services = json_decode($_POST['services'] ?? '[]', true);
$gender = $_SESSION['booking_gender'] ?? '';

// Validate inputs
if (empty($name) || strlen($name) < 2) {
    echo json_encode(['success' => false, 'message' => 'Invalid name']);
    exit;
}

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
    exit;
}

if (empty($services) || !is_array($services)) {
    echo json_encode(['success' => false, 'message' => 'No services selected']);
    exit;
}

if (empty($gender)) {
    echo json_encode(['success' => false, 'message' => 'Gender not specified']);
    exit;
}

if (empty($app_date) || empty($app_time)) {
    echo json_encode(['success' => false, 'message' => 'Appointment date and time are required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Server-side capacity check (max 3 per slot)
    $capacity_query = "SELECT COUNT(*) FROM tokens WHERE appointment_date = :date AND appointment_time = :time AND gender = :gender AND status != 'Cancelled'";
    $cap_stmt = $db->prepare($capacity_query);
    $cap_stmt->execute([':date' => $app_date, ':time' => $app_time, ':gender' => $gender]);
    if ($cap_stmt->fetchColumn() >= 3) {
        echo json_encode(['success' => false, 'message' => 'Sorry, this slot is already full. Please select another time.']);
        exit;
    }

    $db->beginTransaction();

    // 1. Find or create customer
    $query = "SELECT * FROM customers WHERE phone = :phone LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':phone', $phone);
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    $referrer_id = null;

    if ($customer) {
        $customer_id = $customer['id'];
        // Ensure existing customers have a referral code if missing
        if (empty($customer['referral_code'])) {
            $new_ref = 'SALON-' . strtoupper(substr(md5(uniqid($phone, true)), 0, 6));
            $db->prepare("UPDATE customers SET referral_code = ? WHERE id = ?")->execute([$new_ref, $customer_id]);
        }
    } else {
        // Create new customer with unique referral code
        $referral_code = 'SALON-' . strtoupper(substr(md5(uniqid($phone, true)), 0, 6));
        
        // Check if referral code was provided
        if (!empty($referral)) {
            $ref_query = "SELECT id, phone FROM customers WHERE referral_code = :code LIMIT 1";
            $ref_stmt = $db->prepare($ref_query);
            $ref_stmt->bindParam(':code', $referral);
            $ref_stmt->execute();
            $referrer = $ref_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($referrer) {
                // Prevent self-referral if by some chance the same phone is using a code belonging to it
                if ($referrer['phone'] !== $phone) {
                    $referrer_id = $referrer['id'];
                } else {
                    // Just ignore the referral if it's their own
                }
            }
        }

        $insert_customer = "INSERT INTO customers (name, phone, referral_code, referred_by_customer_id) VALUES (:name, :phone, :ref_code, :ref_by)";
        $stmt = $db->prepare($insert_customer);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':ref_code', $referral_code);
        $stmt->bindParam(':ref_by', $referrer_id);
        $stmt->execute();
        
        $customer_id = $db->lastInsertId();
    }

    // 2. Get or create today's counter
    $today = date('Y-m-d');
    $counter_query = "SELECT * FROM token_counters WHERE date = :date LIMIT 1";
    $stmt = $db->prepare($counter_query);
    $stmt->bindParam(':date', $today);
    $stmt->execute();
    $counter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$counter) {
        $insert_counter = "INSERT INTO token_counters (date, boys_counter, girls_counter) VALUES (:date, 0, 0)";
        $stmt = $db->prepare($insert_counter);
        $stmt->bindParam(':date', $today);
        $stmt->execute();
        
        $counter = ['boys_counter' => 0, 'girls_counter' => 0];
    }

    // 3. Increment counter and generate token number
    if ($gender === 'Men') {
        $new_counter = $counter['boys_counter'] + 1;
        $token_number = 'B-' . $new_counter;
        $update_counter = "UPDATE token_counters SET boys_counter = :counter WHERE date = :date";
    } else {
        $new_counter = $counter['girls_counter'] + 1;
        $token_number = 'G-' . $new_counter;
        $update_counter = "UPDATE token_counters SET girls_counter = :counter WHERE date = :date";
    }

    $stmt = $db->prepare($update_counter);
    $stmt->bindParam(':counter', $new_counter);
    $stmt->bindParam(':date', $today);
    $stmt->execute();

    // 4. Create services summary
    $services_summary = implode(', ', array_column($services, 'name'));
    $total_price = array_sum(array_column($services, 'price'));
    $total_duration = array_sum(array_column($services, 'duration'));

    // 5. Insert token
    $insert_token = "INSERT INTO tokens (token_number, gender, customer_id, services_summary, total_price, appointment_date, appointment_time, status, estimated_wait_mins) 
                      VALUES (:token_num, :gender, :customer_id, :services, :price, :app_date, :app_time, 'Waiting', 0)";
    $stmt = $db->prepare($insert_token);
    $stmt->bindParam(':token_num', $token_number);
    $stmt->bindParam(':gender', $gender);
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->bindParam(':services', $services_summary);
    $stmt->bindParam(':price', $total_price);
    $stmt->bindParam(':app_date', $app_date);
    $stmt->bindParam(':app_time', $app_time);
    $stmt->execute();
    
    $token_id = $db->lastInsertId();

    // 6. Calculate queue position and estimated wait
    $queue_query = "SELECT COUNT(*) as count FROM tokens WHERE DATE(created_at) = :today AND gender = :gender AND status = 'Waiting' AND id < :token_id";
    $stmt = $db->prepare($queue_query);
    $stmt->bindParam(':today', $today);
    $stmt->bindParam(':gender', $gender);
    $stmt->bindParam(':token_id', $token_id);
    $stmt->execute();
    $queue = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $position = $queue['count'] + 1; // Current position in queue
    
    // Get average service time from settings (default 20 mins)
    $avg_time_query = "SELECT value FROM settings WHERE `key` = 'avg_service_duration'";
    $avg_time = $db->query($avg_time_query)->fetchColumn() ?: 20;
    
    $estimated_wait = ($position - 1) * $avg_time; // Minutes to wait
    $estimated_time = date('h:i A', strtotime("+{$estimated_wait} minutes"));

    // 7. If referral was used, create referral record
    if ($referrer_id) {
        $insert_referral = "INSERT INTO referrals (referrer_customer_id, referred_customer_id, booking_id, status) 
                           VALUES (:referrer, :referred, :booking, 'Successful')";
        $stmt = $db->prepare($insert_referral);
        $stmt->bindParam(':referrer', $referrer_id);
        $stmt->bindParam(':referred', $customer_id);
        $stmt->bindParam(':booking', $token_id);
        $stmt->execute();
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'token_id' => $token_id,
        'token_number' => $token_number,
        'queue_position' => $position,
        'estimated_wait' => $estimated_wait,
        'estimated_time' => $estimated_time,
        'appointment_date' => date('l, F j, Y', strtotime($app_date)),
        'appointment_time' => date('h:i A', strtotime($app_time)),
        'services_summary' => $services_summary,
        'total_price' => $total_price
    ]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
