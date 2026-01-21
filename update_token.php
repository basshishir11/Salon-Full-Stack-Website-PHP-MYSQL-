<?php
// ajax/update_token.php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$token_id = $_POST['token_id'] ?? '';
$status = $_POST['status'] ?? '';

$valid_statuses = ['Waiting', 'In Service', 'Completed', 'Cancelled'];

if (empty($token_id) || !in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Update token status
    $query = "UPDATE tokens SET status = :status";
    
    // If completing, set completed_at and increment customer visit count
    if ($status === 'Completed') {
        $query .= ", completed_at = NOW()";
    }
    
    $query .= " WHERE id = :token_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':token_id', $token_id);
    $stmt->execute();

    // If completed, increment customer visit count, check milestones, and RECORD REVENUE
    if ($status === 'Completed') {
        // Get customer_id and total_price from token
        $get_token = $db->prepare("SELECT customer_id, total_price FROM tokens WHERE id = :token_id");
        $get_token->bindParam(':token_id', $token_id);
        $get_token->execute();
        $token_data = $get_token->fetch(PDO::FETCH_ASSOC);
        
        $customer_id = $token_data['customer_id'];
        $total_amount = $token_data['total_price'] ?? 0;

        // 1. Record in Revenue Table
        // Check if already recorded to prevent duplicates
        $check_rev = $db->prepare("SELECT id FROM revenue WHERE token_id = :tid");
        $check_rev->execute([':tid' => $token_id]);
        if (!$check_rev->fetch()) {
            $ins_rev = $db->prepare("INSERT INTO revenue (token_id, total_amount, final_amount) VALUES (:tid, :amt, :amt)");
            $ins_rev->execute([':tid' => $token_id, ':amt' => $total_amount]);
        }

        // 2. Increment visit count
        $update_visits = "UPDATE customers SET visit_count = visit_count + 1 WHERE id = :customer_id";
        $stmt = $db->prepare($update_visits);
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->execute();

        // 3. Get new visit count
        $get_visits = $db->prepare("SELECT visit_count FROM customers WHERE id = :customer_id");
        $get_visits->bindParam(':customer_id', $customer_id);
        $get_visits->execute();
        $customer = $get_visits->fetch(PDO::FETCH_ASSOC);
        $visit_count = $customer['visit_count'];

        // 4. Check for milestone rewards (5, 7, 10, 12 - then cycle resets)
        // Correcting the loop logic to reset after 20 visits as requested
        $cycle_size = 20; // Maximum milestone is at 20 visits
        $effective_visits = (($visit_count - 1) % $cycle_size) + 1;

        // Check if there's a milestone for this visit count
        $milestone_query = $db->prepare("SELECT * FROM loyalty_milestones WHERE visits_required = :visits");
        $milestone_query->bindParam(':visits', $effective_visits);
        $milestone_query->execute();
        $milestone = $milestone_query->fetch(PDO::FETCH_ASSOC);

        if ($milestone) {
            // Insert reward for customer
            $insert_reward = $db->prepare("INSERT INTO rewards (customer_id, reward_type, description, value_amount, status) 
                                          VALUES (:customer_id, 'Loyalty', :desc, :value, 'Pending')");
            $insert_reward->execute([
                'customer_id' => $customer_id,
                'desc' => "Loyalty Milestone: " . $effective_visits . " visits",
                'value' => $milestone['value_amount']
            ]);
        }

        // 5. Check if this was a first-time referred visit to reward the REFERRER
        if ($visit_count == 1) {
            $get_referrer = $db->prepare("SELECT referred_by_customer_id FROM customers WHERE id = :cid");
            $get_referrer->execute([':cid' => $customer_id]);
            $ref_by = $get_referrer->fetchColumn();

            if ($ref_by) {
                // Reward the person who referred this customer
                $ins_ref_reward = $db->prepare("INSERT INTO rewards (customer_id, reward_type, description, value_amount, status) 
                                               VALUES (:cid, 'Referral', 'Successful Referral Reward', 20.00, 'Pending')");
                $ins_ref_reward->execute([':cid' => $ref_by]);
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Token updated successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
