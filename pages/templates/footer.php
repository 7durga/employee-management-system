<!-- Footer -->
<footer class="footer mt-auto py-3 bg-light">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <span class="text-muted">
                    &copy; <?php echo date('Y'); ?> Employee Management System. All rights reserved.
                </span>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="text-muted">
                    <i class="fas fa-code-branch"></i> v1.0.0
                    <?php if (isset($_SESSION['role'])): ?>
                        | Logged in as: <?php echo htmlspecialchars(ucfirst($_SESSION['role'])); ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Font Awesome -->
<script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
<!-- Custom Scripts -->
<script>
    // Enable tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Enable popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    });

    // Auto-hide alerts after 5 seconds
    window.setTimeout(function() {
        $(".alert").fadeTo(500, 0).slideUp(500, function(){
            $(this).remove(); 
        });
    }, 5000);
</script>
</body>
</html>
