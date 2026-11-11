// PayBen app.js

(function (window, document) {
    'use strict';
    const PayBen = {
        config: {
            toastDuration: 4500,
            animationDuration: 300,
            currencySymbol: '₹',
            csrfToken: null
        },
        init: function () {
            this.initCSRF();
            this.initSidebar();
            this.initAutoDismissAlerts();
            this.initQuickAmountPickers();
            this.initPhoneInputs();
            this.initTooltips();
        },
        initCSRF: function () {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            if (metaTag) {
                this.config.csrfToken = metaTag.getAttribute('content');
            } else {
                const inputTag = document.querySelector('input[name="csrf_token"]');
                if (inputTag) {
                    this.config.csrfToken = inputTag.value;
                }
            }
        },
        initSidebar: function () {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            const mobileToggle = document.getElementById('mobileSidebarToggle');
            const overlay = document.getElementById('sidebarOverlay');

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.body.classList.toggle('sidebar-collapsed');
                    const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                    localStorage.setItem('payben_sidebar_collapsed', isCollapsed ? '1' : '0');
                });

                // Restore saved desktop state
                if (window.innerWidth > 992 && localStorage.getItem('payben_sidebar_collapsed') === '1') {
                    document.body.classList.add('sidebar-collapsed');
                }
            }

            if (mobileToggle && sidebar) {
                mobileToggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    sidebar.classList.toggle('show');
                    if (overlay) overlay.classList.toggle('show');
                });
            }

            if (overlay && sidebar) {
                overlay.addEventListener('click', function () {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
            }
        },
        initAutoDismissAlerts: function () {
            const alerts = document.querySelectorAll('.alert-dismissible:not(.alert-persistent)');
            alerts.forEach(function (alert) {
                setTimeout(function () {
                    alert.classList.add('fade');
                    setTimeout(function () {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 300);
                }, 5000);
            });
        },
        initQuickAmountPickers: function () {
            document.addEventListener('click', function (e) {
                const target = e.target.closest('[data-quick-amount]');
                if (!target) return;

                e.preventDefault();
                const amount = target.getAttribute('data-quick-amount');
                const targetInputId = target.getAttribute('data-target-input') || 'amount';
                const input = document.getElementById(targetInputId);

                if (input) {
                    input.value = amount;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));

                    // Visual highlight for active quick chip
                    const siblings = target.parentNode.querySelectorAll('[data-quick-amount]');
                    siblings.forEach(el => el.classList.remove('active'));
                    target.classList.add('active');
                }
            });
        },
        initPhoneInputs: function () {
            document.addEventListener('input', function (e) {
                if (e.target.matches('input[type="tel"], input[data-type="phone"]')) {
                    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 10);
                }
            });
        },
        initTooltips: function () {
            if (window.bootstrap && typeof window.bootstrap.Tooltip === 'function') {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new window.bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        }
    };

    /**
     * Toast Notification System
     *
     * @param {string} type 'success' | 'danger' | 'error' | 'warning' | 'info'
     * @param {string} message The notification message content
     * @param {string} [title] Optional header title
     * @param {number} [duration] Duration in milliseconds
     */
    function showToast(type, message, title = '', duration = 4500) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }

        const normalizedType = type === 'error' ? 'danger' : type;

        // Determine icon based on status
        let iconClass = 'bi-info-circle-fill';
        let defaultTitle = 'Notification';
        if (normalizedType === 'success') {
            iconClass = 'bi-check-circle-fill';
            defaultTitle = 'Success';
        } else if (normalizedType === 'danger') {
            iconClass = 'bi-exclamation-triangle-fill';
            defaultTitle = 'Error';
        } else if (normalizedType === 'warning') {
            iconClass = 'bi-exclamation-circle-fill';
            defaultTitle = 'Warning';
        }

        const finalTitle = title || defaultTitle;
        const toast = document.createElement('div');
        toast.className = `payben-toast toast-${normalizedType}`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');

        toast.innerHTML = `
            <div class="toast-icon">
                <i class="bi ${iconClass}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${escapeHtml(finalTitle)}</div>
                <div class="toast-message">${escapeHtml(message)}</div>
            </div>
            <button type="button" class="toast-close" aria-label="Close">
                <i class="bi bi-x"></i>
            </button>
            <div class="toast-progress">
                <div class="toast-progress-bar" style="animation-duration: ${duration}ms;"></div>
            </div>
        `;

        container.appendChild(toast);

        // Close logic
        function removeToast() {
            toast.style.animation = 'toastSlideOut 0.3s forwards';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }

        const closeBtn = toast.querySelector('.toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', removeToast);
        }

        const timer = setTimeout(removeToast, duration);

        toast.addEventListener('mouseenter', () => {
            clearTimeout(timer);
            const pb = toast.querySelector('.toast-progress-bar');
            if (pb) pb.style.animationPlayState = 'paused';
        });

        toast.addEventListener('mouseleave', () => {
            setTimeout(removeToast, 1500);
        });
    }

    /**
     * Show Global Fullscreen Loading Overlay
     *
     * @param {string} [message] Custom loading status text
     */
    function showLoading(message = 'Processing request, please wait...') {
        let loader = document.getElementById('global-loader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'global-loader';
            loader.innerHTML = `
                <div class="spinner-fintech"></div>
                <div class="loader-text" id="global-loader-text">${escapeHtml(message)}</div>
            `;
            document.body.appendChild(loader);
        } else {
            const textEl = document.getElementById('global-loader-text');
            if (textEl) textEl.textContent = message;
        }
        loader.classList.add('active');
    }
    function hideLoading() {
        const loader = document.getElementById('global-loader');
        if (loader) {
            loader.classList.remove('active');
        }
    }

    /**
     * Unified AJAX API Call Utility
     *
     * @param {string} url The target endpoint URL
     * @param {string} [method='GET'] HTTP method (GET, POST, PUT, DELETE)
     * @param {object|FormData|null} [data=null] Request body payload
     * @param {object} [customHeaders={}] Custom HTTP headers
     * @returns {Promise<any>} Resolves with JSON response data
     */
    async function apiCall(url, method = 'GET', data = null, customHeaders = {}) {
        const upperMethod = method.toUpperCase();
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            ...customHeaders
        };

        // Attach CSRF token
        if (PayBen.config.csrfToken) {
            headers['X-CSRF-Token'] = PayBen.config.csrfToken;
        }

        const fetchOptions = {
            method: upperMethod,
            headers: headers
        };

        if (data && upperMethod !== 'GET') {
            if (data instanceof FormData) {
                // Fetch sets proper multipart boundary automatically
                fetchOptions.body = data;
            } else {
                headers['Content-Type'] = 'application/json';
                fetchOptions.body = JSON.stringify(data);
            }
        }

        try {
            const response = await fetch(url, fetchOptions);
            const contentType = response.headers.get('content-type') || '';

            let result;
            if (contentType.includes('application/json')) {
                result = await response.json();
            } else {
                const text = await response.text();
                try {
                    result = JSON.parse(text);
                } catch {
                    result = { success: response.ok, message: text };
                }
            }

            // Handle session expiry or unauthorized status
            if (response.status === 401) {
                showToast('error', 'Session expired. Redirecting to login...', 'Unauthorized');
                setTimeout(() => {
                    window.location.href = '/PayBen/pages/login.php';
                }, 1500);
                throw new Error(result.message || 'Unauthorized');
            }

            if (!response.ok && !result.success) {
                throw new Error(result.message || `HTTP error ${response.status}`);
            }

            return result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    /**
     * Confirmation Modal Dialog
     *
     * @param {object|string} options Options object or message string
     * @param {function} [confirmCallback] Function called on user confirmation
     * @returns {Promise<boolean>}
     */
    function confirmAction(options, confirmCallback) {
        return new Promise((resolve) => {
            let title = 'Are you sure?';
            let message = 'Do you really want to proceed with this action?';
            let confirmText = 'Confirm';
            let cancelText = 'Cancel';
            let confirmBtnClass = 'btn-primary';

            if (typeof options === 'string') {
                message = options;
            } else if (typeof options === 'object' && options !== null) {
                title = options.title || title;
                message = options.message || message;
                confirmText = options.confirmText || confirmText;
                cancelText = options.cancelText || cancelText;
                confirmBtnClass = options.confirmBtnClass || confirmBtnClass;
            }

            let modalBackdrop = document.getElementById('payben-confirm-modal');
            if (!modalBackdrop) {
                modalBackdrop = document.createElement('div');
                modalBackdrop.id = 'payben-confirm-modal';
                modalBackdrop.className = 'payben-modal-backdrop';
                document.body.appendChild(modalBackdrop);
            }

            modalBackdrop.innerHTML = `
                <div class="payben-modal">
                    <div class="payben-modal-header">
                        <h5 class="m-0 fw-bold">${escapeHtml(title)}</h5>
                        <button type="button" class="toast-close" id="pbm-cancel-x">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div class="payben-modal-body">
                        ${escapeHtml(message)}
                    </div>
                    <div class="payben-modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="pbm-cancel-btn">${escapeHtml(cancelText)}</button>
                        <button type="button" class="btn ${confirmBtnClass} btn-sm" id="pbm-confirm-btn">${escapeHtml(confirmText)}</button>
                    </div>
                </div>
            `;

            requestAnimationFrame(() => {
                modalBackdrop.classList.add('show');
            });

            function closeModal(result) {
                modalBackdrop.classList.remove('show');
                setTimeout(() => {
                    if (modalBackdrop.parentNode) {
                        modalBackdrop.parentNode.removeChild(modalBackdrop);
                    }
                    if (result && typeof confirmCallback === 'function') {
                        confirmCallback();
                    }
                    resolve(result);
                }, 200);
            }

            document.getElementById('pbm-confirm-btn').addEventListener('click', () => closeModal(true));
            document.getElementById('pbm-cancel-btn').addEventListener('click', () => closeModal(false));
            document.getElementById('pbm-cancel-x').addEventListener('click', () => closeModal(false));
        });
    }

    /**
     * Form Validation Helper
     *
     * @param {HTMLFormElement|string} form Form element or selector
     * @returns {boolean} True if all inputs are valid
     */
    function validateForm(form) {
        const formElement = typeof form === 'string' ? document.querySelector(form) : form;
        if (!formElement) return false;

        let isValid = true;
        const inputs = formElement.querySelectorAll('input, select, textarea');

        inputs.forEach(input => {
            // Clear previous states
            input.classList.remove('is-invalid', 'is-valid');
            const feedback = input.parentNode.querySelector('.invalid-feedback');
            if (feedback) feedback.remove();

            // Skip hidden or disabled fields
            if (input.type === 'hidden' || input.disabled) return;

            let fieldValid = true;
            let errorMessage = '';

            // Required validation
            if (input.hasAttribute('required') && !input.value.trim()) {
                fieldValid = false;
                errorMessage = 'This field is required.';
            }

            // Email validation
            else if (input.type === 'email' && input.value.trim()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(input.value.trim())) {
                    fieldValid = false;
                    errorMessage = 'Please enter a valid email address.';
                }
            }

            // 10-digit mobile number validation
            else if ((input.type === 'tel' || input.getAttribute('data-type') === 'phone') && input.value.trim()) {
                const phoneRegex = /^[6-9]\d{9}$/;
                if (!phoneRegex.test(input.value.trim())) {
                    fieldValid = false;
                    errorMessage = 'Please enter a valid 10-digit mobile number starting with 6-9.';
                }
            }

            // Numeric minimum / maximum
            else if (input.type === 'number' && input.value.trim()) {
                const val = parseFloat(input.value);
                const min = input.getAttribute('min') ? parseFloat(input.getAttribute('min')) : null;
                const max = input.getAttribute('max') ? parseFloat(input.getAttribute('max')) : null;

                if (min !== null && val < min) {
                    fieldValid = false;
                    errorMessage = `Amount must be at least ₹${min}.`;
                } else if (max !== null && val > max) {
                    fieldValid = false;
                    errorMessage = `Amount cannot exceed ₹${max}.`;
                }
            }

            if (!fieldValid) {
                isValid = false;
                input.classList.add('is-invalid');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                errorDiv.textContent = errorMessage;
                input.parentNode.appendChild(errorDiv);
            } else if (input.value.trim() && !input.classList.contains('is-invalid')) {
                input.classList.add('is-valid');
            }
        });

        return isValid;
    }

    /**
     * Clear validation classes and messages from a form
     *
     * @param {HTMLFormElement|string} form
     */
    function clearValidation(form) {
        const formElement = typeof form === 'string' ? document.querySelector(form) : form;
        if (!formElement) return;

        const inputs = formElement.querySelectorAll('.is-invalid, .is-valid');
        inputs.forEach(input => input.classList.remove('is-invalid', 'is-valid'));

        const feedbacks = formElement.querySelectorAll('.invalid-feedback');
        feedbacks.forEach(fb => fb.remove());
    }

    /**
     * Format numeric value to Indian Currency Format (₹ 1,234.56)
     *
     * @param {number|string} amount
     * @param {boolean} [includeSymbol=true]
     * @returns {string}
     */
    function formatCurrency(amount, includeSymbol = true) {
        const num = parseFloat(amount) || 0;
        const formatted = new Intl.NumberFormat('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(num);

        return includeSymbol ? `${PayBen.config.currencySymbol}${formatted}` : formatted;
    }

    /**
     * Copy text to system clipboard with user toast feedback
     *
     * @param {string} text Text string to copy
     * @param {string} [successMessage='Copied to clipboard!']
     */
    async function copyToClipboard(text, successMessage = 'Copied to clipboard!') {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                document.execCommand('copy');
                textArea.remove();
            }
            showToast('success', successMessage, 'Copied');
        } catch (err) {
            console.error('Failed to copy text: ', err);
            showToast('error', 'Could not copy to clipboard', 'Error');
        }
    }
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (window.innerWidth < 992) {
            if (sidebar) sidebar.classList.toggle('show');
            if (overlay) overlay.classList.toggle('show');
        } else {
            document.body.classList.toggle('sidebar-collapsed');
            const isCollapsed = document.body.classList.contains('sidebar-collapsed');
            localStorage.setItem('payben_sidebar_collapsed', isCollapsed ? '1' : '0');
        }
    }

    /**
     * HTML escape helper to prevent XSS in dynamic rendering
     *
     * @param {string} str
     * @returns {string}
     */
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(str).replace(/[&<>"']/g, m => map[m]);
    }

    // Export methods to PayBen object
    PayBen.showToast = showToast;
    PayBen.showLoading = showLoading;
    PayBen.hideLoading = hideLoading;
    PayBen.apiCall = apiCall;
    PayBen.confirmAction = confirmAction;
    PayBen.validateForm = validateForm;
    PayBen.clearValidation = clearValidation;
    PayBen.formatCurrency = formatCurrency;
    PayBen.copyToClipboard = copyToClipboard;
    PayBen.toggleSidebar = toggleSidebar;
    PayBen.escapeHtml = escapeHtml;

    // Export to global window scope for direct function calls
    window.PayBen = PayBen;
    window.showToast = showToast;
    window.showLoading = showLoading;
    window.hideLoading = hideLoading;
    window.apiCall = apiCall;
    window.confirmAction = confirmAction;
    window.validateForm = validateForm;
    window.clearValidation = clearValidation;
    window.formatCurrency = formatCurrency;
    window.copyToClipboard = copyToClipboard;
    window.toggleSidebar = toggleSidebar;

    // Initialize when DOM content is loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => PayBen.init());
    } else {
        PayBen.init();
    }

})(window, document);
