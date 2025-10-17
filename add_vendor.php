<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Add New Vendor";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vendor_name = trim($_POST['vendor_name']);
    $vendor_address = trim($_POST['vendor_address']);
    $vendor_email = trim($_POST['vendor_email']);
    $vendor_phone = trim($_POST['vendor_phone']);

    if (empty($vendor_name)) {
        $errors[] = "Vendor name is required.";
    }

    if (empty($errors)) {
        $conn = db_connect();
        $sql = "INSERT INTO vendors (name, address, email, phone) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $vendor_name, $vendor_address, $vendor_email, $vendor_phone);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "Vendor added successfully.";
            header("Location: list_vendors.php");
            exit;
        } else {
            $errors[] = "Failed to add vendor.";
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

<div class="card">
    <div class="card-header">
        Vendor Information
    </div>
    <div class="card-body">
        <form action="add_vendor.php" method="post">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="vendor_name" class="form-label">Vendor Name</label>
                    <input type="text" class="form-control" name="vendor_name" id="vendor_name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="vendor_email" class="form-label">Email</label>
                    <input type="email" class="form-control" name="vendor_email" id="vendor_email">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="vendor_phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" name="vendor_phone" id="vendor_phone">
                </div>
            </div>
            <div class="mb-3">
                <label for="vendor_address" class="form-label">Address</label>
                <textarea class="form-control" name="vendor_address" id="vendor_address" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Add Vendor</button>
            <a href="list_vendors.php" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>