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

<p><a href="add_client.php" class="btn">Add New Client</a></p>

<?php if (!empty($clients)): ?>
    <table>
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
                        <!-- Basic edit/delete links - functionality to be built -->
                        <!-- <a href="edit_client.php?id=<?php echo $client['id']; ?>" class="btn btn-sm" style="background-color: #f0ad4e; color:white;">Edit</a> -->
                        <!-- <a href="delete_client.php?id=<?php echo $client['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this client? This might affect existing invoices.');">Delete</a> -->
                        <span>Edit/Delete (To be implemented)</span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php elseif (empty($page_error)): ?>
    <p>No clients found. <a href="add_client.php">Add one now!</a></p>
<?php endif; ?>

<?php
include 'templates/footer.php';
?>
