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
define('APP_BASE_URL', 'http://localhost/'); // Adjust to your local setup, include trailing slash
define('VAT_RATE', 5.00); // VAT Rate in percentage (e.g., 5.00 for 5%)

// --- Default Account IDs from Chart of Accounts ---
// These should correspond to the IDs of the accounts created in `setup_accounts.php`
// It's a good practice to define them here to avoid hardcoding IDs in the business logic.
define('ACCOUNT_ID_CASH', 1);
define('ACCOUNT_ID_ACCOUNTS_RECEIVABLE', 2);
define('ACCOUNT_ID_INPUT_VAT', 4);
define('ACCOUNT_ID_ACCOUNTS_PAYABLE', 10);
define('ACCOUNT_ID_SALES_REVENUE', 30); // Corrected from 10
define('ACCOUNT_ID_VAT_PAYABLE', 11);
define('ACCOUNT_ID_SALES_REVENUE_TICKETS', 32);
define('ACCOUNT_ID_SALES_REVENUE_DTP', 33);
define('ACCOUNT_ID_COST_OF_SALES_TICKETS', 47);
define('ACCOUNT_ID_ACCUMULATED_DEPRECIATION', 5);
define('ACCOUNT_ID_DEPRECIATION_EXPENSE', 46);


// Error reporting - Force display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

?>
