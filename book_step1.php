<?php 
require_once '../includes/csrf.php';
startBookingSession();
require_once '../includes/header.php'; 
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();
$today = date('Y-m-d');

// Fetch active promotions for booking page
$promo_stmt = $db->prepare("SELECT * FROM promotions WHERE is_active = 1 AND start_date <= ? AND end_date >= ? AND FIND_IN_SET('booking', display_location)");
$promo_stmt->execute([$today, $today]);
$booking_promos = $promo_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.gender-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 3px solid transparent;
    min-height: 150px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}
/*.gender-card background pattern or overlay etc*/
.gender-card:active {
    transform: scale(0.98);
}
.gender-card.selected {
    border-color: var(--primary-color);
    box-shadow: 0 4px 12px rgba(108, 99, 255, 0.3);
}
.gender-icon {
    font-size: 48px;
    margin-bottom: 10px;
}
.gender-card.men .gender-icon { color: var(--men-blue); }
.gender-card.women .gender-icon { color: var(--women-pink); }

.promo-banner-slim {
    background: linear-gradient(135deg, #6c63ff 0%, #8e76ff 100%);
    color: white;
    padding: 12px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 4px 10px rgba(108, 99, 255, 0.15);
}
</style>

<div class="booking-step">
    <?php if (!empty($booking_promos)): ?>
        <?php foreach ($booking_promos as $promo): ?>
            <div class="promo-banner-slim">
                <i class="fas fa-gift fa-lg"></i>
                <div style="flex: 1;">
                    <strong style="display: block; font-size: 0.9rem;"><?php echo htmlspecialchars($promo['title']); ?></strong>
                    <span style="font-size: 0.8rem; opacity: 0.9;"><?php echo htmlspecialchars($promo['description']); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2 class="text-center mb-2" style="font-weight: 800; color: #1e293b;">Who are you booking for?</h2>
    <p class="text-center mb-4" style="color: #64748b; font-size: 14px; font-weight: 500;">Step 1 of 4</p>

    <div class="gender-card card men" onclick="selectGender('Men')">
        <div class="gender-icon"><i class="fas fa-mars"></i></div>
        <h3>Men's Services</h3>
    </div>

    <div class="gender-card card women" onclick="selectGender('Women')">
        <div class="gender-icon"><i class="fas fa-venus"></i></div>
        <h3>Women's Services</h3>
    </div>

    <div class="text-center">
        <a href="../index.php" class="text-muted" style="text-decoration: none; font-size: 14px;">
            <i class="fas fa-arrow-left"></i> Go Back Home
        </a>
    </div>
</div>

<script>
function selectGender(gender) {
    window.location.href = 'book_step2.php?gender=' + gender;
}
</script>

<?php require_once '../includes/footer.php'; ?>
