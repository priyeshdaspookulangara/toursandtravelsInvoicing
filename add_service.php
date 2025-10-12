<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Add New Service";
$errors = [];
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_name = isset($_POST['service_name']) ? trim($_POST['service_name']) : '';
    $service_description = isset($_POST['service_description']) ? trim($_POST['service_description']) : '';
    $service_price = isset($_POST['service_price']) ? trim($_POST['service_price']) : '';

    // Validation
    if (empty($service_name)) {
        $errors[] = "Service name is required.";
    }
    if ($service_price === '') { // Allow 0, but not empty string before sanitization
        $errors[] = "Service price is required.";
    } elseif (!is_numeric($service_price) || $service_price < 0) {
        $errors[] = "Service price must be a non-negative number.";
    }

    if (empty($errors)) {
        $conn = db_connect();
        if ($conn) {
            // Sanitize inputs
            $s_service_name = sanitize_string($service_name);
            $s_service_description = sanitize_string($service_description);
            $s_service_price = sanitize_decimal($service_price);
            if ($s_service_price === false && $service_price !== '') { // Check if sanitization failed
                 $errors[] = "Invalid price format after sanitization.";
            }

            if (empty($errors)) { // Re-check errors after sanitization
                $sql = "INSERT INTO services (name, description, price) VALUES (
                            '$s_service_name',
                            '$s_service_description',
                            '$s_service_price'
                        )";

                if (db_query($sql)) {
                    $_SESSION['message'] = "Service '" . htmlspecialchars($service_name) . "' added successfully!";
                    header("Location: list_services.php");
                    exit;
                } else {
                    $errors[] = "Failed to add service. Database error. " . mysqli_error($conn);
                }
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
        <form action="add_service.php" method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="service_name" class="form-label">Service Name:</label>
                        <input type="text" class="form-control" name="service_name" id="service_name" value="<?php echo isset($_POST['service_name']) ? htmlspecialchars($_POST['service_name']) : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="service_price" class="form-label">Price (e.g., 1500.00):</label>
                        <input type="text" class="form-control" name="service_price" id="service_price" value="<?php echo isset($_POST['service_price']) ? htmlspecialchars($_POST['service_price']) : ''; ?>" required pattern="^\d+(\.\d{1,2})?$" title="Enter a valid price, e.g., 1500 or 1500.50">
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label for="service_description" class="form-label">Description:</label>
                <textarea class="form-control" name="service_description" id="service_description" rows="3"><?php echo isset($_POST['service_description']) ? htmlspecialchars($_POST['service_description']) : ''; ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Add Service</button>
            <a href="list_services.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php
include 'templates/header.php';
?>