<?php
/**
 * PayBen - Dashboard Layout Wrapper (Bottom)
 *
 * Convenience wrapper that terminates the .content-body section, renders the
 * dashboard footer, closes .main-content and .app-wrapper, and includes the
 * document footer with scripts, toast container, and loading overlay.
 */
?>
        </main>

        <!-- Dashboard App Footer -->
        <footer class="app-footer">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <span>&copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong>. All rights reserved.</span>
                <span class="text-muted small">
                    <i class="bi bi-shield-check text-success me-1"></i> 256-Bit SSL Encrypted Banking-Grade Security
                </span>
            </div>
        </footer>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
