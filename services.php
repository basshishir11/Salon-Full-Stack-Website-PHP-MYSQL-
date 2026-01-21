<?php require_once '../includes/header.php'; 
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get all active services
$query = "SELECT * FROM services WHERE is_active = 1 ORDER BY gender_type, name";
$stmt = $db->query($query);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by gender
$men_services = array_filter($services, fn($s) => $s['gender_type'] === 'Men');
$women_services = array_filter($services, fn($s) => $s['gender_type'] === 'Women');
$unisex_services = array_filter($services, fn($s) => $s['gender_type'] === 'Unisex');
?>

<style>
.services-section {
    margin-bottom: 40px;
}
.section-title {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 3px solid;
}
.section-title.men { border-color: var(--men-blue); color: var(--men-blue); }
.section-title.women { border-color: var(--women-pink); color: var(--women-pink); }
.section-title.unisex { border-color: #9c27b0; color: #9c27b0; }

.service-list {
    display: grid;
    gap: 15px;
}
.service-item {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}
.service-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    background: var(--light-gray);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: #ccc;
}
.service-details {
    flex: 1;
    padding: 10px 10px 10px 0;
}
.service-name {
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 5px;
}
.service-meta {
    font-size: 13px;
    color: #666;
}
.service-price {
    font-weight: 600;
    color: var(--primary-color);
    font-size: 18px;
    padding-right: 15px;
}
</style>

<style>
.gender-selection {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
}
.gender-btn {
    flex: 1;
    background: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    border: 2px solid #eee;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.gender-btn i {
    font-size: 32px;
    margin-bottom: 10px;
    display: block;
}
.gender-btn.active.men { border-color: var(--men-blue); background: #f0f7ff; color: var(--men-blue); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(74, 144, 217, 0.2); }
.gender-btn.active.women { border-color: var(--women-pink); background: #fff0f6; color: var(--women-pink); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(233, 30, 140, 0.2); }
.gender-btn.men i { color: var(--men-blue); }
.gender-btn.women i { color: var(--women-pink); }

.search-box {
    margin-bottom: 25px;
}
.search-box input {
    width: 100%;
    padding: 12px 15px 12px 45px;
    border: 2px solid #ddd;
    border-radius: 25px;
    font-size: 16px;
    background: white;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23999'%3E%3Cpath d='M15.5 14h-.79l-.28-.27a6.5 6.5 0 0 0 1.48-5.34c-.47-2.78-2.79-5-5.59-5.34a6.505 6.505 0 0 0-7.27 7.27c.34 2.8 2.56 5.12 5.34 5.59a6.5 6.5 0 0 0 5.34-1.48l.27.28v.79l4.25 4.25c.41.41 1.08.41 1.49 0 .41-.41.41-1.08 0-1.49L15.5 14zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: 15px center;
    background-size: 20px;
    transition: border-color 0.3s;
}
.search-box input:focus {
    outline: none;
    border-color: var(--primary-color);
}
</style>

<div class="page-header text-center mb-3">
    <h2>Our Services</h2>
    <p style="color: #666;">Select category to view services</p>
</div>

<div class="gender-selection">
    <div class="gender-btn men active" onclick="filterServices('Men')">
        <i class="fas fa-mars"></i>
        <strong>Men</strong>
    </div>
    <div class="gender-btn women" onclick="filterServices('Women')">
        <i class="fas fa-venus"></i>
        <strong>Women</strong>
    </div>
</div>

<div class="search-box">
    <input type="text" id="serviceSearch" placeholder="Search services..." oninput="searchServices()">
</div>

<div id="menSection">
    <?php if (!empty($men_services)): ?>
    <div class="services-section">
        <h3 class="section-title men">Men's Services</h3>
        <div class="service-list">
            <?php foreach ($men_services as $service): ?>
                <div class="service-item">
                    <?php if (!empty($service['image_path']) && file_exists('../' . $service['image_path'])): ?>
                        <img src="../<?php echo htmlspecialchars($service['image_path']); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" class="service-image">
                    <?php else: ?>
                        <div class="service-image">
                            <i class="fas fa-cut"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="service-details">
                        <div class="service-name"><?php echo htmlspecialchars($service['name']); ?></div>
                        <div class="service-meta">
                            <i class="fas fa-clock"></i> <?php echo $service['duration_mins']; ?> minutes
                        </div>
                    </div>
                    
                    <div class="service-price">
                        NPR <?php echo number_format($service['price'], 0); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<div id="womenSection" style="display: none;">
    <?php if (!empty($women_services)): ?>
    <div class="services-section">
        <h3 class="section-title women">Women's Services</h3>
        <div class="service-list">
            <?php foreach ($women_services as $service): ?>
                <div class="service-item">
                    <?php if (!empty($service['image_path']) && file_exists('../' . $service['image_path'])): ?>
                        <img src="../<?php echo htmlspecialchars($service['image_path']); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" class="service-image">
                    <?php else: ?>
                        <div class="service-image">
                            <i class="fas fa-cut"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="service-details">
                        <div class="service-name"><?php echo htmlspecialchars($service['name']); ?></div>
                        <div class="service-meta">
                            <i class="fas fa-clock"></i> <?php echo $service['duration_mins']; ?> minutes
                        </div>
                    </div>
                    
                    <div class="service-price">
                        NPR <?php echo number_format($service['price'], 0); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<div id="unisexSection">
    <?php if (!empty($unisex_services)): ?>
    <div class="services-section">
        <h3 class="section-title unisex">Unisex Services</h3>
        <div class="service-list">
            <?php foreach ($unisex_services as $service): ?>
                <div class="service-item">
                    <?php if (!empty($service['image_path']) && file_exists('../' . $service['image_path'])): ?>
                        <img src="../<?php echo htmlspecialchars($service['image_path']); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" class="service-image">
                    <?php else: ?>
                        <div class="service-image">
                            <i class="fas fa-cut"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="service-details">
                        <div class="service-name"><?php echo htmlspecialchars($service['name']); ?></div>
                        <div class="service-meta">
                            <i class="fas fa-clock"></i> <?php echo $service['duration_mins']; ?> minutes
                        </div>
                    </div>
                    
                    <div class="service-price">
                        NPR <?php echo number_format($service['price'], 0); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="text-center mt-3">
    <a href="book_step1.php" class="btn btn-primary">Book Now</a>
    <a href="../index.php" class="btn btn-secondary">Back to Home</a>
</div>

<script>
function filterServices(gender) {
    // Buttons
    document.querySelectorAll('.gender-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector('.gender-btn.' + gender.toLowerCase()).classList.add('active');
    
    // Sections
    if (gender === 'Men') {
        document.getElementById('menSection').style.display = 'block';
        document.getElementById('womenSection').style.display = 'none';
    } else {
        document.getElementById('menSection').style.display = 'none';
        document.getElementById('womenSection').style.display = 'block';
    }
    
    // Clear and re-apply search after switching
    searchServices();
}

function searchServices() {
    const searchTerm = document.getElementById('serviceSearch').value.toLowerCase();
    const allServiceItems = document.querySelectorAll('.service-item');
    
    allServiceItems.forEach(item => {
        const serviceName = item.querySelector('.service-name').textContent.toLowerCase();
        
        if (serviceName.includes(searchTerm)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

</script>

<?php require_once '../includes/footer.php'; ?>

