<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'config.php'; // To get ACCOUNT_ID_VAT_PAYABLE

$page_title = "VAT Report";

// Default date range to the current month
$start_date = date('Y-m-01');
$end_date = date('Y-m-t');

$vat_collected = 0;
$vat_adjusted = 0; // For debits against the VAT account, e.g., refunds
$transactions = [];

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = sanitize_string($_GET['start_date']);
    $end_date = sanitize_string($_GET['end_date']);

    // Fetch all transactions from the VAT Payable account within the date range
    $sql = "
        SELECT
            entry_date,
            description,
            debit,
            credit,
            reference_type,
            reference_id
        FROM general_ledger
        WHERE account_id = ?
          AND entry_date BETWEEN ? AND ?
        ORDER BY entry_date ASC
    ";

    $conn = db_connect();
    $stmt = $conn->prepare($sql);
    $vat_account_id = ACCOUNT_ID_VAT_PAYABLE;
    $stmt->bind_param("iss", $vat_account_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = db_fetch_all($result);
    $stmt->close();

    foreach ($transactions as $tx) {
        $vat_collected += (float)$tx['credit'];
        $vat_adjusted += (float)$tx['debit'];
    }
}

$net_vat_payable = $vat_collected - $vat_adjusted;

include 'templates/header.php';
?>

<style>
    .report-container { max-width: 800px; margin: auto; }
    .report-header { text-align: center; margin-bottom: 20px; }
    .report-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .report-table th, .report-table td { padding: 8px; border-bottom: 1px solid #ddd; }
    .report-table th { text-align: left; background-color: #f2f2f2; }
    .summary-table { width: 50%; margin: 20px 0 20px auto; border: 1px solid #ccc; }
    .summary-table td { padding: 10px; }
    .summary-table .total-row td { border-top: 2px solid #333; font-weight: bold; }
</style>

<div class="report-container">
    <h2><?php echo $page_title; ?></h2>

    <form action="report_vat.php" method="get" class="form-inline" style="margin-bottom: 20px;">
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

    <?php if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['start_date'])): ?>
        <div class="report-header">
            <h3>VAT Summary</h3>
            <p>For the period from <?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?></p>
        </div>

        <table class="summary-table">
            <tr>
                <td>Total VAT Collected (from Sales)</td>
                <td class="text-right"><?php echo number_format($vat_collected, 2); ?></td>
            </tr>
            <tr>
                <td>Total VAT Adjustments/Refunds</td>
                <td class="text-right">-<?php echo number_format($vat_adjusted, 2); ?></td>
            </tr>
            <tr class="total-row">
                <td>Net VAT Payable</td>
                <td class="text-right"><?php echo number_format($net_vat_payable, 2); ?></td>
            </tr>
        </table>

        <h3>Transaction Details</h3>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Reference</th>
                    <th class="text-right">VAT Debited</th>
                    <th class="text-right">VAT Credited</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($transactions)): ?>
                <tr><td colspan="5">No VAT transactions recorded in this period.</td></tr>
            <?php else: ?>
                <?php foreach ($transactions as $tx): ?>
                <tr>
                    <td><?php echo htmlspecialchars($tx['entry_date']); ?></td>
                    <td><?php echo htmlspecialchars($tx['description']); ?></td>
                    <td>
                        <?php if ($tx['reference_type'] && $tx['reference_id']): ?>
                            <a href="view_<?php echo htmlspecialchars($tx['reference_type']); ?>.php?id=<?php echo $tx['reference_id']; ?>">
                                <?php echo ucfirst(htmlspecialchars($tx['reference_type'])) . ' #' . $tx['reference_id']; ?>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td class="text-right"><?php echo number_format($tx['debit'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($tx['credit'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Select a date range and click "Generate Report" to view the VAT summary.</p>
    <?php endif; ?>
</div>

<?php
include 'templates/footer.php';
?>