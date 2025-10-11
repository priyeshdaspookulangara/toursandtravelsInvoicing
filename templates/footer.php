<?php if (isset($_SESSION['user_id'])): ?>
        </main> <!-- end main .container-fluid -->
    </div> <!-- end #content -->
</div> <!-- end .d-flex wrapper -->
<?php else: ?>
        </main> <!-- end main .login-box -->
    </div> <!-- end .login-container -->
<?php endif; ?>


<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- Bootstrap 5 JS Bundle (includes Popper.js) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Chart.js for dashboard charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<!-- Custom App JS -->
<script src="<?php echo APP_BASE_URL; ?>assets/js/main.js"></script>

</body>
</html>
<?php
// Rely on PHP's auto-close for DB connection on these short-lived web request scripts.
?>