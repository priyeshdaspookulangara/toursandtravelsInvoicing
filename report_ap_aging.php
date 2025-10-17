<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "A/P Aging Report";
// ... (PHP logic remains the same)

include 'templates/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <small>As of <?php echo date("F j, Y"); ?></small>
</div>

<div class="card mb-4">
    <div class="card-header">Summary</div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col"><strong>Current</strong><br><?php echo number_format($aging['current']['total'], 2); ?></div>
            <div class="col"><strong>1-30 Days</strong><br><?php echo number_format($aging['1-30']['total'], 2); ?></div>
            <div class="col"><strong>31-60 Days</strong><br><?php echo number_format($aging['31-60']['total'], 2); ?></div>
            <div class="col"><strong>61-90 Days</strong><br><?php echo number_format($aging['61-90']['total'], 2); ?></div>
            <div class="col"><strong>91+ Days</strong><br><?php echo number_format($aging['91+']['total'], 2); ?></div>
            <div class="col border-start"><strong>Total Owed</strong><br><?php echo number_format($total_outstanding, 2); ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Detailed Report</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Vendor</th>
                        <th>Bill #</th>
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                        <th class="text-end">Amount Due</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($bills)): ?>
                        <?php foreach ($bills as $bill): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bill['vendor_name']); ?></td>
                                <td><?php echo $bill['bill_id']; ?></td>
                                <td><?php echo htmlspecialchars($bill['due_date']); ?></td>
                                <td><?php echo $bill['days_overdue'] > 0 ? $bill['days_overdue'] : 0; ?></td>
                                <td class="text-end"><?php echo number_format($bill['total_amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No outstanding bills.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-dark fw-bold">
                        <td colspan="4" class="text-end">Total Outstanding:</td>
                        <td class="text-end"><?php echo number_format($total_outstanding, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>