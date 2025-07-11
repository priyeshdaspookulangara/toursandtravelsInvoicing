</div> <!-- end .content -->
</div> <!-- end .container -->

<footer>
    <p>&copy; <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?>. All rights reserved.</p>
    <p>Powered by PlainPHP InvoiceGen</p>
</footer>

</body>
</html>
<?php
// Close database connection if it was opened
if (function_exists('db_close')) {
    // db_close(); // Decided to close it explicitly in scripts or rely on PHP's auto-close for short scripts.
                 // For longer running scripts or daemons, explicit close is better.
                 // For typical web requests, PHP auto-closes, but explicit is good practice.
                 // Let's comment this out from footer for now to avoid issues if db.php isn't included on some simple pages
                 // that might only use config.
}
?>
