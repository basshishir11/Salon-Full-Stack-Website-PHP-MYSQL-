<?php
require_once '../includes/csrf.php';
startBookingSession();

if (!isset($_SESSION['booking_gender'])) {
    header("Location: book_step1.php");
    exit;
}

require_once '../includes/header.php';
?>

<style>
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
}
.form-group input {
    width: 100%;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.2s;
}
.form-group input:focus {
    outline: none;
    border-color: var(--primary-color);
}
.error-text {
    color: var(--danger-color);
    font-size: 13px;
    margin-top: 5px;
    display: none;
}
.success-text {
    color: var(--success-color);
    font-size: 13px;
    margin-top: 5px;
    display: none;
}
.selected-services {
    background: var(--light-gray);
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.service-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #ddd;
}
.slot-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
    gap: 12px;
    margin-top: 10px;
}
.slot-item {
    background: white;
    border: 2px solid #eee;
    border-radius: 12px;
    padding: 12px 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
.slot-item:hover:not(.full) {
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(142, 118, 255, 0.1);
}
.slot-item.selected {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: white;
}
.slot-item.selected small {
    color: rgba(255,255,255,0.8);
}
.slot-item.full {
    background: #f8f9fa;
    border-color: #eee;
    color: #adb5bd;
    cursor: not-allowed;
}
.slot-item strong {
    display: block;
    font-size: 14px;
    margin-bottom: 4px;
}
.slot-item small {
    font-size: 11px;
    color: #666;
}
.selection-summary {
    background: rgba(142, 118, 255, 0.05);
    border: 1px solid rgba(142, 118, 255, 0.2);
    border-left: 4px solid var(--primary-color);
    padding: 15px;
    border-radius: 12px;
    margin-top: 20px;
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<div class="booking-step">
    <h2 class="text-center mb-2">Book Appointment</h2>
    <p class="text-center mb-3" style="color: #666; font-size: 14px;">Please provide your details and select a time</p>

    <div class="selected-services">
        <h4 style="margin-bottom: 10px; font-size: 16px;">Selected Services</h4>
        <div id="servicesList"></div>
        <div style="margin-top: 10px; padding-top: 10px; border-top: 2px solid #ddd;">
            <strong>Total: NPR <span id="totalPrice">0</span></strong>
        </div>
    </div>

    <form id="customerForm">
        <?php csrfField(); ?>
        
        <div class="form-group">
            <label for="name">Full Name *</label>
            <input type="text" id="name" name="name" required minlength="2" placeholder="Enter your name">
            <div class="error-text" id="nameError">Please enter a valid name (min 2 characters)</div>
        </div>

        <div class="form-group">
            <label for="phone">Phone Number *</label>
            <input type="tel" id="phone" name="phone" required pattern="[0-9]{10}" placeholder="98XXXXXXXX">
            <div class="error-text" id="phoneError">Please enter a valid 10-digit phone number</div>
        </div>

        <div class="form-group">
            <label for="appointment_date">Select Date *</label>
            <input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
        </div>

        <div class="form-group">
            <label>Available Time Slots *</label>
            <div id="slotGrid" class="slot-grid">
                <p style="grid-column: 1/-1; text-align: center; color: #666;">Loading slots...</p>
            </div>
            <div id="selectionSummary"></div>
            <input type="hidden" id="appointment_time" name="appointment_time" required>
            <div class="error-text" id="timeError" style="margin-top: 10px;">Please select a time slot</div>
        </div>

        <div class="form-group">
            <label for="referral">Referral Code (Optional)</label>
            <input type="text" id="referral" name="referral" placeholder="Enter referral code">
            <div class="error-text" id="referralError"></div>
            <div class="success-text" id="referralSuccess">Referral code applied!</div>
        </div>

        <button type="submit" class="btn btn-primary mt-2" id="submitBtn">Book Token</button>
        <a href="book_step2.php?gender=<?php echo htmlspecialchars($_SESSION['booking_gender']); ?>" class="btn btn-secondary mt-1">Back</a>
    </form>
</div>

<script>
// Load selected services from session storage
const services = JSON.parse(sessionStorage.getItem('selectedServices') || '[]');

if (services.length === 0) {
    window.location.href = 'book_step1.php';
}

// Display services
const servicesList = document.getElementById('servicesList');
let totalPrice = 0;

services.forEach(service => {
    totalPrice += parseFloat(service.price);
    servicesList.innerHTML += `
        <div class="service-item">
            <span>${service.name}</span>
            <span>NPR ${service.price}</span>
        </div>
    `;
});

document.getElementById('totalPrice').textContent = totalPrice.toFixed(2);

// Slot Selection Logic
const dateInput = document.getElementById('appointment_date');
const slotGrid = document.getElementById('slotGrid');
const timeInput = document.getElementById('appointment_time');
const selectionSummary = document.getElementById('selectionSummary');
const gender = '<?php echo $_SESSION['booking_gender']; ?>';

function loadSlots() {
    const date = dateInput.value;
    if (!date) return;

    slotGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #666; padding: 20px;">Loading available slots...</p>';
    selectionSummary.innerHTML = '';
    timeInput.value = '';
    
    fetch(`../ajax/check_slots.php?date=${date}&gender=${gender}`, {
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                slotGrid.innerHTML = '';
                data.slots.forEach(slot => {
                    const div = document.createElement('div');
                    div.className = 'slot-item' + (!slot.available ? ' full' : '');
                    div.innerHTML = `<strong>${slot.display}</strong><small>${slot.status_text}</small>`;
                    
                    if (slot.available) {
                        div.addEventListener('click', () => {
                            document.querySelectorAll('.slot-item').forEach(i => i.classList.remove('selected'));
                            div.classList.add('selected');
                            timeInput.value = slot.time;
                            document.getElementById('timeError').style.display = 'none';
                            
                            const formattedDate = new Date(data.date).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
                            selectionSummary.innerHTML = `
                                <div class="selection-summary">
                                    <i class="fas fa-calendar-check" style="color: var(--primary-color);"></i> 
                                    <strong>Selected:</strong> ${formattedDate} at ${slot.display}
                                </div>
                            `;
                        });
                    } else {
                        div.innerHTML = `<strong>${slot.display}</strong><small style="color: var(--danger-color);">Full</small>`;
                    }
                    slotGrid.appendChild(div);
                });
            } else {
                slotGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--danger-color);">Error loading slots. Please refresh.</p>';
            }
        })
        .catch(err => {
            slotGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--danger-color);">Connection error.</p>';
        });
}

dateInput.addEventListener('change', loadSlots);
window.addEventListener('DOMContentLoaded', loadSlots);

// Referral validation
const referralInput = document.getElementById('referral');
const referralError = document.getElementById('referralError');
const referralSuccess = document.getElementById('referralSuccess');

referralInput.addEventListener('blur', function() {
    const code = this.value.trim();
    if (code === '') {
        referralError.style.display = 'none';
        referralSuccess.style.display = 'none';
        return;
    }

    const phone = document.getElementById('phone').value.trim();
    fetch(`../ajax/validate_referral.php?code=${encodeURIComponent(code)}&phone=${encodeURIComponent(phone)}`, {
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            if (data.valid) {
                referralError.style.display = 'none';
                referralSuccess.style.display = 'block';
            } else {
                referralSuccess.style.display = 'none';
                referralError.textContent = data.message || 'Invalid referral code';
                referralError.style.display = 'block';
            }
        });
});

// Form submission
document.getElementById('customerForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const name = document.getElementById('name').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const appDate = document.getElementById('appointment_date').value;
    const appTime = document.getElementById('appointment_time').value;

    let valid = true;

    if (!appTime) {
        document.getElementById('timeError').style.display = 'block';
        valid = false;
    } else {
        document.getElementById('timeError').style.display = 'none';
    }

    if (name.length < 2) {
        document.getElementById('nameError').style.display = 'block';
        valid = false;
    } else {
        document.getElementById('nameError').style.display = 'none';
    }

    if (!/^[0-9]{10}$/.test(phone)) {
        document.getElementById('phoneError').style.display = 'block';
        valid = false;
    } else {
        document.getElementById('phoneError').style.display = 'none';
    }

    if (!valid) return;

    const formData = new FormData();
    formData.append('name', name);
    formData.append('phone', phone);
    formData.append('referral', referralInput.value.trim());
    formData.append('appointment_date', appDate);
    formData.append('appointment_time', appTime);
    formData.append('services', JSON.stringify(services));
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Generating Token...';

    fetch('../ajax/generate_token.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            sessionStorage.setItem('tokenInfo', JSON.stringify(data));
            window.location.href = 'book_confirm.php';
        } else {
            alert(data.message || 'Booking failed');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Book Token';
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Book Token';
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
