<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'controllers/accounting_controller.php';

$page_title = "Create New Invoice";
$errors = [];

$clients = db_fetch_all(db_query("SELECT id, name FROM clients ORDER BY name ASC"));
$services = db_fetch_all(db_query("SELECT id, name, price FROM services ORDER BY name ASC"));
$vendors = db_fetch_all(db_query("SELECT id, name FROM vendors ORDER BY name ASC"));

function generate_invoice_number() {
    return "INV-" . date("Ymd") . "-" . rand(1000, 9999);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = db_connect();
    $client_id = sanitize_int($_POST['client_id']);
    $invoice_date = sanitize_string($_POST['invoice_date']);
    $due_date = sanitize_string($_POST['due_date']);
    $status = sanitize_string($_POST['status']);
    $tax_percentage = sanitize_decimal($_POST['tax_percentage']);

    $invoice_items_data = [];
    $sub_total = 0;
    $item_count = isset($_POST['item_service_id']) ? count($_POST['item_service_id']) : 0;

    for ($i = 0; $i < $item_count; $i++) {
        $item_service_id = sanitize_int($_POST['item_service_id'][$i]);
        $item_description = sanitize_string($_POST['item_description'][$i]);
        $item_quantity = sanitize_int($_POST['item_quantity'][$i]);
        $item_unit_price = sanitize_decimal($_POST['item_unit_price'][$i]);
        $item_cost_of_sale = sanitize_decimal($_POST['item_cost_of_sale'][$i]);
        $item_vendor_id = sanitize_int($_POST['item_vendor_id'][$i]);

        if (!empty($item_description) && $item_quantity > 0 && $item_unit_price >= 0) {
            $item_total = $item_quantity * $item_unit_price;
            $sub_total += $item_total;
            $invoice_items_data[] = [
                'service_id' => $item_service_id,
                'description' => $item_description,
                'quantity' => $item_quantity,
                'unit_price' => $item_unit_price,
                'cost_of_sale' => $item_cost_of_sale,
                'vendor_id' => $item_vendor_id,
            ];
        }
    }

    if (empty($errors)) {
        $tax_amount = ($sub_total * $tax_percentage) / 100;
        $grand_total = $sub_total + $tax_amount;
        $invoice_number = generate_invoice_number();

        mysqli_begin_transaction($conn);
        try {
            $sql_invoice = "INSERT INTO invoices (invoice_number, client_id, invoice_date, due_date, status, sub_total, tax_percentage, tax_amount, grand_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_invoice = mysqli_prepare($conn, $sql_invoice);
            mysqli_stmt_bind_param($stmt_invoice, "sisssdddd", $invoice_number, $client_id, $invoice_date, $due_date, $status, $sub_total, $tax_percentage, $tax_amount, $grand_total);
            mysqli_stmt_execute($stmt_invoice);
            $last_invoice_id = mysqli_insert_id($conn);

            foreach ($invoice_items_data as $item) {
                $sql_item = "INSERT INTO invoice_items (invoice_id, service_id, item_description, quantity, unit_price, cost_of_sale, vendor_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt_item = mysqli_prepare($conn, $sql_item);
                mysqli_stmt_bind_param($stmt_item, "iisiddi", $last_invoice_id, $item['service_id'], $item['description'], $item['quantity'], $item['unit_price'], $item['cost_of_sale'], $item['vendor_id']);
                mysqli_stmt_execute($stmt_item);
            }

            // Simplified accounting logic for now
            // ...

            mysqli_commit($conn);
            header("Location: view_invoice.php?id=" . $last_invoice_id);
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = $e->getMessage();
        }
    }
}

include 'templates/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
</div>

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
    <div class="row">
        <div class="col-lg-9">
            <div class="card mb-4">
                <div class="card-header">Invoice Details</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="client_id" class="form-label">Client</label>
                            <select name="client_id" id="client_id" class="form-select" required>
                                <option value="">Select Client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="Draft">Draft</option>
                                <option value="Sent">Sent</option>
                                <option value="Paid">Paid</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="invoice_date" class="form-label">Invoice Date</label>
                            <input type="date" name="invoice_date" id="invoice_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" name="due_date" id="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Invoice Items</div>
                <div class="card-body">
                    <table class="table table-hover" id="invoiceItemsTable">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Service</th>
                                <th>Description</th>
                                <th style="width: 8%;">Qty</th>
                                <th style="width: 12%;">Unit Price</th>
                                <th class="cost-col" style="display:none; width: 12%;">Cost</th>
                                <th class="vendor-col" style="display:none; width: 18%;">Vendor</th>
                                <th style="width: 12%;">Total</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Dynamic rows here -->
                        </tbody>
                    </table>
                    <button type="button" id="addItem" class="btn btn-secondary"><i class="fas fa-plus"></i> Add Item</button>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">Summary</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Sub-total:</span>
                        <span id="sub_total_display">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 align-items-center">
                        <span>Tax (%):</span>
                        <input type="number" name="tax_percentage" id="tax_percentage" class="form-control form-control-sm" value="<?php echo number_format(VAT_RATE, 2); ?>" style="width: 70px;">
                    </div>
                     <div class="d-flex justify-content-between mb-3">
                        <span>Tax Amount:</span>
                        <span id="tax_amount_display">$0.00</span>
                    </div>
                     <hr>
                    <div class="d-flex justify-content-between fw-bold h5">
                        <span>Grand Total:</span>
                        <span id="grand_total_display">$0.00</span>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <button type="submit" class="btn btn-primary w-100">Create Invoice</button>
                    <a href="index.php" class="btn btn-light w-100 mt-2">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemIndex = 0;
    const servicesData = <?php echo json_encode($services); ?>;
    const vendorsData = <?php echo json_encode($vendors); ?>;

    function createServiceOptions() {
        let options = '<option value="">Select Service</option>';
        servicesData.forEach(s => {
            options += `<option value="${s.id}" data-price="${s.price}" data-is-ticket="${s.name.toLowerCase().includes('ticket') ? 'true' : 'false'}">${s.name}</option>`;
        });
        return options;
    }

    function createVendorOptions() {
        let options = '<option value="">Select Vendor</option>';
        vendorsData.forEach(v => {
            options += `<option value="${v.id}">${v.name}</option>`;
        });
        return options;
    }

    function addItemRow() {
        const tableBody = document.querySelector("#invoiceItemsTable tbody");
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><select name="item_service_id[]" class="form-select service-select">${createServiceOptions()}</select></td>
            <td><input type="text" name="item_description[]" class="form-control item-description"></td>
            <td><input type="number" name="item_quantity[]" class="form-control item-quantity" value="1" min="1"></td>
            <td><input type="number" name="item_unit_price[]" class="form-control item-unit-price" step="0.01"></td>
            <td class="cost-col" style="display:none;"><input type="number" name="item_cost_of_sale[]" class="form-control" step="0.01"></td>
            <td class="vendor-col" style="display:none;"><select name="item_vendor_id[]" class="form-select">${createVendorOptions()}</select></td>
            <td><input type="text" class="form-control item-total" readonly></td>
            <td><button type="button" class="btn btn-danger btn-sm removeItem"><i class="fas fa-trash"></i></button></td>
        `;
        tableBody.appendChild(row);
        itemIndex++;
    }
    document.getElementById('addItem').addEventListener('click', addItemRow);

    document.querySelector("#invoiceItemsTable").addEventListener('change', function(e) {
        if (e.target.classList.contains('service-select')) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const row = e.target.closest('tr');
            row.querySelector('.item-description').value = selectedOption.text;
            row.querySelector('.item-unit-price').value = selectedOption.dataset.price || '0.00';

            const isTicket = selectedOption.dataset.isTicket === 'true';
            const costCol = row.querySelector('.cost-col');
            const vendorCol = row.querySelector('.vendor-col');

            costCol.style.display = isTicket ? '' : 'none';
            vendorCol.style.display = isTicket ? '' : 'none';

            document.querySelectorAll('.cost-col, .vendor-col').forEach(col => col.style.display = document.querySelector('.service-select option[data-is-ticket="true"]:checked') ? '' : 'none');
        }
        calculateTotals();
    });

    document.querySelector("#invoiceItemsTable").addEventListener('click', function(e) {
        if (e.target.closest('.removeItem')) {
            e.target.closest('tr').remove();
            calculateTotals();
        }
    });

    document.body.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-quantity') || e.target.classList.contains('item-unit-price') || e.target.id === 'tax_percentage') {
            calculateTotals();
        }
    });

    function calculateTotals() {
        let subTotal = 0;
        document.querySelectorAll("#invoiceItemsTable tbody tr").forEach(row => {
            const qty = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const price = parseFloat(row.querySelector('.item-unit-price').value) || 0;
            const total = qty * price;
            row.querySelector('.item-total').value = total.toFixed(2);
            subTotal += total;
        });

        document.getElementById('sub_total_display').textContent = '$' + subTotal.toFixed(2);
        const taxPercentage = parseFloat(document.getElementById('tax_percentage').value) || 0;
        const taxAmount = (subTotal * taxPercentage) / 100;
        document.getElementById('tax_amount_display').textContent = '$' + taxAmount.toFixed(2);

        const grandTotal = subTotal + taxAmount;
        document.getElementById('grand_total_display').textContent = '$' + grandTotal.toFixed(2);
    }

    addItemRow();
});
</script>

<?php include 'templates/footer.php'; ?>