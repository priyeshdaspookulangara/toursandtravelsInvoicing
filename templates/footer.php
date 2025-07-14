<?php
// templates/footer.php
// This file is intended to be included at the end of your HTML body.
// It closes tags opened in header.php and can include global JavaScript files or scripts.

// Note: BASE_URL and ASSETS_PATH should already be defined via config.php,
// which is included in header.php. No need to redefine here typically.
// However, for robustness or direct testing of footer, a check might be useful.
if (!defined('BASE_URL')) {
    // This indicates config.php was not loaded, which is a problem.
    // For now, define a fallback to prevent immediate errors, but this should be investigated.
    define('BASE_URL', '/');
    error_log("Warning: BASE_URL not defined when footer.php was loaded. Fallback to '/'");
}
?>

            <!-- </main> --> <!-- main content area closed in header.php to allow content injection -->
            <!-- </div> --> <!-- .container-fluid from header.php closed in header.php -->
        <!-- </div> --> <!-- #page-content-wrapper from header.php closed in header.php -->
    <!-- </div> --> <!-- #wrapper from header.php closed in header.php -->

    <!-- The structure from header.php is:
    <div id="wrapper">
        <aside id="sidebar-wrapper">...</aside>
        <div id="page-content-wrapper">
            <nav class="navbar">...</nav>
            <div class="container-fluid px-4"> (content wrapper)
                <nav id="breadcrumb-nav-container">...</nav>
                <main class="pt-3"> (this is where page content goes)
                </main> <--- This is what should be closed by the page itself or just before footer.
            </div> <--- this container-fluid is also for page content.
        </div>
    </div>
    -->
    <!-- The main tag was closed in header.php for some reason, which is unusual.
         Typically, header opens main, page puts content, footer closes main.
         Revisiting header.php, <main> is indeed closed there.
         This means individual pages must output their content *outside* a main tag if header.php's main is used,
         or header.php's main should be removed or left open.

         Let's assume the individual pages will be wrapped in their own <main> or semantic equivalent,
         or that the <main> in header.php is intended to wrap the breadcrumbs and the start of content area.
         For now, footer.php will not attempt to close <main> again.
    -->

<footer class="footer mt-auto py-3 bg-light border-top site-footer">
    <div class="container-fluid text-center">
        <span class="text-muted">&copy; <?php echo date('Y'); ?> Your Company Name. All rights reserved.</span>
        <!-- You can add more links or information here -->
        <!-- <p><a href="<?php echo BASE_URL; ?>privacy_policy/">Privacy Policy</a> | <a href="<?php echo BASE_URL; ?>terms/">Terms of Service</a></p> -->
    </div>
</footer>

<!-- JavaScript files are already included at the end of header.php -->
<!-- This ensures jQuery and Bootstrap JS are available for the main content of the page -->
<!-- If you have page-specific JS that must run after everything else, you can include it here. -->
<?php
// Example for page-specific scripts to be loaded at the very end
// if (isset($page_specific_footer_scripts) && is_array($page_specific_footer_scripts)) {
//     foreach ($page_specific_footer_scripts as $script_url) {
//         echo '<script src="' . htmlspecialchars($script_url) . '?v=' . time() . '"></script>' . "\n";
//     }
// }
?>

</body>
</html>
