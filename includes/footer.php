<?php

?>

<!-- Toasts -->
<div id="toast-container" class="toast-container" aria-live="polite" aria-atomic="true">
    <?php
    if (function_exists('getFlashMessage')) {
        $flash = getFlashMessage();
        if ($flash && !empty($flash['message'])):
            $flashType = $flash['type'] ?? 'info';
                    $normalizedType = match ($flashType) {
                'danger', 'error' => 'danger',
                'warning' => 'warning',
                'success' => 'success',
                default => 'info'
            };
            $flashTitle = match ($normalizedType) {
                'danger' => 'Action Failed',
                'warning' => 'Attention Required',
                'success' => 'Success',
                default => 'Notification'
            };
            $flashIcon = match ($normalizedType) {
                'danger' => 'bi-exclamation-octagon-fill',
                'warning' => 'bi-exclamation-triangle-fill',
                'success' => 'bi-check-circle-fill',
                default => 'bi-info-circle-fill'
            };
    ?>
    <div class="payben-toast toast-<?= $normalizedType ?>" role="alert" aria-live="assertive" aria-atomic="true">
        <i class="bi <?= $flashIcon ?> toast-icon"></i>
        <div class="toast-content">
            <div class="toast-title"><?= $flashTitle ?></div>
            <div class="toast-message"><?= sanitize($flash['message']) ?></div>
        </div>
        <button type="button" class="toast-close" onclick="this.closest('.payben-toast').remove();" aria-label="Close">
            <i class="bi bi-x"></i>
        </button>
        <div class="toast-progress">
            <div class="toast-progress-bar" style="animation-duration: 5000ms;"></div>
        </div>
    </div>
    <script>
        setTimeout(function() {
            var initialToast = document.querySelector('.payben-toast');
            if (initialToast) {
                initialToast.style.animation = 'toastSlideOut 0.3s forwards';
                setTimeout(function() {
                    if (initialToast && initialToast.parentNode) {
                        initialToast.parentNode.removeChild(initialToast);
                    }
                }, 300);
            }
        }, 5000);
    </script>
    <?php
        endif;
    }
    ?>
</div>

<!-- Loader -->
<div id="global-loader" class="loading-overlay" aria-hidden="true">
    <div class="spinner-fintech"></div>
    <div class="loader-text" id="global-loader-text">Processing request, please wait...</div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>

<?php if (isset($extraScripts)) echo $extraScripts; ?>

</body>
</html>
