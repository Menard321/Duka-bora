/**
 * script.js – Duka Bora IMS
 * Global JavaScript: Navigation, Form Validation, Sale Calculation, Cookies
 *
 * @package DukaBora
 */

'use strict';

/* ================================================================
   1. Mobile Navigation Toggle
   ================================================================ */
(function initNavToggle() {
    const toggler = document.getElementById('navbarToggler');
    const menu    = document.getElementById('navbarMenu');

    if (!toggler || !menu) return;

    toggler.addEventListener('click', () => {
        const isOpen = menu.classList.toggle('open');
        toggler.setAttribute('aria-expanded', isOpen.toString());
    });

    // Close menu when a link is clicked (mobile UX)
    menu.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            menu.classList.remove('open');
            toggler.setAttribute('aria-expanded', 'false');
        });
    });

    // Close when clicking outside
    document.addEventListener('click', (e) => {
        if (!toggler.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.remove('open');
            toggler.setAttribute('aria-expanded', 'false');
        }
    });
})();

/* ================================================================
   2. Auto-dismiss Flash Alerts
   ================================================================ */
(function initAlertDismiss() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        // Auto remove after 6 seconds
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity    = '0';
            alert.style.transform  = 'translateY(-8px)';
            setTimeout(() => alert.remove(), 500);
        }, 6000);
    });
})();

/* ================================================================
   3. Delete Confirmation
   ================================================================ */
(function initDeleteConfirmation() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-confirm]');
        if (!btn) return;

        const message = btn.dataset.confirm || 'Are you sure you want to delete this item? This action cannot be undone.';
        if (!confirm(message)) {
            e.preventDefault();
        }
    });
})();

/* ================================================================
   4. Record Sale – Live Price Calculation
   ================================================================ */
(function initSaleCalculation() {
    const productSelect = document.getElementById('sale_product_id');
    const qtyInput      = document.getElementById('sale_qty');
    const totalDisplay  = document.getElementById('sale_total_display');
    const totalInput    = document.getElementById('sale_total_price');
    const stockInfo     = document.getElementById('stock_info');

    if (!productSelect || !qtyInput) return;

    function recalculate() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const price          = parseFloat(selectedOption?.dataset?.price  || 0);
        const stock          = parseInt(selectedOption?.dataset?.stock    || 0, 10);
        const qty            = parseInt(qtyInput.value || 0, 10);

        // Update stock info label
        if (stockInfo) {
            if (productSelect.value === '') {
                stockInfo.textContent = '';
            } else {
                stockInfo.textContent = `Available stock: ${stock} unit${stock !== 1 ? 's' : ''}`;
                stockInfo.className   = stock === 0
                    ? 'form-error'
                    : stock < 5
                        ? 'form-hint text-warning'
                        : 'form-hint';
            }
        }

        // Validate quantity
        if (qty > stock) {
            qtyInput.classList.add('is-invalid');
            qtyInput.classList.remove('is-valid');
        } else if (qty > 0) {
            qtyInput.classList.add('is-valid');
            qtyInput.classList.remove('is-invalid');
        } else {
            qtyInput.classList.remove('is-valid', 'is-invalid');
        }

        // Calculate total
        const total = price * (isNaN(qty) || qty < 0 ? 0 : qty);

        if (totalDisplay) {
            totalDisplay.textContent = `TZS ${total.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })}`;
        }
        if (totalInput) {
            totalInput.value = total.toFixed(2);
        }
    }

    productSelect.addEventListener('change', recalculate);
    qtyInput.addEventListener('input', recalculate);

    // Run on load if values are pre-filled
    recalculate();
})();

/* ================================================================
   5. Client-Side Form Validation (generic)
   ================================================================ */
(function initFormValidation() {
    const forms = document.querySelectorAll('[data-validate]');

    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            let valid = true;

            // Clear previous errors
            form.querySelectorAll('.client-error').forEach(el => el.remove());
            form.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));

            // Required fields
            form.querySelectorAll('[required]').forEach(input => {
                const value = input.value.trim();
                if (value === '') {
                    markInvalid(input, 'This field is required.');
                    valid = false;
                }
            });

            // Numeric fields (price)
            form.querySelectorAll('[data-type="price"]').forEach(input => {
                const val = parseFloat(input.value);
                if (isNaN(val) || val <= 0) {
                    markInvalid(input, 'Price must be a number greater than 0.');
                    valid = false;
                }
            });

            // Stock fields
            form.querySelectorAll('[data-type="stock"]').forEach(input => {
                const val = parseInt(input.value, 10);
                if (isNaN(val) || val < 0) {
                    markInvalid(input, 'Stock quantity must be 0 or more.');
                    valid = false;
                }
            });

            // Quantity (sale) fields
            form.querySelectorAll('[data-type="qty"]').forEach(input => {
                const val = parseInt(input.value, 10);
                if (isNaN(val) || val <= 0) {
                    markInvalid(input, 'Quantity must be a positive whole number.');
                    valid = false;
                }
            });

            if (!valid) {
                e.preventDefault();
                // Scroll to first error
                const firstError = form.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });
    });

    function markInvalid(input, message) {
        input.classList.add('is-invalid');
        const errEl = document.createElement('span');
        errEl.className   = 'form-error client-error';
        errEl.textContent = '✖ ' + message;
        const parent = input.closest('.form-group') || input.parentNode;
        parent.appendChild(errEl);
    }
})();

/* ================================================================
   6. Table Row Search / Filter
   ================================================================ */
(function initTableFilter() {
    const filterInput = document.getElementById('tableFilter');
    if (!filterInput) return;

    const tableId = filterInput.dataset.table || 'mainTable';
    const table   = document.getElementById(tableId);
    if (!table)  return;

    filterInput.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        const rows  = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
})();

/* ================================================================
   7. Cookie Utility – Last Viewed Product
   (PHP sets the cookie; JS reads and highlights on products.php)
   ================================================================ */
const CookieUtil = {
    get(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    },
    set(name, value, days = 30) {
        const expires = new Date(Date.now() + days * 864e5).toUTCString();
        document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/`;
    }
};

// Highlight recently viewed product row in products table
(function highlightLastViewed() {
    const cookie = CookieUtil.get('last_viewed_product');
    if (!cookie) return;

    try {
        const data = JSON.parse(cookie);
        if (!data?.id) return;

        const row = document.querySelector(`tr[data-product-id="${data.id}"]`);
        if (row) {
            row.style.background = 'var(--primary-50)';
            row.style.outline    = '2px solid var(--primary-300)';
        }
    } catch (_) { /* ignore malformed cookie */ }
})();

/* ================================================================
   8. Animated Number Counter for stat cards
   ================================================================ */
(function initCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    if (!counters.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el     = entry.target;
            const target = parseFloat(el.dataset.counter);
            const isCurrency = el.dataset.currency === 'true';
            const duration   = 800;
            const start      = performance.now();

            observer.unobserve(el);

            function step(timestamp) {
                const progress = Math.min((timestamp - start) / duration, 1);
                const eased    = 1 - Math.pow(1 - progress, 3); // ease-out-cubic
                const value    = target * eased;

                el.textContent = isCurrency
                    ? 'TZS ' + value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    : Math.round(value).toLocaleString();

                if (progress < 1) requestAnimationFrame(step);
            }

            requestAnimationFrame(step);
        });
    }, { threshold: 0.3 });

    counters.forEach(el => observer.observe(el));
})();
