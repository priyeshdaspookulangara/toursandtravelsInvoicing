<?php

// Database Configuration
define('DB_HOST', 'localhost'); // Or your database host
define('DB_USER', 'root');    // Your database username
define('DB_PASS', '');        // Your database password - SET THIS!
define('DB_NAME', 'invoice_system'); // Your database name

// Company Details (Pre-defined)
define('COMPANY_NAME', 'Apex Tours & Travels');
define('COMPANY_ADDRESS', '123 Adventure Road, Travel City, TC 56789');
define('COMPANY_PHONE', '+1-555-123-4567');
define('COMPANY_EMAIL', 'contact@apextravels.com');
define('COMPANY_WEBSITE', 'www.apextravels.com');
define('COMPANY_LOGO_PATH', 'assets/images/logo.png'); // Example path, ensure this image exists or remove if not used

// Application Settings
define('APP_BASE_URL', 'http://localhost/invoice_generator/'); // Adjust to your local setup, include trailing slash

// Error reporting - Recommended for development, turn off or log to file in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

?>
