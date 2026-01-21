<?php 
session_start();
require_once '../includes/header.php'; 

$token_id = $_GET['token_id'] ?? '';
if (empty($token_id)) {
    header('Location: ../index.php');
    exit;
}
?>

<style>
.track-container {
    max-width: 500px;
    margin: 0 auto;
    padding: 20px;
}
.status-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.token-display {
    font-size: 48px;
    font-weight: bold;
    color: var(--primary-color);
    margin: 20px 0;
}
.status-text {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 10px;
}
.status-waiting { color: var(--warning-color); }
.status-in-service { color: var(--primary-color); }
.status-completed { color: var(--success-color); }
.status-cancelled { color: var(--danger-color); }
.info-grid {
    display: grid;
    gap: 15px;
    margin-top: 20px;
    text-align: left;
}
.info-item {
    display: flex;
    justify-content: space-between;
    padding: 12px;
    background: var(--light-gray);
    border-radius: 8px;
}
.pulse {
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>

<div class="track-container">
    <h2 style="text-align: center;">Track Your Token</h2>
    
    <div class="status-card">
        <div style="color: #666;">Your Token Number</div>
        <div class="token-display" id="tokenNumber">Loading...</div>
        
        <div class="status-text" id="statusText">Checking status...</div>
        
        <div class="info-grid" id="infoGrid">
            <!-- Dynamic content will be inserted here -->
        </div>
    </div>
    
    <div class="text-center" style="display: flex; flex-direction: column; gap: 10px; align-items: center;">
        <a href="../index.php" class="btn btn-secondary" style="width: 200px;">Back to Home</a>
        <button id="cancelBtn" class="btn btn-danger" style="width: 200px; display: none;" onclick="cancelToken()">
            <i class="fas fa-times"></i> Cancel My Token
        </button>
    </div>
</div>

<script>
const tokenId = <?php echo json_encode($token_id); ?>;
let notificationPermission = 'default';

// Request notification permission
if ('Notification' in window) {
    Notification.requestPermission().then(permission => {
        notificationPermission = permission;
    });
}

function showNotification(title, body) {
    if (notificationPermission === 'granted') {
        new Notification(title, {
            body: body,
            icon: '../assets/images/logo.png',
            badge: '../assets/images/logo.png',
            tag: 'token-' + tokenId
        });
        
        // Play notification sound (if available)
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGmN7uxXElBSyBzvLYiTcIGWi77eefTRAMUKfj8LZjHAY4ktfyzHksBSR3x/DdkEAKFF606+uoVRQKRpje7sVxJQUsgs7y14k4CBdpvO3nn00NDVK06POzYB0KOZPh8s9+KwUnfc3y3I4+ChVhtet1pEoRDEeg4u3GeSkFLYXQ8tmKOQgYa77u559NEAxVp+TwtWAdCjqU4fLPfisFJ33N8tyOPgoVYbXrdaRKEQxHoOLtxnkpBS2F0PLZijkIGGu+7v///////////////');
        audio.play().catch(e => console.log('Audio play failed:', e));
    }
}

function updateStatus() {
    fetch(`../ajax/check_notifications.php?token_id=${tokenId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                document.getElementById('statusText').textContent = 'Error: ' + data.message;
                return;
            }
            
            // Update display
            document.getElementById('tokenNumber').textContent = data.token_number;
            
            const statusEl = document.getElementById('statusText');
            statusEl.className = 'status-text status-' + data.status.toLowerCase().replace(' ', '-');
            statusEl.textContent = data.status;
            
            // Update info grid
            let infoHTML = '';
            
            if (data.status === 'Waiting') {
                infoHTML = `
                    <div class="info-item">
                        <span>Queue Position</span>
                        <strong class="pulse">${data.queue_position}</strong>
                    </div>
                    <div class="info-item">
                        <span>Estimated Wait</span>
                        <strong>${data.estimated_wait} minutes</strong>
                    </div>
                `;
                
                // Check for notifications
                if (data.should_notify_15min) {
                    showNotification('Almost Your Turn!', `${data.customer_name}, you're almost up! You have about 15 minutes.`);
                }
                
                if (data.should_notify_turn) {
                    showNotification('Your Turn!', `${data.customer_name}, it's your turn now! Please proceed to the counter.`);
                }

                // Show cancel button if status is Waiting
                document.getElementById('cancelBtn').style.display = 'block';
            } else if (data.status === 'In Service') {
                document.getElementById('cancelBtn').style.display = 'none';
                infoHTML = `
                    <div class="info-item" style="background: #cfe2ff;">
                        <span style="width: 100%; text-align: center;"><strong>You're being served!</strong></span>
                    </div>
                `;
                
                if (data.should_notify_turn) {
                    showNotification('Service Started', `${data.customer_name}, your service has started!`);
                }
            } else if (data.status === 'Completed') {
                infoHTML = `
                    <div class="info-item" style="background: #d1e7dd;">
                        <span style="width: 100%; text-align: center;"><strong>Service Completed! Thank you for visiting.</strong></span>
                    </div>
                `;
            } else if (data.status === 'Cancelled') {
                document.getElementById('cancelBtn').style.display = 'none';
                infoHTML = `
                    <div class="info-item" style="background: #f8d7da;">
                        <span style="width: 100%; text-align: center;"><strong>Token Cancelled</strong></span>
                    </div>
                `;
            } else {
                // For other statuses (Completed, etc.)
                document.getElementById('cancelBtn').style.display = 'none';
            }
            
            document.getElementById('infoGrid').innerHTML = infoHTML;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('statusText').textContent = 'Connection error';
        });
}

// Update immediately
updateStatus();

// Poll every 15 seconds for more responsive status changes
setInterval(updateStatus, 15000);

function cancelToken() {
    if (!confirm('Are you sure you want to cancel your booking? This action cannot be undone.')) {
        return;
    }

    const cancelBtn = document.getElementById('cancelBtn');
    cancelBtn.disabled = true;
    cancelBtn.textContent = 'Cancelling...';

    const formData = new FormData();
    formData.append('token_id', tokenId);

    fetch('../ajax/cancel_token.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateStatus();
        } else {
            alert(data.message || 'Cancellation failed');
            cancelBtn.disabled = false;
            cancelBtn.innerHTML = '<i class="fas fa-times"></i> Cancel My Token';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Connection error. Please try again.');
        cancelBtn.disabled = false;
        cancelBtn.innerHTML = '<i class="fas fa-times"></i> Cancel My Token';
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
