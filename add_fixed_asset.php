<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Add New Fixed Asset";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $asset_name = trim($_POST['asset_name']);
    $purchase_date = trim($_POST['purchase_date']);
    $cost = filter_var(trim($_POST['cost']), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $useful_life = filter_var(trim($_POST['useful_life']), FILTER_SANITIZE_NUMBER_INT);

    if (empty($asset_name)) $errors[] = "Asset name is required.";
    if (empty($purchase_date)) $errors[] = "Purchase date is required.";
    if ($cost <= 0) $errors[] = "Cost must be a positive number.";
    if ($useful_life <= 0) $errors[] = "Useful life must be a positive integer.";

    if (empty($errors)) {
        $conn = db_connect();
        $sql = "INSERT INTO fixed_assets (asset_name, purchase_date, cost, useful_life, depreciation_method) VALUES (?, ?, ?, ?, 'Straight-Line')";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssdi", $asset_name, $purchase_date, $cost, $useful_life);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "Fixed asset added successfully!";
            header("Location: list_fixed_assets.php");
            exit;
        } else {
            $errors[] = "Failed to add fixed asset.";
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
        Asset Information
    </div>
    <div class="card-body">
        <form action="add_fixed_asset.php" method="post">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="asset_name" class="form-label">Asset Name</label>
                    <input type="text" class="form-control" name="asset_name" id="asset_name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="purchase_date" class="form-label">Purchase Date</label>
                    <input type="date" class="form-control" name="purchase_date" id="purchase_date" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="cost" class="form-label">Cost</label>
                    <input type="number" step="0.01" class="form-control" name="cost" id="cost" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="useful_life" class="form-label">Useful Life (Years)</label>
                    <input type="number" class="form-control" name="useful_life" id="useful_life" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Add Asset</button>
            <a href="list_fixed_assets.php" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>