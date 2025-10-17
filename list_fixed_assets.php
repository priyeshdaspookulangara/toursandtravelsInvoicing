<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Fixed Asset Register";
$assets = db_fetch_all(db_query("SELECT * FROM fixed_assets ORDER BY purchase_date DESC"));

include 'templates/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <a href="add_fixed_asset.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Asset</a>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        All Fixed Assets
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Asset Name</th>
                        <th>Purchase Date</th>
                        <th>Cost</th>
                        <th>Useful Life (Yrs)</th>
                        <th>Depreciation Method</th>
                        <th>Accum. Depreciation</th>
                        <th>Book Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($assets)): ?>
                        <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($asset['asset_name']); ?></td>
                                <td><?php echo htmlspecialchars($asset['purchase_date']); ?></td>
                                <td><?php echo number_format($asset['cost'], 2); ?></td>
                                <td><?php echo htmlspecialchars($asset['useful_life']); ?></td>
                                <td><?php echo htmlspecialchars($asset['depreciation_method']); ?></td>
                                <td><?php echo number_format($asset['accumulated_depreciation'], 2); ?></td>
                                <td><?php echo number_format($asset['book_value'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No fixed assets found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>