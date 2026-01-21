<?php 
session_start();
require_once '../includes/header.php'; 
?>

<style>
.confirmation-card {
    text-align: center;
    padding: 30px 20px;
}
.token-display {
    font-size: 48px;
    font-weight: bold;
    color: var(--primary-color);
    margin: 20px 0;
    padding: 20px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 12px;
}
.info-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}
.info-row:last-child {
    border-bottom: none;
}
.success-icon {
    font-size: 64px;
    color: var(--success-color);
    margin-bottom: 15px;
}
</style>

<div class="confirmation-card">
    <div class="success-icon">
        <i class="fas fa-check-circle"></i>
    </div>
    
    <h2>Booking Confirmed!</h2>
    <p style="color: #666; margin-bottom: 20px;">Your token has been generated</p>

    <div class="token-display" id="tokenNumber">
        Loading...
    </div>

    <div class="card" style="text-align: left; margin: 20px 0;">
        <div class="info-row">
            <span style="color: #666;">Appointment Date:</span>
            <strong id="appointmentDate">-</strong>
        </div>
        <div class="info-row">
            <span style="color: #666;">Appointment Time:</span>
            <strong id="appointmentTime">-</strong>
        </div>
        <div class="info-row">
            <span style="color: #666;">Queue Position:</span>
            <strong id="queuePosition">-</strong>
        </div>
        <div class="info-row">
            <span style="color: #666;">Services:</span>
            <strong id="servicesSummary">-</strong>
        </div>
        <div class="info-row">
            <span style="color: #666;">Total Amount:</span>
            <strong style="color: var(--primary-color);">NPR <span id="totalPrice">-</span></strong>
        </div>
    </div>

    <div class="card" style="background: #fff3cd; border-left: 4px solid #ffc107;">
        <p style="margin: 0; font-size: 14px;">
            <i class="fas fa-bell"></i> 
            <strong>We'll notify you <?php echo getSetting('notify_before_mins', '15'); ?> minutes before your turn!</strong><br>
            You can leave the salon area and explore nearby. We'll send you a reminder.
        </p>
    </div>

    <a href="../index.php" class="btn btn-primary mt-3">Back to Home</a>
    <a href="#" id="trackLink" class="btn btn-secondary mt-1">
        <i class="fas fa-search"></i> Track Token Status
    </a>
    <button id="cancelBtn" class="btn btn-danger mt-1" onclick="cancelBooking()">
        <i class="fas fa-times"></i> Cancel Booking
    </button>
    <button class="btn btn-secondary mt-1" onclick="window.print()">
        <i class="fas fa-download"></i> Save/Print Token
    </button>
</div>

<script>
// Load token info from session storage
const tokenInfo = JSON.parse(sessionStorage.getItem('tokenInfo') || '{}');

if (!tokenInfo.success) {
    window.location.href = 'book_step1.php';
} else {
    document.getElementById('tokenNumber').textContent = tokenInfo.token_number;
    document.getElementById('appointmentDate').textContent = tokenInfo.appointment_date;
    document.getElementById('appointmentTime').textContent = tokenInfo.appointment_time;
    document.getElementById('queuePosition').textContent = tokenInfo.queue_position;
    document.getElementById('servicesSummary').textContent = tokenInfo.services_summary;
    document.getElementById('totalPrice').textContent = tokenInfo.total_price;
    
    // Set tracking link
    if (tokenInfo.token_id) {
        document.getElementById('trackLink').href = 'track_token.php?token_id=' + tokenInfo.token_id;
    }

    // Clear session storage
    sessionStorage.removeItem('selectedServices');
    // We keep tokenInfo for a bit in case they cancel, but the logic below clears it
}

function cancelBooking() {
    if (!confirm('Are you sure you want to cancel this booking?')) {
        return;
    }

    const cancelBtn = document.getElementById('cancelBtn');
    cancelBtn.disabled = true;
    cancelBtn.textContent = 'Cancelling...';

    const formData = new FormData();
    formData.append('token_id', tokenInfo.token_id);

    fetch('../ajax/cancel_token.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Booking cancelled successfully.');
            sessionStorage.removeItem('tokenInfo');
            window.location.href = '../index.php';
        } else {
            alert(data.message || 'Cancellation failed');
            cancelBtn.disabled = false;
            cancelBtn.innerHTML = '<i class="fas fa-times"></i> Cancel Booking';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Connection error');
        cancelBtn.disabled = false;
        cancelBtn.innerHTML = '<i class="fas fa-times"></i> Cancel Booking';
    });
}

</script>

<?php require_once '../includes/footer.php'; ?>
