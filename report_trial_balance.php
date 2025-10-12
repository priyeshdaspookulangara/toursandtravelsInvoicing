<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Trial Balance Report";

$start_date = isset($_GET['start_date']) ? sanitize_string($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize_string($_GET['end_date']) : date('Y-m-t');

$accounts_data = [];
$total_debits = 0;
$total_credits = 0;

// The SQL query to get the trial balance
$sql = "
    SELECT
        coa.id,
        coa.account_name,
        coa.account_type,
        SUM(CASE WHEN gl.debit > 0 THEN gl.debit ELSE 0 END) as total_debit,
        SUM(CASE WHEN gl.credit > 0 THEN gl.credit ELSE 0 END) as total_credit
    FROM
        chart_of_accounts coa
    LEFT JOIN
        general_ledger gl ON coa.id = gl.account_id
                         AND gl.entry_date BETWEEN ? AND ?
    WHERE
        coa.is_active = 1
    GROUP BY
        coa.id, coa.account_name, coa.account_type
    HAVING
        total_debit > 0 OR total_credit > 0
    ORDER BY
        coa.account_type, coa.account_name;
";

$conn = db_connect();
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$accounts_data = db_fetch_all($result);

foreach ($accounts_data as $account) {
    $total_debits += $account['total_debit'];
    $total_credits += $account['total_credit'];
}


include 'templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form action="report_trial_balance.php" method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Account Name</th>
                <th>Account Type</th>
                <th class="text-end">Debit</th>
                <th class="text-end">Credit</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($accounts_data)): ?>
                <?php foreach ($accounts_data as $account): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                        <td><?php echo htmlspecialchars($account['account_type']); ?></td>
                        <td class="text-end"><?php echo number_format($account['total_debit'], 2); ?></td>
                        <td class="text-end"><?php echo number_format($account['total_credit'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">No transactions found for this period.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="table-dark fw-bold">
                <td colspan="2" class="text-end">Totals:</td>
                <td class="text-end"><?php echo number_format($total_debits, 2); ?></td>
                <td class="text-end"><?php echo number_format($total_credits, 2); ?></td>
            </tr>
            <?php if (number_format($total_debits, 2) != number_format($total_credits, 2)): ?>
            <tr>
                <td colspan="4" class="text-center text-danger fw-bold">
                    Warning: Debits and Credits do not match!
                </td>
            </tr>
            <?php endif; ?>
        </tfoot>
    </table>
</div>


<?php include 'templates/footer.php'; ?>