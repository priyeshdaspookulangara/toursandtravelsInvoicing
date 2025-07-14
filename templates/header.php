<?php
// Ensure config.php is loaded for BASE_URL and ASSETS_PATH
// Corrected path to config.php assuming header.php is in templates/ and config.php is in root
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
} else {
    // Define fallbacks if config.php is not available
    // This is not ideal, config.php should always be found.
    error_log("CRITICAL: config.php not found from templates/header.php. Using fallback BASE_URL and ASSETS_PATH.");
    if (!defined('BASE_URL')) define('BASE_URL', '/'); // Assuming app is at the root
    if (!defined('ASSETS_PATH')) define('ASSETS_PATH', 'assets/'); // Assuming 'assets' folder is at the root
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoicing Application</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Font Awesome for icons (optional, but good for sidebar toggle and menu icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo rtrim(ASSETS_PATH, '/'); ?>/css/style.css?v=<?php echo time(); // Cache busting ?>">
</head>
<body>

<div class="d-flex" id="wrapper">

    <!-- Sidebar -->
    <aside class="border-end" id="sidebar-wrapper">
        <div class="sidebar-heading border-bottom">
            <a href="<?php echo BASE_URL; ?>" class="text-decoration-none">
                <i class="fas fa-file-invoice-dollar me-2"></i>InvoiceApp
            </a>
        </div>
        <div class="list-group list-group-flush accordion" id="sidebarAccordion">

            <a href="<?php echo BASE_URL; ?>" class="list-group-item list-group-item-action list-group-item-light p-3">
                <i class="fas fa-tachometer-alt fa-fw me-2"></i>Dashboard
            </a>

            <!-- Group 1: Invoicing -->
            <div class="list-group-item list-group-item-action p-0">
                <button class="list-group-item list-group-item-action list-group-item-light p-3 accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInvoicing" aria-expanded="false" aria-controls="collapseInvoicing">
                    <span><i class="fas fa-file-invoice fa-fw me-2"></i>Invoicing</span>
                </button>
                <div id="collapseInvoicing" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
                    <ul class="list-group list-group-flush nested-group">
                        <li class="list-group-item list-group-item-action py-2 nested-item">
                            <a href="<?php echo BASE_URL; ?>new_invoice/" class="text-decoration-none d-block ps-3"><i class="fas fa-plus fa-fw me-2"></i>New Invoice</a>
                        </li>
                        <li class="list-group-item list-group-item-action py-2 nested-item">
                            <a href="<?php echo BASE_URL; ?>view_invoices/" class="text-decoration-none d-block ps-3"><i class="fas fa-list fa-fw me-2"></i>View Invoices</a>
                        </li>
                        <!-- Add more invoice related links here -->
                    </ul>
                </div>
            </div>

            <!-- Group 2: Management (Example) -->
            <div class="list-group-item list-group-item-action p-0">
                <button class="list-group-item list-group-item-action list-group-item-light p-3 accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseManagement" aria-expanded="false" aria-controls="collapseManagement">
                    <span><i class="fas fa-users-cog fa-fw me-2"></i>Management</span>
                </button>
                <div id="collapseManagement" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
                    <ul class="list-group list-group-flush nested-group">
                        <li class="list-group-item list-group-item-action py-2 nested-item">
                            <a href="#" class="text-decoration-none d-block ps-3">Clients (Placeholder)</a>
                        </li>
                        <li class="list-group-item list-group-item-action py-2 nested-item">
                            <a href="#" class="text-decoration-none d-block ps-3">Products (Placeholder)</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Group 3: Settings (Example) -->
            <div class="list-group-item list-group-item-action p-0">
                 <button class="list-group-item list-group-item-action list-group-item-light p-3 accordion-button collapsed d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSettings" aria-expanded="false" aria-controls="collapseSettings">
                    <span><i class="fas fa-cogs fa-fw me-2"></i>Settings</span>
                </button>
                <div id="collapseSettings" class="accordion-collapse collapse" data-bs-parent="#sidebarAccordion">
                    <ul class="list-group list-group-flush nested-group">
                        <li class="list-group-item list-group-item-action py-2 nested-item">
                            <a href="#" class="text-decoration-none d-block ps-3">Profile (Placeholder)</a>
                        </li>
                        <li class="list-group-item list-group-item-action py-2 nested-item">
                            <a href="#" class="text-decoration-none d-block ps-3">Application (Placeholder)</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </aside>
    <!-- /#sidebar-wrapper -->

    <!-- Page Content Wrapper -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
            <div class="container-fluid">
                <button class="btn btn-sm" id="sidebarToggle"><i class="fas fa-bars fa-lg"></i></button>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                        <li class="nav-item active"><a class="nav-link" href="<?php echo BASE_URL; ?>">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="#!">Link</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">User</a>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="#!">Profile</a>
                                <a class="dropdown-item" href="#!">Settings</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#!">Logout</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid px-4">
            <!-- Breadcrumb Navigation Placeholder -->
            <nav aria-label="breadcrumb" class="mt-3 bg-light rounded p-2" id="breadcrumb-nav-container" style="display: none;">
                <ol class="breadcrumb mb-0" id="breadcrumb-container">
                    <!-- Dynamic Breadcrumb Items: PHP/JS should populate these <li> items -->
                    <!-- Example:
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Current Page</li>
                    -->
                </ol>
            </nav>

            <!-- Main content starts here -->
            <!-- Ensure this opening div is closed in footer.php -->
            <main class="pt-3">
                <!-- Page specific content will go here -->
            </main> <!-- This main tag is closed here for structure, content will be injected -->
        </div> <!-- /.container-fluid -->
    </div> <!-- /#page-content-wrapper -->
</div> <!-- /#wrapper -->

<!-- Bootstrap 5 JS Bundle with Popper CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- jQuery CDN -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<!-- Custom JS -->
<script src="<?php echo rtrim(ASSETS_PATH, '/'); ?>/js/main.js?v=<?php echo time(); // Cache busting ?>"></script>

</body>
</html>
