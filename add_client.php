<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Add New Client";
$errors = [];
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $client_name = isset($_POST['client_name']) ? trim($_POST['client_name']) : '';
    $client_address = isset($_POST['client_address']) ? trim($_POST['client_address']) : '';
    $client_email = isset($_POST['client_email']) ? trim($_POST['client_email']) : '';
    $client_phone = isset($_POST['client_phone']) ? trim($_POST['client_phone']) : '';

    // Validation
    if (empty($client_name)) {
        $errors[] = "Client name is required.";
    }
    if (!empty($client_email) && !filter_var($client_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    // Add more validation as needed (e.g., phone format)

    if (empty($errors)) {
        $conn = db_connect();
        if ($conn) {
            // Sanitize inputs
            $s_client_name = sanitize_string($client_name);
            $s_client_address = sanitize_string($client_address);
            $s_client_email = sanitize_email($client_email); // Use specific email sanitizer
            if ($client_email && $s_client_email === false) { // Check if sanitization failed for a non-empty email
                 $errors[] = "Invalid email provided after sanitization.";
            }
            $s_client_phone = sanitize_string($client_phone); // Basic string sanitization for phone

            if (empty($errors)) { // Re-check errors after sanitization attempts
                $sql_check_email = "SELECT id FROM clients WHERE email = '$s_client_email' AND '$s_client_email' <> ''";
                $email_check_result = db_query($sql_check_email);

                if ($email_check_result && mysqli_num_rows($email_check_result) > 0) {
                    $errors[] = "A client with this email already exists.";
                } else {
                    $sql = "INSERT INTO clients (name, address, email, phone) VALUES (
                                '$s_client_name',
                                '$s_client_address',
                                '$s_client_email',
                                '$s_client_phone'
                            )";

                    if (db_query($sql)) {
                        $_SESSION['message'] = "Client '" . htmlspecialchars($client_name) . "' added successfully!";
                        header("Location: list_clients.php");
                        exit;
                    } else {
                        $errors[] = "Failed to add client. Database error. " . mysqli_error($conn);
                    }
                }
            }
            // db_close(); // Handled by shutdown or end of script
        } else {
            $errors[] = "Database connection failed.";
        }
    }
}

include 'templates/header.php';
?>

<h2><?php echo $page_title; ?></h2>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="add_client.php" method="post">
    <div class="form-group">
        <label for="client_name">Client Name:</label>
        <input type="text" name="client_name" id="client_name" value="<?php echo isset($_POST['client_name']) ? htmlspecialchars($_POST['client_name']) : ''; ?>" required>
    </div>
    <div class="form-group">
        <label for="client_email">Email:</label>
        <input type="email" name="client_email" id="client_email" value="<?php echo isset($_POST['client_email']) ? htmlspecialchars($_POST['client_email']) : ''; ?>">
    </div>
    <div class="form-group">
        <label for="client_phone">Phone:</label>
        <input type="text" name="client_phone" id="client_phone" value="<?php echo isset($_POST['client_phone']) ? htmlspecialchars($_POST['client_phone']) : ''; ?>">
    </div>
    <div class="form-group">
        <label for="client_address">Address:</label>
        <textarea name="client_address" id="client_address"><?php echo isset($_POST['client_address']) ? htmlspecialchars($_POST['client_address']) : ''; ?></textarea>
    </div>

    <button type="submit" class="btn">Add Client</button>
    <a href="list_clients.php" class="btn" style="background-color: #777;">Cancel</a>
</form>

<?php
include 'templates/footer.php';
?>
