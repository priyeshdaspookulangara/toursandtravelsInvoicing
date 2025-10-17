<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'controllers/accounting_controller.php';

$page_title = "Add New Expense";
$errors = [];

$expense_categories = db_fetch_all(db_query("SELECT id, account_name FROM chart_of_accounts WHERE account_type = 'Expense' ORDER BY account_name ASC"));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = db_connect();
    $expense_date = sanitize_string($_POST['expense_date']);
    $category_id = sanitize_int($_POST['category_id']);
    $amount = sanitize_decimal($_POST['amount']);
    $vendor = sanitize_string($_POST['vendor']);
    $description = sanitize_string($_POST['description']);

    if (empty($expense_date)) $errors[] = "Expense date is required.";
    if (empty($category_id)) $errors[] = "Category is required.";
    if ($amount <= 0) $errors[] = "Amount must be a positive number.";

    if (empty($errors)) {
        mysqli_begin_transaction($conn);
        try {
            $sql_expense = "INSERT INTO expenses (expense_date, category_id, amount, vendor, description) VALUES (?, ?, ?, ?, ?)";
            $stmt_expense = mysqli_prepare($conn, $sql_expense);
            mysqli_stmt_bind_param($stmt_expense, "sidss", $expense_date, $category_id, $amount, $vendor, $description);
            mysqli_stmt_execute($stmt_expense);
            $last_expense_id = mysqli_insert_id($conn);

            $journal_entries = [
                ['account_id' => $category_id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => ACCOUNT_ID_CASH, 'debit' => 0, 'credit' => $amount]
            ];
            create_journal_transaction($expense_date, "Expense: " . $description, $journal_entries, 'expense', $last_expense_id);

            mysqli_commit($conn);
            $_SESSION['message'] = "Expense recorded successfully.";
            header("Location: list_expenses.php");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = $e->getMessage();
        }
    }
}

include 'templates/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <strong>Please correct the following errors:</strong>
    <ul>
        <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        Expense Details
    </div>
    <div class="card-body">
        <form action="add_expense.php" method="post">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="expense_date" class="form-label">Expense Date</label>
                    <input type="date" class="form-control" name="expense_date" id="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="amount" class="form-label">Amount</label>
                    <input type="number" step="0.01" class="form-control" name="amount" id="amount" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="category_id" class="form-label">Category</label>
                    <select name="category_id" id="category_id" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php foreach ($expense_categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['account_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="vendor" class="form-label">Vendor (Optional)</label>
                    <input type="text" class="form-control" name="vendor" id="vendor">
                </div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" name="description" id="description" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Record Expense</button>
            <a href="list_expenses.php" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>