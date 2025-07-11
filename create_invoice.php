<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Create New Invoice";
$errors = [];
$success_message = '';

// Fetch clients for dropdown
$clients_sql = "SELECT id, name FROM clients ORDER BY name ASC";
$clients_result = db_query($clients_sql);
$clients = db_fetch_all($clients_result);

// Fetch packages for dropdown
$packages_sql = "SELECT id, name, price FROM packages ORDER BY name ASC";
$packages_result = db_query($packages_sql);
$packages = db_fetch_all($packages_result);

// Function to generate a unique invoice number (simple version)
function generate_invoice_number() {
    // Example: INV-YYYYMMDD-XXXX (XXXX is a random or sequential number)
    // This should be made more robust to ensure uniqueness, possibly checking the DB.
    $prefix = "INV-";
    $date_part = date("Ymd");
    // Check the last invoice number to increment. For simplicity, using a random part.
    // A better way is to query DB for MAX(id) or a dedicated sequence.
    $random_part = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);

    $potential_invoice_number = $prefix . $date_part . "-" . $random_part;

    // Ensure it's unique
    $sql_check = "SELECT id FROM invoices WHERE invoice_number = '" . sanitize_string($potential_invoice_number) . "'";
    $res = db_query($sql_check);
    while($res && mysqli_num_rows($res) > 0) {
        $random_part = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
        $potential_invoice_number = $prefix . $date_part . "-" . $random_part;
        $res = db_query("SELECT id FROM invoices WHERE invoice_number = '" . sanitize_string($potential_invoice_number) . "'");
    }
    return $potential_invoice_number;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = db_connect(); // Establish connection for the transaction-like block

    // Sanitize and validate main invoice details
    $client_id = isset($_POST['client_id']) ? sanitize_int($_POST['client_id']) : null;
    $invoice_date = isset($_POST['invoice_date']) ? sanitize_string($_POST['invoice_date']) : ''; // Assuming YYYY-MM-DD
    $due_date = isset($_POST['due_date']) ? sanitize_string($_POST['due_date']) : '';       // Assuming YYYY-MM-DD
    $status = isset($_POST['status']) ? sanitize_string($_POST['status']) : 'Draft';
    $tax_percentage = isset($_POST['tax_percentage']) ? sanitize_decimal($_POST['tax_percentage']) : 0.00;
    $discount_amount = isset($_POST['discount_amount']) ? sanitize_decimal($_POST['discount_amount']) : 0.00;
    $amount_paid = isset($_POST['amount_paid']) ? sanitize_decimal($_POST['amount_paid']) : 0.00;
    $payment_terms = isset($_POST['payment_terms']) ? sanitize_string($_POST['payment_terms']) : '';
    $notes = isset($_POST['notes']) ? sanitize_string($_POST['notes']) : '';

    // Basic Validation
    if (empty($client_id)) $errors[] = "Client is required.";
    if (empty($invoice_date)) $errors[] = "Invoice date is required.";
    // Add more validation (date formats, numeric ranges etc.)

    $invoice_items_data = [];
    $sub_total = 0;

    // Process invoice items (assuming up to 5 item rows for simplicity without JS)
    // In a real app, JS would allow adding/removing rows dynamically.
    $item_count = isset($_POST['item_package_id']) ? count($_POST['item_package_id']) : 0;

    for ($i = 0; $i < $item_count; $i++) {
        $item_package_id = isset($_POST['item_package_id'][$i]) ? sanitize_int($_POST['item_package_id'][$i]) : null;
        $item_description = isset($_POST['item_description'][$i]) ? sanitize_string(trim($_POST['item_description'][$i])) : '';
        $item_quantity = isset($_POST['item_quantity'][$i]) ? sanitize_int($_POST['item_quantity'][$i]) : 0;
        $item_unit_price = isset($_POST['item_unit_price'][$i]) ? sanitize_decimal($_POST['item_unit_price'][$i]) : 0;

        if (!empty($item_description) && $item_quantity > 0 && $item_unit_price >= 0) {
            if ($item_package_id === false || $item_quantity === false || $item_unit_price === false) {
                $errors[] = "Invalid data in one of the item rows (row " . ($i+1) . "). Please check numbers.";
                // break; // Stop processing items if one is bad
            }

            $item_total = $item_quantity * $item_unit_price;
            $sub_total += $item_total;
            $invoice_items_data[] = [
                'package_id' => $item_package_id ?: 'NULL', // Handle empty selection for custom items
                'description' => $item_description,
                'quantity' => $item_quantity,
                'unit_price' => $item_unit_price,
                'total_price' => $item_total // Though DB calculates it, good to have it here
            ];
        } elseif (!empty($item_description) || $item_quantity > 0 || $item_unit_price > 0) {
            // If any part of an item row is filled, but not validly for processing
            if (empty($item_description) && $item_quantity > 0) {
                 $errors[] = "Item description is missing for an item with quantity (row " . ($i+1) . ").";
            }
            // Could add more specific errors for partially filled rows.
        }
    }

    if (empty($invoice_items_data) && empty($errors)) { // Check if errors occurred before this
        $errors[] = "At least one valid invoice item is required.";
    }


    if (empty($errors)) {
        // Calculate financials
        $tax_amount_calculated = ($sub_total * $tax_percentage) / 100;
        $grand_total = $sub_total + $tax_amount_calculated - $discount_amount;
        // balance_due is auto-calculated by DB: grand_total - amount_paid

        $invoice_number = generate_invoice_number();
        $s_invoice_number = sanitize_string($invoice_number); // Already sanitized in generate_invoice_number but good practice

        // Begin "transaction"
        mysqli_autocommit($conn, false); // Start transaction
        $queries_ok = true;

        $sql_invoice = "INSERT INTO invoices (invoice_number, client_id, invoice_date, due_date, status, sub_total, tax_percentage, tax_amount, discount_amount, grand_total, amount_paid, payment_terms, notes) VALUES (
            '$s_invoice_number', $client_id, '$invoice_date', '$due_date', '$status', $sub_total, $tax_percentage, $tax_amount_calculated, $discount_amount, $grand_total, $amount_paid, '$payment_terms', '$notes'
        )";

        if (db_query($sql_invoice)) {
            $last_invoice_id = db_insert_id();
            if ($last_invoice_id) {
                foreach ($invoice_items_data as $item) {
                    $item_sql = "INSERT INTO invoice_items (invoice_id, package_id, item_description, quantity, unit_price) VALUES (
                        $last_invoice_id,
                        {$item['package_id']},
                        '{$item['description']}',
                        {$item['quantity']},
                        {$item['unit_price']}
                    )";
                    if (!db_query($item_sql)) {
                        $errors[] = "Failed to add an invoice item: " . mysqli_error($conn);
                        $queries_ok = false;
                        break;
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
        // db_close(); // Connection closed by shutdown or end of script
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
                    <th>Package/Service</th>
                    <th>Custom Description</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 0; $i < 5; $i++): // Fixed 5 rows for simplicity ?>
                <tr>
                    <td>
                        <select name="item_package_id[]" class="package-select" data-row-id="<?php echo $i; ?>">
                            <option value="">Custom Item</option>
                            <?php foreach ($packages as $package): ?>
                                <option value="<?php echo $package['id']; ?>" data-price="<?php echo $package['price']; ?>" data-description="<?php echo htmlspecialchars($package['name']); ?>">
                                    <?php echo htmlspecialchars($package['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="text" name="item_description[]" class="item-description" id="item_description_<?php echo $i; ?>" placeholder="Service Details"></td>
                    <td><input type="number" name="item_quantity[]" class="item-quantity" id="item_quantity_<?php echo $i; ?>" value="1" min="0" step="1"></td>
                    <td><input type="text" name="item_unit_price[]" class="item-unit-price" id="item_unit_price_<?php echo $i; ?>" placeholder="0.00" pattern="^\d+(\.\d{1,2})?$"></td>
                    <td><input type="text" name="item_total[]" class="item-total" readonly tabindex="-1"></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
        <!-- A "Add Item" button would ideally be here if using JS -->
    </fieldset>

    <fieldset>
        <legend>Financials</legend>
        <div class="form-group">
            <label for="sub_total_display">Sub-total:</label>
            <input type="text" id="sub_total_display" readonly> <!-- Display only, calculated by JS or on server -->
        </div>
        <div class="form-group">
            <label for="tax_percentage">Tax (%):</label>
            <input type="text" name="tax_percentage" id="tax_percentage" value="<?php echo isset($_POST['tax_percentage']) ? htmlspecialchars($_POST['tax_percentage']) : '0.00'; ?>" pattern="^\d+(\.\d{1,2})?$">
        </div>
        <div class="form-group">
            <label for="tax_amount_display">Tax Amount:</label>
            <input type="text" id="tax_amount_display" readonly> <!-- Display only -->
        </div>
        <div class="form-group">
            <label for="discount_amount">Discount (Amount):</label>
            <input type="text" name="discount_amount" id="discount_amount" value="<?php echo isset($_POST['discount_amount']) ? htmlspecialchars($_POST['discount_amount']) : '0.00'; ?>" pattern="^\d+(\.\d{1,2})?$">
        </div>
        <div class="form-group">
            <label for="grand_total_display">Grand Total:</label>
            <input type="text" id="grand_total_display" readonly> <!-- Display only -->
        </div>
         <div class="form-group">
            <label for="amount_paid">Amount Paid:</label>
            <input type="text" name="amount_paid" id="amount_paid" value="<?php echo isset($_POST['amount_paid']) ? htmlspecialchars($_POST['amount_paid']) : '0.00'; ?>" pattern="^\d+(\.\d{1,2})?$">
        </div>
        <!-- Balance Due is calculated by DB and shown on view -->
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
// Basic JavaScript for auto-filling price and description, and calculating totals.
// This is minimal; a real application would benefit from a more robust JS solution.
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('invoiceItemsTable');
    const packageSelects = table.querySelectorAll('.package-select');
    const taxPercentageInput = document.getElementById('tax_percentage');
    const discountAmountInput = document.getElementById('discount_amount');

    function updateItemRow(rowId, selectedPackage) {
        const descriptionInput = document.getElementById('item_description_' + rowId);
        const unitPriceInput = document.getElementById('item_unit_price_' + rowId);

        if (selectedPackage && selectedPackage.value !== "") {
            const price = selectedPackage.options[selectedPackage.selectedIndex].getAttribute('data-price');
            const description = selectedPackage.options[selectedPackage.selectedIndex].getAttribute('data-description');
            unitPriceInput.value = parseFloat(price).toFixed(2);
            descriptionInput.value = description; // Overwrites custom description
        } else {
            // If "Custom Item" or nothing selected, user fills manually
            // Optionally clear price if custom is selected and it was pre-filled:
            // unitPriceInput.value = '';
        }
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

    packageSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            updateItemRow(this.getAttribute('data-row-id'), this);
        });
        // Initialize on load if a package is pre-selected (e.g. form repopulation)
        if(select.value){
             updateItemRow(select.getAttribute('data-row-id'), select);
        }
    });

    table.addEventListener('input', function(event) {
        if (event.target.classList.contains('item-quantity') || event.target.classList.contains('item-unit-price')) {
            // Find parent TR then row ID or index. This is a bit fragile.
            let row = event.target.closest('tr');
            if(row) {
                let rowIndex = Array.from(row.parentNode.children).indexOf(row);
                 // If package is "Custom Item", changing qty/price should update total
                const packageSelect = row.querySelector('.package-select');
                if (!packageSelect || packageSelect.value === "") {
                     // Allow manual price edit only if custom item
                    if (event.target.classList.contains('item-unit-price')) {
                        // Price input is being changed
                    }
                } else {
                    // If a package is selected, unit price is locked by default.
                    // If you want to allow override, you'd need more logic here.
                    // For now, if package is selected, changing qty recalculates total based on package price.
                }
                calculateRowTotal(rowIndex);
            }
        }
    });

    taxPercentageInput.addEventListener('input', calculateOverallTotals);
    discountAmountInput.addEventListener('input', calculateOverallTotals);

    // Initial calculation for all rows and overall totals
    for(let i=0; i<5; i++){ // Assuming 5 rows
        const qtyInput = document.getElementById('item_quantity_' + i);
        const priceInput = document.getElementById('item_unit_price_' + i);
        if(qtyInput.value && priceInput.value){ // only calculate if both have values
             calculateRowTotal(i);
        }
    }
    calculateOverallTotals(); // Calculate overall totals on page load
});
</script>

<?php
include 'templates/footer.php';
?>
