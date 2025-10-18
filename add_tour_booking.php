<?php
require_once 'auth.php';
require_once 'db.php';
require_once 'controllers/accounting_controller.php';

$page_title = "Add Tour Booking";
$errors = [];
$clients = db_fetch_all(db_query("SELECT id, name FROM clients ORDER BY name ASC"));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = db_connect();

    // Sanitize all input data
    $invoice_no = sanitize_string($_POST['invoice_no']);
    $booking_date = sanitize_string($_POST['booking_date']);
    $client_id = sanitize_int($_POST['client_id']);
    $client_name = sanitize_string($_POST['client_name']);
    $package_name = sanitize_string($_POST['package_name']);
    $destination = sanitize_string($_POST['destination']);
    $travel_date_start = sanitize_string($_POST['travel_date_start']);
    $travel_date_end = sanitize_string($_POST['travel_date_end']);
    $number_of_travelers = sanitize_int($_POST['number_of_travelers']);
    $total_cost = sanitize_decimal($_POST['total_cost']);
    $payment_status = sanitize_string($_POST['payment_status']);
    $notes = sanitize_string($_POST['notes']);

    mysqli_begin_transaction($conn);
    try {
        if (empty($client_id)) {
            $sql_client = "INSERT INTO clients (name) VALUES (?)";
            $stmt_client = mysqli_prepare($conn, $sql_client);
            mysqli_stmt_bind_param($stmt_client, "s", $client_name);
            mysqli_stmt_execute($stmt_client);
            $client_id = mysqli_insert_id($conn);
        }

        $sql_booking = "INSERT INTO tour_bookings (invoice_no, booking_date, client_id, client_name, package_name, destination, travel_date_start, travel_date_end, number_of_travelers, total_cost, payment_status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_booking = mysqli_prepare($conn, $sql_booking);
        mysqli_stmt_bind_param($stmt_booking, "ssissssiisss", $invoice_no, $booking_date, $client_id, $client_name, $package_name, $destination, $travel_date_start, $travel_date_end, $number_of_travelers, $total_cost, $payment_status, $notes);
        mysqli_stmt_execute($stmt_booking);
        $booking_id = mysqli_insert_id($conn);

        // Accounting entries
        $journal_entries = [
            ['account_id' => ACCOUNT_ID_ACCOUNTS_RECEIVABLE, 'debit' => $total_cost, 'credit' => 0],
            ['account_id' => ACCOUNT_ID_SALES_REVENUE_TOURS, 'debit' => 0, 'credit' => $total_cost]
        ];
        create_journal_transaction($booking_date, "Tour booking #$booking_id", $journal_entries, 'tour_booking', $booking_id);

        mysqli_commit($conn);
        $_SESSION['message'] = "Tour booking created successfully.";
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $errors[] = "Failed to create tour booking: " . $e->getMessage();
    }
}

include 'templates/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
</div>

<form action="add_tour_booking.php" method="POST">
    <div class="card mb-4">
        <div class="card-header">Booking Details</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="invoice_no" class="form-label">Invoice Number</label>
                    <input type="text" id="invoice_no" name="invoice_no" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="booking_date" class="form-label">Date of Booking</label>
                    <input type="date" id="booking_date" name="booking_date" class="form-control" required>
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
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Tour Package Details</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="package_name" class="form-label">Package Name</label>
                    <input type="text" id="package_name" name="package_name" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="destination" class="form-label">Destination</label>
                    <input type="text" id="destination" name="destination" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="travel_date_start" class="form-label">Travel Start Date</label>
                    <input type="date" id="travel_date_start" name="travel_date_start" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="travel_date_end" class="form-label">Travel End Date</label>
                    <input type="date" id="travel_date_end" name="travel_date_end" class="form-control">
                </div>
            </div>
             <div class="mb-3">
                <label for="number_of_travelers" class="form-label">Number of Travelers</label>
                <input type="number" id="number_of_travelers" name="number_of_travelers" class="form-control" min="1">
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Pricing & Payment</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="total_cost" class="form-label">Total Cost</label>
                    <input type="number" id="total_cost" name="total_cost" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="payment_status" class="form-label">Payment Status</label>
                    <select id="payment_status" name="payment_status" class="form-select">
                        <option value="Pending">Payment Pending</option>
                        <option value="Paid">Paid in Full</option>
                        <option value="Partial">Partial Payment</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
            </div>
        </div>
    </div>

    <div class="text-center">
        <button type="submit" class="btn btn-primary btn-lg">Create Tour Booking</button>
    </div>
</form>

<?php include 'templates/footer.php'; ?>