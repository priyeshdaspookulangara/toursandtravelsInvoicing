<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Vendor Bills";

// Fetch all vendor bills with vendor names
$sql = "SELECT vb.*, v.name as vendor_name
        FROM vendor_bills vb
        JOIN vendors v ON vb.vendor_id = v.id
        ORDER BY vb.bill_date DESC";
$bills_result = db_query($sql);
$bills = db_fetch_all($bills_result);

include 'templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_vendor_bill.php" class="btn btn-sm btn-success">
            <i class="fas fa-plus"></i> Add New Bill
        </a>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>Vendor</th>
                <th>Due Date</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($bills)): ?>
                <?php foreach ($bills as $bill): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($bill['bill_date']); ?></td>
                        <td><?php echo htmlspecialchars($bill['vendor_name']); ?></td>
                        <td><?php echo htmlspecialchars($bill['due_date']); ?></td>
                        <td><?php echo number_format($bill['total_amount'], 2); ?></td>
                        <td>
                            <span class="badge <?php echo $bill['status'] == 'Paid' ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo htmlspecialchars($bill['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($bill['status'] == 'Unpaid'): ?>
                                <a href="record_bill_payment.php?bill_id=<?php echo $bill['id']; ?>" class="btn btn-sm btn-primary" onclick="return confirm('Are you sure you want to mark this bill as paid?');">Mark as Paid</a>
                            <?php else: ?>
                                <span class="text-muted">Paid</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No vendor bills found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'templates/footer.php'; ?>