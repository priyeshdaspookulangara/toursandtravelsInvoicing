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

$conn = db_connect();

// Fetch the bill details
$sql_bill = "SELECT * FROM vendor_bills WHERE id = ? AND status = 'Unpaid'";
$stmt_bill = mysqli_prepare($conn, $sql_bill);
mysqli_stmt_bind_param($stmt_bill, "i", $bill_id);
mysqli_stmt_execute($stmt_bill);
$bill_result = mysqli_stmt_get_result($stmt_bill);
$bill = db_fetch_assoc($bill_result);

if (!$bill) {
    $_SESSION['message'] = "Error: Bill not found or already paid.";
    header("Location: list_vendor_bills.php");
    exit;
}

mysqli_begin_transaction($conn);
try {
    // 1. Update bill status to 'Paid'
    $sql_update = "UPDATE vendor_bills SET status = 'Paid' WHERE id = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "i", $bill_id);
    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception("Failed to update bill status.");
    }

    // 2. Post payment to General Ledger
    $payment_date = date('Y-m-d');
    $description_gl = "Payment for vendor bill #$bill_id";
    $entries = [
        ['account_id' => ACCOUNT_ID_ACCOUNTS_PAYABLE, 'debit' => $bill['total_amount'], 'credit' => 0],
        ['account_id' => ACCOUNT_ID_CASH, 'debit' => 0, 'credit' => $bill['total_amount']],
    ];

    if (!create_journal_transaction($payment_date, $description_gl, $entries, 'bill_payment', $bill_id)) {
        throw new Exception("Failed to post journal entries for bill payment.");
    }

    mysqli_commit($conn);
    $_SESSION['message'] = "Bill #$bill_id marked as paid successfully.";

} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['message'] = "Error processing payment: " . $e->getMessage();
}

header("Location: list_vendor_bills.php");
exit;
?>