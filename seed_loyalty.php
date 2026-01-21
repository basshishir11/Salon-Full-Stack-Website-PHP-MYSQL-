<?php
// seed_loyalty.php
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Seed Milestones if empty
    $check_milestones = $db->query("SELECT COUNT(*) FROM loyalty_milestones")->fetchColumn();
    if ($check_milestones == 0) {
        $milestones = [
            ['visits' => 5, 'type' => 'Discount', 'desc' => '10% Discount on next visit', 'value' => 10.00],
            ['visits' => 10, 'type' => 'Free Service', 'desc' => 'Free Haircut or Head Massage', 'value' => 50.00],
            ['visits' => 15, 'type' => 'Product', 'desc' => 'Free Hair Styling Product', 'value' => 30.00],
            ['visits' => 20, 'type' => 'VIP', 'desc' => '25% Discount on all services', 'value' => 25.00]
        ];

        $stmt = $db->prepare("INSERT INTO loyalty_milestones (visits_required, reward_type, description, value_amount) VALUES (?, ?, ?, ?)");
        foreach ($milestones as $m) {
            $stmt->execute([$m['visits'], $m['type'], $m['desc'], $m['value']]);
        }
        echo "Loyalty milestones seeded successfully.<br>";
    }

    // 2. Ensure all existing customers have referral codes
    $customers = $db->query("SELECT id, phone FROM customers WHERE referral_code IS NULL OR referral_code = ''");
    $update_stmt = $db->prepare("UPDATE customers SET referral_code = :code WHERE id = :id");
    
    $count = 0;
    while ($row = $customers->fetch(PDO::FETCH_ASSOC)) {
        $new_code = 'SALON-' . strtoupper(substr(md5(uniqid($row['phone'], true)), 0, 6));
        $update_stmt->execute([':code' => $new_code, ':id' => $row['id']]);
        $count++;
    }
    
    if ($count > 0) {
        echo "Generated $count missing referral codes.<br>";
    } else {
        echo "All customers already have referral codes.<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
