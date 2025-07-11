<?php
require_once 'auth.php'; // Ensure user is authenticated
require_once 'db.php';   // Database connection and query functions

$invoice_id = null;
$invoice_number_for_message = ''; // For user feedback

// For this basic version, we'll allow GET requests as it's linked with a JS confirm.
// For enhanced security, one might check for a POST request and a CSRF token.
// if ($_SERVER["REQUEST_METHOD"] !== "POST") {
//    $_SESSION['message'] = "Invalid request method for deleting invoice.";
//    header("Location: index.php");
//    exit;
// }

if (isset($_GET['id'])) {
    $invoice_id = sanitize_int($_GET['id']);
}
// Or if using POST:
// if (isset($_POST['invoice_id'])) {
//    $invoice_id = sanitize_int($_POST['invoice_id']);
// }


if (!$invoice_id || $invoice_id <= 0) {
    $_SESSION['message'] = "Invalid or missing Invoice ID for deletion.";
    header("Location: index.php");
    exit;
}

$conn = db_connect();

// Optional: Fetch invoice number for a more user-friendly message before deleting
$sql_get_invoice_number = "SELECT invoice_number FROM invoices WHERE id = $invoice_id";
$result_get_number = db_query($sql_get_invoice_number);
if ($result_get_number && mysqli_num_rows($result_get_number) > 0) {
    $row = db_fetch_assoc($result_get_number);
    $invoice_number_for_message = $row['invoice_number'];
} else {
    $_SESSION['message'] = "Invoice with ID " . htmlspecialchars($invoice_id) . " not found or already deleted.";
    header("Location: index.php");
    exit;
}


// Proceed with deletion
// The ON DELETE CASCADE for invoice_items table will handle deletion of related items.
$sql_delete_invoice = "DELETE FROM invoices WHERE id = $invoice_id";

mysqli_autocommit($conn, false); // Start transaction for atomicity, though simple delete might not strictly need it unless other checks were involved.
$delete_successful = false;

if (db_query($sql_delete_invoice)) {
    if (db_affected_rows() > 0) {
        $delete_successful = true;
        mysqli_commit($conn);
        $_SESSION['message'] = "Invoice '" . htmlspecialchars($invoice_number_for_message) . "' (ID: " . htmlspecialchars($invoice_id) . ") and its items have been deleted successfully.";
    } else {
        // No rows affected, invoice might have been deleted by another process between fetch and delete
        mysqli_rollback($conn); // Rollback, though nothing changed
        $_SESSION['message'] = "Invoice '" . htmlspecialchars($invoice_number_for_message) . "' (ID: " . htmlspecialchars($invoice_id) . ") was not found or already deleted. No changes made.";
    }
} else {
    mysqli_rollback($conn);
    // Provide a more generic error for the user, log the specific SQL error for the admin
    $_SESSION['message'] = "Error deleting invoice '" . htmlspecialchars($invoice_number_for_message) . "' (ID: " . htmlspecialchars($invoice_id) . "). Please try again. If the problem persists, contact support.";
    error_log("Failed to delete invoice ID $invoice_id: " . mysqli_error($conn)); // Log detailed error
}

mysqli_autocommit($conn, true); // Restore autocommit behavior
// db_close(); // Connection closed by shutdown or end of script

header("Location: index.php");
exit;
?>
