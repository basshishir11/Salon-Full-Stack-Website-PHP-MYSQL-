<?php require_once '../includes/header.php'; ?>

<div class="page-header text-center mb-4">
    <h2>Contact Us</h2>
    <p style="color: #666;">We'd love to hear from you!</p>
</div>

<div class="card">
    <div class="contact-info-item">
        <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
        <div>
            <strong>Our Location</strong>
            <p style="font-size: 14px; color: #666;"><?php echo htmlspecialchars(getSetting('shop_address', '123 Main Street, City')); ?></p>
        </div>
    </div>

    <div class="contact-info-item">
        <div class="contact-icon"><i class="fas fa-phone-alt"></i></div>
        <div>
            <strong>Call Us</strong>
            <p style="font-size: 14px; color: #666;">
                <?php 
                $call = getSetting('phone_call', 'Not set');
                if ($call !== 'Not set') {
                    echo '<a href="tel:' . preg_replace('/[^0-9+]/', '', $call) . '" style="color: var(--primary-color); text-decoration: none; font-weight: bold;">' . htmlspecialchars($call) . '</a>';
                } else {
                    echo htmlspecialchars($call);
                }
                ?>
            </p>
        </div>
    </div>

    <div class="contact-info-item">
        <div class="contact-icon"><i class="fab fa-whatsapp"></i></div>
        <div>
            <strong>WhatsApp</strong>
            <p style="font-size: 14px; color: #666;">
                <?php 
                $wa = getSetting('phone_whatsapp', 'Not set');
                if ($wa !== 'Not set') {
                    echo '<a href="https://wa.me/' . preg_replace('/[^0-9]/', '', $wa) . '" target="_blank" style="color: #25D366; text-decoration: none; font-weight: bold;">' . htmlspecialchars($wa) . '</a>';
                } else {
                    echo htmlspecialchars($wa);
                }
                ?>
            </p>
        </div>
    </div>

    <div class="contact-info-item">
        <div class="contact-icon"><i class="fas fa-envelope"></i></div>
        <div>
            <strong>Email</strong>
            <p style="font-size: 14px; color: #666;"><?php echo htmlspecialchars(getSetting('shop_email', 'info@example.com')); ?></p>
        </div>
    </div>
</div>

<div class="map-container">
    <?php 
    $mapUrl = getSetting('map_embed', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3532.814237197825!2d85.319087!3d27.707521!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjfCsDQyJzI3LjEiTiA4NcKwMTknMDguNyJF!5e0!3m2!1sen!2snp!4v1625000000000!5m2!1sen!2snp');
    ?>
    <iframe src="<?php echo $mapUrl; ?>" loading="lazy"></iframe>
</div>

<div class="text-center mt-3">
    <a href="book_step1.php" class="btn btn-primary mb-2">Book a Token</a>
    <a href="../index.php" class="btn btn-secondary">Back to Home</a>
</div>

<?php require_once '../includes/footer.php'; ?>
