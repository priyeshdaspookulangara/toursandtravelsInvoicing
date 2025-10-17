<?php
require_once 'db.php';
require_once 'accounting.php';

echo "Seeding database with test data...\n";

$conn = db_connect();

// --- 1. Truncate tables for a clean slate ---
$tables_to_truncate = ['general_ledger', 'expenses', 'invoice_items', 'invoices', 'services', 'clients'];
foreach ($tables_to_truncate as $table) {
    if (db_query("DELETE FROM $table")) {
        echo "Cleared table: $table\n";
        // Reset auto-increment
        db_query("ALTER TABLE $table AUTO_INCREMENT = 1");
    } else {
        die("Failed to truncate table $table. Aborting.\n");
    }
}
echo "All tables cleared.\n";


// --- 2. Create sample data ---
// Client
$client_sql = "INSERT INTO clients (name, email, address, phone) VALUES ('Test Client Corp', 'test@example.com', '123 Test Ave', '555-1234')";
db_query($client_sql);
$client_id = db_insert_id();
echo "Created client with ID: $client_id\n";

// Service
$service_sql = "INSERT INTO services (name, description, price) VALUES ('Web Development', 'Standard web dev services', 1200.00)";
db_query($service_sql);
$service_id = db_insert_id();
echo "Created service with ID: $service_id\n";

// --- 3. Create a "Paid" Invoice ---
$paid_invoice_date = date('Y-m-d', strtotime('-1 month'));
$paid_invoice = [
    'invoice_number' => 'INV-PAID-001',
    'client_id' => $client_id,
    'invoice_date' => $paid_invoice_date,
    'due_date' => date('Y-m-d'),
    'status' => 'Paid',
    'sub_total' => 1200.00,
    'tax_percentage' => 5.00,
    'tax_amount' => 60.00,
    'discount_amount' => 0.00,
    'grand_total' => 1260.00,
    'amount_paid' => 1260.00,
    'payment_terms' => 'Paid in full.',
    'notes' => 'Test paid invoice.'
];
$sql = "INSERT INTO invoices (invoice_number, client_id, invoice_date, due_date, status, sub_total, tax_percentage, tax_amount, discount_amount, grand_total, amount_paid, payment_terms, notes) VALUES ('{$paid_invoice['invoice_number']}', {$paid_invoice['client_id']}, '{$paid_invoice['invoice_date']}', '{$paid_invoice['due_date']}', '{$paid_invoice['status']}', {$paid_invoice['sub_total']}, {$paid_invoice['tax_percentage']}, {$paid_invoice['tax_amount']}, {$paid_invoice['discount_amount']}, {$paid_invoice['grand_total']}, {$paid_invoice['amount_paid']}, '{$paid_invoice['payment_terms']}', '{$paid_invoice['notes']}')";
db_query($sql);
$paid_invoice_id = db_insert_id();
// Add item
db_query("INSERT INTO invoice_items (invoice_id, service_id, item_description, quantity, unit_price) VALUES ($paid_invoice_id, $service_id, 'Web Development', 1, 1200.00)");
// Add journal entries
create_journal_transaction($paid_invoice_date, "Invoice #{$paid_invoice['invoice_number']}", [
    ['account_id' => ACCOUNT_ID_ACCOUNTS_RECEIVABLE, 'debit' => 1260.00, 'credit' => 0],
    ['account_id' => ACCOUNT_ID_SALES_REVENUE, 'debit' => 0, 'credit' => 1200.00],
    ['account_id' => ACCOUNT_ID_VAT_PAYABLE, 'debit' => 0, 'credit' => 60.00],
], 'invoice', $paid_invoice_id);
create_journal_transaction($paid_invoice_date, "Payment for Invoice #{$paid_invoice['invoice_number']}", [
    ['account_id' => ACCOUNT_ID_CASH, 'debit' => 1260.00, 'credit' => 0],
    ['account_id' => ACCOUNT_ID_ACCOUNTS_RECEIVABLE, 'debit' => 0, 'credit' => 1260.00],
], 'payment', $paid_invoice_id);
echo "Created 'Paid' invoice with ID: $paid_invoice_id\n";


// --- 4. Create a "Draft" Invoice ---
$draft_invoice_date = date('Y-m-d');
$draft_invoice = [
    'invoice_number' => 'INV-DRAFT-001',
    'client_id' => $client_id,
    'invoice_date' => $draft_invoice_date,
    'due_date' => date('Y-m-d', strtotime('+30 days')),
    'status' => 'Draft',
    'sub_total' => 500.00,
    'tax_percentage' => 5.00,
    'tax_amount' => 25.00,
    'discount_amount' => 0.00,
    'grand_total' => 525.00,
    'amount_paid' => 0.00,
    'payment_terms' => 'Due on receipt.',
    'notes' => 'Test draft invoice.'
];
$sql = "INSERT INTO invoices (invoice_number, client_id, invoice_date, due_date, status, sub_total, tax_percentage, tax_amount, discount_amount, grand_total, amount_paid, payment_terms, notes) VALUES ('{$draft_invoice['invoice_number']}', {$draft_invoice['client_id']}, '{$draft_invoice['invoice_date']}', '{$draft_invoice['due_date']}', '{$draft_invoice['status']}', {$draft_invoice['sub_total']}, {$draft_invoice['tax_percentage']}, {$draft_invoice['tax_amount']}, {$draft_invoice['discount_amount']}, {$draft_invoice['grand_total']}, {$draft_invoice['amount_paid']}, '{$draft_invoice['payment_terms']}', '{$draft_invoice['notes']}')";
db_query($sql);
$draft_invoice_id = db_insert_id();
// Add item
db_query("INSERT INTO invoice_items (invoice_id, item_description, quantity, unit_price) VALUES ($draft_invoice_id, 'Consulting', 5, 100.00)");
// Add journal entries
create_journal_transaction($draft_invoice_date, "Invoice #{$draft_invoice['invoice_number']}", [
    ['account_id' => ACCOUNT_ID_ACCOUNTS_RECEIVABLE, 'debit' => 525.00, 'credit' => 0],
    ['account_id' => ACCOUNT_ID_SALES_REVENUE, 'debit' => 0, 'credit' => 500.00],
    ['account_id' => ACCOUNT_ID_VAT_PAYABLE, 'debit' => 0, 'credit' => 25.00],
], 'invoice', $draft_invoice_id);
echo "Created 'Draft' invoice with ID: $draft_invoice_id\n";


// --- 5. Create an Expense ---
$expense_date = date('Y-m-d', strtotime('-2 weeks'));
$expense_sql = "INSERT INTO expenses (expense_date, category_id, amount, vendor, description) VALUES ('$expense_date', 43, 75.50, 'Office Supply Co', 'Printer Ink')"; // 43 is Office Supplies
db_query($expense_sql);
$expense_id = db_insert_id();
// Add journal entry
create_journal_transaction($expense_date, "Expense: Printer Ink", [
    ['account_id' => 43, 'debit' => 75.50, 'credit' => 0],
    ['account_id' => ACCOUNT_ID_CASH, 'debit' => 0, 'credit' => 75.50],
], 'expense', $expense_id);
echo "Created expense with ID: $expense_id\n";


echo "Database seeding complete.\n";
?>