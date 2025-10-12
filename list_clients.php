<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Manage Clients";

// Fetch all clients
$sql = "SELECT * FROM clients ORDER BY name ASC";
$clients_result = db_query($sql);
$clients = [];
if ($clients_result) {
    $clients = db_fetch_all($clients_result);
} else {
    $page_error = "Error fetching clients: " . mysqli_error(db_connect());
}

// Placeholder for delete functionality - will be a separate script or handled via POST request
// For now, links will go to a non-existent edit_client.php and delete_client.php

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

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_client.php" class="btn btn-sm btn-success">
            <i class="fas fa-plus"></i> Add New Client
        </a>
    </div>
</div>

<?php if (!empty($clients)): ?>
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
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($client['name']); ?></td>
                        <td><?php echo htmlspecialchars($client['email']); ?></td>
                        <td><?php echo htmlspecialchars($client['phone']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($client['address'])); ?></td>
                        <td class="actions">
                            <span class="badge bg-secondary">N/A</span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php elseif (empty($page_error)): ?>
    <p>No clients found. <a href="add_client.php">Add one now!</a></p>
<?php endif; ?>

<?php
include 'templates/footer.php';
?>
