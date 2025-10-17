<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';

// Define a function to generate breadcrumbs
function get_breadcrumbs() {
    $path = basename($_SERVER['SCRIPT_NAME']);
    $breadcrumbs = [];

    if ($path == 'index.php' || $path == '') {
        $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Dashboard</li>';
    } else {
        $breadcrumbs[] = '<li class="breadcrumb-item"><a href="' . APP_BASE_URL . 'index.php">Dashboard</a></li>';
    }

    // Don't add another breadcrumb for the dashboard itself
    if ($path != 'index.php' && $path != '') {
        switch ($path) {
            case 'create_invoice.php':
                $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Create Invoice</li>';
                break;
            case 'edit_invoice.php':
                $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Edit Invoice</li>';
                break;
            case 'view_invoice.php':
                 $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">View Invoice</li>';
                break;
            case 'list_clients.php':
                $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Manage Clients</li>';
                break;
            case 'add_client.php':
                 $breadcrumbs[] = '<li class="breadcrumb-item"><a href="list_clients.php">Manage Clients</a></li>';
                 $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Add Client</li>';
                break;
            case 'list_vendors.php':
                $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Manage Vendors</li>';
                break;
            case 'add_vendor.php':
                $breadcrumbs[] = '<li class="breadcrumb-item"><a href="list_vendors.php">Manage Vendors</a></li>';
                $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Add Vendor</li>';
                break;
            case 'list_services.php':
                $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Manage Services</li>';
                break;
            case 'add_service.php':
                $breadcrumbs[] = '<li class="breadcrumb-item"><a href="list_services.php">Manage Services</a></li>';
                $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Add Service</li>';
                break;
            case 'list_expenses.php':
                $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Manage Expenses</li>';
                break;
            case 'add_expense.php':
                $breadcrumbs[] = '<li class="breadcrumb-item"><a href="list_expenses.php">Manage Expenses</a></li>';
                $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Add Expense</li>';
                break;
            case 'list_fixed_assets.php':
                $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Fixed Asset Register</li>';
                break;
            case 'add_fixed_asset.php':
                $breadcrumbs[] = '<li class="breadcrumb-item"><a href="list_fixed_assets.php">Fixed Asset Register</a></li>';
                $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Add Fixed Asset</li>';
                break;
            case 'bank_reconciliation.php':
                $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Bank Reconciliation</li>';
                break;
            case 'report_profit_loss.php':
                $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Profit & Loss Report</li>';
                break;
            case 'report_vat.php':
                $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">VAT Report</li>';
                break;
            case 'report_trial_balance.php':
                $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Trial Balance Report</li>';
                break;
            case 'report_balance_sheet.php':
                $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Balance Sheet Report</li>';
                break;
            case 'report_ap_aging.php':
                $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">A/P Aging Report</li>';
                break;
        }
    }
    return implode('', $breadcrumbs);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : COMPANY_NAME; ?> - Invoice System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?php echo APP_BASE_URL; ?>assets/css/style.css">
</head>
<body>

<?php if (isset($_SESSION['user_id'])): ?>
    <!-- Authenticated Layout -->
    <div class="d-flex">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3><?php echo COMPANY_NAME; ?></h3>
            </div>
            <ul class="list-unstyled components">
                <li><a href="<?php echo APP_BASE_URL; ?>index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="<?php echo APP_BASE_URL; ?>create_invoice.php"><i class="fas fa-plus"></i> Create Invoice</a></li>
                <li><a href="<?php echo APP_BASE_URL; ?>list_clients.php"><i class="fas fa-users"></i> Manage Clients</a></li>
                <li><a href="<?php echo APP_BASE_URL; ?>list_vendors.php"><i class="fas fa-store"></i> Manage Vendors</a></li>
                <li><a href="<?php echo APP_BASE_URL; ?>list_services.php"><i class="fas fa-concierge-bell"></i> Manage Services</a></li>
                <li><a href="<?php echo APP_BASE_URL; ?>list_expenses.php"><i class="fas fa-money-bill-wave"></i> Manage Expenses</a></li>
                <li><a href="<?php echo APP_BASE_URL; ?>list_fixed_assets.php"><i class="fas fa-building"></i> Fixed Asset Register</a></li>
                <li>
                    <a href="#accountingSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-calculator"></i> Accounting</a>
                    <ul class="collapse list-unstyled" id="accountingSubmenu">
                        <li><a href="<?php echo APP_BASE_URL; ?>list_vendor_bills.php">Vendor Bills</a></li>
                        <li><a href="<?php echo APP_BASE_URL; ?>bank_reconciliation.php">Bank Reconciliation</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#reportsSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-chart-pie"></i> Reports</a>
                    <ul class="collapse list-unstyled" id="reportsSubmenu">
                        <li><a href="<?php echo APP_BASE_URL; ?>report_ap_aging.php">A/P Aging</a></li>
                        <li><a href="<?php echo APP_BASE_URL; ?>report_balance_sheet.php">Balance Sheet</a></li>
                        <li><a href="<?php echo APP_BASE_URL; ?>report_trial_balance.php">Trial Balance</a></li>
                        <li><a href="<?php echo APP_BASE_URL; ?>report_profit_loss.php">Profit & Loss</a></li>
                        <li><a href="<?php echo APP_BASE_URL; ?>report_vat.php">VAT Report</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="list-unstyled CTAs">
                <li><a href="<?php echo APP_BASE_URL; ?>logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-dark">
                        <i class="fas fa-align-left"></i>
                    </button>
                    <div class="ms-auto">
                        <ol class="breadcrumb mb-0">
                            <?php echo get_breadcrumbs(); ?>
                        </ol>
                    </div>
                </div>
            </nav>
            <main class="container-fluid">
<?php else: ?>
    <!-- Unauthenticated Layout (e.g., for Login Page) -->
    <div class="login-container">
        <main class="login-box">
<?php endif; ?>
    <!-- Page-specific content starts here -->