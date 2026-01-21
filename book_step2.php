<?php
require_once '../includes/csrf.php';
startBookingSession();
$gender = $_GET['gender'] ?? '';
if (empty($gender)) {
    header("Location: book_step1.php");
    exit;
}
$_SESSION['booking_gender'] = $gender;

require_once '../includes/header.php'; 
?>

<style>
.service-card {
    cursor: pointer;
    border: 2px solid #ddd;
    transition: all 0.2s ease;
    position: relative;
}
.service-card.selected {
    border-color: var(--primary-color);
    background-color: #f0edff;
}
.service-card input[type="checkbox"] {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 24px;
    height: 24px;
    cursor: pointer;
}
.service-info {
    display: flex;
    align-items: center;
    gap: 15px;
}
.service-icon {
    width: 60px;
    height: 60px;
    background: var(--light-gray);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    overflow: hidden;
}
.service-details {
    flex: 1;
}
.service-name {
    font-weight: 600;
    margin-bottom: 5px;
}
.service-meta {
    font-size: 13px;
    color: #666;
}
.badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    margin-right: 5px;
}
.badge-men { background: #e3f2fd; color: var(--men-blue); }
.badge-women { background: #fce4ec; color: var(--women-pink); }
.badge-unisex { background: #f3e5f5; color: #9c27b0; }
</style>

<div class="booking-step">
    <h2 class="text-center mb-2">Choose your services</h2>
    <p class="text-center mb-3" style="color: #666; font-size: 14px;">Step 2 of 4 • <?php echo htmlspecialchars($gender); ?>'s Services</p>

    <div class="search-box mb-3">
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" id="serviceSearch" class="form-control" placeholder="Search for a service..." style="border: 2px solid #ddd; border-left: none; border-radius: 0 50px 50px 0; padding: 12px; font-size: 16px;">
        </div>
    </div>

    <div id="servicesContainer">
        <p class="text-center">Loading services...</p>
    </div>

    <div id="errorMsg" class="text-center mt-2" style="color: var(--danger-color); display: none;"></div>

    <button id="nextBtn" class="btn btn-primary mt-3" disabled>Next</button>
    <a href="book_step1.php" class="btn btn-secondary mt-1">Back</a>
</div>

<script>
const selectedServices = [];
const gender = '<?php echo htmlspecialchars($gender); ?>';
let allServices = [];

// Fetch services
fetch('../ajax/get_services.php?gender=' + gender, {
    credentials: 'include'
})
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            allServices = data.services;
            displayServices(allServices);
        } else {
            document.getElementById('errorMsg').textContent = data.message;
            document.getElementById('errorMsg').style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('errorMsg').textContent = 'Failed to load services';
        document.getElementById('errorMsg').style.display = 'block';
    });

// Search functionality
document.getElementById('serviceSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const filteredServices = allServices.filter(service => 
        service.name.toLowerCase().includes(searchTerm)
    );
    displayServices(filteredServices);
});

function displayServices(services) {
    const container = document.getElementById('servicesContainer');
    
    if (services.length === 0) {
        container.innerHTML = '<p class="text-center">No services found</p>';
        return;
    }

    container.innerHTML = services.map(service => {
        const isSelected = selectedServices.find(s => s.id == service.id);
        const selectedClass = isSelected ? 'selected' : '';
        const checkedAttr = isSelected ? 'checked' : '';
        
        // Check if service has an image
        let iconHtml = '<i class="fas fa-cut"></i>';
        if (service.image_path) {
            iconHtml = `<img src="../${service.image_path}" alt="${service.name}" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">`;
        }
        
        return `
            <div class="service-card card ${selectedClass}" data-id="${service.id}" data-name="${service.name}" data-price="${service.price}" data-duration="${service.duration_mins}">
                <input type="checkbox" class="service-checkbox" ${checkedAttr}>
                <div class="service-info">
                    <div class="service-icon">
                        ${iconHtml}
                    </div>
                    <div class="service-details">
                        <div class="service-name">${service.name}</div>
                        <div class="service-meta">
                            <span class="badge badge-${service.gender_type.toLowerCase()}">${service.gender_type}</span>
                            <span>${service.duration_mins} min</span> • 
                            <span style="font-weight: 600;">NPR ${service.price}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    // Add event listeners
    document.querySelectorAll('.service-card').forEach(card => {
        const checkbox = card.querySelector('.service-checkbox');
        
        card.addEventListener('click', function(e) {
            if (e.target !== checkbox) {
                checkbox.checked = !checkbox.checked;
            }
            toggleService(this, checkbox.checked);
        });

        checkbox.addEventListener('change', function() {
            toggleService(card, this.checked);
        });
    });
}

function toggleService(card, isSelected) {
    const serviceId = card.dataset.id;
    
    if (isSelected) {
        card.classList.add('selected');
        if (!selectedServices.find(s => s.id === serviceId)) {
            selectedServices.push({
                id: serviceId,
                name: card.dataset.name,
                price: card.dataset.price,
                duration: card.dataset.duration
            });
        }
    } else {
        card.classList.remove('selected');
        const index = selectedServices.findIndex(s => s.id === serviceId);
        if (index > -1) {
            selectedServices.splice(index, 1);
        }
    }

    document.getElementById('nextBtn').disabled = selectedServices.length === 0;
}

document.getElementById('nextBtn').addEventListener('click', function() {
    if (selectedServices.length > 0) {
        sessionStorage.setItem('selectedServices', JSON.stringify(selectedServices));
        window.location.href = 'book_step3.php';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
