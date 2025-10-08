<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Profit & Loss Statement";

// Determine date range: use GET parameters if available, otherwise default to the current month.
$start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? sanitize_string($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? sanitize_string($_GET['end_date']) : date('Y-m-t');

// A flag to know if the user has actively generated a report
$report_generated = isset($_GET['start_date']);

$revenues = [];
$expenses = [];
$total_revenue = 0;
$total_expense = 0;

// The query now runs on every page load, using the determined date range.
$sql = "
    SELECT
        coa.account_name,
        coa.account_type,
        SUM(gl.credit) as total_credits,
        SUM(gl.debit) as total_debits
    FROM general_ledger gl
    JOIN chart_of_accounts coa ON gl.account_id = coa.id
    WHERE gl.entry_date BETWEEN ? AND ?
      AND coa.account_type IN ('Revenue', 'Expense')
    GROUP BY coa.id, coa.account_name, coa.account_type
    ORDER BY coa.account_type, coa.account_name
";

$conn = db_connect();
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$report_data = db_fetch_all($result);
$stmt->close();

foreach ($report_data as $item) {
    if ($item['account_type'] == 'Revenue') {
        $balance = $item['total_credits'] - $item['total_debits'];
        $revenues[] = ['name' => $item['account_name'], 'amount' => $balance];
        $total_revenue += $balance;
    } elseif ($item['account_type'] == 'Expense') {
        $balance = $item['total_debits'] - $item['total_credits'];
        $expenses[] = ['name' => $item['account_name'], 'amount' => $balance];
        $total_expense += $balance;
    }
}

$net_profit_loss = $total_revenue - $total_expense;

include 'templates/header.php';
?>

<style>
    .report-container { max-width: 800px; margin: auto; }
    .report-header { text-align: center; margin-bottom: 20px; }
    .report-table { width: 100%; border-collapse: collapse; }
    .report-table th, .report-table td { padding: 8px; border-bottom: 1px solid #ddd; }
    .report-table th { text-align: left; background-color: #f2f2f2; }
    .report-table .section-header { font-weight: bold; font-size: 1.1em; padding-top: 15px; }
    .report-table .total-row td { border-top: 2px solid #333; font-weight: bold; }
    .net-profit { color: green; }
    .net-loss { color: red; }
</style>

<div class="report-container">
    <h2><?php echo $page_title; ?></h2>

    <form action="report_profit_loss.php" method="get" class="form-inline" style="margin-bottom: 20px;">
        <div class="form-group">
            <label for="start_date">Start Date:</label>
            <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
        </div>
        <div class="form-group">
            <label for="end_date">End Date:</label>
            <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
        </div>
        <button type="submit" class="btn">Generate Report</button>
    </form>

    <?php if ($report_generated): ?>
        <div class="report-header">
            <h3>Profit & Loss Statement</h3>
            <p>For the period from <?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?></p>
        </div>

        <table class="report-table">
            <!-- Revenue Section -->
            <tr>
                <td colspan="2" class="section-header">Revenue</td>
            </tr>
            <?php if (empty($revenues)): ?>
                <tr><td>No revenue recorded in this period.</td><td></td></tr>
            <?php else: ?>
                <?php foreach ($revenues as $revenue): ?>
                <tr>
                    <td><?php echo htmlspecialchars($revenue['name']); ?></td>
                    <td class="text-right"><?php echo number_format($revenue['amount'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <tr class="total-row">
                <td>Total Revenue</td>
                <td class="text-right"><?php echo number_format($total_revenue, 2); ?></td>
            </tr>

            <!-- Expenses Section -->
            <tr>
                <td colspan="2" class="section-header">Expenses</td>
            </tr>
            <?php if (empty($expenses)): ?>
                <tr><td>No expenses recorded in this period.</td><td></td></tr>
            <?php else: ?>
                <?php foreach ($expenses as $expense): ?>
                <tr>
                    <td><?php echo htmlspecialchars($expense['name']); ?></td>
                    <td class="text-right"><?php echo number_format($expense['amount'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <tr class="total-row">
                <td>Total Expenses</td>
                <td class="text-right"><?php echo number_format($total_expense, 2); ?></td>
            </tr>

            <!-- Net Profit/Loss Section -->
            <tr class="total-row">
                <td><strong>Net <?php echo ($net_profit_loss >= 0) ? "Profit" : "Loss"; ?></strong></td>
                <td class="text-right <?php echo ($net_profit_loss >= 0) ? "net-profit" : "net-loss"; ?>">
                    <strong><?php echo number_format($net_profit_loss, 2); ?></strong>
                </td>
            </tr>
        </table>
    <?php else: ?>
        <p>Select a date range and click "Generate Report" to view the Profit & Loss statement.</p>
    <?php endif; ?>
</div>

<?php
include 'templates/footer.php';
?>