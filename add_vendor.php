<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Add New Vendor";
$errors = [];
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vendor_name = isset($_POST['vendor_name']) ? trim($_POST['vendor_name']) : '';
    $vendor_address = isset($_POST['vendor_address']) ? trim($_POST['vendor_address']) : '';
    $vendor_email = isset($_POST['vendor_email']) ? trim($_POST['vendor_email']) : '';
    $vendor_phone = isset($_POST['vendor_phone']) ? trim($_POST['vendor_phone']) : '';

    // Validation
    if (empty($vendor_name)) {
        $errors[] = "Vendor name is required.";
    }
    if (!empty($vendor_email) && !filter_var($vendor_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($errors)) {
        $conn = db_connect();
        if ($conn) {
            // Sanitize inputs
            $s_vendor_name = trim($vendor_name);
            $s_vendor_address = trim($vendor_address);
            $s_vendor_email = trim($vendor_email);
            $s_vendor_phone = trim($vendor_phone);

            // Check if email already exists
            if (!empty($s_vendor_email)) {
                $sql_check_email = "SELECT id FROM vendors WHERE email = ?";
                $stmt_check = mysqli_prepare($conn, $sql_check_email);
                mysqli_stmt_bind_param($stmt_check, "s", $s_vendor_email);
                mysqli_stmt_execute($stmt_check);
                $result_check = mysqli_stmt_get_result($stmt_check);
                if (mysqli_num_rows($result_check) > 0) {
                    $errors[] = "A vendor with this email already exists.";
                }
                mysqli_stmt_close($stmt_check);
            }

            if (empty($errors)) {
                $sql = "INSERT INTO vendors (name, address, email, phone) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssss", $s_vendor_name, $s_vendor_address, $s_vendor_email, $s_vendor_phone);

                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['message'] = "Vendor '" . htmlspecialchars($s_vendor_name) . "' added successfully!";
                    header("Location: list_vendors.php");
                    exit;
                } else {
                    $errors[] = "Failed to add vendor. Database error: " . mysqli_stmt_error($stmt);
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $errors[] = "Database connection failed.";
        }
    }
}

include 'templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <h4 class="alert-heading">Please correct the following errors:</h4>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="add_vendor.php" method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="vendor_name" class="form-label">Vendor Name:</label>
                        <input type="text" class="form-control" name="vendor_name" id="vendor_name" value="<?php echo isset($_POST['vendor_name']) ? htmlspecialchars($_POST['vendor_name']) : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="vendor_email" class="form-label">Email:</label>
                        <input type="email" class="form-control" name="vendor_email" id="vendor_email" value="<?php echo isset($_POST['vendor_email']) ? htmlspecialchars($_POST['vendor_email']) : ''; ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="vendor_phone" class="form-label">Phone:</label>
                        <input type="text" class="form-control" name="vendor_phone" id="vendor_phone" value="<?php echo isset($_POST['vendor_phone']) ? htmlspecialchars($_POST['vendor_phone']) : ''; ?>">
                    </div>
                </div>
            </div>
             <div class="mb-3">
                <label for="vendor_address" class="form-label">Address:</label>
                <textarea class="form-control" name="vendor_address" id="vendor_address" rows="3"><?php echo isset($_POST['vendor_address']) ? htmlspecialchars($_POST['vendor_address']) : ''; ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Add Vendor</button>
            <a href="list_vendors.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php
include 'templates/footer.php';
?>