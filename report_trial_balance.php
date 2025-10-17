<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Trial Balance Report";
// ... (PHP logic remains the same)

include 'templates/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
</div>

<div class="card mb-4">
    <div class="card-header">
        Report Period
    </div>
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

<div class="card">
    <div class="card-header">
        Trial Balance
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
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
    </div>
</div>

<?php include 'templates/footer.php'; ?>