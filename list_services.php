<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Manage Services";
$services = db_fetch_all(db_query("SELECT * FROM services ORDER BY name ASC"));

include 'templates/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <a href="add_service.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Service</a>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        All Services
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th class="text-end">Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($services)): ?>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($service['name']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($service['description'])); ?></td>
                                <td class="text-end"><?php echo number_format($service['price'], 2); ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-outline-secondary">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No services found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>