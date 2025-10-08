<?php
if (session_status() == PHP_SESSION_NONE) {
    // This check is crucial if config.php might not have been included yet,
    // or if session_start() in config.php failed for some reason.
    // However, config.php should ideally be included before this header.
    session_start();
}
require_once __DIR__ . '/../config.php'; // Adjust path as necessary
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF_8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : COMPANY_NAME; ?> - Invoice System</title>
    <!-- Basic Styling - You can replace this with a link to a proper CSS file -->
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; color: #333; }
        .container { width: 80%; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        header { background-color: #333; color: #fff; padding: 10px 0; text-align: center; }
        header h1 { margin: 0; }
        nav ul { list-style-type: none; padding: 0; text-align: center; background-color: #444; }
        nav ul li { display: inline; margin-right: 20px; }
        nav ul li a { color: white; text-decoration: none; padding: 10px 15px; display: inline-block; }
        nav ul li a:hover { background-color: #555; }
        .dropdown { position: relative; display: inline-block; }
        .dropdown-menu { display: none; position: absolute; background-color: #444; min-width: 160px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1; list-style-type: none; padding: 0; margin: 0; }
        .dropdown-menu li a { color: white; padding: 12px 16px; text-decoration: none; display: block; }
        .dropdown-menu li a:hover { background-color: #555; }
        .dropdown:hover .dropdown-menu { display: block; }
        .content { padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="email"],
        .form-group input[type="date"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .form-group textarea { min-height: 80px; }
        .btn { padding: 10px 15px; background-color: #5cb85c; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn:hover { background-color: #4cae4c; }
        .btn-danger { background-color: #d9534f; }
        .btn-danger:hover { background-color: #c9302c; }
        .btn-info { background-color: #5bc0de; }
        .btn-info:hover { background-color: #46b8da; }
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
        .alert-danger { color: #a94442; background-color: #f2dede; border-color: #ebccd1; }
        .alert-success { color: #3c763d; background-color: #dff0d8; border-color: #d6e9c6; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .actions a { margin-right: 5px; text-decoration: none; }
        footer { text-align: center; padding: 20px; margin-top: 30px; background-color: #333; color: #fff; }
    </style>
</head>
<body>

<header>
    <h1><?php echo COMPANY_NAME; ?> - Invoice System</h1>
</header>

<nav>
    <ul>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="<?php echo APP_BASE_URL; ?>index.php">Dashboard (Invoices)</a></li>
            <li><a href="<?php echo APP_BASE_URL; ?>create_invoice.php">Create Invoice</a></li>
            <li><a href="<?php echo APP_BASE_URL; ?>list_clients.php">Manage Clients</a></li>
            <li><a href="<?php echo APP_BASE_URL; ?>list_services.php">Manage Services</a></li>
            <li><a href="<?php echo APP_BASE_URL; ?>list_expenses.php">Manage Expenses</a></li>
            <li class="dropdown">
                <a href="#">Reports</a>
                <ul class="dropdown-menu">
                    <li><a href="<?php echo APP_BASE_URL; ?>report_profit_loss.php">Profit & Loss</a></li>
                    <li><a href="<?php echo APP_BASE_URL; ?>report_vat.php">VAT Report</a></li>
                </ul>
            </li>
            <li><a href="<?php echo APP_BASE_URL; ?>logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
        <?php else: ?>
            <li><a href="<?php echo APP_BASE_URL; ?>login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>

<div class="container">
    <div class="content">
    <!-- Main content will go here -->
