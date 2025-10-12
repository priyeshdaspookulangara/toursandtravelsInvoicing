<?php
require_once 'auth.php'; // Optional: Or a different auth mechanism for cron jobs
require_once 'db.php';
require_once 'controllers/accounting_controller.php';

echo "<h3>Running Monthly Depreciation...</h3>";

$conn = db_connect();
$today = new DateTime();
$current_month_start = new DateTime('first day of this month');

// Fetch all assets that are due for depreciation
$sql_assets = "SELECT * FROM fixed_assets WHERE last_depreciation_date IS NULL OR last_depreciation_date < ?";
$stmt_assets = mysqli_prepare($conn, $sql_assets);
$current_month_start_str = $current_month_start->format('Y-m-d');
mysqli_stmt_bind_param($stmt_assets, "s", $current_month_start_str);
mysqli_stmt_execute($stmt_assets);
$assets_result = mysqli_stmt_get_result($stmt_assets);
$assets = db_fetch_all($assets_result);


if (empty($assets)) {
    echo "<p>No fixed assets to depreciate at this time.</p>";
    exit;
}

mysqli_begin_transaction($conn);
try {
    foreach ($assets as $asset) {
        $purchase_date = new DateTime($asset['purchase_date']);
        $useful_life_months = $asset['useful_life'] * 12;
        $monthly_depreciation = $asset['cost'] / $useful_life_months;
        $end_of_life = (clone $purchase_date)->add(new DateInterval("P{$useful_life_months}M"));

        // Check if the asset is still within its useful life
        if ($today >= $purchase_date && $today < $end_of_life) {
            echo "<p>Depreciating asset: " . htmlspecialchars($asset['asset_name']) . "...</p>";

            // 1. Update accumulated depreciation and last depreciation date on the asset
            $new_accumulated = $asset['accumulated_depreciation'] + $monthly_depreciation;
            $today_str = $today->format('Y-m-d');
            $sql_update_asset = "UPDATE fixed_assets SET accumulated_depreciation = ?, last_depreciation_date = ? WHERE id = ?";
            $stmt_update = mysqli_prepare($conn, $sql_update_asset);
            mysqli_stmt_bind_param($stmt_update, "dsi", $new_accumulated, $today_str, $asset['id']);
            mysqli_stmt_execute($stmt_update);

            // 2. Post to General Ledger
            $entry_date = $today->format('Y-m-d');
            $description = "Monthly depreciation for " . htmlspecialchars($asset['asset_name']);
            $entries = [
                ['account_id' => ACCOUNT_ID_DEPRECIATION_EXPENSE, 'debit' => $monthly_depreciation, 'credit' => 0],
                ['account_id' => ACCOUNT_ID_ACCUMULATED_DEPRECIATION, 'debit' => 0, 'credit' => $monthly_depreciation],
            ];
            if (!create_journal_transaction($entry_date, $description, $entries, 'depreciation', $asset['id'])) {
                throw new Exception("Failed to post depreciation journal entries for asset ID " . $asset['id']);
            }

            echo "<p style='color: green;'>Successfully posted depreciation of " . number_format($monthly_depreciation, 2) . "</p>";
        }
    }
    mysqli_commit($conn);
    echo "<h4>Depreciation run completed successfully.</h4>";

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "<h4 style='color: red;'>An error occurred: " . $e->getMessage() . "</h4>";
}

db_close();
?>