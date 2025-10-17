<?php
// This script should be run once after the main setup.php to populate the chart of accounts.
// It's separated to keep the initial database setup clean and to allow for easy modification
// of the chart of accounts without re-running the entire site setup.

require_once 'config.php';
require_once 'db.php';

// --- Chart of Accounts Data ---
// The IDs are explicitly defined here to match the constants in config.php
$accounts = [
    // Assets
    ['id' => 1, 'account_name' => 'Cash and Bank', 'account_type' => 'Asset', 'description' => 'Cash on hand and in bank accounts.'],
    ['id' => 2, 'account_name' => 'Accounts Receivable', 'account_type' => 'Asset', 'description' => 'Money owed to the company by its clients.'],
    ['id' => 3, 'account_name' => 'Prepaid Expenses', 'account_type' => 'Asset', 'description' => 'Expenses paid in advance.'],

    // Liabilities
    ['id' => 10, 'account_name' => 'Accounts Payable', 'account_type' => 'Liability', 'description' => 'Money the company owes to its suppliers.'],
    ['id' => 11, 'account_name' => 'VAT Payable', 'account_type' => 'Liability', 'description' => 'VAT collected from sales, to be paid to the government.'],

    // Equity
    ['id' => 20, 'account_name' => 'Owner\'s Equity', 'account_type' => 'Equity', 'description' => 'The owner\'s investment in the company.'],
    ['id' => 21, 'account_name' => 'Retained Earnings', 'account_type' => 'Equity', 'description' => 'Profits reinvested in the company.'],

    // Revenue
    ['id' => 30, 'account_name' => 'Sales Revenue', 'account_type' => 'Revenue', 'description' => 'Revenue from primary business activities (tours, tickets, etc.).'],
    ['id' => 31, 'account_name' => 'Service Fees', 'account_type' => 'Revenue', 'description' => 'Revenue from other services like visa processing.'],

    // Expenses
    ['id' => 40, 'account_name' => 'Salaries and Wages', 'account_type' => 'Expense', 'description' => 'Employee salaries and wages.'],
    ['id' => 41, 'account_name' => 'Rent Expense', 'account_type' => 'Expense', 'description' => 'Rent for office space.'],
    ['id' => 42, 'account_name' => 'Utilities Expense', 'account_type' => 'Expense', 'description' => 'Cost of utilities like electricity and water.'],
    ['id' => 43, 'account_name' => 'Office Supplies', 'account_type' => 'Expense', 'description' => 'Cost of office supplies.'],
    ['id' => 44, 'account_name' => 'Travel Expense', 'account_type' => 'Expense', 'description' => 'Costs associated with business travel.'],
    ['id' => 45, 'account_name' => 'Bank Service Charges', 'account_type' => 'Expense', 'description' => 'Fees charged by the bank.'],
];

echo "<h3>Setting up Chart of Accounts...</h3>";

$db = db_connect(); // Corrected function call
$all_successful = true;

foreach ($accounts as $account) {
    // Check if the account already exists by name to prevent duplicates on re-run
    $stmt_check = $db->prepare("SELECT id FROM chart_of_accounts WHERE account_name = ?");
    $stmt_check->bind_param("s", $account['account_name']);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows === 0) {
        $sql = "INSERT INTO `chart_of_accounts` (`id`, `account_name`, `account_type`, `description`, `is_active`) VALUES (?, ?, ?, ?, 1)";

        // Use prepared statements to prevent SQL injection
        $stmt_insert = $db->prepare($sql);
        if ($stmt_insert) {
            $stmt_insert->bind_param(
                "isss",
                $account['id'],
                $account['account_name'],
                $account['account_type'],
                $account['description']
            );

            if ($stmt_insert->execute()) {
                echo "<p style='color: green;'>Successfully inserted account: " . htmlspecialchars($account['account_name']) . "</p>";
            } else {
                echo "<p style='color: red;'>Error inserting account '" . htmlspecialchars($account['account_name']) . "': " . $stmt_insert->error . "</p>";
                $all_successful = false;
            }
            $stmt_insert->close();
        } else {
            echo "<p style='color: red;'>Error preparing statement for '" . htmlspecialchars($account['account_name']) . "': " . $db->error . "</p>";
            $all_successful = false;
        }
    } else {
        echo "<p style='color: orange;'>Skipped: Account '" . htmlspecialchars($account['account_name']) . "' already exists.</p>";
    }
    $stmt_check->close();
}

$db->close();

if ($all_successful) {
    echo "<h4>Chart of Accounts setup complete!</h4>";
    echo "<p><strong>IMPORTANT:</strong> For security, please delete this file (`setup_accounts.php`) from your server now.</p>";
} else {
    echo "<h4 style='color: red;'>Chart of Accounts setup encountered errors. Please check the messages above.</h4>";
}

?>