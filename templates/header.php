<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';

if (!function_exists('get_breadcrumbs')) {
    function get_breadcrumbs() {
        $path = basename($_SERVER['SCRIPT_NAME']);
        $breadcrumbs = ['<li class="breadcrumb-item"><a href="' . APP_BASE_URL . 'index.php">Dashboard</a></li>'];

        switch ($path) {
            case 'index.php': return '<li class="breadcrumb-item active" aria-current="page">Dashboard</li>';
            case 'create_invoice.php': $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Create Invoice</li>'; break;
            case 'edit_invoice.php': $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Edit Invoice</li>'; break;
            case 'view_invoice.php': $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">View Invoice</li>'; break;
            case 'list_clients.php': $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Manage Clients</li>'; break;
            case 'add_client.php': $breadcrumbs[] = '<li class="breadcrumb-item"><a href="list_clients.php">Manage Clients</a></li><li class="breadcrumb-item active" aria-current="page">Add Client</li>'; break;
            case 'list_vendors.php': $breadcrumbs[] = '<li class="breadcrumb-item active" aria-current="page">Manage Vendors</li>'; break;
            case 'add_vendor.php': $breadcrumbs[] = '<li class="breadcrumb-item"><a href="list_vendors.php">Manage Vendors</a></li><li class="breadcrumb-item active" aria-current="page">Add Vendor</li>'; break;
            // Add other cases here
        }
        return implode('', $breadcrumbs);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : COMPANY_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_BASE_URL; ?>assets/css/modern-style.css">
</head>
<body>

<?php if (isset($_SESSION['user_id'])): ?>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><?php echo COMPANY_NAME; ?></h3>
        </div>
        <ul class="list-unstyled components">
            <li><a href="<?php echo APP_BASE_URL; ?>index.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
            <li><a href="<?php echo APP_BASE_URL; ?>create_invoice.php"><i class="fas fa-plus me-2"></i>Create Invoice</a></li>
            <li><a href="<?php echo APP_BASE_URL; ?>list_clients.php"><i class="fas fa-users me-2"></i>Manage Clients</a></li>
            <li><a href="<?php echo APP_BASE_URL; ?>list_vendors.php"><i class="fas fa-store me-2"></i>Manage Vendors</a></li>
            <li><a href="<?php echo APP_BASE_URL; ?>list_services.php"><i class="fas fa-concierge-bell me-2"></i>Manage Services</a></li>
            <li><a href="<?php echo APP_BASE_URL; ?>list_expenses.php"><i class="fas fa-money-bill-wave me-2"></i>Manage Expenses</a></li>
            <li><a href="<?php echo APP_BASE_URL; ?>list_fixed_assets.php"><i class="fas fa-building me-2"></i>Fixed Assets</a></li>
            <li>
                <a href="#accountingSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-calculator me-2"></i>Accounting</a>
                <ul class="collapse list-unstyled" id="accountingSubmenu">
                    <li><a href="<?php echo APP_BASE_URL; ?>list_vendor_bills.php">Vendor Bills</a></li>
                    <li><a href="<?php echo APP_BASE_URL; ?>bank_reconciliation.php">Bank Reconciliation</a></li>
                </ul>
            </li>
            <li>
                <a href="#reportsSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-chart-pie me-2"></i>Reports</a>
                <ul class="collapse list-unstyled" id="reportsSubmenu">
                    <li><a href="<?php echo APP_BASE_URL; ?>report_ap_aging.php">A/P Aging</a></li>
                    <li><a href="<?php echo APP_BASE_URL; ?>report_balance_sheet.php">Balance Sheet</a></li>
                    <li><a href="<?php echo APP_BASE_URL; ?>report_trial_balance.php">Trial Balance</a></li>
                    <li><a href="<?php echo APP_BASE_URL; ?>report_profit_loss.php">P&L</a></li>
                    <li><a href="<?php echo APP_BASE_URL; ?>report_vat.php">VAT Report</a></li>
                </ul>
            </li>
        </ul>
        <ul class="list-unstyled CTAs">
            <li><a href="<?php echo APP_BASE_URL; ?>logout.php" class="logout btn btn-danger text-white"><i class="fas fa-sign-out-alt me-2"></i>Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
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
        <main class="container-fluid p-4">
<?php else: ?>
    <!-- Unauthenticated Layout -->
    <div class="container">
        <main class="py-5">
<?php endif; ?>
    <!-- Page-specific content starts here -->