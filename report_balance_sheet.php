<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Balance Sheet";

$report_date = isset($_GET['report_date']) ? sanitize_string($_GET['report_date']) : date('Y-m-t');

function get_account_balances($account_type, $report_date) {
    $sql = "
        SELECT
            coa.account_name,
            (SUM(CASE WHEN gl.debit > 0 THEN gl.debit ELSE 0 END) - SUM(CASE WHEN gl.credit > 0 THEN gl.credit ELSE 0 END)) as balance
        FROM chart_of_accounts coa
        JOIN general_ledger gl ON coa.id = gl.account_id
        WHERE coa.account_type = ? AND gl.entry_date <= ?
        GROUP BY coa.id, coa.account_name
        HAVING balance != 0
        ORDER BY coa.account_name;
    ";
    $conn = db_connect();
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $account_type, $report_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return db_fetch_all($result);
}

function get_net_income($report_date) {
    $sql = "
        SELECT
            SUM(CASE WHEN coa.account_type = 'Revenue' THEN gl.credit - gl.debit ELSE 0 END) as total_revenue,
            SUM(CASE WHEN coa.account_type = 'Expense' THEN gl.debit - gl.credit ELSE 0 END) as total_expense
        FROM general_ledger gl
        JOIN chart_of_accounts coa ON gl.account_id = coa.id
        WHERE gl.entry_date <= ?
          AND (coa.account_type = 'Revenue' OR coa.account_type = 'Expense');
    ";
    $conn = db_connect();
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $report_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = db_fetch_assoc($result);
    return ($row['total_revenue'] ?? 0) - ($row['total_expense'] ?? 0);
}

$assets = get_account_balances('Asset', $report_date);
$liabilities = get_account_balances('Liability', $report_date);
$equity = get_account_balances('Equity', $report_date);
$net_income = get_net_income($report_date);

$total_assets = 0;
foreach ($assets as $asset) {
    $total_assets += $asset['balance'];
}

$total_liabilities = 0;
foreach ($liabilities as $liability) {
    // Liabilities have a credit balance, so we expect a negative result from the query. We flip it.
    $total_liabilities -= $liability['balance'];
}

$total_equity = 0;
foreach ($equity as $eq) {
    // Equity has a credit balance, so we expect a negative result from the query. We flip it.
    $total_equity -= $eq['balance'];
}
$total_equity += $net_income; // Add net income to equity

$total_liabilities_and_equity = $total_liabilities + $total_equity;

include 'templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form action="report_balance_sheet.php" method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="report_date" class="form-label">As of Date</label>
                <input type="date" class="form-control" id="report_date" name="report_date" value="<?php echo $report_date; ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <!-- Assets -->
    <div class="col-md-6">
        <h4>Assets</h4>
        <table class="table">
            <?php foreach ($assets as $asset): ?>
            <tr>
                <td><?php echo htmlspecialchars($asset['account_name']); ?></td>
                <td class="text-end"><?php echo number_format($asset['balance'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="table-dark fw-bold">
                <td>Total Assets</td>
                <td class="text-end"><?php echo number_format($total_assets, 2); ?></td>
            </tr>
        </table>
    </div>

    <!-- Liabilities and Equity -->
    <div class="col-md-6">
        <h4>Liabilities</h4>
        <table class="table">
            <?php foreach ($liabilities as $liability): ?>
            <tr>
                <td><?php echo htmlspecialchars($liability['account_name']); ?></td>
                <td class="text-end"><?php echo number_format(-$liability['balance'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="fw-bold">
                <td>Total Liabilities</td>
                <td class="text-end"><?php echo number_format($total_liabilities, 2); ?></td>
            </tr>
        </table>

        <h4>Equity</h4>
        <table class="table">
            <?php foreach ($equity as $eq): ?>
            <tr>
                <td><?php echo htmlspecialchars($eq['account_name']); ?></td>
                <td class="text-end"><?php echo number_format(-$eq['balance'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td>Current Year Earnings</td>
                <td class="text-end"><?php echo number_format($net_income, 2); ?></td>
            </tr>
            <tr class="fw-bold">
                <td>Total Equity</td>
                <td class="text-end"><?php echo number_format($total_equity, 2); ?></td>
            </tr>
        </table>

        <table class="table">
             <tr class="table-dark fw-bold">
                <td>Total Liabilities and Equity</td>
                <td class="text-end"><?php echo number_format($total_liabilities_and_equity, 2); ?></td>
            </tr>
        </table>
    </div>
</div>

<?php include 'templates/footer.php'; ?>