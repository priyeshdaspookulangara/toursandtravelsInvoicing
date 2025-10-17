<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Add New Service";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_name = trim($_POST['service_name']);
    $service_description = trim($_POST['service_description']);
    $service_price = filter_var(trim($_POST['service_price']), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    if (empty($service_name)) $errors[] = "Service name is required.";
    if ($service_price === '' || $service_price < 0) $errors[] = "Service price must be a non-negative number.";

    if (empty($errors)) {
        $conn = db_connect();
        $sql = "INSERT INTO services (name, description, price) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssd", $service_name, $service_description, $service_price);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "Service added successfully.";
            header("Location: list_services.php");
            exit;
        } else {
            $errors[] = "Failed to add service.";
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
        Service Information
    </div>
    <div class="card-body">
        <form action="add_service.php" method="post">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="service_name" class="form-label">Service Name</label>
                    <input type="text" class="form-control" name="service_name" id="service_name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="service_price" class="form-label">Price</label>
                    <input type="number" step="0.01" class="form-control" name="service_price" id="service_price" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="service_description" class="form-label">Description</label>
                <textarea class="form-control" name="service_description" id="service_description" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Add Service</button>
            <a href="list_services.php" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>