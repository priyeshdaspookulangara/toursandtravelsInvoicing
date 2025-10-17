<?php
require_once 'auth.php';
require_once 'db.php';

$page_title = "Bank Reconciliation";
// ... (PHP logic remains the same)

include 'templates/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
</div>

<div class="card mb-4">
    <div class="card-header">
        Reconciliation Period
    </div>
    <div class="card-body">
        <form action="bank_reconciliation.php" method="get" class="row g-3 align-items-end">
            <!-- ... form fields from previous version ... -->
        </form>
    </div>
</div>

<?php if ($selected_account): ?>
<form action="bank_reconciliation.php?<?php echo http_build_query($_GET); ?>" method="post">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Unreconciled Transactions</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <!-- ... table from previous version ... -->
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Summary</div>
                <div class="card-body">
                    <!-- ... summary list from previous version ... -->
                </div>
                <div class="card-footer text-center">
                    <button type="submit" name="reconcile" class="btn btn-success w-100" <?php if (empty($transactions)) echo 'disabled'; ?>>Save Reconciliation</button>
                </div>
            </div>
        </div>
    </div>
</form>
<?php endif; ?>

<script>
// ... script from previous version ...
</script>

<?php include 'templates/footer.php'; ?>