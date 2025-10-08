<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'accounting.php'; // Include accounting functions

$page_title = "Edit Invoice";
$errors = [];
$success_message = '';

$invoice_id = isset($_GET['id']) ? sanitize_int($_GET['id']) : null;

if (!$invoice_id) {
    $_SESSION['message'] = "No invoice ID provided for editing.";
    header("Location: index.php");
    exit;
}

$conn = db_connect();

// Fetch existing invoice data
$sql_fetch_invoice = "SELECT * FROM invoices WHERE id = $invoice_id";
$invoice_result = db_query($sql_fetch_invoice);
if (!$invoice_result || mysqli_num_rows($invoice_result) == 0) {
    $_SESSION['message'] = "Invoice not found (ID: $invoice_id).";
    header("Location: index.php");
    exit;
}
$invoice_data = db_fetch_assoc($invoice_result);

// Fetch existing invoice items
$sql_fetch_items = "SELECT * FROM invoice_items WHERE invoice_id = $invoice_id ORDER BY id ASC";
$items_result = db_query($sql_fetch_items);
$invoice_items_data_existing = db_fetch_all($items_result);

// Fetch clients and services for dropdowns
$clients = db_fetch_all(db_query("SELECT id, name FROM clients ORDER BY name ASC"));
$services = db_fetch_all(db_query("SELECT id, name, price FROM services ORDER BY name ASC"));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submitted_invoice_id = isset($_POST['invoice_id']) ? sanitize_int($_POST['invoice_id']) : null;
    if ($submitted_invoice_id !== $invoice_id) {
        $errors[] = "Invoice ID mismatch. Aborting update.";
    }

    // Sanitize and validate main invoice details
    $client_id = isset($_POST['client_id']) ? sanitize_int($_POST['client_id']) : null;
    $invoice_date = isset($_POST['invoice_date']) ? sanitize_string($_POST['invoice_date']) : '';
    $due_date = isset($_POST['due_date']) ? sanitize_string($_POST['due_date']) : '';
    $status = isset($_POST['status']) ? sanitize_string($_POST['status']) : 'Draft';
    $tax_percentage = isset($_POST['tax_percentage']) ? sanitize_decimal($_POST['tax_percentage']) : VAT_RATE;
    $discount_amount = isset($_POST['discount_amount']) ? sanitize_decimal($_POST['discount_amount']) : 0.00;
    $amount_paid = isset($_POST['amount_paid']) ? sanitize_decimal($_POST['amount_paid']) : 0.00;
    $payment_terms = isset($_POST['payment_terms']) ? sanitize_string($_POST['payment_terms']) : '';
    $notes = isset($_POST['notes']) ? sanitize_string($_POST['notes']) : '';
    $invoice_number_posted = isset($_POST['invoice_number']) ? sanitize_string($_POST['invoice_number']) : $invoice_data['invoice_number'];

    if (empty($client_id)) $errors[] = "Client is required.";
    if (empty($invoice_date)) $errors[] = "Invoice date is required.";
    if (empty($invoice_number_posted)) $errors[] = "Invoice number cannot be empty.";

    $invoice_items_submitted = [];
    $sub_total = 0;
    $item_count = isset($_POST['item_service_id']) ? count($_POST['item_service_id']) : 0;

    for ($i = 0; $i < $item_count; $i++) {
        $item_service_id = isset($_POST['item_service_id'][$i]) ? sanitize_int($_POST['item_service_id'][$i]) : null;
        $item_description = isset($_POST['item_description'][$i]) ? sanitize_string(trim($_POST['item_description'][$i])) : '';
        $item_quantity = isset($_POST['item_quantity'][$i]) ? sanitize_int($_POST['item_quantity'][$i]) : 0;
        $item_unit_price = isset($_POST['item_unit_price'][$i]) ? sanitize_decimal($_POST['item_unit_price'][$i]) : 0;

        if (!empty($item_description) && $item_quantity > 0 && $item_unit_price >= 0) {
            $item_total = $item_quantity * $item_unit_price;
            $sub_total += $item_total;
            $invoice_items_submitted[] = [
                'service_id' => $item_service_id ?: 'NULL',
                'description' => $item_description,
                'quantity' => $item_quantity,
                'unit_price' => $item_unit_price,
            ];
        }
    }

    if (empty($invoice_items_submitted) && empty($errors)) {
        $errors[] = "At least one valid invoice item is required.";
    }

    if (empty($errors)) {
        $tax_amount_calculated = ($sub_total * $tax_percentage) / 100;
        $grand_total = $sub_total + $tax_amount_calculated - $discount_amount;

        mysqli_autocommit($conn, false); // Start transaction
        $queries_ok = true;

        // 1. Delete existing journal entries for this invoice
        $sql_delete_gl = "DELETE FROM general_ledger WHERE reference_type = 'invoice' AND reference_id = $invoice_id";
        if (!db_query($sql_delete_gl)) {
            $errors[] = "Failed to clear old accounting entries: " . mysqli_error($conn);
            $queries_ok = false;
        }

        // 2. Update main invoice table
        if ($queries_ok) {
            $sql_update_invoice = "UPDATE invoices SET
                client_id = $client_id, invoice_number = '$invoice_number_posted', invoice_date = '$invoice_date',
                due_date = '$due_date', status = '$status', sub_total = $sub_total, tax_percentage = $tax_percentage,
                tax_amount = $tax_amount_calculated, discount_amount = $discount_amount, grand_total = $grand_total,
                amount_paid = $amount_paid, payment_terms = '$payment_terms', notes = '$notes'
                WHERE id = $invoice_id";

            if (!db_query($sql_update_invoice)) {
                $errors[] = "Failed to update invoice: " . mysqli_error($conn);
                $queries_ok = false;
            }
        }

        // 3. Delete old items and insert new ones
        if ($queries_ok) {
            $sql_delete_items = "DELETE FROM invoice_items WHERE invoice_id = $invoice_id";
            if (db_query($sql_delete_items)) {
                foreach ($invoice_items_submitted as $item) {
                    $item_sql = "INSERT INTO invoice_items (invoice_id, service_id, item_description, quantity, unit_price) VALUES (
                        $invoice_id, {$item['service_id']}, '{$item['description']}', {$item['quantity']}, {$item['unit_price']}
                    )";
                    if (!db_query($item_sql)) {
                        $errors[] = "Failed to update an invoice item: " . mysqli_error($conn);
                        $queries_ok = false;
                        break;
                    }
                }
            } else {
                $errors[] = "Failed to clear old invoice items: " . mysqli_error($conn);
                $queries_ok = false;
            }
        }

        // 4. Create new journal entries for the updated invoice
        if ($queries_ok) {
            $journal_entries = [
                ['account_id' => ACCOUNT_ID_ACCOUNTS_RECEIVABLE, 'debit' => $grand_total, 'credit' => 0],
                ['account_id' => ACCOUNT_ID_SALES_REVENUE, 'debit' => 0, 'credit' => $sub_total],
            ];
            if ($tax_amount_calculated > 0) {
                $journal_entries[] = ['account_id' => ACCOUNT_ID_VAT_PAYABLE, 'debit' => 0, 'credit' => $tax_amount_calculated];
            }

            $journal_description = "Update for Invoice #{$invoice_number_posted}.";

            if (!create_journal_transaction($invoice_date, $journal_description, $journal_entries, 'invoice', $invoice_id)) {
                $errors[] = "Failed to post updated accounting entries to the general ledger.";
                $queries_ok = false;
            }
        }

        if ($queries_ok) {
            mysqli_commit($conn);
            $_SESSION['message'] = "Invoice " . htmlspecialchars($invoice_number_posted) . " updated successfully!";
            header("Location: view_invoice.php?id=" . $invoice_id);
            exit;
        } else {
            mysqli_rollback($conn);
            $errors[] = "Invoice update failed. Transaction rolled back.";
        }
        mysqli_autocommit($conn, true);
    }

    // If errors, repopulate data for the form to show user's attempted changes
    if(!empty($errors)){
        $invoice_data['client_id'] = $_POST['client_id'] ?? $invoice_data['client_id'];
        $invoice_data['invoice_number'] = $_POST['invoice_number'] ?? $invoice_data['invoice_number'];
        $invoice_data['invoice_date'] = $_POST['invoice_date'] ?? $invoice_data['invoice_date'];
        $invoice_data['due_date'] = $_POST['due_date'] ?? $invoice_data['due_date'];
        $invoice_data['status'] = $_POST['status'] ?? $invoice_data['status'];
        $invoice_data['tax_percentage'] = $_POST['tax_percentage'] ?? $invoice_data['tax_percentage'];
        $invoice_data['discount_amount'] = $_POST['discount_amount'] ?? $invoice_data['discount_amount'];
        $invoice_data['amount_paid'] = $_POST['amount_paid'] ?? $invoice_data['amount_paid'];
        $invoice_data['payment_terms'] = $_POST['payment_terms'] ?? $invoice_data['payment_terms'];
        $invoice_data['notes'] = $_POST['notes'] ?? $invoice_data['notes'];

        $invoice_items_data_existing = [];
        if(isset($_POST['item_service_id'])) {
            for ($i = 0; $i < count($_POST['item_service_id']); $i++) {
                 $invoice_items_data_existing[] = [
                    'service_id' => $_POST['item_service_id'][$i],
                    'item_description' => $_POST['item_description'][$i],
                    'quantity' => $_POST['item_quantity'][$i],
                    'unit_price' => $_POST['item_unit_price'][$i],
                 ];
            }
        }
    }
}

include 'templates/header.php';
?>

<h2><?php echo $page_title; ?>: <?php echo htmlspecialchars($invoice_data['invoice_number']); ?></h2>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>Please correct the following errors:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="edit_invoice.php?id=<?php echo $invoice_id; ?>" method="post" id="invoiceForm">
    <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
    <input type="hidden" name="invoice_number" value="<?php echo htmlspecialchars($invoice_data['invoice_number']); ?>">

    <fieldset>
        <legend>Invoice Details</legend>
        <div class="form-group">
            <label for="client_id">Client:</label>
            <select name="client_id" id="client_id" required>
                <option value="">Select Client</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo $client['id']; ?>" <?php echo ($invoice_data['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($client['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="invoice_date">Invoice Date:</label>
            <input type="date" name="invoice_date" id="invoice_date" value="<?php echo htmlspecialchars($invoice_data['invoice_date']); ?>" required>
        </div>
        <div class="form-group">
            <label for="due_date">Due Date:</label>
            <input type="date" name="due_date" id="due_date" value="<?php echo htmlspecialchars($invoice_data['due_date']); ?>" required>
        </div>
        <div class="form-group">
            <label for="status">Status:</label>
            <select name="status" id="status">
                <option value="Draft" <?php echo ($invoice_data['status'] == 'Draft') ? 'selected' : ''; ?>>Draft</option>
                <option value="Sent" <?php echo ($invoice_data['status'] == 'Sent') ? 'selected' : ''; ?>>Sent</option>
                <option value="Paid" <?php echo ($invoice_data['status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                <option value="Overdue" <?php echo ($invoice_data['status'] == 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
            </select>
        </div>
    </fieldset>

    <fieldset>
        <legend>Invoice Items</legend>
        <table id="invoiceItemsTable">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Custom Description</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $item_rows_to_display = max(5, count($invoice_items_data_existing));
                for ($i = 0; $i < $item_rows_to_display; $i++):
                    $current_item = $invoice_items_data_existing[$i] ?? null;
                    $selected_service_id = $current_item['service_id'] ?? '';
                    $item_description_val = $current_item['item_description'] ?? ($current_item['description'] ?? '');
                    $item_quantity_val = $current_item['quantity'] ?? '1';
                    $item_unit_price_val = isset($current_item['unit_price']) ? number_format((float)$current_item['unit_price'], 2, '.', '') : '';
                ?>
                <tr>
                    <td>
                        <select name="item_service_id[]" class="service-select" data-row-id="<?php echo $i; ?>">
                            <option value="">Custom Item</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>"
                                        data-price="<?php echo $service['price']; ?>"
                                        data-description="<?php echo htmlspecialchars($service['name']); ?>"
                                        <?php echo ($selected_service_id == $service['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="text" name="item_description[]" class="item-description" id="item_description_<?php echo $i; ?>" value="<?php echo htmlspecialchars($item_description_val); ?>" placeholder="Service Details"></td>
                    <td><input type="number" name="item_quantity[]" class="item-quantity" id="item_quantity_<?php echo $i; ?>" value="<?php echo htmlspecialchars($item_quantity_val); ?>" min="0" step="1"></td>
                    <td><input type="text" name="item_unit_price[]" class="item-unit-price" id="item_unit_price_<?php echo $i; ?>" value="<?php echo htmlspecialchars($item_unit_price_val); ?>" placeholder="0.00" pattern="^\d+(\.\d{1,2})?$"></td>
                    <td><input type="text" name="item_total[]" class="item-total" readonly tabindex="-1"></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </fieldset>

    <fieldset>
        <legend>Financials</legend>
         <div class="form-group">
            <label for="sub_total_display">Sub-total:</label>
            <input type="text" id="sub_total_display" value="<?php echo number_format((float)($invoice_data['sub_total'] ?? 0), 2, '.', ''); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="tax_percentage">Tax (%):</label>
            <input type="text" name="tax_percentage" id="tax_percentage" value="<?php echo number_format((float)($invoice_data['tax_percentage'] ?? VAT_RATE), 2, '.', ''); ?>" pattern="^\d+(\.\d{1,2})?$">
        </div>
        <div class="form-group">
            <label for="tax_amount_display">Tax Amount:</label>
            <input type="text" id="tax_amount_display" value="<?php echo number_format((float)($invoice_data['tax_amount'] ?? 0), 2, '.', ''); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="discount_amount">Discount (Amount):</label>
            <input type="text" name="discount_amount" id="discount_amount" value="<?php echo number_format((float)($invoice_data['discount_amount'] ?? 0), 2, '.', ''); ?>" pattern="^\d+(\.\d{1,2})?$">
        </div>
        <div class="form-group">
            <label for="grand_total_display">Grand Total:</label>
            <input type="text" id="grand_total_display" value="<?php echo number_format((float)($invoice_data['grand_total'] ?? 0), 2, '.', ''); ?>" readonly>
        </div>
         <div class="form-group">
            <label for="amount_paid">Amount Paid:</label>
            <input type="text" name="amount_paid" id="amount_paid" value="<?php echo number_format((float)($invoice_data['amount_paid'] ?? 0), 2, '.', ''); ?>" pattern="^\d+(\.\d{1,2})?$">
        </div>
    </fieldset>

    <fieldset>
        <legend>Notes & Terms</legend>
        <div class="form-group">
            <label for="payment_terms">Payment Terms:</label>
            <textarea name="payment_terms" id="payment_terms"><?php echo htmlspecialchars($invoice_data['payment_terms'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="notes">Notes/Additional Information:</label>
            <textarea name="notes" id="notes"><?php echo htmlspecialchars($invoice_data['notes'] ?? ''); ?></textarea>
        </div>
    </fieldset>

    <button type="submit" class="btn">Update Invoice</button>
    <a href="view_invoice.php?id=<?php echo $invoice_id; ?>" class="btn" style="background-color: #777;">Cancel</a>
</form>

<script>
// Re-use the same JavaScript from create_invoice.php
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('invoiceItemsTable');
    const serviceSelects = table.querySelectorAll('.service-select');
    const taxPercentageInput = document.getElementById('tax_percentage');
    const discountAmountInput = document.getElementById('discount_amount');

    function updateItemRow(rowId, selectedServiceElement) {
        const row = selectedServiceElement ? selectedServiceElement.closest('tr') : document.querySelector(`#invoiceItemsTable tbody tr:nth-child(${parseInt(rowId) + 1})`);
        if (!row) return;

        const descriptionInput = row.querySelector('.item-description');
        const unitPriceInput = row.querySelector('.item-unit-price');

        if (selectedServiceElement && selectedServiceElement.value !== "") {
            const price = selectedServiceElement.options[selectedServiceElement.selectedIndex].getAttribute('data-price');
            const description = selectedServiceElement.options[selectedServiceElement.selectedIndex].getAttribute('data-description');
            if (unitPriceInput) unitPriceInput.value = parseFloat(price).toFixed(2);
            if (descriptionInput) descriptionInput.value = description;
        }
        calculateRowTotal(rowId);
    }

    function calculateRowTotal(rowId) {
        const row = document.querySelector(`#invoiceItemsTable tbody tr:nth-child(${parseInt(rowId) + 1})`);
        if (!row) return;

        const quantityInput = row.querySelector('.item-quantity');
        const unitPriceInput = row.querySelector('.item-unit-price');
        const totalInput = row.querySelector('.item-total');

        const quantity = parseFloat(quantityInput.value) || 0;
        const unitPrice = parseFloat(unitPriceInput.value) || 0;
        if (totalInput) totalInput.value = (quantity * unitPrice).toFixed(2);

        calculateOverallTotals();
    }

    function calculateOverallTotals() {
        let subTotal = 0;
        const itemTotals = table.querySelectorAll('.item-total');
        itemTotals.forEach(function(totalField) {
            if(totalField.value) subTotal += parseFloat(totalField.value) || 0;
        });
        document.getElementById('sub_total_display').value = subTotal.toFixed(2);

        const taxPercentage = parseFloat(taxPercentageInput.value) || 0;
        const taxAmount = (subTotal * taxPercentage) / 100;
        document.getElementById('tax_amount_display').value = taxAmount.toFixed(2);

        const discountAmount = parseFloat(discountAmountInput.value) || 0;
        const grandTotal = subTotal + taxAmount - discountAmount;
        document.getElementById('grand_total_display').value = grandTotal.toFixed(2);
    }

    serviceSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            updateItemRow(this.getAttribute('data-row-id'), this);
        });
    });

    table.addEventListener('input', function(event) {
        const target = event.target;
        if (target.classList.contains('item-quantity') || target.classList.contains('item-unit-price')) {
            let rowElement = target.closest('tr');
            if(rowElement) {
                let rowIndex = Array.from(rowElement.parentNode.children).indexOf(rowElement);
                calculateRowTotal(rowIndex);
            }
        }
    });

    if(taxPercentageInput) taxPercentageInput.addEventListener('input', calculateOverallTotals);
    if(discountAmountInput) discountAmountInput.addEventListener('input', calculateOverallTotals);

    const rowCount = table.querySelectorAll('tbody tr').length;
    for(let i = 0; i < rowCount; i++){
        calculateRowTotal(i);
    }
    calculateOverallTotals();
});
</script>

<?php
include 'templates/footer.php';
?>