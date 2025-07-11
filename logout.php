<?php
// config.php should ensure session is started.
require_once 'config.php';

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to login page
// Ensure APP_BASE_URL is available. If config.php was included, it should be.
$login_page_url = defined('APP_BASE_URL') ? APP_BASE_URL . 'login.php' : 'login.php';
header("Location: " . $login_page_url);
exit;
?>
