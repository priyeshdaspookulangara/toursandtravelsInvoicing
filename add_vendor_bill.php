<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'controllers/accounting_controller.php';

$page_title = "Add Vendor Bill";
$errors = [];

// Fetch vendors for the dropdown
$vendors_result = db_query("SELECT id, name FROM vendors ORDER BY name ASC");
$vendors = db_fetch_all($vendors_result);

// Fetch expense accounts for the dropdown
$expense_accounts_result = db_query("SELECT id, account_name FROM chart_of_accounts WHERE account_type = 'Expense' ORDER BY account_name ASC");
$expense_accounts = db_fetch_all($expense_accounts_result);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vendor_id = sanitize_int($_POST['vendor_id']);
    $expense_account_id = sanitize_int($_POST['expense_account_id']);
    $bill_date = sanitize_string($_POST['bill_date']);
    $due_date = sanitize_string($_POST['due_date']);
    $total_amount = sanitize_decimal($_POST['total_amount']);
    $description = sanitize_string($_POST['description']);
    $vat_rate = VAT_RATE; // From config.php

    // --- Validation ---
    if (empty($vendor_id)) $errors[] = "Please select a vendor.";
    if (empty($expense_account_id)) $errors[] = "Please select an expense category.";
    if (empty($bill_date)) $errors[] = "Bill date is required.";
    if (empty($due_date)) $errors[] = "Due date is required.";
    if ($total_amount <= 0) $errors[] = "Total amount must be a positive number.";

    if (empty($errors)) {
        // Calculate VAT amount from total amount
        $sub_total = $total_amount / (1 + ($vat_rate / 100));
        $vat_amount = $total_amount - $sub_total;

        // --- Database Insertion ---
        $conn = db_connect();
        mysqli_begin_transaction($conn);

        try {
            // 1. Insert the vendor bill
            $sql_bill = "INSERT INTO vendor_bills (vendor_id, bill_date, due_date, total_amount, vat_amount, description, status) VALUES (?, ?, ?, ?, ?, ?, 'Unpaid')";
            $stmt_bill = mysqli_prepare($conn, $sql_bill);
            mysqli_stmt_bind_param($stmt_bill, "issdds", $vendor_id, $bill_date, $due_date, $total_amount, $vat_amount, $description);
            mysqli_stmt_execute($stmt_bill);
            $bill_id = mysqli_insert_id($conn);

            // 2. Post to General Ledger
            $description_gl = "Vendor bill #$bill_id; Vendor: " . ($vendors[$vendor_id]['name'] ?? 'N/A');

            // Debit Expense Account (sub_total)
            record_gl_transaction($bill_date, $expense_account_id, $sub_total, 0, $description_gl, 'vendor_bill', $bill_id);

            // Debit Input VAT Recoverable (vat_amount)
            record_gl_transaction($bill_date, ACCOUNT_ID_INPUT_VAT, $vat_amount, 0, $description_gl, 'vendor_bill', $bill_id);

            // Credit Accounts Payable (total_amount)
            record_gl_transaction($bill_date, ACCOUNT_ID_ACCOUNTS_PAYABLE, 0, $total_amount, $description_gl, 'vendor_bill', $bill_id);

            mysqli_commit($conn);
            $_SESSION['message'] = "Vendor bill added successfully and journal entries posted.";
            header("Location: list_vendor_bills.php");
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = "Transaction failed: " . $e->getMessage();
        }
    }
}

include 'templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="add_vendor_bill.php" method="post">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="vendor_id" class="form-label">Vendor</label>
                    <select class="form-select" id="vendor_id" name="vendor_id" required>
                        <option value="">Select a Vendor</option>
                        <?php foreach ($vendors as $vendor): ?>
                            <option value="<?php echo $vendor['id']; ?>"><?php echo htmlspecialchars($vendor['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="expense_account_id" class="form-label">Expense Category</label>
                    <select class="form-select" id="expense_account_id" name="expense_account_id" required>
                        <option value="">Select a Category</option>
                        <?php foreach ($expense_accounts as $account): ?>
                            <option value="<?php echo $account['id']; ?>"><?php echo htmlspecialchars($account['account_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="bill_date" class="form-label">Bill Date</label>
                    <input type="date" class="form-control" id="bill_date" name="bill_date" required>
                </div>
                <div class="col-md-6">
                    <label for="due_date" class="form-label">Due Date</label>
                    <input type="date" class="form-control" id="due_date" name="due_date" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="total_amount" class="form-label">Total Amount (VAT Inclusive)</label>
                    <input type="number" step="0.01" class="form-control" id="total_amount" name="total_amount" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description / Memo</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Bill</button>
            <a href="list_vendor_bills.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>