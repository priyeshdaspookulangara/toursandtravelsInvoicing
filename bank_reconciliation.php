<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Bank Reconciliation";

// Fetch cash/bank accounts for the dropdown
$cash_accounts_result = db_query("SELECT id, account_name FROM chart_of_accounts WHERE account_name LIKE '%Cash%' OR account_name LIKE '%Bank%'");
$cash_accounts = db_fetch_all($cash_accounts_result);

$selected_account = isset($_GET['account_id']) ? sanitize_int($_GET['account_id']) : null;
$start_date = isset($_GET['start_date']) ? sanitize_string($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize_string($_GET['end_date']) : date('Y-m-t');
$statement_balance = isset($_GET['statement_balance']) ? sanitize_decimal($_GET['statement_balance']) : 0.00;

$transactions = [];
$gl_balance = 0;
$difference = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reconcile'])) {
    $conn = db_connect();
    $reconciled_ids = isset($_POST['transaction_ids']) ? $_POST['transaction_ids'] : [];
    if (!empty($reconciled_ids)) {
        $ids_placeholder = implode(',', array_fill(0, count($reconciled_ids), '?'));
        $sql_update = "UPDATE general_ledger SET is_reconciled = 1 WHERE id IN ($ids_placeholder)";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, str_repeat('i', count($reconciled_ids)), ...$reconciled_ids);
        if (mysqli_stmt_execute($stmt_update)) {
            $_SESSION['message'] = "Transactions successfully reconciled.";
        } else {
            $errors[] = "Failed to update transactions.";
        }
    }
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

if ($selected_account) {
    // Fetch only unreconciled transactions for the selected account and period
    $sql = "SELECT * FROM general_ledger
            WHERE account_id = ?
            AND entry_date BETWEEN ? AND ?
            AND is_reconciled = 0
            ORDER BY entry_date ASC";

    $conn = db_connect();
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iss", $selected_account, $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $transactions = db_fetch_all($result);

    // Calculate the closing balance from the General Ledger
    // For simplicity, we're calculating the sum of transactions in the period.
    // A real implementation would need to get the opening balance and add/subtract transactions.
    $balance_sql = "SELECT SUM(debit) - SUM(credit) as balance FROM general_ledger WHERE account_id = ? AND entry_date <= ?";
    $stmt_balance = mysqli_prepare($conn, $balance_sql);
    mysqli_stmt_bind_param($stmt_balance, "is", $selected_account, $end_date);
    mysqli_stmt_execute($stmt_balance);
    $balance_result = mysqli_stmt_get_result($stmt_balance);
    $balance_row = db_fetch_assoc($balance_result);
    $gl_balance = $balance_row['balance'] ?? 0;

    $difference = $gl_balance - $statement_balance;
}


include 'templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form action="bank_reconciliation.php" method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="account_id" class="form-label">Bank/Cash Account</label>
                <select class="form-select" id="account_id" name="account_id" required>
                    <option value="">Select Account</option>
                    <?php foreach ($cash_accounts as $account): ?>
                        <option value="<?php echo $account['id']; ?>" <?php if ($selected_account == $account['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($account['account_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label">Statement Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">Statement End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-2">
                 <label for="statement_balance" class="form-label">Statement Ending Balance</label>
                <input type="number" step="0.01" class="form-control" id="statement_balance" name="statement_balance" value="<?php echo $statement_balance; ?>" required>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary">Run</button>
            </div>
        </form>
    </div>
</div>

<?php if ($selected_account): ?>
<form action="bank_reconciliation.php?<?php echo http_build_query($_GET); ?>" method="post">
    <div class="row">
        <div class="col-md-8">
            <h4>Unreconciled Transactions</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select_all_tx"></th>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Debit</th>
                            <th>Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $tx): ?>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input tx-checkbox" name="transaction_ids[]" value="<?php echo $tx['id']; ?>"></td>
                                    <td><?php echo htmlspecialchars($tx['entry_date']); ?></td>
                                    <td><?php echo htmlspecialchars($tx['description']); ?></td>
                                    <td><?php echo number_format($tx['debit'], 2); ?></td>
                                    <td><?php echo number_format($tx['credit'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No unreconciled transactions found for this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-4">
            <h4>Reconciliation Summary</h4>
            <div class="card">
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Statement Ending Balance
                            <span><?php echo number_format($statement_balance, 2); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            General Ledger Balance
                            <span><?php echo number_format($gl_balance, 2); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center fw-bold <?php echo $difference != 0 ? 'text-danger' : 'text-success'; ?>">
                            Difference
                            <span><?php echo number_format($difference, 2); ?></span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="mt-3 text-center">
                <button type="submit" name="reconcile" class="btn btn-success" <?php if (empty($transactions)) echo 'disabled'; ?>>Save Reconciliation</button>
            </div>
        </div>
    </div>
</form>

<script>
document.getElementById('select_all_tx').addEventListener('change', function(e) {
    document.querySelectorAll('.tx-checkbox').forEach(checkbox => {
        checkbox.checked = e.target.checked;
    });
});
</script>
<?php endif; ?>


<?php include 'templates/footer.php'; ?>