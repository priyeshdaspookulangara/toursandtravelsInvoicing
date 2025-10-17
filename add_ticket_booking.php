<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'controllers/accounting_controller.php';

$page_title = "Add Air Ticket Booking";
$errors = [];
$clients = db_fetch_all(db_query("SELECT id, name FROM clients ORDER BY name ASC"));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = db_connect();

    // Sanitize all input data
    $invoice_no = sanitize_string($_POST['invoice_no']);
    $issue_date = sanitize_string($_POST['issue_date']);
    $pnr = sanitize_string($_POST['pnr']);
    $ticket_no = sanitize_string($_POST['ticket_no']);
    $client_id = sanitize_int($_POST['client_id']);
    $client_name = sanitize_string($_POST['client_name']);
    $client_email = sanitize_email($_POST['client_email']);
    $client_address = sanitize_string($_POST['client_address']);
    $currency = sanitize_string($_POST['currency']);
    $base_fare = sanitize_decimal($_POST['base_fare']);
    $taxes = sanitize_decimal($_POST['taxes']);
    $agency_fee = sanitize_decimal($_POST['agency_fee']);
    $total_amount = sanitize_decimal($_POST['total_amount']);
    $payment_status = sanitize_string($_POST['payment_status']);
    $payment_method = sanitize_string($_POST['payment_method']);
    $notes = sanitize_string($_POST['notes']);

    // Passenger details
    $passenger_names = $_POST['passenger_name'];
    $airlines = $_POST['airline'];
    $flight_nos = $_POST['flight_no'];
    $routes = $_POST['route'];

    mysqli_begin_transaction($conn);
    try {
        if (empty($client_id)) {
            $sql_client = "INSERT INTO clients (name, email, address) VALUES (?, ?, ?)";
            $stmt_client = mysqli_prepare($conn, $sql_client);
            mysqli_stmt_bind_param($stmt_client, "sss", $client_name, $client_email, $client_address);
            mysqli_stmt_execute($stmt_client);
            $client_id = mysqli_insert_id($conn);
        }

        $sql_booking = "INSERT INTO ticket_bookings (invoice_no, issue_date, pnr, ticket_no, client_id, client_name, client_email, client_address, currency, base_fare, taxes, agency_fee, total_amount, payment_status, payment_method, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_booking = mysqli_prepare($conn, $sql_booking);
        mysqli_stmt_bind_param($stmt_booking, "ssssisssddddssss", $invoice_no, $issue_date, $pnr, $ticket_no, $client_id, $client_name, $client_email, $client_address, $currency, $base_fare, $taxes, $agency_fee, $total_amount, $payment_status, $payment_method, $notes);
        mysqli_stmt_execute($stmt_booking);
        $booking_id = mysqli_insert_id($conn);

        for ($i = 0; $i < count($passenger_names); $i++) {
            $sql_passenger = "INSERT INTO ticket_passengers (booking_id, passenger_name, airline, flight_no, route) VALUES (?, ?, ?, ?, ?)";
            $stmt_passenger = mysqli_prepare($conn, $sql_passenger);
            mysqli_stmt_bind_param($stmt_passenger, "issss", $booking_id, $passenger_names[$i], $airlines[$i], $flight_nos[$i], $routes[$i]);
            mysqli_stmt_execute($stmt_passenger);
        }

        // Accounting entries
        $journal_entries = [
            ['account_id' => ACCOUNT_ID_ACCOUNTS_RECEIVABLE, 'debit' => $total_amount, 'credit' => 0],
            ['account_id' => ACCOUNT_ID_SALES_REVENUE_TICKETS, 'debit' => 0, 'credit' => $base_fare + $agency_fee],
            ['account_id' => ACCOUNT_ID_VAT_PAYABLE, 'debit' => 0, 'credit' => $taxes] // Assuming taxes are VAT
        ];
        create_journal_transaction($issue_date, "Ticket booking #$booking_id", $journal_entries, 'ticket_booking', $booking_id);

        mysqli_commit($conn);
        $_SESSION['message'] = "Ticket booking created successfully.";
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $errors[] = "Failed to create ticket booking: " . $e->getMessage();
    }
}

include 'templates/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
</div>

<form action="add_ticket_booking.php" method="POST">
    <div class="card mb-4">
        <div class="card-header">Invoice & Booking References</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="invoice_no" class="form-label">Invoice Number</label>
                    <input type="text" id="invoice_no" name="invoice_no" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="issue_date" class="form-label">Date of Issue</label>
                    <input type="date" id="issue_date" name="issue_date" class="form-control" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="pnr" class="form-label">PNR (Booking Reference)</label>
                    <input type="text" id="pnr" name="pnr" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="ticket_no" class="form-label">Main Ticket Number</label>
                    <input type="text" id="ticket_no" name="ticket_no" class="form-control" required>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Customer/Client Details</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="client_id" class="form-label">Select Existing Client</label>
                    <select id="client_id" name="client_id" class="form-select">
                        <option value="">-- New Client --</option>
                        <?php foreach($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="client_name" class="form-label">Client Name (or Company)</label>
                    <input type="text" id="client_name" name="client_name" class="form-control" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="client_email" class="form-label">Client Email</label>
                    <input type="email" id="client_email" name="client_email" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="client_address" class="form-label">Client Address</label>
                    <textarea id="client_address" name="client_address" class="form-control" rows="1"></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            Passenger & Flight Details
            <button type="button" id="add-passenger" class="btn btn-sm btn-success">+ Add Passenger</button>
        </div>
        <div class="card-body" id="passenger-container">
            <!-- Passenger rows will be inserted here by JavaScript -->
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Pricing & Fees</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="base_fare" class="form-label">Base Airfare</label>
                    <input type="number" id="base_fare" name="base_fare" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="taxes" class="form-label">Total Taxes & Surcharges</label>
                    <input type="number" id="taxes" name="taxes" class="form-control" step="0.01" min="0">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="agency_fee" class="form-label">Agency Fee/Service Charge</label>
                    <input type="number" id="agency_fee" name="agency_fee" class="form-control" step="0.01" min="0">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="total_amount" class="form-label">Total Invoice Amount</label>
                    <input type="text" id="total_amount" name="total_amount" class="form-control" readonly>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Payment Status & Notes</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="payment_status" class="form-label">Payment Status</label>
                    <select id="payment_status" name="payment_status" class="form-select">
                        <option value="Pending">Payment Pending</option>
                        <option value="Paid">Paid in Full</option>
                        <option value="Partial">Partial Payment</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="payment_method" class="form-label">Payment Method</label>
                    <input type="text" id="payment_method" name="payment_method" class="form-control">
                </div>
            </div>
            <div class="mb-3">
                <label for="notes" class="form-label">Important Notes / Terms</label>
                <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
            </div>
        </div>
    </div>

    <div class="text-center">
        <button type="submit" class="btn btn-primary btn-lg">Generate & Submit Invoice</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passengerContainer = document.getElementById('passenger-container');
    const addPassengerBtn = document.getElementById('add-passenger');
    let passengerIndex = 0;

    function addPassengerRow() {
        const row = document.createElement('div');
        row.className = 'border p-3 mb-3 repeatable-row';
        row.innerHTML = `
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="passenger_name_${passengerIndex}" class="form-label">Passenger Name</label>
                    <input type="text" id="passenger_name_${passengerIndex}" name="passenger_name[]" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="airline_${passengerIndex}" class="form-label">Airline</label>
                    <input type="text" id="airline_${passengerIndex}" name="airline[]" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="flight_no_${passengerIndex}" class="form-label">Flight No.</label>
                    <input type="text" id="flight_no_${passengerIndex}" name="flight_no[]" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="route_${passengerIndex}" class="form-label">Route (e.g., JFK-LHR)</label>
                    <input type="text" id="route_${passengerIndex}" name="route[]" class="form-control">
                </div>
            </div>
        `;
        passengerContainer.appendChild(row);
        passengerIndex++;
    }

    addPassengerBtn.addEventListener('click', addPassengerRow);
    addPassengerRow(); // Add the first row initially

    // Auto-fill client details on dropdown change
    document.getElementById('client_id').addEventListener('change', function() {
        const selectedId = this.value;
        if (selectedId) {
            // In a real app, you would fetch this via AJAX
            // For now, we'll assume the data is available if needed
        } else {
            document.getElementById('client_name').value = '';
            document.getElementById('client_email').value = '';
            document.getElementById('client_address').value = '';
        }
    });

    // Calculate total amount
    const pricingFields = ['base_fare', 'taxes', 'agency_fee'];
    pricingFields.forEach(fieldId => {
        document.getElementById(fieldId).addEventListener('input', function() {
            const baseFare = parseFloat(document.getElementById('base_fare').value) || 0;
            const taxes = parseFloat(document.getElementById('taxes').value) || 0;
            const agencyFee = parseFloat(document.getElementById('agency_fee').value) || 0;
            const total = baseFare + taxes + agencyFee;
            document.getElementById('total_amount').value = total.toFixed(2);
        });
    });
});
</script>

<?php include 'templates/footer.php'; ?>