<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Sharma Salon</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            max-width: 400px;
            width: 90%;
            padding: 30px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        #error-msg {
            color: var(--danger-color);
            margin-bottom: 15px;
            display: none;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2 class="text-center" style="color: var(--primary-color);">Admin Login</h2>
    
    <div id="error-msg" class="text-center"></div>

    <form id="loginForm">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" id="email" required placeholder="Enter your email">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" id="password" required placeholder="Enter your password">
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
    
    <div class="text-center mt-2">
        <a href="../../index.php" style="font-size: 14px; text-decoration: underline;">Back to Home</a>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const errorMsg = document.getElementById('error-msg');
    
    fetch('../../ajax/login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'dashboard.php';
        } else {
            errorMsg.textContent = data.message;
            errorMsg.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorMsg.textContent = "An error occurred.";
        errorMsg.style.display = 'block';
    });
});
</script>

</body>
</html>
