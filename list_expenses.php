<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Manage Expenses";
$sql = "SELECT e.*, coa.account_name as category_name FROM expenses e JOIN chart_of_accounts coa ON e.category_id = coa.id ORDER BY e.expense_date DESC";
$expenses = db_fetch_all(db_query($sql));

include 'templates/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <a href="add_expense.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Expense</a>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        All Expenses
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Vendor</th>
                        <th class="text-end">Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($expenses)): ?>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date("Y-m-d", strtotime($expense['expense_date']))); ?></td>
                                <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($expense['description'])); ?></td>
                                <td><?php echo htmlspecialchars($expense['vendor']); ?></td>
                                <td class="text-end"><?php echo number_format($expense['amount'], 2); ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-outline-secondary">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No expenses found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>