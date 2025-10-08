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

<h2><?php echo $page_title; ?></h2>

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

<p><a href="add_service.php" class="btn">Add New Service</a></p>

<?php if (!empty($services)): ?>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th class="text-right">Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($services as $service): ?>
                <tr>
                    <td><?php echo htmlspecialchars($service['name']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($service['description'])); ?></td>
                    <td class="text-right"><?php echo number_format($service['price'], 2); ?></td>
                    <td class="actions">
                        <!-- Basic edit/delete links - functionality to be built -->
                        <!-- <a href="edit_service.php?id=<?php echo $service['id']; ?>" class="btn btn-sm" style="background-color: #f0ad4e; color:white;">Edit</a> -->
                        <!-- <a href="delete_service.php?id=<?php echo $service['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this service? This might affect existing invoice items if not handled carefully.');">Delete</a> -->
                        <span>Edit/Delete (To be implemented)</span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php elseif (empty($page_error)): ?>
    <p>No services found. <a href="add_service.php">Add one now!</a></p>
<?php endif; ?>

<?php
include 'templates/footer.php';
?>