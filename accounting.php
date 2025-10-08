<?php
require_once 'db.php';
require_once 'config.php';

/**
 * Creates a complete journal transaction with multiple debits and/or credits.
 * Ensures the total debits equal total credits before committing to the database.
 *
 * @param string $entry_date        The date of the transaction (Y-m-d).
 * @param string $description       A description for the entire transaction.
 * @param array  $entries           An array of journal entry lines. Each line is an associative array:
 *                                  e.g., [['account_id' => 1, 'debit' => 105.00, 'credit' => 0],
 *                                         ['account_id' => 10, 'debit' => 0, 'credit' => 100.00],
 *                                         ['account_id' => 11, 'debit' => 0, 'credit' => 5.00]]
 * @param string|null $ref_type     (Optional) The type of reference (e.g., 'invoice', 'payment').
 * @param int|null    $ref_id       (Optional) The ID of the reference.
 * @return bool True on success, false on failure.
 */
function create_journal_transaction($entry_date, $description, array $entries, $ref_type = null, $ref_id = null) {
    $conn = db_connect();
    if (!$conn) {
        error_log("Database connection failed in create_journal_transaction.");
        return false;
    }

    $total_debits = 0.00;
    $total_credits = 0.00;

    foreach ($entries as $entry) {
        $total_debits += (float) $entry['debit'];
        $total_credits += (float) $entry['credit'];
    }

    // Check if the transaction is balanced. Use a small tolerance for float comparison.
    if (abs($total_debits - $total_credits) > 0.001) {
        error_log("Journal transaction is not balanced. Debits: $total_debits, Credits: $total_credits");
        return false;
    }

    // Begin database transaction
    $conn->begin_transaction();

    try {
        $sql = "INSERT INTO general_ledger (entry_date, account_id, debit, credit, description, reference_type, reference_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        foreach ($entries as $entry) {
            // Only insert entries with a non-zero debit or credit
            if ((float)$entry['debit'] > 0 || (float)$entry['credit'] > 0) {
                $stmt->bind_param(
                    "siddssi",
                    $entry_date,
                    $entry['account_id'],
                    $entry['debit'],
                    $entry['credit'],
                    $description,
                    $ref_type,
                    $ref_id
                );

                if (!$stmt->execute()) {
                    throw new Exception("Execute failed for account " . $entry['account_id'] . ": " . $stmt->error);
                }
            }
        }

        // If all executions were successful, commit the transaction
        $conn->commit();
        $stmt->close();
        return true;

    } catch (Exception $e) {
        // If anything fails, roll back the entire transaction
        $conn->rollback();
        error_log("Journal transaction failed and was rolled back: " . $e->getMessage());
        if (isset($stmt)) $stmt->close();
        return false;
    }
}
?>