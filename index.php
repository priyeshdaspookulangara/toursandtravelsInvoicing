<?php
require_once 'auth.php'; // Session check, redirects to login if not authenticated
require_once 'db.php';   // Database connection and query functions

$page_title = "Dashboard - Invoices";

// Fetch all invoices along with client names
// Need to join with clients table to get client_name
$sql = "SELECT invoices.*, clients.name AS client_name
        FROM invoices
        JOIN clients ON invoices.client_id = clients.id
        ORDER BY invoices.invoice_date DESC, invoices.id DESC";

$invoices_result = db_query($sql);
$invoices = [];
if ($invoices_result) {
    $invoices = db_fetch_all($invoices_result);
} elseif ($conn === null || !$conn) { // Check if connection itself failed earlier
    $page_error = "Database connection error. Cannot fetch invoices.";
}
 else {
    // db_query returned false, error is already logged by db_query
    $page_error = "An error occurred while trying to fetch invoices. Please try again later.";
}

include 'templates/header.php';
?>

<h2><?php echo $page_title; ?></h2>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<?php if (isset($page_error)): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($page_error); ?>
    </div>
<?php endif; ?>

<p><a href="create_invoice.php" class="btn">Create New Invoice</a></p>

<?php if (!empty($invoices)): ?>
    <table>
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Client Name</th>
                <th>Invoice Date</th>
                <th>Due Date</th>
                <th>Grand Total</th>
                <th>Amount Paid</th>
                <th>Balance Due</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoices as $invoice): ?>
                <tr>
                    <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                    <td><?php echo htmlspecialchars($invoice['client_name']); ?></td>
                    <td><?php echo htmlspecialchars($invoice['invoice_date']); ?></td>
                    <td><?php echo htmlspecialchars($invoice['due_date']); ?></td>
                    <td class="text-right"><?php echo number_format($invoice['grand_total'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($invoice['amount_paid'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($invoice['balance_due'], 2); ?></td>
                    <td><?php echo htmlspecialchars($invoice['status']); ?></td>
                    <td class="actions">
                        <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-info btn-sm">View</a>
                        <a href="edit_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm" style="background-color: #f0ad4e; color:white;">Edit</a>
                        <a href="delete_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this invoice?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php elseif (empty($page_error)): ?>
    <p>No invoices found. <a href="create_invoice.php">Create one now!</a></p>
<?php endif; ?>

<?php
include 'templates/footer.php';
?>
