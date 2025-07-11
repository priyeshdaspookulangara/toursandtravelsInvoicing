<?php
// Ensure session is started. config.php should handle this, but as a fallback.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in.
// If 'user_id' is not set in the session, redirect to login.php.
if (!isset($_SESSION['user_id'])) {
    // Store the requested page URL to redirect back after login (optional)
    // $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];

    // Determine the correct path to login.php relative to APP_BASE_URL
    // This assumes APP_BASE_URL is defined in config.php
    if (!defined('APP_BASE_URL')) {
        // Fallback if config.php wasn't included or APP_BASE_URL isn't set
        // This might happen if auth.php is included directly without prior setup
        // A more robust solution would ensure config.php is always loaded first.
        // For now, we assume a common structure or die.
        // Consider the case where auth.php is in a subdirectory.
        $login_url = 'login.php'; // Simplistic fallback
        // A better fallback might try to guess based on script location,
        // but for this project, APP_BASE_URL from config.php is expected.
        // If APP_BASE_URL is properly set in config.php and config.php is included
        // before auth.php, this direct call won't be necessary.
        require_once __DIR__ . '/config.php'; // Try to load config if not already
    }

    header("Location: " . APP_BASE_URL . "login.php");
    exit;
}

// Optional: Could add checks for user roles or permissions here if the application grows.
// Optional: Update last activity time for session timeout (more advanced)
// $_SESSION['last_activity'] = time();
?>
