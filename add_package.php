<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Add New Package/Service";
$errors = [];
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $package_name = isset($_POST['package_name']) ? trim($_POST['package_name']) : '';
    $package_description = isset($_POST['package_description']) ? trim($_POST['package_description']) : '';
    $package_price = isset($_POST['package_price']) ? trim($_POST['package_price']) : '';

    // Validation
    if (empty($package_name)) {
        $errors[] = "Package name is required.";
    }
    if ($package_price === '') { // Allow 0, but not empty string before sanitization
        $errors[] = "Package price is required.";
    } elseif (!is_numeric($package_price) || $package_price < 0) {
        $errors[] = "Package price must be a non-negative number.";
    }

    if (empty($errors)) {
        $conn = db_connect();
        if ($conn) {
            // Sanitize inputs
            $s_package_name = sanitize_string($package_name);
            $s_package_description = sanitize_string($package_description);
            // Sanitize decimal specifically for price
            $s_package_price = sanitize_decimal($package_price);
            if ($s_package_price === false && $package_price !== '') { // Check if sanitization failed
                 $errors[] = "Invalid price format after sanitization.";
            }


            if (empty($errors)) { // Re-check errors after sanitization
                // Check if package with the same name already exists (optional, based on business rules)
                // For this example, we'll allow duplicate names but one might want to prevent it
                // $sql_check_name = "SELECT id FROM packages WHERE name = '$s_package_name'";
                // $name_check_result = db_query($sql_check_name);
                // if ($name_check_result && mysqli_num_rows($name_check_result) > 0) {
                //    $errors[] = "A package with this name already exists.";
                // } else {

                $sql = "INSERT INTO packages (name, description, price) VALUES (
                            '$s_package_name',
                            '$s_package_description',
                            '$s_package_price'
                        )";

                if (db_query($sql)) {
                    $_SESSION['message'] = "Package/Service '" . htmlspecialchars($package_name) . "' added successfully!";
                    header("Location: list_packages.php");
                    exit;
                } else {
                    $errors[] = "Failed to add package. Database error. " . mysqli_error($conn);
                }
                // }
            }
            // db_close();
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

<form action="add_package.php" method="post">
    <div class="form-group">
        <label for="package_name">Package/Service Name:</label>
        <input type="text" name="package_name" id="package_name" value="<?php echo isset($_POST['package_name']) ? htmlspecialchars($_POST['package_name']) : ''; ?>" required>
    </div>
    <div class="form-group">
        <label for="package_price">Price (e.g., 1500.00):</label>
        <input type="text" name="package_price" id="package_price" value="<?php echo isset($_POST['package_price']) ? htmlspecialchars($_POST['package_price']) : ''; ?>" required pattern="^\d+(\.\d{1,2})?$" title="Enter a valid price, e.g., 1500 or 1500.50">
    </div>
    <div class="form-group">
        <label for="package_description">Description:</label>
        <textarea name="package_description" id="package_description"><?php echo isset($_POST['package_description']) ? htmlspecialchars($_POST['package_description']) : ''; ?></textarea>
    </div>

    <button type="submit" class="btn">Add Package/Service</button>
    <a href="list_packages.php" class="btn" style="background-color: #777;">Cancel</a>
</form>

<?php
include 'templates/footer.php';
?>
