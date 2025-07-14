<?php
// invoice_details/index.php
// Displays the complete details of a single invoice.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_connect.php'; // $mysqli is now available

$page_title = "Invoice Details";
$current_page = "invoice_details"; // For potential sidebar highlighting (though usually accessed from view_invoices)

$invoice = null;
$invoice_items = [];
$error_message = '';

// Get invoice ID from URL parameter
$invoice_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

if (!$invoice_id) {
    $_SESSION['error_message'] = "Invalid or missing invoice ID.";
    header("Location: " . BASE_URL . "view_invoices/");
    exit();
}

// Fetch invoice header details
$sql_invoice = "SELECT id, invoice_number, client_name, invoice_date, total_amount, created_at
                FROM invoices
                WHERE id = " . $invoice_id;
$result_invoice = mysqli_query($mysqli, $sql_invoice);

if ($result_invoice && mysqli_num_rows($result_invoice) > 0) {
    $invoice = mysqli_fetch_assoc($result_invoice);
    $page_title = "Invoice " . htmlspecialchars($invoice['invoice_number']); // Update page title

    // Fetch invoice items
    $sql_items = "SELECT id, item_description, quantity, unit_price, line_total
                  FROM invoice_items
                  WHERE invoice_id = " . $invoice_id . " ORDER BY id ASC";
    $result_items = mysqli_query($mysqli, $sql_items);

    if ($result_items) {
        while ($row = mysqli_fetch_assoc($result_items)) {
            $invoice_items[] = $row;
        }
    } else {
        error_log("Error fetching invoice items for ID $invoice_id: " . mysqli_error($mysqli));
        $error_message = "Could not retrieve items for this invoice. Please try again later.";
    }
} else {
    if (!$result_invoice) { // SQL error
        error_log("Error fetching invoice header for ID $invoice_id: " . mysqli_error($mysqli));
        $error_message = "Error retrieving invoice details. Please try again later.";
    } else { // Invoice not found
        $_SESSION['error_message'] = "Invoice with ID " . htmlspecialchars($invoice_id) . " not found.";
        header("Location: " . BASE_URL . "view_invoices/");
        exit();
    }
}


require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h2><?php echo $page_title; ?></h2>
        </div>
        <div class="col text-end">
            <a href="<?php echo BASE_URL; ?>view_invoices/" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Invoices
            </a>
            <!-- Add Print button later if needed -->
            <!-- <button onclick="window.print();" class="btn btn-info ms-2"><i class="fas fa-print me-1"></i> Print</button> -->
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($invoice): ?>
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Invoice #:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?><br>
                        <strong>Client:</strong> <?php echo htmlspecialchars($invoice['client_name']); ?>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <strong>Date:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($invoice['invoice_date']))); ?><br>
                        <strong>Created:</strong> <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($invoice['created_at']))); ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($invoice_items)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Description</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $item_count = 1; ?>
                                <?php foreach ($invoice_items as $item): ?>
                                    <tr>
                                        <td><?php echo $item_count++; ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($item['item_description'])); ?></td>
                                        <td class="text-end"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                        <td class="text-end"><?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td class="text-end"><?php echo number_format($item['line_total'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <!-- Subtotal if needed, though usually derived from items -->
                                <!-- <tr>
                                    <td colspan="4" class="text-end fw-bold">Subtotal:</td>
                                    <td class="text-end fw-bold"><?php // echo number_format(array_sum(array_column($invoice_items, 'line_total')), 2); ?></td>
                                </tr> -->
                                <!-- Tax, Discount rows can be added here if applicable -->
                                <tr>
                                    <td colspan="4" class="text-end fw-bold fs-5">Grand Total:</td>
                                    <td class="text-end fw-bold fs-5"><?php echo number_format($invoice['total_amount'], 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">This invoice currently has no items.</div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-muted">
                Thank you for your business!
                <!-- You can add payment terms or notes here -->
            </div>
        </div>
    <?php elseif (empty($error_message)): // Should not happen if $invoice is null and no error_message, but as a fallback ?>
        <div class="alert alert-warning">Invoice details could not be loaded.</div>
    <?php endif; ?>

</div>

<script>
jQuery(document).ready(function($) {
    // Update breadcrumbs for this page
    var $breadcrumbContainer = $("#breadcrumb-container");
    if ($breadcrumbContainer.length && <?php echo $invoice ? 'true' : 'false'; ?>) {
        var homeUrl = '<?php echo BASE_URL; ?>';
        var invoiceNum = '<?php echo $invoice ? htmlspecialchars(addslashes($invoice['invoice_number'])) : ""; ?>';
        $breadcrumbContainer.empty()
            .append('<li class="breadcrumb-item"><a href="' + homeUrl + '">Home</a></li>')
            .append('<li class="breadcrumb-item"><a href="' + homeUrl + 'view_invoices/">Invoices</a></li>')
            .append('<li class="breadcrumb-item active" aria-current="page">Invoice ' + invoiceNum + '</li>');
        $("#breadcrumb-nav-container").show();
    } else if ($breadcrumbContainer.length) {
        // Fallback breadcrumb if invoice data is not available
        var homeUrl = '<?php echo BASE_URL; ?>';
        $breadcrumbContainer.empty()
            .append('<li class="breadcrumb-item"><a href="' + homeUrl + '">Home</a></li>')
            .append('<li class="breadcrumb-item"><a href="' + homeUrl + 'view_invoices/">Invoices</a></li>')
            .append('<li class="breadcrumb-item active" aria-current="page">Details</li>');
        $("#breadcrumb-nav-container").show();
    }
});
</script>

<?php
require_once __DIR__ . '/../templates/footer.php';
?>
