<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Manage Services";

// Fetch all services
$sql = "SELECT * FROM services ORDER BY name ASC";
$services_result = db_query($sql);
$services = [];
if ($services_result) {
    $services = db_fetch_all($services_result);
} else {
    $page_error = "Error fetching services: " . mysqli_error(db_connect());
}

// Placeholder for delete/edit functionality

include 'templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_service.php" class="btn btn-sm btn-success">
            <i class="fas fa-plus"></i> Add New Service
        </a>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($page_error)): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($page_error); ?>
    </div>
<?php endif; ?>

<?php if (!empty($services)): ?>
    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th class="text-end">Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $service): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($service['name']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($service['description'])); ?></td>
                        <td class="text-end"><?php echo number_format($service['price'], 2); ?></td>
                        <td class="actions">
                           <span class="badge bg-secondary">N/A</span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php elseif (empty($page_error)): ?>
    <p>No services found. <a href="add_service.php">Add one now!</a></p>
<?php endif; ?>

<?php
include 'templates/footer.php';
?>