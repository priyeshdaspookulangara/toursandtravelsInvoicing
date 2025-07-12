<?php
// view_invoices/index.php
// Displays a list of all existing invoices.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_connect.php'; // $mysqli is now available

$page_title = "View Invoices";
$current_page = "view_invoices"; // For highlighting active link in sidebar

$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
$error_message = '';
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}


// Fetch all invoices from the database
// Ordered by invoice_date descending, then by id descending as a fallback for same-day invoices
$invoices = [];
$sql = "SELECT id, invoice_number, client_name, invoice_date, total_amount, created_at
        FROM invoices
        ORDER BY invoice_date DESC, id DESC";
$result = mysqli_query($mysqli, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $invoices[] = $row;
    }
} else {
    // Log error and set a message for the user
    error_log("Error fetching invoices: " . mysqli_error($mysqli));
    $error_message = "Could not retrieve invoices from the database. Please try again later.";
}

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h2><?php echo $page_title; ?></h2>
        </div>
        <div class="col text-end">
            <a href="<?php echo BASE_URL; ?>new_invoice/" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Create New Invoice
            </a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            All Invoices
        </div>
        <div class="card-body">
            <?php if (empty($invoices) && empty($error_message)): ?>
                <div class="alert alert-info" role="alert">
                    No invoices found. <a href="<?php echo BASE_URL; ?>new_invoice/" class="alert-link">Create the first one!</a>
                </div>
            <?php elseif (!empty($invoices)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Client Name</th>
                                <th>Invoice Date</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['client_name']); ?></td>
                                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($invoice['invoice_date']))); ?></td>
                                    <td class="text-end"><?php echo number_format($invoice['total_amount'], 2); ?></td>
                                    <td class="text-center">
                                        <a href="<?php echo BASE_URL; ?>invoice_details/?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <!-- Add Edit and Delete buttons later if needed -->
                                        <!--
                                        <a href="<?php echo BASE_URL; ?>edit_invoice/?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-warning ms-1" title="Edit Invoice">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>delete_invoice/?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-danger ms-1" title="Delete Invoice" onclick="return confirm('Are you sure you want to delete this invoice?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Update breadcrumbs for this page
    var $breadcrumbContainer = $("#breadcrumb-container");
    if ($breadcrumbContainer.length) {
        var homeUrl = '<?php echo BASE_URL; ?>';
        $breadcrumbContainer.empty()
            .append('<li class="breadcrumb-item"><a href="' + homeUrl + '">Home</a></li>')
            .append('<li class="breadcrumb-item active" aria-current="page"><?php echo $page_title; ?></li>');
        $("#breadcrumb-nav-container").show();
    }

    // Auto-dismiss success alerts after a few seconds
    window.setTimeout(function() {
        $(".alert-success").fadeTo(500, 0).slideUp(500, function(){
            $(this).remove();
        });
    }, 4000); // 4 seconds
});
</script>

<?php
require_once __DIR__ . '/../templates/footer.php';
?>
