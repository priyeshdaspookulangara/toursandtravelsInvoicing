<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'accounting.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = db_connect();

    // Sanitize and validate inputs
    $invoice_id = isset($_POST['invoice_id']) ? sanitize_int($_POST['invoice_id']) : null;
    $payment_amount = isset($_POST['payment_amount']) ? sanitize_decimal($_POST['payment_amount']) : null;
    $payment_date = isset($_POST['payment_date']) ? sanitize_string($_POST['payment_date']) : null;

    $errors = [];
    if (empty($invoice_id)) {
        $errors[] = "Invoice ID is missing.";
    }
    if (empty($payment_amount) || !is_numeric($payment_amount) || $payment_amount <= 0) {
        $errors[] = "A valid, positive payment amount is required.";
    }
    if (empty($payment_date)) {
        $errors[] = "Payment date is required.";
    }

    if (empty($errors)) {
        // Begin transaction
        mysqli_autocommit($conn, false);
        $queries_ok = true;

        // 1. Update the amount_paid on the invoice
        $sql_update_invoice = "UPDATE invoices SET amount_paid = amount_paid + ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update_invoice);
        if ($stmt_update) {
            $stmt_update->bind_param("di", $payment_amount, $invoice_id);
            if (!$stmt_update->execute()) {
                $errors[] = "Failed to update invoice payment amount: " . $stmt_update->error;
                $queries_ok = false;
            }
            $stmt_update->close();
        } else {
            $errors[] = "Failed to prepare invoice update statement: " . $conn->error;
            $queries_ok = false;
        }

        // 2. Post the transaction to the general ledger
        if ($queries_ok) {
            $journal_entries = [
                // Debit Cash for the amount received
                ['account_id' => ACCOUNT_ID_CASH, 'debit' => $payment_amount, 'credit' => 0],
                // Credit Accounts Receivable as the client's debt is reduced
                ['account_id' => ACCOUNT_ID_ACCOUNTS_RECEIVABLE, 'debit' => 0, 'credit' => $payment_amount],
            ];

            $invoice_number_query = db_query("SELECT invoice_number FROM invoices WHERE id = $invoice_id");
            $invoice_number = db_fetch_assoc($invoice_number_query)['invoice_number'] ?? 'N/A';
            $journal_description = "Payment received for Invoice #{$invoice_number}.";

            if (!create_journal_transaction($payment_date, $journal_description, $journal_entries, 'payment', $invoice_id)) {
                $errors[] = "Failed to post payment to the general ledger.";
                $queries_ok = false;
            }
        }

        // Commit or rollback transaction
        if ($queries_ok) {
            mysqli_commit($conn);
            $_SESSION['message'] = "Payment of " . number_format($payment_amount, 2) . " recorded successfully!";
        } else {
            mysqli_rollback($conn);
            // Combine errors into a single session message
            $_SESSION['message'] = "Failed to record payment. Errors: " . implode(" ", $errors);
        }
        mysqli_autocommit($conn, true);

    } else {
        $_SESSION['message'] = "Invalid data submitted. Errors: " . implode(" ", $errors);
    }

    // Redirect back to the invoice view page
    header("Location: view_invoice.php?id=" . $invoice_id);
    exit;

} else {
    // If accessed directly without POST, redirect to homepage
    header("Location: index.php");
    exit;
}
?>