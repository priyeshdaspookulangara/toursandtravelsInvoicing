<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Fixed Asset Register";

// Fetch all fixed assets
$sql = "SELECT * FROM fixed_assets ORDER BY purchase_date DESC";
$assets_result = db_query($sql);
$assets = db_fetch_all($assets_result);

include 'templates/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_fixed_asset.php" class="btn btn-sm btn-success">
            <i class="fas fa-plus"></i> Add New Asset
        </a>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped">
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

<?php include 'templates/footer.php'; ?>