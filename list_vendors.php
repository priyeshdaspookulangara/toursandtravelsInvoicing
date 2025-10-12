<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Manage Vendors";

// Fetch all vendors
$sql = "SELECT * FROM vendors ORDER BY name ASC";
$vendors_result = db_query($sql);
$vendors = [];
if ($vendors_result) {
    $vendors = db_fetch_all($vendors_result);
} else {
    $page_error = "Error fetching vendors: " . mysqli_error(db_connect());
}

include 'templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_vendor.php" class="btn btn-sm btn-success">
            <i class="fas fa-plus"></i> Add New Vendor
        </a>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<?php if (isset($page_error)): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($page_error); ?>
    </div>
<?php endif; ?>

<?php if (!empty($vendors)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vendors as $vendor): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($vendor['name']); ?></td>
                        <td><?php echo htmlspecialchars($vendor['email']); ?></td>
                        <td><?php echo htmlspecialchars($vendor['phone']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($vendor['address'])); ?></td>
                        <td class="actions">
                            <span class="badge bg-secondary">N/A</span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php elseif (empty($page_error)): ?>
    <p>No vendors found. <a href="add_vendor.php">Add one now!</a></p>
<?php endif; ?>

<?php
include 'templates/footer.php';
?>