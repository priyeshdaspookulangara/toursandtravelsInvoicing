<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "View Invoice";
$invoice_id = isset($_GET['id']) ? sanitize_int($_GET['id']) : null;

if (!$invoice_id) {
    $error_message = "No invoice ID specified or the ID is invalid. Please go back to the <a href='index.php'>invoice list</a>.";
} else {
    $conn = db_connect();

    // Fetch invoice details
    $sql_invoice = "SELECT i.*, c.name as client_name, c.address as client_address, c.email as client_email, c.phone as client_phone
                    FROM invoices i
                    JOIN clients c ON i.client_id = c.id
                    WHERE i.id = $invoice_id";
    $invoice_result = db_query($sql_invoice);

    if ($invoice_result && mysqli_num_rows($invoice_result) > 0) {
        $invoice = db_fetch_assoc($invoice_result);

        // Fetch invoice items, joining with services table now
        $sql_items = "SELECT ii.*, s.name as service_name
                      FROM invoice_items ii
                      LEFT JOIN services s ON ii.service_id = s.id
                      WHERE ii.invoice_id = $invoice_id
                      ORDER BY ii.id ASC";
        $items_result = db_query($sql_items);
        $invoice_items = db_fetch_all($items_result);

    } else {
        $error_message = "Invoice not found with ID: " . htmlspecialchars($invoice_id) . ". Go back to <a href='index.php'>invoice list</a>.";
    }
}

if (isset($invoice) && $invoice) {
    $page_title = "Invoice " . htmlspecialchars($invoice['invoice_number']);
}

include 'templates/header.php';
?>

<style>
    /* Specific styles for invoice view page for better print layout */
    .invoice-box {
        max-width: 800px;
        margin: auto;
        padding: 30px;
        border: 1px solid #eee;
        box-shadow: 0 0 10px rgba(0, 0, 0, .15);
        font-size: 16px;
        line-height: 24px;
        font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
        color: #555;
    }
    .invoice-box table {
        width: 100%;
        line-height: inherit;
        text-align: left;
        border-collapse: collapse;
    }
    .invoice-box table td { padding: 5px; vertical-align: top; }
    .invoice-box table tr.top table td { padding-bottom: 20px; }
    .invoice-box table tr.top table td.title { font-size: 45px; line-height: 45px; color: #333; }
    .invoice-box table tr.information table td { padding-bottom: 40px; }
    .invoice-box table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
    .invoice-box table tr.item td { border-bottom: 1px solid #eee; }
    .invoice-box table tr.item.last td { border-bottom: none; }
    .invoice-box table tr.total td:nth-child(2) { border-top: 2px solid #eee; font-weight: bold; }
    .text-right { text-align: right !important; }
    .company-details address { white-space: pre-line; }
    .notes-terms { margin-top: 30px; font-size: 0.9em; }
    .notes-terms h5 { margin-bottom: 5px; }
    .payment-form-container { max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; background-color: #f9f9f9; border-radius: 8px; }

    @media print {
        body, .invoice-box { border: 0; margin: 0; padding: 0; box-shadow: none; }
        .container { width: 100% !important; margin: 0 !important; padding: 0 !important; }
        nav, footer, .btn, .actions, .alert, .payment-form-container { display: none !important; }
        .content { padding: 0 !important; }
    }
</style>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-info">
        <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger">
        <?php echo $error_message; ?>
    </div>
<?php elseif (isset($invoice) && $invoice): ?>
    <div class="actions" style="margin-bottom: 20px; max-width: 800px; margin: auto; padding: 0 0 20px 0;">
        <a href="index.php" class="btn btn-info">Back to List</a>
        <a href="edit_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn" style="background-color:#f0ad4e; color:white;">Edit Invoice</a>
        <button onclick="window.print();" class="btn">Print Invoice</button>
    </div>

    <div class="invoice-box">
        <table>
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title">
                                <?php if (defined('COMPANY_LOGO_PATH') && !empty(COMPANY_LOGO_PATH) && file_exists(COMPANY_LOGO_PATH)): ?>
                                    <img src="<?php echo COMPANY_LOGO_PATH; ?>" style="max-width:150px; max-height:100px;" alt="<?php echo htmlspecialchars(COMPANY_NAME); ?>">
                                <?php else: ?>
                                    <?php echo htmlspecialchars(COMPANY_NAME); ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                Invoice #: <?php echo htmlspecialchars($invoice['invoice_number']); ?><br>
                                Created: <?php echo htmlspecialchars(date("F j, Y", strtotime($invoice['invoice_date']))); ?><br>
                                Due: <?php echo htmlspecialchars(date("F j, Y", strtotime($invoice['due_date']))); ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="company-details">
                                <strong><?php echo htmlspecialchars(COMPANY_NAME); ?></strong><br>
                                <address>
                                <?php echo nl2br(htmlspecialchars(COMPANY_ADDRESS)); ?><br>
                                Phone: <?php echo htmlspecialchars(COMPANY_PHONE); ?><br>
                                Email: <?php echo htmlspecialchars(COMPANY_EMAIL); ?><br>
                                <?php if(defined('COMPANY_WEBSITE') && COMPANY_WEBSITE): ?>
                                Website: <?php echo htmlspecialchars(COMPANY_WEBSITE); ?>
                                <?php endif; ?>
                                </address>
                            </td>
                            <td class="text-right">
                                <strong>Bill To:</strong><br>
                                <?php echo htmlspecialchars($invoice['client_name']); ?><br>
                                <?php if(!empty($invoice['client_address'])) echo nl2br(htmlspecialchars($invoice['client_address'])) . "<br>"; ?>
                                <?php if(!empty($invoice['client_email'])) echo htmlspecialchars($invoice['client_email']) . "<br>"; ?>
                                <?php if(!empty($invoice['client_phone'])) echo htmlspecialchars($invoice['client_phone']); ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="heading">
                <td>Item Description</td>
                <td class="text-right">Qty</td>
                <td class="text-right">Unit Price</td>
                <td class="text-right">Total</td>
            </tr>

            <?php if (!empty($invoice_items)): ?>
                <?php foreach ($invoice_items as $item): ?>
                <tr class="item">
                    <td>
                        <?php echo htmlspecialchars($item['item_description']); ?>
                        <?php if($item['service_id'] && $item['service_name'] && $item['item_description'] !== $item['service_name']): ?>
                            <small style="display:block; color:#777;">(Service: <?php echo htmlspecialchars($item['service_name']); ?>)</small>
                        <?php endif; ?>
                    </td>
                    <td class="text-right"><?php echo htmlspecialchars($item['quantity']); ?></td>
                    <td class="text-right"><?php echo number_format($item['unit_price'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($item['total_price'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            <tr class="total">
                <td colspan="3" class="text-right">Subtotal:</td>
                <td class="text-right"><?php echo number_format($invoice['sub_total'], 2); ?></td>
            </tr>
            <?php if (isset($invoice['tax_amount']) && (float)$invoice['tax_amount'] > 0): ?>
            <tr class="total">
                <td colspan="3" class="text-right">Tax (<?php echo number_format($invoice['tax_percentage'], 2); ?>%):</td>
                <td class="text-right"><?php echo number_format($invoice['tax_amount'], 2); ?></td>
            </tr>
            <?php endif; ?>
            <?php if (isset($invoice['discount_amount']) && (float)$invoice['discount_amount'] > 0): ?>
            <tr class="total">
                <td colspan="3" class="text-right">Discount:</td>
                <td class="text-right">-<?php echo number_format($invoice['discount_amount'], 2); ?></td>
            </tr>
            <?php endif; ?>
            <tr class="total">
                <td colspan="3" class="text-right"><strong>Grand Total:</strong></td>
                <td class="text-right"><strong><?php echo number_format($invoice['grand_total'], 2); ?></strong></td>
            </tr>
             <tr class="total">
                <td colspan="3" class="text-right">Amount Paid:</td>
                <td class="text-right"><?php echo number_format($invoice['amount_paid'], 2); ?></td>
            </tr>
             <tr class="total" style="font-weight:bold; background-color:#f9f9f9;">
                <td colspan="3" class="text-right">Balance Due:</td>
                <td class="text-right"><?php echo number_format($invoice['balance_due'], 2); ?></td>
            </tr>
        </table>

        <div class="notes-terms">
            <?php if (!empty($invoice['payment_terms'])): ?><h5>Payment Terms:</h5><p><?php echo nl2br(htmlspecialchars($invoice['payment_terms'])); ?></p><?php endif; ?>
            <?php if (!empty($invoice['notes'])): ?><h5>Notes:</h5><p><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p><?php endif; ?>
        </div>
         <div style="margin-top:30px; text-align:center; font-size:0.8em; color: #777;">
            Invoice Status: <?php echo htmlspecialchars($invoice['status']); ?>
        </div>
    </div>

    <?php if ($invoice['balance_due'] > 0.001): ?>
    <div class="payment-form-container">
        <h3>Record a Payment</h3>
        <form action="record_payment.php" method="post">
            <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
            <div class="form-group">
                <label for="payment_amount">Payment Amount:</label>
                <input type="text" name="payment_amount" id="payment_amount" value="<?php echo number_format($invoice['balance_due'], 2, '.', ''); ?>" required pattern="^\d+(\.\d{1,2})?$" title="Enter a valid payment amount.">
            </div>
            <div class="form-group">
                <label for="payment_date">Payment Date:</label>
                <input type="date" name="payment_date" id="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <button type="submit" class="btn">Record Payment</button>
        </form>
    </div>
    <?php endif; ?>

<?php else: ?>
    <?php if(!isset($error_message)): ?>
    <p>Could not load invoice data. Please ensure a valid invoice ID is provided.</p>
    <?php endif; ?>
<?php endif; ?>

<?php
include 'templates/footer.php';
?>