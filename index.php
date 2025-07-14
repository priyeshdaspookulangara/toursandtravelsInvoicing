<?php
// index.php (Root)
// Main entry point for the application.
// Can serve as a simple dashboard or redirect to a default page.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure config.php is loaded first for constants like BASE_URL
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    // Critical error if config.php is missing.
    // Define fallbacks to prevent immediate fatal errors, but this needs fixing.
    if (!defined('BASE_URL')) define('BASE_URL', '/');
    error_log("CRITICAL: config.php not found from root index.php. Application may not work correctly.");
    // For a real app, you might die here or redirect to an error page.
}


// Then load database connection
if (file_exists(__DIR__ . '/db_connect.php')) {
    require_once __DIR__ . '/db_connect.php'; // $mysqli is now available
} else {
    // Critical error if db_connect.php is missing.
    error_log("CRITICAL: db_connect.php not found from root index.php. Database functionality will fail.");
    // Display an error and exit, as the app is likely unusable.
    die("A critical file (db_connect.php) is missing. The application cannot continue. Please contact support.");
}


$page_title = "Dashboard - Invoicing App";
$current_page = "dashboard"; // For highlighting active link in sidebar

// Optional: Fetch some quick stats for the dashboard
$total_invoices = 0;
$total_amount_billed = 0.00;
// $pending_invoices = 0; // Example, if you add a status column to your invoices table

// Query for total invoices
$result_total_invoices = mysqli_query($mysqli, "SELECT COUNT(*) as count FROM invoices");
if ($result_total_invoices) {
    $row = mysqli_fetch_assoc($result_total_invoices);
    $total_invoices = $row['count'];
    mysqli_free_result($result_total_invoices);
} else {
    error_log("Error fetching total invoices count: " . mysqli_error($mysqli));
}

// Query for total amount billed
$result_total_amount = mysqli_query($mysqli, "SELECT SUM(total_amount) as sum_total FROM invoices");
if ($result_total_amount) {
    $row = mysqli_fetch_assoc($result_total_amount);
    $total_amount_billed = $row['sum_total'] ?? 0.00; // Use null coalescing operator for PHP 7+
    mysqli_free_result($result_total_amount);
} else {
    error_log("Error fetching sum of total amounts: " . mysqli_error($mysqli));
}


require_once __DIR__ . '/templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col">
            <h2>Welcome to Your Invoicing Dashboard!</h2>
            <p class="lead">Quickly manage your invoices and view key metrics.</p>
        </div>
    </div>

    <!-- Dashboard Stats -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Invoices</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_invoices; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <a href="<?php echo BASE_URL; ?>view_invoices/" class="stretched-link text-decoration-none">
                        <small class="text-muted">View Details <i class="fas fa-arrow-circle-right"></i></small>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Billed</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($total_amount_billed, 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                     <small class="text-muted">(Across all invoices)</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Create New Invoice</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">&nbsp;</div> <!-- For alignment -->
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-plus-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                     <a href="<?php echo BASE_URL; ?>new_invoice/" class="stretched-link text-decoration-none">
                        <small class="text-muted">Start New Invoice <i class="fas fa-arrow-circle-right"></i></small>
                    </a>
                </div>
            </div>
        </div>

        <!-- Placeholder for another stat card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Actions (Example)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                        </div>
                    </div>
                     <small class="text-muted">(e.g., Overdue Invoices)</small>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="<?php echo BASE_URL; ?>new_invoice/" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-plus fa-fw me-2"></i>Create New Invoice</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="<?php echo BASE_URL; ?>view_invoices/" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-list fa-fw me-2"></i>View All Invoices</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <!-- Add more links like "Manage Clients", "Settings" when implemented -->
                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center disabled" tabindex="-1" aria-disabled="true">
                            <span><i class="fas fa-users fa-fw me-2"></i>Manage Clients (Coming Soon)</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center disabled" tabindex="-1" aria-disabled="true">
                            <span><i class="fas fa-cogs fa-fw me-2"></i>Application Settings (Coming Soon)</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5 mb-4">
             <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Activity (Placeholder)</h6>
                </div>
                <div class="card-body">
                    <p class="text-center text-muted mt-3">
                        <i class="fas fa-history fa-2x mb-2"></i><br>
                        No recent activity to display.
                    </p>
                    <!-- Example of recent activity item:
                    <div class="small">
                        <strong>Invoice INV-00123</strong> created for Client X.
                        <span class="text-muted float-end"><em>2 hours ago</em></span>
                    </div>
                    <hr class="my-2">
                    -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Update breadcrumbs for this page (Dashboard/Home)
    var $breadcrumbContainer = $("#breadcrumb-container");
    if ($breadcrumbContainer.length) {
        var homeUrl = '<?php echo BASE_URL; ?>'; // Should be defined from config.php
        $breadcrumbContainer.empty(); // Clear any previous breadcrumbs

        // For the dashboard/root index.php, "Home" is usually the active page.
        $breadcrumbContainer.append('<li class="breadcrumb-item active" aria-current="page"><i class="fas fa-home me-1"></i>Home</li>');

        $("#breadcrumb-nav-container").show(); // Ensure the breadcrumb area is visible
    }
});
</script>

<?php
require_once __DIR__ . '/templates/footer.php';
?>
