<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "A/P Aging Report";

$today = date('Y-m-d');

$sql = "
    SELECT
        v.name as vendor_name,
        vb.id as bill_id,
        vb.bill_date,
        vb.due_date,
        vb.total_amount,
        DATEDIFF(?, vb.due_date) as days_overdue
    FROM vendor_bills vb
    JOIN vendors v ON vb.vendor_id = v.id
    WHERE vb.status = 'Unpaid'
    ORDER BY vendor_name, days_overdue DESC;
";

$conn = db_connect();
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $today);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$bills = db_fetch_all($result);

// Initialize aging buckets
$aging = [
    'current' => ['total' => 0, 'bills' => []],
    '1-30' => ['total' => 0, 'bills' => []],
    '31-60' => ['total' => 0, 'bills' => []],
    '61-90' => ['total' => 0, 'bills' => []],
    '91+' => ['total' => 0, 'bills' => []],
];
$total_outstanding = 0;

foreach ($bills as $bill) {
    $total_outstanding += $bill['total_amount'];
    $days = $bill['days_overdue'];
    if ($days <= 0) {
        $aging['current']['total'] += $bill['total_amount'];
        $aging['current']['bills'][] = $bill;
    } elseif ($days <= 30) {
        $aging['1-30']['total'] += $bill['total_amount'];
        $aging['1-30']['bills'][] = $bill;
    } elseif ($days <= 60) {
        $aging['31-60']['total'] += $bill['total_amount'];
        $aging['31-60']['bills'][] = $bill;
    } elseif ($days <= 90) {
        $aging['61-90']['total'] += $bill['total_amount'];
        $aging['61-90']['bills'][] = $bill;
    } else {
        $aging['91+']['total'] += $bill['total_amount'];
        $aging['91+']['bills'][] = $bill;
    }
}

include 'templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
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

<h4>Detailed Report</h4>
<div class="table-responsive">
    <table class="table table-striped table-sm">
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

<?php include 'templates/footer.php'; ?>