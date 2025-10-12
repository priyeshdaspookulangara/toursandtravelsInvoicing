<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Manage Expenses";

// Fetch all expenses, joining with chart_of_accounts to get the category name
$sql = "SELECT e.*, coa.account_name as category_name
        FROM expenses e
        JOIN chart_of_accounts coa ON e.category_id = coa.id
        ORDER BY e.expense_date DESC";

$expenses_result = db_query($sql);
$expenses = [];
if ($expenses_result) {
    $expenses = db_fetch_all($expenses_result);
} else {
    $page_error = "Error fetching expenses: " . mysqli_error(db_connect());
}

include 'templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_expense.php" class="btn btn-sm btn-success">
            <i class="fas fa-plus"></i> Add New Expense
        </a>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($page_error)): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($page_error); ?>
    </div>
<?php endif; ?>

<?php if (!empty($expenses)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-sm">
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
                <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(date("Y-m-d", strtotime($expense['expense_date']))); ?></td>
                        <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($expense['description'])); ?></td>
                        <td><?php echo htmlspecialchars($expense['vendor']); ?></td>
                        <td class="text-end"><?php echo number_format($expense['amount'], 2); ?></td>
                        <td class="actions">
                            <span class="badge bg-secondary">N/A</span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php elseif (empty($page_error)): ?>
    <p>No expenses found. <a href="add_expense.php">Add one now!</a></p>
<?php endif; ?>

<?php
include 'templates/footer.php';
?>