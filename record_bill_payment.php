<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'controllers/accounting_controller.php';

if (!isset($_GET['bill_id'])) {
    header("Location: list_vendor_bills.php");
    exit;
}

$bill_id = sanitize_int($_GET['bill_id']);
if (!$bill_id) {
    header("Location: list_vendor_bills.php");
    exit;
}

// Fetch the bill details
$sql_bill = "SELECT * FROM vendor_bills WHERE id = $bill_id AND status = 'Unpaid'";
$bill_result = db_query($sql_bill);
$bill = db_fetch_assoc($bill_result);

if (!$bill) {
    $_SESSION['message'] = "Error: Bill not found or already paid.";
    header("Location: list_vendor_bills.php");
    exit;
}

// --- Start Transaction ---
$conn = db_connect();
mysqli_begin_transaction($conn);

try {
    // 1. Update bill status to 'Paid'
    $sql_update = "UPDATE vendor_bills SET status = 'Paid' WHERE id = $bill_id";
    if (!db_query($sql_update)) {
        throw new Exception("Failed to update bill status.");
    }

    // 2. Post payment to General Ledger
    $payment_date = date('Y-m-d'); // Use today's date for payment
    $description_gl = "Payment for vendor bill #$bill_id";

    // Debit Accounts Payable
    record_gl_transaction($payment_date, ACCOUNT_ID_ACCOUNTS_PAYABLE, $bill['total_amount'], 0, $description_gl, 'bill_payment', $bill_id);

    // Credit Cash/Bank
    record_gl_transaction($payment_date, ACCOUNT_ID_CASH, 0, $bill['total_amount'], $description_gl, 'bill_payment', $bill_id);

    // --- Commit Transaction ---
    mysqli_commit($conn);
    $_SESSION['message'] = "Bill #$bill_id marked as paid successfully.";

} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['message'] = "Error processing payment: " . $e->getMessage();
}

header("Location: list_vendor_bills.php");
exit;
?>