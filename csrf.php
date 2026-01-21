<?php
// includes/csrf.php
// CSRF Token Helper Functions

/**
 * Safely start a session with a consistent cookie path
 */
function startBookingSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Force session cookie to be available across all subdirectories (/pages, /ajax, etc.)
        // We use '/' as the path for maximum compatibility on local servers like XAMPP
        session_set_cookie_params([
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

/**
 * Generate a CSRF token and store it in the session
 * @return string The generated token
 */
function generateCSRFToken() {
    startBookingSession();
    
    if (empty($_SESSION['csrf_token'])) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
    } else {
        $token = $_SESSION['csrf_token'];
    }
    // Refresh the timestamp on every access to prevent expiration during long forms
    $_SESSION['csrf_token_time'] = time();
    
    return $token;
}

/**
 * Get the current CSRF token from session
 * @return string|null The token or null if not set
 */
function getCSRFToken() {
    startBookingSession();
    
    if (isset($_SESSION['csrf_token'])) {
        // Refresh the timestamp on every access to keep the session alive during activity
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'] ?? null;
}

/**
 * Verify a CSRF token
 * @param string $token The token to verify
 * @return bool True if valid, false otherwise
 */
function verifyCSRFToken($token) {
    startBookingSession();
    
    // Check if token exists in session
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Check if token is expired (valid for 4 hours for better reliability)
    if (isset($_SESSION['csrf_token_time'])) {
        $tokenAge = time() - $_SESSION['csrf_token_time'];
        if ($tokenAge > 14400) { // 4 hours
            return false;
        }
    }
    
    // Compare tokens using timing-safe comparison
    $isValid = hash_equals($_SESSION['csrf_token'], $token);
    
    if ($isValid) {
        // Refresh timestamp on successful verification to slide the window
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $isValid;
}

/**
 * Output a hidden CSRF token field for forms
 */
function csrfField() {
    $token = getCSRFToken();
    if (!$token) {
        $token = generateCSRFToken();
    }
    
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
?>
