<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'accounting.php'; // Include the new accounting functions

$page_title = "Create New Invoice";
$errors = [];
$success_message = '';

// Fetch clients for dropdown
$clients_sql = "SELECT id, name FROM clients ORDER BY name ASC";
$clients_result = db_query($clients_sql);
$clients = db_fetch_all($clients_result);

// Fetch services for dropdown
$services_sql = "SELECT id, name, price FROM services ORDER BY name ASC";
$services_result = db_query($services_sql);
$services = db_fetch_all($services_result);

// Function to generate a unique invoice number
function generate_invoice_number() {
    $prefix = "INV-";
    $date_part = date("Ymd");

    // Find the highest invoice number for today to increment
    $sql_check = "SELECT invoice_number FROM invoices WHERE invoice_number LIKE '" . sanitize_string($prefix . $date_part) . "-%' ORDER BY invoice_number DESC LIMIT 1";
    $res = db_query($sql_check);
    if ($res && mysqli_num_rows($res) > 0) {
        $last_invoice = mysqli_fetch_assoc($res)['invoice_number'];
        $last_num_part = (int)substr($last_invoice, -4);
        $new_num_part = $last_num_part + 1;
    } else {
        $new_num_part = 1;
    }

    $sequential_part = str_pad($new_num_part, 4, '0', STR_PAD_LEFT);

    return $prefix . $date_part . "-" . $sequential_part;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = db_connect(); // Establish connection for the transaction

    // Sanitize and validate main invoice details
    $client_id = isset($_POST['client_id']) ? sanitize_int($_POST['client_id']) : null;
    $invoice_date = isset($_POST['invoice_date']) ? sanitize_string($_POST['invoice_date']) : ''; // YYYY-MM-DD
    $due_date = isset($_POST['due_date']) ? sanitize_string($_POST['due_date']) : '';       // YYYY-MM-DD
    $status = isset($_POST['status']) ? sanitize_string($_POST['status']) : 'Draft';
    $tax_percentage = isset($_POST['tax_percentage']) ? sanitize_decimal($_POST['tax_percentage']) : VAT_RATE; // Default to VAT_RATE
    $discount_amount = isset($_POST['discount_amount']) ? sanitize_decimal($_POST['discount_amount']) : 0.00;
    $amount_paid = isset($_POST['amount_paid']) ? sanitize_decimal($_POST['amount_paid']) : 0.00;
    $payment_terms = isset($_POST['payment_terms']) ? sanitize_string($_POST['payment_terms']) : '';
    $notes = isset($_POST['notes']) ? sanitize_string($_POST['notes']) : '';

    // Basic Validation
    if (empty($client_id)) $errors[] = "Client is required.";
    if (empty($invoice_date)) $errors[] = "Invoice date is required.";

    $invoice_items_data = [];
    $sub_total = 0;

    $item_count = isset($_POST['item_service_id']) ? count($_POST['item_service_id']) : 0;

    for ($i = 0; $i < $item_count; $i++) {
        $item_service_id = isset($_POST['item_service_id'][$i]) ? sanitize_int($_POST['item_service_id'][$i]) : null;
        $item_description = isset($_POST['item_description'][$i]) ? sanitize_string(trim($_POST['item_description'][$i])) : '';
        $item_quantity = isset($_POST['item_quantity'][$i]) ? sanitize_int($_POST['item_quantity'][$i]) : 0;
        $item_unit_price = isset($_POST['item_unit_price'][$i]) ? sanitize_decimal($_POST['item_unit_price'][$i]) : 0;
        $item_unit_type = isset($_POST['item_unit_type'][$i]) ? sanitize_string($_POST['item_unit_type'][$i]) : '';

        if (!empty($item_description) && $item_quantity > 0 && $item_unit_price >= 0) {
             if ($item_service_id === false || $item_quantity === false || $item_unit_price === false) {
                $errors[] = "Invalid data in one of the item rows (row " . ($i+1) . "). Please check numbers.";
            }

            $item_total = $item_quantity * $item_unit_price;
            $sub_total += $item_total;
            $invoice_items_data[] = [
                'service_id' => $item_service_id ?: 'NULL',
                'description' => $item_description,
                'quantity' => $item_quantity,
                'unit_price' => $item_unit_price,
                'unit_type' => $item_unit_type,
            ];
        } elseif (!empty($item_description) || $item_quantity > 0 || $item_unit_price > 0) {
            if (empty($item_description) && $item_quantity > 0) {
                 $errors[] = "Item description is missing for an item with quantity (row " . ($i+1) . ").";
            }
        }
    }

    if (empty($invoice_items_data) && empty($errors)) {
        $errors[] = "At least one valid invoice item is required.";
    }


    if (empty($errors)) {
        // Calculate financials
        $tax_amount_calculated = ($sub_total * $tax_percentage) / 100;
        $grand_total = $sub_total + $tax_amount_calculated - $discount_amount;

        $invoice_number = generate_invoice_number();
        $s_invoice_number = sanitize_string($invoice_number);

        // Begin transaction
        mysqli_autocommit($conn, false);
        $queries_ok = true;

        $sql_invoice = "INSERT INTO invoices (invoice_number, client_id, invoice_date, due_date, status, sub_total, tax_percentage, tax_amount, discount_amount, grand_total, amount_paid, payment_terms, notes) VALUES (
            '$s_invoice_number', $client_id, '$invoice_date', '$due_date', '$status', $sub_total, $tax_percentage, $tax_amount_calculated, $discount_amount, $grand_total, $amount_paid, '$payment_terms', '$notes'
        )";

        if (db_query($sql_invoice)) {
            $last_invoice_id = db_insert_id();
            if ($last_invoice_id) {
                $has_dtp = false;
                foreach ($invoice_items_data as $item) {
                    $item_sql = "INSERT INTO invoice_items (invoice_id, service_id, item_description, quantity, unit_price, unit_type) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt_item = mysqli_prepare($conn, $item_sql);
                    mysqli_stmt_bind_param($stmt_item, "iisids", $last_invoice_id, $item['service_id'], $item['description'], $item['quantity'], $item['unit_price'], $item['unit_type']);
                    if (!mysqli_stmt_execute($stmt_item)) {
                        $errors[] = "Failed to add an invoice item: " . mysqli_stmt_error($stmt_item);
                        $queries_ok = false;
                        break;
                    }
                    if (strpos(strtolower($item['description']), 'dtp') !== false) {
                        $has_dtp = true;
                    }
                }
            } else {
                $errors[] = "Failed to retrieve last invoice ID.";
                $queries_ok = false;
            }
        } else {
            $errors[] = "Failed to create invoice: " . mysqli_error($conn);
            $queries_ok = false;
        }

        // If invoice saved, now post to General Ledger
        if ($queries_ok) {
            $sales_revenue_account = $has_dtp ? ACCOUNT_ID_SALES_REVENUE_DTP : ACCOUNT_ID_SALES_REVENUE;
            $journal_entries = [
                // Debit Accounts Receivable for the grand total
                ['account_id' => ACCOUNT_ID_ACCOUNTS_RECEIVABLE, 'debit' => $grand_total, 'credit' => 0],
                // Credit Sales Revenue for the sub-total
                ['account_id' => $sales_revenue_account, 'debit' => 0, 'credit' => $sub_total],
            ];
            // Add VAT entry only if there is tax
            if ($tax_amount_calculated > 0) {
                $journal_entries[] = ['account_id' => ACCOUNT_ID_VAT_PAYABLE, 'debit' => 0, 'credit' => $tax_amount_calculated];
            }

            $journal_description = "Invoice #{$invoice_number} generated.";

            if (!create_journal_transaction($invoice_date, $journal_description, $journal_entries, 'invoice', $last_invoice_id)) {
                $errors[] = "Failed to post accounting entries to the general ledger.";
                $queries_ok = false;
            }
        }


        if ($queries_ok) {
            mysqli_commit($conn); // Commit transaction
            $_SESSION['message'] = "Invoice " . htmlspecialchars($invoice_number) . " created successfully!";
            header("Location: view_invoice.php?id=" . $last_invoice_id);
            exit;
        } else {
            mysqli_rollback($conn); // Rollback transaction
            $errors[] = "Invoice creation failed. Transaction rolled back.";
        }
        mysqli_autocommit($conn, true); // Restore autocommit
    }
}


include 'templates/header.php';
?>

<h2><?php echo $page_title; ?></h2>

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

<form action="create_invoice.php" method="post" id="invoiceForm">
    <fieldset>
        <legend>Invoice Details</legend>
        <div class="form-group">
            <label for="client_id">Client:</label>
            <select name="client_id" id="client_id" required>
                <option value="">Select Client</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo $client['id']; ?>" <?php echo (isset($_POST['client_id']) && $_POST['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($client['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="invoice_date">Invoice Date:</label>
            <input type="date" name="invoice_date" id="invoice_date" value="<?php echo isset($_POST['invoice_date']) ? htmlspecialchars($_POST['invoice_date']) : date('Y-m-d'); ?>" required>
        </div>
        <div class="form-group">
            <label for="due_date">Due Date:</label>
            <input type="date" name="due_date" id="due_date" value="<?php echo isset($_POST['due_date']) ? htmlspecialchars($_POST['due_date']) : date('Y-m-d', strtotime('+30 days')); ?>" required>
        </div>
        <div class="form-group">
            <label for="status">Status:</label>
            <select name="status" id="status">
                <option value="Draft" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Draft') ? 'selected' : ''; ?>>Draft</option>
                <option value="Sent" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Sent') ? 'selected' : ''; ?>>Sent</option>
                <option value="Paid" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                <option value="Overdue" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
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
                    <th class="dtp-col" style="display:none;">Unit Type</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 0; $i < 5; $i++): ?>
                <tr class="item-row">
                    <td>
                        <select name="item_service_id[]" class="service-select" data-row-id="<?php echo $i; ?>">
                            <option value="">Custom Item</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>" data-price="<?php echo $service['price']; ?>" data-description="<?php echo htmlspecialchars($service['name']); ?>" data-is-dtp="<?php echo (strpos(strtolower($service['name']), 'dtp') !== false) ? 'true' : 'false'; ?>">
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="text" name="item_description[]" class="item-description" id="item_description_<?php echo $i; ?>" placeholder="Service Details"></td>
                    <td><input type="number" name="item_quantity[]" class="item-quantity" id="item_quantity_<?php echo $i; ?>" value="1" min="0" step="1"></td>
                    <td><input type="text" name="item_unit_price[]" class="item-unit-price" id="item_unit_price_<?php echo $i; ?>" placeholder="0.00" pattern="^\d+(\.\d{1,2})?$"></td>
                    <td class="dtp-col" style="display:none;">
                        <select name="item_unit_type[]" class="form-select">
                            <option value="Pages">Pages</option>
                            <option value="Hours">Hours</option>
                            <option value="Project">Project</option>
                        </select>
                    </td>
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
            <input type="text" id="sub_total_display" readonly>
        </div>
        <div class="form-group">
            <label for="tax_percentage">Tax (%):</label>
            <input type="text" name="tax_percentage" id="tax_percentage" value="<?php echo isset($_POST['tax_percentage']) ? htmlspecialchars($_POST['tax_percentage']) : number_format(VAT_RATE, 2); ?>" pattern="^\d+(\.\d{1,2})?$">
        </div>
        <div class="form-group">
            <label for="tax_amount_display">Tax Amount:</label>
            <input type="text" id="tax_amount_display" readonly>
        </div>
        <div class="form-group">
            <label for="discount_amount">Discount (Amount):</label>
            <input type="text" name="discount_amount" id="discount_amount" value="<?php echo isset($_POST['discount_amount']) ? htmlspecialchars($_POST['discount_amount']) : '0.00'; ?>" pattern="^\d+(\.\d{1,2})?$">
        </div>
        <div class="form-group">
            <label for="grand_total_display">Grand Total:</label>
            <input type="text" id="grand_total_display" readonly>
        </div>
         <div class="form-group">
            <label for="amount_paid">Amount Paid (Initial):</label>
            <input type="text" name="amount_paid" id="amount_paid" value="<?php echo isset($_POST['amount_paid']) ? htmlspecialchars($_POST['amount_paid']) : '0.00'; ?>" pattern="^\d+(\.\d{1,2})?$">
        </div>
    </fieldset>

    <fieldset>
        <legend>Notes & Terms</legend>
        <div class="form-group">
            <label for="payment_terms">Payment Terms:</label>
            <textarea name="payment_terms" id="payment_terms"><?php echo isset($_POST['payment_terms']) ? htmlspecialchars($_POST['payment_terms']) : 'Payment due within 30 days.'; ?></textarea>
        </div>
        <div class="form-group">
            <label for="notes">Notes/Additional Information:</label>
            <textarea name="notes" id="notes"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : 'Thank you for your business!'; ?></textarea>
        </div>
    </fieldset>

    <button type="submit" class="btn">Create Invoice</button>
    <a href="index.php" class="btn" style="background-color: #777;">Cancel</a>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('invoiceItemsTable');
    const serviceSelects = table.querySelectorAll('.service-select');
    const taxPercentageInput = document.getElementById('tax_percentage');
    const discountAmountInput = document.getElementById('discount_amount');

    function updateItemRow(rowId, selectedService) {
        const row = document.querySelector(`#invoiceItemsTable tbody tr:nth-child(${parseInt(rowId) + 1})`);
        const descriptionInput = row.querySelector('.item-description');
        const unitPriceInput = row.querySelector('.item-unit-price');
        const dtpCol = row.querySelector('.dtp-col');

        if (selectedService && selectedService.value !== "") {
            const selectedOption = selectedService.options[selectedService.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            const description = selectedOption.getAttribute('data-description');
            const isDtp = selectedOption.getAttribute('data-is-dtp') === 'true';

            unitPriceInput.value = parseFloat(price).toFixed(2);
            descriptionInput.value = description;
            dtpCol.style.display = isDtp ? '' : 'none';
        } else {
            dtpCol.style.display = 'none';
        }

        const anyDtp = Array.from(document.querySelectorAll('.service-select')).some(s => s.options[s.selectedIndex]?.dataset.isDtp === 'true');
        document.querySelectorAll('.dtp-col').forEach(th => th.style.display = anyDtp ? '' : 'none');

        calculateRowTotal(rowId);
    }

    function calculateRowTotal(rowId) {
        const quantityInput = document.getElementById('item_quantity_' + rowId);
        const unitPriceInput = document.getElementById('item_unit_price_' + rowId);
        const totalInput = document.querySelector(`#invoiceItemsTable tbody tr:nth-child(${parseInt(rowId) + 1}) .item-total`);

        const quantity = parseFloat(quantityInput.value) || 0;
        const unitPrice = parseFloat(unitPriceInput.value) || 0;
        totalInput.value = (quantity * unitPrice).toFixed(2);
        calculateOverallTotals();
    }

    function calculateOverallTotals() {
        let subTotal = 0;
        const itemTotals = table.querySelectorAll('.item-total');
        itemTotals.forEach(function(totalField) {
            subTotal += parseFloat(totalField.value) || 0;
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
        if(select.value){
             updateItemRow(select.getAttribute('data-row-id'), select);
        }
    });

    table.addEventListener('input', function(event) {
        if (event.target.classList.contains('item-quantity') || event.target.classList.contains('item-unit-price')) {
            let row = event.target.closest('tr');
            if(row) {
                let rowIndex = Array.from(row.parentNode.children).indexOf(row);
                calculateRowTotal(rowIndex);
            }
        }
    });

    taxPercentageInput.addEventListener('input', calculateOverallTotals);
    discountAmountInput.addEventListener('input', calculateOverallTotals);

    for(let i=0; i<5; i++){
        const qtyInput = document.getElementById('item_quantity_' + i);
        const priceInput = document.getElementById('item_unit_price_' + i);
        if(qtyInput.value && priceInput.value){
             calculateRowTotal(i);
        }
    }
    calculateOverallTotals();
});
</script>

<?php
include 'templates/footer.php';
?>