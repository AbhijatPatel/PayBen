        </main>

        <!-- Admin Global Footer -->
        <footer class="py-3 px-4 border-top border-secondary border-opacity-10 text-secondary small d-flex flex-column flex-md-row justify-content-between align-items-center gap-2" style="background-color: #0b0f19;">
            <div>
                &copy; <?php echo date('Y'); ?> <strong class="text-white"><?php echo APP_NAME; ?> Technologies Pvt. Ltd.</strong> Administrative Console.
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-secondary bg-opacity-25 text-light py-1 px-2">PHP <?php echo PHP_VERSION; ?></span>
                <span class="badge bg-secondary bg-opacity-25 text-light py-1 px-2">Session Secure</span>
                <span class="text-secondary">&bull;</span>
                <span class="text-white-50"><i class="bi bi-clock me-1"></i> <?php echo date('H:i T'); ?></span>
            </div>
        </footer>
    </div>
</div>

<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Admin Interaction Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mobileToggle = document.getElementById('mobileSidebarToggle');
    const mobileClose = document.getElementById('mobileSidebarClose');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        if (sidebar && overlay) {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }
    }

    function closeSidebar() {
        if (sidebar && overlay) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        }
    }

    if (mobileToggle) mobileToggle.addEventListener('click', toggleSidebar);
    if (mobileClose) mobileClose.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // Auto dismiss flash alerts
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            if (window.bootstrap && bootstrap.Alert) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                if (bsAlert) bsAlert.close();
            }
        }, 5000);
    });
});
</script>

</body>
</html>
