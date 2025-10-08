<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'accounting.php'; // Important for transactional integrity with ledger

$invoice_id = isset($_GET['id']) ? sanitize_int($_GET['id']) : null;

if (!$invoice_id || $invoice_id <= 0) {
    $_SESSION['message'] = "Invalid or missing Invoice ID for deletion.";
    header("Location: index.php");
    exit;
}

$conn = db_connect();

// Fetch invoice number for user-friendly messages
$sql_get_invoice_number = "SELECT invoice_number FROM invoices WHERE id = $invoice_id";
$result_get_number = db_query($sql_get_invoice_number);
if ($result_get_number && mysqli_num_rows($result_get_number) > 0) {
    $invoice_number_for_message = db_fetch_assoc($result_get_number)['invoice_number'];
} else {
    $_SESSION['message'] = "Invoice with ID " . htmlspecialchars($invoice_id) . " not found or already deleted.";
    header("Location: index.php");
    exit;
}

// Start transaction
mysqli_autocommit($conn, false);
$queries_ok = true;

// 1. Delete associated journal entries from the general ledger
// This includes entries for the invoice creation and any payments.
$sql_delete_gl = "DELETE FROM general_ledger WHERE (reference_type = 'invoice' AND reference_id = ?) OR (reference_type = 'payment' AND reference_id = ?)";
$stmt_gl = $conn->prepare($sql_delete_gl);
if ($stmt_gl) {
    $stmt_gl->bind_param("ii", $invoice_id, $invoice_id);
    if (!$stmt_gl->execute()) {
        $queries_ok = false;
        error_log("Failed to delete journal entries for invoice ID $invoice_id: " . $stmt_gl->error);
    }
    $stmt_gl->close();
} else {
    $queries_ok = false;
    error_log("Failed to prepare GL delete statement for invoice ID $invoice_id: " . $conn->error);
}

// 2. Delete the invoice itself. ON DELETE CASCADE will handle invoice_items.
if ($queries_ok) {
    $sql_delete_invoice = "DELETE FROM invoices WHERE id = ?";
    $stmt_invoice = $conn->prepare($sql_delete_invoice);
    if ($stmt_invoice) {
        $stmt_invoice->bind_param("i", $invoice_id);
        if ($stmt_invoice->execute()) {
            if ($stmt_invoice->affected_rows == 0) {
                // This means the invoice was already gone, which is an inconsistent state
                // since we fetched it earlier. Rollback.
                $queries_ok = false;
                $_SESSION['message'] = "Invoice '" . htmlspecialchars($invoice_number_for_message) . "' was not found at the time of deletion. The transaction has been rolled back.";
            }
        } else {
            $queries_ok = false;
            error_log("Failed to delete invoice ID $invoice_id: " . $stmt_invoice->error);
        }
        $stmt_invoice->close();
    } else {
        $queries_ok = false;
        error_log("Failed to prepare invoice delete statement for invoice ID $invoice_id: " . $conn->error);
    }
}

// Commit or rollback the transaction
if ($queries_ok) {
    mysqli_commit($conn);
    $_SESSION['message'] = "Invoice '" . htmlspecialchars($invoice_number_for_message) . "' and all associated accounting entries have been deleted successfully.";
} else {
    mysqli_rollback($conn);
    // Use a generic message for the user but keep the specific error in the log
    $_SESSION['message'] = "Error deleting invoice '" . htmlspecialchars($invoice_number_for_message) . "'. The transaction was rolled back to ensure data integrity.";
}

mysqli_autocommit($conn, true); // Restore default behavior

header("Location: index.php");
exit;
?>