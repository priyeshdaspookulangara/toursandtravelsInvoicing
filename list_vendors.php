<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Manage Vendors";
$vendors = db_fetch_all(db_query("SELECT * FROM vendors ORDER BY name ASC"));

include 'templates/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <a href="add_vendor.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Vendor</a>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        All Vendors
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
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
                    <?php if (!empty($vendors)): ?>
                        <?php foreach ($vendors as $vendor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($vendor['name']); ?></td>
                                <td><?php echo htmlspecialchars($vendor['email']); ?></td>
                                <td><?php echo htmlspecialchars($vendor['phone']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($vendor['address'])); ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-outline-secondary">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No vendors found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>