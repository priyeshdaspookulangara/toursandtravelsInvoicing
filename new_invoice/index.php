<?php
// new_invoice/index.php
// This page handles both displaying the new invoice form and processing its submission.

// Start session to store messages (e.g., success/error after submission)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and configuration
require_once __DIR__ . '/../db_connect.php'; // $mysqli is now available

$page_title = "Create New Invoice";
$current_page = "new_invoice"; // For highlighting active link in sidebar

// Initialize variables for form fields to prevent errors and for sticky form
$invoice_number = '';
$client_name = '';
$invoice_date = date('Y-m-d'); // Default to today
$invoice_items_data = [
    ['description' => '', 'quantity' => 1, 'unit_price' => ''] // Start with one empty item row
];
$total_amount = 0;

$success_message = '';
$error_message = '';

// Check for messages from previous operations (e.g., after redirect)
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}


// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_invoice'])) {
    // Sanitize and retrieve form data
    $invoice_number = isset($_POST['invoice_number']) ? trim(mysqli_real_escape_string($mysqli, $_POST['invoice_number'])) : '';
    $client_name = isset($_POST['client_name']) ? trim(mysqli_real_escape_string($mysqli, $_POST['client_name'])) : '';
    $invoice_date = isset($_POST['invoice_date']) ? trim(mysqli_real_escape_string($mysqli, $_POST['invoice_date'])) : date('Y-m-d');
    // total_amount is calculated, but we'll re-verify from items on server side for security.

    $items_description = isset($_POST['item_description']) ? $_POST['item_description'] : [];
    $items_quantity = isset($_POST['item_quantity']) ? $_POST['item_quantity'] : [];
    $items_unit_price = isset($_POST['item_unit_price']) ? $_POST['item_unit_price'] : [];

    // --- Validation (Basic) ---
    if (empty($invoice_number)) {
        $error_message .= "Invoice Number is required.<br>";
    } else {
        // Check if invoice number already exists
        $check_sql = "SELECT id FROM invoices WHERE invoice_number = '" . $invoice_number . "'";
        $check_result = mysqli_query($mysqli, $check_sql);
        if (mysqli_num_rows($check_result) > 0) {
            $error_message .= "Invoice Number '" . htmlspecialchars($invoice_number) . "' already exists. Please use a unique invoice number.<br>";
        }
    }
    if (empty($client_name)) {
        $error_message .= "Client Name is required.<br>";
    }
    if (empty($invoice_date)) {
        $error_message .= "Invoice Date is required.<br>";
    }
    if (empty($items_description) || count($items_description) === 0) {
        $error_message .= "At least one item is required for the invoice.<br>";
    }

    $calculated_grand_total = 0;
    $valid_items = [];

    foreach ($items_description as $key => $desc) {
        $desc = trim(mysqli_real_escape_string($mysqli, $desc));
        $qty = isset($items_quantity[$key]) ? filter_var($items_quantity[$key], FILTER_VALIDATE_INT) : 0;
        $price = isset($items_unit_price[$key]) ? filter_var(str_replace(',', '', $items_unit_price[$key]), FILTER_VALIDATE_FLOAT) : 0.0;

        if (empty($desc)) {
            $error_message .= "Item description for row " . ($key + 1) . " is required.<br>";
            continue; // Skip this item if description is missing
        }
        if ($qty === false || $qty <= 0) {
            $error_message .= "Quantity for item '" . htmlspecialchars($desc) . "' must be a positive whole number.<br>";
            $qty = 0; // Set to 0 if invalid to avoid calculation errors
        }
        if ($price === false || $price < 0) {
            $error_message .= "Unit Price for item '" . htmlspecialchars($desc) . "' must be a positive number.<br>";
            $price = 0.0; // Set to 0.0 if invalid
        }

        $line_total = $qty * $price;
        $calculated_grand_total += $line_total;
        $valid_items[] = [
            'description' => $desc,
            'quantity' => $qty,
            'unit_price' => $price,
            'line_total' => $line_total
        ];
    }

    if (count($valid_items) === 0 && empty($error_message)) { // If all items were invalid but no specific error was set for items yet
        $error_message .= "No valid items found in the invoice. Please ensure all item details are correct.<br>";
    }


    // If no validation errors, proceed to insert into database
    if (empty($error_message)) {
        mysqli_begin_transaction($mysqli); // Start transaction

        try {
            // Insert into invoices table
            $sql_invoice = "INSERT INTO invoices (invoice_number, client_name, invoice_date, total_amount)
                            VALUES ('" . $invoice_number . "', '" . $client_name . "', '" . $invoice_date . "', " . $calculated_grand_total . ")";

            if (mysqli_query($mysqli, $sql_invoice)) {
                $invoice_id = mysqli_insert_id($mysqli); // Get the ID of the newly inserted invoice

                // Insert into invoice_items table
                $all_items_inserted = true;
                foreach ($valid_items as $item) {
                    $sql_item = "INSERT INTO invoice_items (invoice_id, item_description, quantity, unit_price, line_total)
                                 VALUES (" . $invoice_id . ", '" . $item['description'] . "', " . $item['quantity'] . ", " . $item['unit_price'] . ", " . $item['line_total'] . ")";
                    if (!mysqli_query($mysqli, $sql_item)) {
                        $all_items_inserted = false;
                        // Log detailed error
                        error_log("Error inserting invoice item for invoice ID $invoice_id: " . mysqli_error($mysqli));
                        $error_message = "Error saving invoice items. Please try again. Details: " . mysqli_error($mysqli); // Show specific error for debugging
                        break; // Exit loop on first item error
                    }
                }

                if ($all_items_inserted) {
                    mysqli_commit($mysqli); // Commit transaction
                    $_SESSION['success_message'] = "Invoice '" . htmlspecialchars($invoice_number) . "' created successfully!";
                    // Redirect to view invoices page or the newly created invoice's detail page
                    header("Location: " . BASE_URL . "view_invoices/"); // Or BASE_URL . "invoice_details/?id=" . $invoice_id
                    exit();
                } else {
                    mysqli_rollback($mysqli); // Rollback transaction
                    // Error message already set
                }
            } else {
                mysqli_rollback($mysqli); // Rollback transaction
                // Log detailed error
                error_log("Error inserting invoice header: " . mysqli_error($mysqli));
                $error_message = "Error saving invoice. Please try again. Details: " . mysqli_error($mysqli); // Show specific error for debugging
            }
        } catch (Exception $e) {
            mysqli_rollback($mysqli);
            error_log("Exception during invoice creation: " . $e->getMessage());
            $error_message = "An unexpected error occurred. Please try again. Details: " . $e->getMessage();
        }
    }

    // If there were errors, repopulate $invoice_items_data for sticky form
    if (!empty($error_message)) {
        $invoice_items_data = []; // Reset and repopulate
        foreach ($items_description as $key => $desc) {
            $invoice_items_data[] = [
                'description' => isset($_POST['item_description'][$key]) ? htmlspecialchars($_POST['item_description'][$key]) : '',
                'quantity' => isset($_POST['item_quantity'][$key]) ? htmlspecialchars($_POST['item_quantity'][$key]) : 1,
                'unit_price' => isset($_POST['item_unit_price'][$key]) ? htmlspecialchars($_POST['item_unit_price'][$key]) : ''
            ];
        }
        // If no items were submitted but there was an error, ensure at least one blank row for the form
        if (empty($invoice_items_data)) {
            $invoice_items_data = [['description' => '', 'quantity' => 1, 'unit_price' => '']];
        }
    }
} // End of POST handling

// Include the header template
require_once __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col">
            <h2><?php echo $page_title; ?></h2>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success" role="alert">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <strong>Error:</strong><br>
            <?php echo $error_message; // Ensure this is HTML-safe if it contains user input directly, though errors are usually dev-generated ?>
        </div>
    <?php endif; ?>

    <form action="<?php echo BASE_URL; ?>new_invoice/" method="POST" id="invoiceForm">
        <div class="card">
            <div class="card-header">
                Invoice Details
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="invoice_number" class="form-label">Invoice Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="invoice_number" name="invoice_number" value="<?php echo htmlspecialchars($invoice_number); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="client_name" class="form-label">Client Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="client_name" name="client_name" value="<?php echo htmlspecialchars($client_name); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="invoice_date" class="form-label">Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="invoice_date" name="invoice_date" value="<?php echo htmlspecialchars($invoice_date); ?>" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Invoice Items</span>
                <button type="button" class="btn btn-sm btn-success" id="addItemBtn"><i class="fas fa-plus me-1"></i> Add Item</button>
            </div>
            <div class="card-body">
                <table class="table table-bordered" id="invoiceItemsTable">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Description <span class="text-danger">*</span></th>
                            <th style="width: 15%;">Quantity <span class="text-danger">*</span></th>
                            <th style="width: 20%;">Unit Price <span class="text-danger">*</span></th>
                            <th style="width: 20%;">Line Total</th>
                            <th style="width: 5%;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="invoiceItemsTbody">
                        <?php foreach ($invoice_items_data as $index => $item): ?>
                        <tr class="invoice-item-row">
                            <td>
                                <input type="text" name="item_description[]" class="form-control item-description" placeholder="Item description" value="<?php echo htmlspecialchars($item['description']); ?>" required>
                            </td>
                            <td>
                                <input type="number" name="item_quantity[]" class="form-control item-quantity text-end" placeholder="1" min="1" value="<?php echo htmlspecialchars($item['quantity']); ?>" required>
                            </td>
                            <td>
                                <input type="text" name="item_unit_price[]" class="form-control item-unit-price text-end" placeholder="0.00" value="<?php echo htmlspecialchars($item['unit_price']); ?>" required>
                            </td>
                            <td>
                                <span class="line-total-display fw-bold text-end d-block pe-2">0.00</span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger removeItemBtn"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                            <td id="subtotalDisplay" class="fw-bold text-end pe-2">0.00</td>
                            <td></td>
                        </tr>
                        <!-- You can add rows for Tax, Discount here if needed -->
                        <tr>
                            <td colspan="3" class="text-end fw-bold fs-5">Grand Total:</td>
                            <td id="grandTotalDisplay" class="fw-bold fs-5 text-end pe-2">0.00</td>
                            <input type="hidden" name="total_amount" id="total_amount_hidden" value="0">
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="mt-4 text-end">
            <a href="<?php echo BASE_URL; ?>view_invoices/" class="btn btn-secondary">Cancel</a>
            <button type="submit" name="save_invoice" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Invoice</button>
        </div>
    </form>
</div>

<?php
// JavaScript for dynamic item rows and calculations
// This could also be moved to a separate assets/js/invoice.js file
?>
<script>
jQuery(document).ready(function($) {

    function formatCurrency(amount) {
        return parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    function parseCurrency(value) {
        // Remove common currency symbols and thousands separators
        let cleanedValue = String(value).replace(/[$,]/g, '');
        let number = parseFloat(cleanedValue);
        return isNaN(number) ? 0 : number;
    }


    function calculateLineTotal(row) {
        const quantity = parseInt(row.find('.item-quantity').val()) || 0;
        const unitPrice = parseCurrency(row.find('.item-unit-price').val()) || 0;
        const lineTotal = quantity * unitPrice;
        row.find('.line-total-display').text(formatCurrency(lineTotal));
        return lineTotal;
    }

    function calculateGrandTotal() {
        let subtotal = 0;
        $('#invoiceItemsTbody tr.invoice-item-row').each(function() {
            subtotal += calculateLineTotal($(this));
        });
        $('#subtotalDisplay').text(formatCurrency(subtotal));

        // For now, grand total is same as subtotal. Add tax/discount logic here if needed.
        const grandTotal = subtotal;
        $('#grandTotalDisplay').text(formatCurrency(grandTotal));
        $('#total_amount_hidden').val(grandTotal.toFixed(2)); // Update hidden input for form submission
    }

    // Add item row
    $('#addItemBtn').on('click', function() {
        const newRowHtml = `
            <tr class="invoice-item-row">
                <td><input type="text" name="item_description[]" class="form-control item-description" placeholder="Item description" required></td>
                <td><input type="number" name="item_quantity[]" class="form-control item-quantity text-end" placeholder="1" min="1" value="1" required></td>
                <td><input type="text" name="item_unit_price[]" class="form-control item-unit-price text-end" placeholder="0.00" required></td>
                <td><span class="line-total-display fw-bold text-end d-block pe-2">0.00</span></td>
                <td><button type="button" class="btn btn-sm btn-danger removeItemBtn"><i class="fas fa-trash"></i></button></td>
            </tr>`;
        $('#invoiceItemsTbody').append(newRowHtml);
        attachRowEventListeners($('#invoiceItemsTbody tr:last-child'));
        calculateGrandTotal(); // Recalculate after adding a new row (which might have default values)
    });

    // Remove item row
    // Use event delegation for dynamically added rows
    $('#invoiceItemsTbody').on('click', '.removeItemBtn', function() {
        $(this).closest('tr.invoice-item-row').remove();
        if ($('#invoiceItemsTbody tr.invoice-item-row').length === 0) {
            // Optionally, add a default blank row if all items are removed
            // $('#addItemBtn').click();
            // Or just let it be empty and rely on server-side validation for at least one item
        }
        calculateGrandTotal();
    });

    // Attach event listeners for quantity and unit price changes
    function attachRowEventListeners(row) {
        row.find('.item-quantity, .item-unit-price').on('input change', function() {
            calculateGrandTotal();
        });
         // Format unit price on blur
        row.find('.item-unit-price').on('blur', function() {
            let value = parseCurrency($(this).val());
            $(this).val(value > 0 ? value.toFixed(2) : ''); // Keep empty if 0 or invalid, or format 0.00
            calculateGrandTotal(); // Recalculate as blur might change the formatted value
        });
    }

    // Initial calculation for existing rows (e.g., on page load with sticky form data)
    $('#invoiceItemsTbody tr.invoice-item-row').each(function() {
        attachRowEventListeners($(this));
    });
    calculateGrandTotal(); // Initial calculation on page load


    // Client-side validation hint (optional, server-side is key)
    $("#invoiceForm").on("submit", function(event) {
        let isValid = true;
        let firstInvalidField = null;

        // Check required fields in header
        $("#invoice_number, #client_name, #invoice_date").each(function() {
            if ($(this).val().trim() === "") {
                $(this).addClass("is-invalid");
                isValid = false;
                if (!firstInvalidField) firstInvalidField = $(this);
            } else {
                $(this).removeClass("is-invalid");
            }
        });

        // Check if at least one item row exists
        if ($('#invoiceItemsTbody tr.invoice-item-row').length === 0) {
            isValid = false;
            // You might want to show a general error message for no items
            if (!$('.alert-danger:contains("At least one item is required")').length) {
                 // Avoid adding duplicate messages if server already added one
                const noItemsError = '<div class="alert alert-danger mt-2" role="alert">At least one item is required for the invoice.</div>';
                $(noItemsError).insertAfter($("#invoiceItemsTable"));
            }
             if (!firstInvalidField) {
                // If no other field was invalid, maybe focus on the "Add Item" button or table
                // For simplicity, we won't focus here.
            }
        } else {
            // Remove "no items" error if items are now present
             $('.alert-danger:contains("At least one item is required")').remove();
        }


        // Check required fields in item rows
        $('#invoiceItemsTbody tr.invoice-item-row').each(function() {
            const row = $(this);
            row.find('.item-description, .item-quantity, .item-unit-price').each(function() {
                const field = $(this);
                let val = field.val().trim();

                if (field.hasClass('item-quantity')) {
                    if (val === "" || parseInt(val) <= 0) {
                        field.addClass("is-invalid");
                        isValid = false;
                        if (!firstInvalidField) firstInvalidField = field;
                    } else {
                        field.removeClass("is-invalid");
                    }
                } else if (field.hasClass('item-unit-price')) {
                     if (val === "" || parseCurrency(val) < 0) { // Allow 0 price, but not negative.
                        field.addClass("is-invalid");
                        isValid = false;
                        if (!firstInvalidField) firstInvalidField = field;
                    } else {
                        field.removeClass("is-invalid");
                    }
                } else { // Description
                    if (val === "") {
                        field.addClass("is-invalid");
                        isValid = false;
                        if (!firstInvalidField) firstInvalidField = field;
                    } else {
                        field.removeClass("is-invalid");
                    }
                }
            });
        });

        if (!isValid) {
            event.preventDefault(); // Stop form submission
            if (firstInvalidField) {
                firstInvalidField.focus();
            }
            // Add a general message
            if (!$('.alert-danger:contains("Please correct the errors")').length && !$('#invoiceForm .is-invalid').length == 0) {
                 const generalError = '<div class="alert alert-danger mt-2 temp-validation-error" role="alert">Please correct the errors highlighted below.</div>';
                 $(generalError).insertBefore($("#invoiceForm .card").first());
            }
        } else {
            $('.temp-validation-error').remove(); // Clear temp error if all good now
        }
    });

    // Remove 'is-invalid' class on input
    $("#invoiceForm").on("input change", ".is-invalid", function() {
        $(this).removeClass("is-invalid");
    });


    // Update breadcrumbs for this page
    // This is a more specific breadcrumb update than the generic one in main.js
    var $breadcrumbContainer = $("#breadcrumb-container");
    if ($breadcrumbContainer.length) {
        var homeUrl = '<?php echo BASE_URL; ?>';
        $breadcrumbContainer.empty()
            .append('<li class="breadcrumb-item"><a href="' + homeUrl + '">Home</a></li>')
            .append('<li class="breadcrumb-item"><a href="' + homeUrl + 'view_invoices/">Invoices</a></li>')
            .append('<li class="breadcrumb-item active" aria-current="page"><?php echo $page_title; ?></li>');
        $("#breadcrumb-nav-container").show();
    }

});
</script>

<?php
// Include the footer template
require_once __DIR__ . '/../templates/footer.php';
?>
