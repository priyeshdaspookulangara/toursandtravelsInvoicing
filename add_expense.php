<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'accounting.php';

$page_title = "Add New Expense";
$errors = [];

// Fetch expense accounts from the chart of accounts for the category dropdown
$sql_expense_accounts = "SELECT id, account_name FROM chart_of_accounts WHERE account_type = 'Expense' AND is_active = 1 ORDER BY account_name ASC";
$expense_accounts_result = db_query($sql_expense_accounts);
$expense_categories = db_fetch_all($expense_accounts_result);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = db_connect();

    // Sanitize and validate inputs
    $expense_date = isset($_POST['expense_date']) ? sanitize_string($_POST['expense_date']) : '';
    $category_id = isset($_POST['category_id']) ? sanitize_int($_POST['category_id']) : null;
    $amount = isset($_POST['amount']) ? sanitize_decimal($_POST['amount']) : null;
    $vendor = isset($_POST['vendor']) ? sanitize_string($_POST['vendor']) : '';
    $description = isset($_POST['description']) ? sanitize_string($_POST['description']) : '';

    // Validation
    if (empty($expense_date)) $errors[] = "Expense date is required.";
    if (empty($category_id)) $errors[] = "Expense category is required.";
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) $errors[] = "A valid, positive amount is required.";
    if (empty($description)) $errors[] = "Description is required.";

    if (empty($errors)) {
        mysqli_autocommit($conn, false); // Start transaction
        $queries_ok = true;

        // 1. Insert the expense record
        $sql_insert_expense = "INSERT INTO expenses (expense_date, category_id, amount, vendor, description) VALUES (?, ?, ?, ?, ?)";
        $stmt_expense = $conn->prepare($sql_insert_expense);
        if ($stmt_expense) {
            $stmt_expense->bind_param("sidss", $expense_date, $category_id, $amount, $vendor, $description);
            if (!$stmt_expense->execute()) {
                $errors[] = "Failed to add expense record: " . $stmt_expense->error;
                $queries_ok = false;
            }
            $last_expense_id = $stmt_expense->insert_id;
            $stmt_expense->close();
        } else {
            $errors[] = "Failed to prepare expense insert statement: " . $conn->error;
            $queries_ok = false;
        }

        // 2. Post the transaction to the general ledger
        if ($queries_ok) {
            // The journal entry for an expense is:
            // Debit: The specific expense account (e.g., 'Rent Expense')
            // Credit: The asset account used for payment (e.g., 'Cash and Bank')
            $journal_entries = [
                ['account_id' => $category_id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => ACCOUNT_ID_CASH, 'debit' => 0, 'credit' => $amount],
            ];

            $journal_description = "Expense recorded: " . $description;

            if (!create_journal_transaction($expense_date, $journal_description, $journal_entries, 'expense', $last_expense_id)) {
                $errors[] = "Failed to post expense to the general ledger.";
                $queries_ok = false;
            }
        }

        // Commit or rollback transaction
        if ($queries_ok) {
            mysqli_commit($conn);
            $_SESSION['message'] = "Expense recorded successfully!";
            header("Location: list_expenses.php");
            exit;
        } else {
            mysqli_rollback($conn);
            $errors[] = "Expense recording failed. Transaction rolled back.";
        }
        mysqli_autocommit($conn, true);
    }
}

include 'templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <h4 class="alert-heading">Please correct the following errors:</h4>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="add_expense.php" method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="expense_date" class="form-label">Expense Date:</label>
                        <input type="date" class="form-control" name="expense_date" id="expense_date" value="<?php echo isset($_POST['expense_date']) ? htmlspecialchars($_POST['expense_date']) : date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount:</label>
                        <input type="text" class="form-control" name="amount" id="amount" value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>" required pattern="^\d+(\.\d{1,2})?$" title="Enter a valid amount, e.g., 150.50">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Expense Category:</label>
                        <select class="form-select" name="category_id" id="category_id" required>
                            <option value="">Select a Category</option>
                            <?php foreach ($expense_categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['account_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="vendor" class="form-label">Vendor/Supplier (Optional):</label>
                        <input type="text" class="form-control" name="vendor" id="vendor" value="<?php echo isset($_POST['vendor']) ? htmlspecialchars($_POST['vendor']) : ''; ?>">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description:</label>
                <textarea class="form-control" name="description" id="description" required rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Record Expense</button>
            <a href="list_expenses.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php
include 'templates/footer.php';
?>