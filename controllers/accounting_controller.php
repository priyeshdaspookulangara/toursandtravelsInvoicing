<?php
require_once __DIR__ . '/../db.php';

/**
 * Records a single transaction line in the general ledger.
 * This function uses prepared statements to prevent SQL injection.
 *
 * @param mysqli $conn The database connection object.
 * @param string $date The date of the transaction (Y-m-d).
 * @param int $account_id The ID of the account.
 * @param float $debit The debit amount.
 * @param float $credit The credit amount.
 * @param string $description A description of the transaction.
 * @param string $ref_type The type of reference (e.g., 'invoice').
 * @param int $ref_id The ID of the reference.
 * @return bool True on success, false on failure.
 */
function record_gl_transaction($conn, $date, $account_id, $debit, $credit, $description, $ref_type, $ref_id) {
    // Basic validation
    if (empty($date) || empty($account_id) || ($debit == 0 && $credit == 0)) {
        error_log("GL Transaction validation failed: Date, AccountID, or amounts are missing.");
        return false;
    }

    $sql = "INSERT INTO general_ledger (entry_date, account_id, debit, credit, description, reference_type, reference_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Failed to prepare GL transaction statement: " . mysqli_error($conn));
        return false;
    }

    mysqli_stmt_bind_param($stmt, "siddssi", $date, $account_id, $debit, $credit, $description, $ref_type, $ref_id);

    $success = mysqli_stmt_execute($stmt);

    if (!$success) {
        error_log("Failed to execute GL transaction statement: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

    return $success;
}

/**
 * Creates a complete journal transaction with multiple entries.
 * This function ensures that the total debits equal total credits.
 *
 * @param string $date The date of the transaction.
 * @param string $description The overall description for the journal entry.
 * @param array $entries An array of journal entry lines. Each line is an associative array with 'account_id', 'debit', and 'credit'.
 * @param string $ref_type The reference type (e.g., 'invoice').
 * @param int $ref_id The reference ID.
 * @return bool True if the transaction was successfully recorded, false otherwise.
 */
function create_journal_transaction($date, $description, $entries, $ref_type, $ref_id) {
    $total_debits = 0;
    $total_credits = 0;
    $conn = db_connect();

    foreach ($entries as $entry) {
        $total_debits += $entry['debit'];
        $total_credits += $entry['credit'];
    }

    // Ensure the transaction is balanced
    if (round($total_debits, 2) != round($total_credits, 2)) {
        error_log("Journal transaction is not balanced. Debits: $total_debits, Credits: $total_credits");
        return false;
    }

    foreach ($entries as $entry) {
        if (!record_gl_transaction($conn, $date, $entry['account_id'], $entry['debit'], $entry['credit'], $description, $ref_type, $ref_id)) {
            // If any part of the transaction fails, we shouldn't proceed.
            // A transaction block in the calling code should handle rollback.
            return false;
        }
    }

    return true;
}