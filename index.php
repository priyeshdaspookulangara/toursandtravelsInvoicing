<?php
require_once 'auth.php'; // Session check, redirects to login if not authenticated
require_once 'db.php';   // Database connection and query functions

$page_title = "Dashboard";

// Fetch all invoices along with client names for the table view
$sql = "SELECT invoices.*, clients.name AS client_name
        FROM invoices
        JOIN clients ON invoices.client_id = clients.id
        ORDER BY invoices.invoice_date DESC, invoices.id DESC";

$invoices_result = db_query($sql);
$invoices = [];
if ($invoices_result) {
    $invoices = db_fetch_all($invoices_result);
} else {
    $page_error = "An error occurred while trying to fetch invoices.";
}

include 'templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="create_invoice.php" class="btn btn-sm btn-success">
            <i class="fas fa-plus"></i> Create New Invoice
        </a>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Dashboard Charts Row -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-line"></i> Monthly Revenue (Last 6 Months)
            </div>
            <div class="card-body">
                <canvas id="monthlyRevenueChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i> Invoice Status
            </div>
            <div class="card-body">
                <canvas id="invoiceStatusChart"></canvas>
            </div>
        </div>
    </div>
</div>


<!-- Invoices Table -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-file-invoice"></i> All Invoices
    </div>
    <div class="card-body">
        <?php if (isset($page_error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($page_error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($invoices)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
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
                    </tbody>
                </table>
            </div>
        <?php elseif (empty($page_error)): ?>
            <p>No invoices found. <a href="create_invoice.php">Create one now!</a></p>
        <?php endif; ?>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
$(document).ready(function() {
    // Fetch data for charts via AJAX
    $.ajax({
        url: '<?php echo APP_BASE_URL; ?>api/dashboard_data.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // --- Render Monthly Revenue Chart (Line) ---
                const revenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
                new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: response.data.monthly_revenue.labels,
                        datasets: [{
                            label: 'Total Revenue',
                            data: response.data.monthly_revenue.values,
                            fill: true,
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                // --- Render Invoice Status Chart (Pie) ---
                const statusCtx = document.getElementById('invoiceStatusChart').getContext('2d');
                new Chart(statusCtx, {
                    type: 'pie',
                    data: {
                        labels: response.data.invoice_status.labels,
                        datasets: [{
                            label: 'Invoice Count',
                            data: response.data.invoice_status.values,
                            backgroundColor: [
                                'rgb(255, 99, 132)',  // Draft
                                'rgb(54, 162, 235)', // Sent
                                'rgb(75, 192, 192)', // Paid
                                'rgb(255, 205, 86)', // Overdue
                                'rgb(153, 102, 255)' // Other statuses
                            ],
                            hoverOffset: 4
                        }]
                    }
                });
            } else {
                console.error("Failed to load dashboard data: " + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error fetching dashboard data: " + error);
        }
    });
});
</script>