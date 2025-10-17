<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Dashboard";
$invoices = db_fetch_all(db_query("SELECT invoices.*, clients.name AS client_name FROM invoices JOIN clients ON invoices.client_id = clients.id ORDER BY invoices.invoice_date DESC, invoices.id DESC"));

include 'templates/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <a href="create_invoice.php" class="btn btn-primary"><i class="fas fa-plus"></i> Create New Invoice</a>
</div>

<?php if (isset($_SESSION['message'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Monthly Revenue (Last 6 Months)</div>
            <div class="card-body">
                <canvas id="monthlyRevenueChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Invoice Status</div>
            <div class="card-body">
                <canvas id="invoiceStatusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">All Invoices</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Client Name</th>
                        <th>Invoice Date</th>
                        <th>Due Date</th>
                        <th class="text-end">Grand Total</th>
                        <th class="text-end">Balance Due</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($invoices)): ?>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                <td><?php echo htmlspecialchars($invoice['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($invoice['invoice_date']); ?></td>
                                <td><?php echo htmlspecialchars($invoice['due_date']); ?></td>
                                <td class="text-end"><?php echo number_format($invoice['grand_total'], 2); ?></td>
                                <td class="text-end"><?php echo number_format($invoice['balance_due'], 2); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($invoice['status']); ?></span></td>
                                <td class="actions">
                                    <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-info btn-sm" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="edit_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="delete_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this invoice?');"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center">No invoices found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
// ... script from previous version ...
</script>