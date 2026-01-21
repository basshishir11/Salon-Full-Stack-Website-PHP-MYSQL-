<?php
// pages/admin/add_service.php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get categories
$categories = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Service</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-nav {
            background: var(--dark-gray);
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .admin-nav a { color: #fff; margin-left: 15px; text-decoration: underline; }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        .image-preview {
            width: 200px;
            height: 200px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            overflow: hidden;
        }
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .error-msg {
            color: var(--danger-color);
            font-size: 14px;
            margin-top: 10px;
            display: none;
        }
    </style>
</head>
<body>

<div class="admin-nav">
    <div><strong>Add Service</strong></div>
    <div>
        <a href="manage_services.php">Back to Services</a>
        <a href="dashboard.php">Dashboard</a>
    </div>
</div>

<div class="container" style="max-width: 600px;">
    <h2>Add New Service</h2>

    <form id="addServiceForm" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Service Name *</label>
            <input type="text" id="name" name="name" required>
        </div>

        <div class="form-group">
            <label for="category">Category (Men/Women/Unisex) *</label>
            <select id="category" name="category_id" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" data-name="<?php echo htmlspecialchars($cat['name']); ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- gender_type is now automatically set based on category -->
        <input type="hidden" id="gender_type" name="gender_type">

        <div class="form-group">
            <label for="price">Price (NPR) *</label>
            <input type="number" id="price" name="price" step="0.01" min="0" required>
        </div>

        <div class="form-group">
            <label for="duration">Duration (minutes) *</label>
            <input type="number" id="duration" name="duration_mins" min="1" required>
        </div>

        <div class="form-group">
            <label for="image">Service Image (Optional)</label>
            <input type="file" id="image" name="image" accept="image/*">
            <div class="image-preview" id="imagePreview">
                <span style="color: #ccc;">No image selected</span>
            </div>
        </div>

        <div class="error-msg" id="errorMsg"></div>

        <button type="submit" class="btn btn-primary" id="submitBtn">Add Service</button>
        <a href="manage_services.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
// Image preview
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('imagePreview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '<span style="color: #ccc;">No image selected</span>';
    }
});

// Form submission
document.getElementById('addServiceForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = document.getElementById('submitBtn');
    const errorMsg = document.getElementById('errorMsg');
    
    // Auto-set gender_type hidden field based on selected category text
    const categorySelect = document.getElementById('category');
    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    document.getElementById('gender_type').value = selectedOption.getAttribute('data-name');

    submitBtn.disabled = true;
    submitBtn.textContent = 'Adding...';
    errorMsg.style.display = 'none';

    fetch('../../ajax/add_service.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'manage_services.php';
        } else {
            errorMsg.textContent = data.message || 'Failed to add service';
            errorMsg.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Add Service';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorMsg.textContent = 'An error occurred';
        errorMsg.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Add Service';
    });
});
</script>

</body>
</html>
